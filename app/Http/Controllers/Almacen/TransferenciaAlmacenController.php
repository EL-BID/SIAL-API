<?php

namespace App\Http\Controllers\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;

use App\Models\Usuario, App\Models\Insumo, App\Models\Almacen, App\Models\Inventario, App\Models\InventarioDetalle, App\Models\Pedido, App\Models\PedidoInsumo, App\Models\Stock, App\Models\Movimiento, App\Models\MovimientoInsumos, App\Models\MovimientoPedido;
use App\Models\HistorialMovimientoTransferencia;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;
use DateTime;
use Carbon\Carbon;

class TransferenciaAlmacenController extends Controller{

    public function stats(Request $request){
        $almacen = Almacen::find($request->get('almacen_id'));
        // Akira: en los datos de alternos hay que ver si se pone la cantidad de alternos o en base a su estatus
        $pedidos_stats = Pedido::select(DB::raw(
            '
            count(
                1
            ) as todos,
            count(
                case when status = "BR" then 1 else null end
            ) as borradores,
            count(
                case when status = "SD" then 1 else null end
            ) as por_surtir,
            count(
                case when status = "ET" then 1 else null end
            ) as en_transito,
            count(
                case when status = "PFI" then 1 else null end
            ) as por_finalizar,
            count(
                case when status = "FI" then 1 else null end
            ) as finalizados,
            count(
                case when status = "EX-CA" then 1 else null end
            ) as cancelados
            '
        ))->where('almacen_proveedor',$almacen->id)->where('tipo_pedido_id','PEA')->first();

        $presupuesto_stats = DB::select('
            select 
                sum(IF((IM.tipo = "ME" and IM.es_causes = 1) OR (IM.tipo = "MC"),PI.monto_enviado,0)) as causes_y_material,
                sum(IF(IM.tipo = "ME" and IM.es_causes = 0,PI.monto_enviado,0)) as no_causes
            from
                pedidos_insumos PI, pedidos P, insumos_medicos IM
            where PI.deleted_at is null and P.deleted_at is null and PI.pedido_id = P.id and IM.clave = PI.insumo_medico_clave and P.tipo_pedido_id = "PEA" and P.status != "BR" and P.almacen_proveedor = :almacen_id
            ',['almacen_id'=>$almacen->id]);

        return Response::json(['stats'=>$pedidos_stats,'presupuesto'=>$presupuesto_stats[0]],200);
    }

    public function index(Request $request){
        $parametros = Input::only('status','q','page','per_page');

        $almacen = Almacen::find($request->get('almacen_id'));

        $transferencias = Pedido::where('almacen_proveedor',$almacen->id)->where('tipo_pedido_id','PEA');
        
        if ($parametros['q']) {
            $transferencias =  $transferencias->where(function($query) use ($parametros) {
                    $query->where('descripcion','LIKE',"%".$parametros['q']."%")->orWhere('folio','LIKE',"%".$parametros['q']."%")->orWhere('clues','LIKE',"%".$parametros['q']."%");
            });
        }

        if(isset($parametros['status'])) {
            $transferencias = $transferencias->where("status",$parametros['status']);
        }
        
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
            $transferencias = $transferencias->paginate($resultadosPorPagina);
        } else {
            $transferencias = $transferencias->get();
        }

        return Response::json([ 'data' => $transferencias],200);
    }

    public function show(Request $request, $id){
        $almacen = Almacen::find($request->get('almacen_id'));
    	//$pedido = Pedido::where('almacen_solicitante',$request->get('almacen_id'))->find($id);
        $pedido = Pedido::where('almacen_proveedor',$almacen->id)->find($id);
        
        if(!$pedido){
            return Response::json(['error' => "No se encuentra el pedido que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }else{
            $pedido = $pedido->load("insumos.tipoInsumo","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos","almacenSolicitante.unidadMedica","almacenProveedor","proveedor","encargadoAlmacen","director");
            //$pedido = $pedido->load("movimientos.transferenciaSurtida.insumos","movimientos.transferenciaRecibidaBorrador.insumos","movimientos.transferenciaRecibida.insumos","movimientos.reintegro.insumos");
            //$pedido = $pedido->load("movimientosTransferenciasCompleto");
            $pedido = $pedido->load("historialTransferenciaCompleto");
        }
        return Response::json([ 'data' => $pedido],200);
    }


    // store function editada por Akira
    public function store(Request $request){
        $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'almacen_solicitante'   => 'required',
            'clues_destino'         => 'required',
            'descripcion'           => 'required',
            'fecha'                 => 'required|date',
            'insumos'               => 'required',
        ];

        $parametros = Input::all();
        $v = Validator::make($parametros, $reglas, $mensajes);
        
        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }
        
        try {
            DB::beginTransaction();

            $almacen = Almacen::with('unidadMedica','encargado')->find($request->get('almacen_id'));
            
            // ** 1ero creamos el pedido  **
            $folio = null;
            $status = "BR";
            $status_movimiento = "BR";
            $recepcion_permitida = 0;
            if(isset($parametros['finalizar'])){
                $anio = date('Y');
                
                $folio_template = $almacen->clues . '-' . $anio . '-PEA-';
                $max_folio = Pedido::where('clues',$almacen->clues)->where('folio','like',$folio_template.'%')->max('folio');
                
                if(!$max_folio){
                    $prox_folio = 1;
                }else{
                    $max_folio = explode('-',$max_folio);
                    $prox_folio = intval($max_folio[3]) + 1;
                }
                $folio = $folio_template . str_pad($prox_folio, 3, "0", STR_PAD_LEFT);
                $status = "ET";
                $status_movimiento = "FI";
                $recepcion_permitida = 1;
            }
            
            $datos_pedido = [
                'tipo_pedido_id' => "PEA",
                'clues' => $almacen->clues,
                'clues_destino' =>$parametros['clues_destino'],
                'folio' => $folio,
                'fecha' => $parametros['fecha'],
                'descripcion' => $parametros['descripcion'],
                'observaciones' => $parametros['observaciones'],
                'almacen_solicitante' => $parametros['almacen_solicitante'],
                'almacen_proveedor' => $almacen->id,
                'status' => $status,
                'recepcion_permitida' => $recepcion_permitida
            ];

            $pedido = Pedido::create($datos_pedido);


            $total_claves = count($parametros['insumos']);
            $total_insumos = 0;
            $total_monto = 0;
            $monto_para_sacar_iva = 0;

            foreach ($parametros['insumos'] as $key => $value) {
                $reglas_insumos = [
                    'clave'           => 'required',
                    'cantidad'        => 'required|integer|min:0'
                ];  

                $v = Validator::make($value, $reglas_insumos, $mensajes);

                if ($v->fails()) {
                    DB::rollBack();
                    return Response::json(['error' => 'El insumo con clave: '.$value['clave'].' tiene un valor incorrecto.'], 500);
                }
                if($value['cantidad'] > 0){
                    $insumo = [
                        'insumo_medico_clave' => $value['clave'],
                        'cantidad_solicitada' => $value['cantidad'],
                        'cantidad_enviada' => $value['cantidad'],
                        'monto_solicitado' => $value['cantidad']*$value['precio'], 
                        'monto_enviado' => $value['cantidad']*$value['precio'], 
                        'precio_unitario' => $value['precio'],
                        'tipo_insumo_id' => $value['tipo_insumo_id'],
                        'pedido_id' => $pedido->id
                    ];

                    PedidoInsumo::create($insumo);

                    $total_insumos += $value['cantidad'];
                    $total_monto += $insumo['monto_solicitado'];
    
                    if($value['tipo'] == 'MC'){
                        $monto_para_sacar_iva += $value['precio'];
                    }
                }
            }

            if($monto_para_sacar_iva > 0){
                $total_monto += $monto_para_sacar_iva*16/100;
            }

            $almacen_solicitante = Almacen::with('unidadMedica','encargado')->find($parametros['almacen_solicitante']);

            $pedido->director_id = $almacen_solicitante->unidadMedica->director_id;
            $pedido->encargado_almacen_id = $almacen_solicitante->encargado_almacen_id;


            $pedido->total_claves_solicitadas = $total_claves;
            $pedido->total_cantidad_solicitada = $total_insumos;
            $pedido->total_monto_solicitado = $total_monto;
            $pedido->save();
            

            // ** Segundo creamos el movimiento  **
           
            $datos_movimiento = [
                'almacen_id' => $almacen->id,
                'tipo_movimiento_id' => 3,
                'fecha_movimiento' =>$parametros['fecha'],
                'status' => $status_movimiento
            ];
            $movimiento = Movimiento::create($datos_movimiento);
            
            $datos_movimiento_pedido = [
                'movimiento_id' => $movimiento->id,
                'pedido_id' => $pedido->id,
                'recibe' =>  ($almacen_solicitante->encargado)?$almacen_solicitante->encargado->nombre:'sin asignar',
                'entrega' => ($almacen->encargado)?$almacen->encargado->nombre:'sin asignar' // O podríamos usar el nombre del usuario
            ];
            $movimiento_pedido = MovimientoPedido::create($datos_movimiento_pedido);
            
          
            foreach ($parametros['movimiento_insumos'] as $key => $value) {
                $reglas_insumos = [
                    'stock_id'           => 'required',
                    'tipo'           => 'required',
                    'tipo_insumo_id' => 'required',
                    'clave'           => 'required',
                    'precio'        => 'required|numeric',
                    'cantidad'        => 'required|integer|min:0'
                ];  

                $v = Validator::make($value, $reglas_insumos, $mensajes);

                if ($v->fails()) {
                    DB::rollBack();
                    return Response::json(['error' => 'El stock con clave: '.$value['clave'].' tiene un valor incorrecto.'], 500);
                }
                if($value['cantidad'] > 0){

                     
                    $precio_total = $value['cantidad']*$value['precio'];
                    $iva = 0.00;

                    if($value['tipo'] == "MC"){
                        //$precio_total = $precio_total * 1.16;
                        $iva = $precio_total * 0.16;
                    } 
                    
                    $insumo = [
                        'movimiento_id' => $movimiento->id,   
                        'tipo_insumo_id' => $value['tipo_insumo_id'],
                        'stock_id' => $value['stock_id'],
                        'clave_insumo_medico' => $value['clave'],
                        'cantidad' => $value['cantidad'],
                        'precio_unitario' => $value['precio'],
                        'iva' => $iva, 
                        'precio_total' => $precio_total
                    ];

                    MovimientoInsumos::create($insumo);

                    // Actualizamos stock si se ha finalizado
                    if(isset($parametros['finalizar'])){
                        $stock = Stock::find($value['stock_id']);

                        $cantidad_x_envase = $stock->existencia_unidosis / $stock->existencia;

                        $existencia_final = $stock->existencia - $value['cantidad'];
                        $existencia_final_unidosis = $stock->existencia_unidosis - ($value['cantidad'] * $cantidad_x_envase);

                        if($existencia_final < 0){
                            DB::rollBack();
                            return Response::json(['error' => 'El insumo con clave: '.$value['clave'].' ya no tiene stock.'], 500);
                        }

                        $stock->existencia = $existencia_final;
                        $stock->existencia_unidosis = $existencia_final_unidosis;

                        $stock->save();         
                    }
                }

            }
            
            $historial_datos = [
                'almacen_origen'=>$pedido->almacen_proveedor,
                'almacen_destino'=>$pedido->almacen_solicitante,
                'clues_origen'=>$pedido->clues,
                'clues_destino'=>($pedido->clues_destino)?$pedido->clues_destino:$pedido->clues,
                'pedido_id'=>$pedido->id,
                'evento'=>'SURTIO PEA',
                'movimiento_id'=>$movimiento->id,
                'total_unidades'=>$pedido->total_cantidad_solicitada,
                'total_claves'=>$pedido->total_claves_solicitadas,
                'total_monto'=>$pedido->total_monto_solicitado,
                'fecha_inicio_captura'=>$movimiento->created_at,
                'fecha_finalizacion'=>null
            ];

            if(isset($parametros['finalizar'])){
                $historial_datos['fecha_finalizacion'] = Carbon::now();
            }

            $historial = HistorialMovimientoTransferencia::create($historial_datos);
            
            $pedido->insumos;
            $movimiento->movimientoPedido;
            $movimiento->insumos;

            $respuesta = [
                'pedido' => $pedido,
                'movimiento' => $movimiento
            ];

            //DB::rollBack();
            DB::commit();
            return Response::json([ 'data' => $respuesta ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        }

    } // store function editada por Akira

    //Harima: Se agrego metodo para actualizar
    public function update(Request $request, $id){
        $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'almacen_solicitante'   => 'required',
            'clues_destino'         => 'required',
            'descripcion'           => 'required',
            'fecha'                 => 'required|date',
            'insumos'               => 'required',
        ];

        $parametros = Input::all();
        $v = Validator::make($parametros, $reglas, $mensajes);
        
        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }
        
        try {
            DB::beginTransaction();

            $almacen = Almacen::with('unidadMedica','encargado')->find($request->get('almacen_id'));
            
            // ** 1ero creamos el pedido  **
            $folio = null;
            $status = "BR";
            $status_movimiento = "BR";
            $recepcion_permitida = 0;
            if(isset($parametros['finalizar'])){
                $anio = date('Y');
                
                $folio_template = $almacen->clues . '-' . $anio . '-PEA-';
                $max_folio = Pedido::where('clues',$almacen->clues)->where('folio','like',$folio_template.'%')->max('folio');
                
                if(!$max_folio){
                    $prox_folio = 1;
                }else{
                    $max_folio = explode('-',$max_folio);
                    $prox_folio = intval($max_folio[3]) + 1;
                }
                $folio = $folio_template . str_pad($prox_folio, 3, "0", STR_PAD_LEFT);
                $status = "ET";
                $status_movimiento = "FI";
                $recepcion_permitida = 1;
            }
            
            $pedido = Pedido::find($id);

            //$pedido->tipo_pedido_id = "PEA";
            //$pedido->clues = $almacen->clues;
            $pedido->clues_destino =$parametros['clues_destino'];
            $pedido->folio = $folio;
            $pedido->fecha = $parametros['fecha'];
            $pedido->descripcion = $parametros['descripcion'];
            $pedido->observaciones = $parametros['observaciones'];
            $pedido->almacen_solicitante = $parametros['almacen_solicitante'];
            //$pedido->almacen_proveedor = $almacen->id;
            $pedido->status = $status;
            $pedido->recepcion_permitida = $recepcion_permitida;

            $pedido->save();

            $total_claves = count($parametros['insumos']);
            $total_insumos = 0;
            $total_monto = 0;
            $monto_para_sacar_iva = 0;

            /*
            foreach ($parametros['insumos'] as $key => $value) {
                $reglas_insumos = [
                    'clave'           => 'required',
                    'cantidad'        => 'required|integer|min:0'
                ];  

                $v = Validator::make($value, $reglas_insumos, $mensajes);

                if ($v->fails()) {
                    DB::rollBack();
                    return Response::json(['error' => 'El insumo con clave: '.$value['clave'].' tiene un valor incorrecto.'], 500);
                }
                if($value['cantidad'] > 0){
                    $insumo = [
                        'insumo_medico_clave' => $value['clave'],
                        'cantidad_solicitada' => $value['cantidad'],
                        'cantidad_enviada' => $value['cantidad'],
                        'monto_solicitado' => $value['cantidad']*$value['precio'], 
                        'precio_unitario' => $value['precio'],
                        'tipo_insumo_id' => $value['tipo_insumo_id'],
                        'pedido_id' => $pedido->id
                    ];

                    PedidoInsumo::create($insumo);

                    $total_insumos += $value['cantidad'];
                    $total_monto += $insumo['monto_solicitado'];
    
                    if($value['tipo'] == 'MC'){
                        $monto_para_sacar_iva += $value['precio'];
                    }
                }
            }
            */

            $lista_insumos_db = PedidoInsumo::where('pedido_id',$pedido->id)->withTrashed()->get();
            
            if(count($lista_insumos_db) > count($parametros['insumos'])){
                $total_max_insumos = count($lista_insumos_db);
            }else{
                $total_max_insumos = count($parametros['insumos']);
            }
            
            for ($i=0; $i < $total_max_insumos ; $i++) {
                if(isset($lista_insumos_db[$i])){ //Si existe un registro en la base de datos se edita o elimina.
                    $insumo_db = $lista_insumos_db[$i];
                    if(isset($parametros['insumos'][$i])){ //Si hay insumos desde el fomulario, editamos el insumo de la base de datos.
                        $insumo_form = $parametros['insumos'][$i];
    
                        $insumo_db->deleted_at = null; //Por si el elemento ya esta liminado, lo restauramos
                        $insumo_db->insumo_medico_clave = $insumo_form['clave'];
                        $insumo_db->cantidad_solicitada = $insumo_form['cantidad'];
                        $insumo_db->cantidad_enviada = $insumo_form['cantidad'];
                        $insumo_db->precio_unitario = $insumo_form['precio'];
                        $insumo_db->monto_solicitado = $insumo_form['cantidad']*$insumo_form['precio'];
                        $insumo_db->monto_enviado = $insumo_form['cantidad']*$insumo_form['precio'];
                        $insumo_db->tipo_insumo_id = $insumo_form['tipo_insumo_id'];
    
                        $insumo_db->save();
                    }else{ //de lo contrario eliminamos el insumo de la base de datos.
                        $insumo_db->delete();
                    }
                }else{ //SI no existe un registro en la base de datos, se crea uno nuevo
                    $insumo_form = $parametros['insumos'][$i];
                    $insumo_db = new PedidoInsumo();

                    $insumo_db->deleted_at = null; //Por si el elemento ya esta liminado, lo restauramos
                    $insumo_db->insumo_medico_clave = $insumo_form['clave'];
                    $insumo_db->cantidad_solicitada = $insumo_form['cantidad'];
                    $insumo_db->cantidad_enviada = $insumo_form['cantidad'];
                    $insumo_db->precio_unitario = $insumo_form['precio'];
                    $insumo_db->monto_solicitado = $insumo_form['cantidad']*$insumo_form['precio'];
                    $insumo_db->monto_enviado = $insumo_form['cantidad']*$insumo_form['precio'];
                    $insumo_db->tipo_insumo_id = $insumo_form['tipo_insumo_id'];
                    $insumo_db->pedido_id = $pedido->id;

                    $insumo_db->save();
                }

                if(isset($parametros['insumos'][$i])){
                    $insumo_form = $parametros['insumos'][$i];
                    $total_insumos += $insumo_form['cantidad'];
                    $total_monto += $insumo_form['cantidad']*$insumo_form['precio'];
    
                    if($insumo_form['tipo'] == 'MC'){
                        $monto_para_sacar_iva += ($insumo_form['cantidad']*$insumo_form['precio']);
                    }
                }
            }

            if($monto_para_sacar_iva > 0){
                $total_monto += $monto_para_sacar_iva*16/100;
            }

            $almacen_solicitante = Almacen::with('unidadMedica','encargado')->find($parametros['almacen_solicitante']);

            //DB::rollBack();
            //return  Response::json(['data' => $almacen_solicitante, 'parametros'=>$parametros], HttpResponse::HTTP_CONFLICT);

            /*if(!$almacen_solicitante->encargado){
                throw new \Exception("El encargado del almacen al que se enviaran los insumos, no esta especificado", 1);
            }*/
            
            $pedido->director_id = $almacen_solicitante->unidadMedica->director_id;
            $pedido->encargado_almacen_id = $almacen_solicitante->encargado_almacen_id;


            $pedido->total_claves_solicitadas = $total_claves;
            $pedido->total_cantidad_solicitada = $total_insumos;
            $pedido->total_monto_solicitado = $total_monto;
            $pedido->save();
            

            // ** Segundo creamos el movimiento  **
            $movimiento_completo = MovimientoPedido::movimientoCompleto()->where('pedido_id',$pedido->id)->where('status','BR')->where('tipo_movimiento_id',3)->first();

            $movimiento = Movimiento::find($movimiento_completo->movimiento_id);
            $movimiento->fecha_movimiento = $parametros['fecha'];
            $movimiento->status = $status_movimiento;
            $movimiento->save();

            $movimiento_pedido = MovimientoPedido::find($movimiento_completo->id);
            $movimiento_pedido->recibe = ($almacen_solicitante->encargado)?$almacen_solicitante->encargado->nombre:'sin asignar';
            $movimiento_pedido->entrega = ($almacen->encargado)?$almacen->encargado->nombre:'sin asignar';
            $movimiento_pedido->save();
            
            /*
            foreach ($parametros['movimiento_insumos'] as $key => $value) {
                $reglas_insumos = [
                    'stock_id'           => 'required',
                    'tipo'           => 'required',
                    'tipo_insumo_id' => 'required',
                    'clave'           => 'required',
                    'precio'        => 'required|numeric',
                    'cantidad'        => 'required|integer|min:0'
                ];  

                $v = Validator::make($value, $reglas_insumos, $mensajes);

                if ($v->fails()) {
                    DB::rollBack();
                    return Response::json(['error' => 'El stock con clave: '.$value['clave'].' tiene un valor incorrecto.'], 500);
                }
                if($value['cantidad'] > 0){
                    $precio_total = $value['cantidad']*$value['precio'];
                    $iva = 0.00;

                    if($value['tipo'] == "MC"){
                        $precio_total = $precio_total * 1.16;
                        $iva = $precio_total * 0.16;
                    } 
                    
                    $insumo = [
                        'movimiento_id' => $movimiento->id,   
                        'tipo_insumo_id' => $value['tipo_insumo_id'],
                        'stock_id' => $value['stock_id'],
                        'clave_insumo_medico' => $value['clave'],
                        'cantidad' => $value['cantidad'],
                        'precio_unitario' => $value['precio'],
                        'iva' => $iva, 
                        'precio_total' => $precio_total
                    ];

                    MovimientoInsumos::create($insumo);

                    // Actualizamos stock si se ha finalizado
                    if(isset($parametros['finalizar'])){
                        $stock = Stock::find($value['stock_id']);

                        $existencia_final = $stock->existencia - $value['cantidad'];

                        if($existencia_final < 0){
                            DB::rollBack();
                            return Response::json(['error' => 'El insumo con clave: '.$value['clave'].' ya no tiene stock.'], 500);
                        }
                        $stock->existencia = $existencia_final;
                        $stock->save();
                    }
                }
            }
            */

            $lista_insumos_db = MovimientoInsumos::where('movimiento_id',$movimiento_completo->movimiento_id)->withTrashed()->get();
            
            if(count($lista_insumos_db) > count($parametros['movimiento_insumos'])){
                $total_max_insumos = count($lista_insumos_db);
            }else{
                $total_max_insumos = count($parametros['movimiento_insumos']);
            }
            
            for ($i=0; $i < $total_max_insumos ; $i++) {
                if(isset($lista_insumos_db[$i])){ //Si existe un registro en la base de datos se edita o elimina.
                    $insumo_db = $lista_insumos_db[$i];
                    if(isset($parametros['movimiento_insumos'][$i])){ //Si hay insumos desde el fomulario, editamos el insumo de la base de datos.
                        $insumo_form = $parametros['movimiento_insumos'][$i];
                        
                        $insumo_db->deleted_at = null; //Por si el elemento ya esta liminado, lo restauramos
                        $insumo_db->tipo_insumo_id = $insumo_form['tipo_insumo_id'];
                        $insumo_db->stock_id = $insumo_form['stock_id'];
                        $insumo_db->clave_insumo_medico = $insumo_form['clave'];
                        $insumo_db->cantidad = $insumo_form['cantidad'];
                        $insumo_db->precio_unitario = $insumo_form['precio'];
                        $insumo_db->iva = ($insumo_form['cantidad']*$insumo_form['precio']) * 0.16;
                        $insumo_db->precio_total = $insumo_form['cantidad']*$insumo_form['precio'];
    
                        $insumo_db->save();
                    }else{ //de lo contrario eliminamos el insumo de la base de datos.
                        $insumo_db->delete();
                    }
                }else{ //SI no existe un registro en la base de datos, se crea uno nuevo
                    $insumo_form = $parametros['movimiento_insumos'][$i];
                    $insumo_db = new MovimientoInsumos();

                    $insumo_db->tipo_insumo_id = $insumo_form['tipo_insumo_id'];
                    $insumo_db->stock_id = $insumo_form['stock_id'];
                    $insumo_db->clave_insumo_medico = $insumo_form['clave'];
                    $insumo_db->cantidad = $insumo_form['cantidad'];
                    $insumo_db->precio_unitario = $insumo_form['precio'];
                    $insumo_db->iva = ($insumo_form['cantidad']*$insumo_form['precio']) * 0.16;
                    $insumo_db->precio_total = $insumo_form['cantidad']*$insumo_form['precio'];
                    $insumo_db->movimiento_id = $movimiento->id;

                    $insumo_db->save();
                }

                // Actualizamos stock si se ha finalizado
                if(isset($parametros['finalizar'])){
                    if(isset($parametros['movimiento_insumos'][$i])){
                        $insumo_form = $parametros['movimiento_insumos'][$i];
                        $stock = Stock::find($insumo_form['stock_id']);

                        $cantidad_x_envase = $stock->existencia_unidosis / $stock->existencia;

                        $existencia_final = $stock->existencia - $insumo_form['cantidad'];
                        $existencia_final_unidosis = $stock->existencia_unidosis - ($insumo_form['cantidad'] * $cantidad_x_envase);
    
                        if($existencia_final < 0){
                            DB::rollBack();
                            return Response::json(['error' => 'El insumo con clave: '.$insumo_form['clave'].' ya no tiene stock.'], 500);
                        }
                        $stock->existencia = $existencia_final;
                        $stock->existencia_unidosis = $existencia_final_unidosis;
                        $stock->save();
                    }
                }
            }

            $historial = HistorialMovimientoTransferencia::where('pedido_id',$pedido->id)->where('evento','SURTIO PEA')->where('movimiento_id',$movimiento->id)->first();

            $historial_datos['almacen_origen'] = $pedido->almacen_proveedor;
            $historial_datos['almacen_destino'] = $pedido->almacen_solicitante;
            $historial_datos['clues_origen'] = $pedido->clues;
            $historial_datos['clues_destino'] = ($pedido->clues_destino)?$pedido->clues_destino:$pedido->clues;
            $historial_datos['total_unidades'] = $pedido->total_cantidad_solicitada;
            $historial_datos['total_claves'] = $pedido->total_claves_solicitadas;
            $historial_datos['total_monto'] = $pedido->total_monto_solicitado;
            
            if(isset($parametros['finalizar'])){
                $historial_datos['fecha_finalizacion'] = Carbon::now();
            }

            $historial->update($historial_datos);

            $pedido->insumos;
            $movimiento->movimientoPedido;
            $movimiento->insumos;

            $respuesta = [
                'pedido' => $pedido,
                'movimiento' => $movimiento
            ];

            //DB::rollBack();
            DB::commit();
            return Response::json([ 'data' => $respuesta ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        }

    }

    private function guardarDatosInventario($inventario,$lista_insumos_form){
        try{
            DB::beginTransaction();

            $lista_insumos_db = InventarioDetalle::where('inventario_id',$inventario->id)->withTrashed()->get();
            
            if(count($lista_insumos_db) > count($lista_insumos_form)){
                $total_max_insumos = count($lista_insumos_db);
            }else{
                $total_max_insumos = count($lista_insumos_form);
            }
            
            for ($i=0; $i < $total_max_insumos ; $i++) {
                if(isset($lista_insumos_db[$i])){ //Si existen en la base de datos se editan o eliminan.
                    $insumo_db = $lista_insumos_db[$i];
                    if(isset($lista_insumos_form[$i])){ //Si hay insumos desde el fomulario, editamos el insumo de la base de datos.
                        $insumo_form = $lista_insumos_form[$i];
    
                        $insumo_db->deleted_at = null; //Por si el elemento ya esta liminado, lo restauramos
                        $insumo_db->insumo_medico_clave = $lista_insumos_form['clave'];
                        $insumo_db->codigo_barras = $lista_insumos_form['codigo_barras'];
                        $insumo_db->lote = $lista_insumos_form['lote'];
                        $insumo_db->fecha_caducidad = $lista_insumos_form['fecha_caducidad'];
                        $insumo_db->cantidad = $lista_insumos_form['cantidad'];
                        $insumo_db->precio_unitario = $lista_insumos_form['precio_unitario'];
                        $insumo_db->monto = $lista_insumos_form['monto'];
    
                        $insumo_db->save();
                    }else{ //de lo contrario eliminamos el insumo de la base de datos.
                        $insumo_db->delete();
                    }
                }else{ //SI no existen en la base de datos, se crean nuevos
                    $insumo_db = new InventarioDetalle();

                    $insumo_db->inventario_id = $inventario->id;
                    $insumo_db->insumo_medico_clave = $lista_insumos_form['clave'];
                    $insumo_db->codigo_barras = $lista_insumos_form['codigo_barras'];
                    $insumo_db->lote = $lista_insumos_form['lote'];
                    $insumo_db->fecha_caducidad = $lista_insumos_form['fecha_caducidad'];
                    $insumo_db->cantidad = $lista_insumos_form['cantidad'];
                    $insumo_db->precio_unitario = $lista_insumos_form['precio_unitario'];
                    $insumo_db->monto = $lista_insumos_form['monto'];

                    $insumo_db->save();
                }
            }

            //Falta sumar los totales, de claves y montos

            DB::commit();

            return array('status'=>200, 'msg'=>'Exito');

        }catch (\Exception $e) {
            DB::rollBack();
            return array('status'=>HttpResponse::HTTP_CONFLICT, 'error'=>$e->getMessage());
        }
    }

    public function surtir($id, Request $request){
        $datos = Input::all();

        try{
            $pedido = Pedido::find($id);
            if($pedido->status != 'SD' && $pedido->status != 'ET'){
                return Response::json(['error' => 'El pedido ya no puede surtirse.'], 500);
            }
            
            $pedido->load('insumos.conDatosInsumo');

            $stock_ids = [];
            $claves = [];
            $cantidades_stock = [];
            foreach($datos['lista'] as $stock){
                $stock_ids[] = $stock['stock_id'];
                if(!isset($claves[$stock['clave']])){
                    $claves[$stock['clave']] = ['cantidad'=>0,'tipo_insumo_id'=>0,'precio'=>0,'cantidad_x_envase'=>0];
                }
                $claves[$stock['clave']]['cantidad'] += $stock['cantidad'];
                $cantidades_stock[$stock['stock_id']] = $stock['cantidad'];
            }

            $stocks = Stock::where('almacen_id',$pedido->almacen_proveedor)->whereIn('id',$stock_ids)->get();

            DB::beginTransaction();
            $total_cantidad_enviada = 0;
            $total_monto_enviado = 0;
            $monto_para_iva = 0;
            //Harima: Actualizamos el pedido para actualizar lo que se envio
            foreach($pedido->insumos as $insumo){
                if(isset($claves[$insumo->insumo_medico_clave])){
                    if(!$insumo->cantidad_enviada){
                        $insumo->cantidad_enviada = 0;
                    }
                    $insumo->cantidad_enviada += $claves[$insumo->insumo_medico_clave]['cantidad'];
                    $insumo->monto_enviado = $insumo->precio_unitario * $insumo->cantidad_enviada;

                    $insumo->save();

                    $total_cantidad_enviada += $claves[$insumo->insumo_medico_clave]['cantidad'];
                    $total_monto_enviado += $claves[$insumo->insumo_medico_clave]['cantidad'] * $insumo->precio_unitario;

                    if($insumo->conDatosInsumo->tipo == 'MC'){
                        $monto_para_iva += $claves[$insumo->insumo_medico_clave]['cantidad'] * $insumo->precio_unitario;
                    }

                    $claves[$insumo->insumo_medico_clave]['tipo_insumo_id'] = $insumo->tipo_insumo_id;
                    $claves[$insumo->insumo_medico_clave]['precio'] = $insumo->precio_unitario;
                    $claves[$insumo->insumo_medico_clave]['cantidad_x_envase'] = $insumo->conDatosInsumo->cantidad_x_envase;
                }
            }

            if($monto_para_iva > 0){
                $total_monto_enviado += $monto_para_iva*16/100;
            }

            $pedido->status = 'ET';
            $pedido->recepcion_permitida = 1;
            $pedido->save();

            $movimiento_insumos = [];
            //Harima: Actualizamos el stock y creamos los datos de insumos para el movimiento
            foreach($stocks as $stock_item){
                $stock_item->existencia -= $cantidades_stock[$stock_item->id];
                $stock_item->existencia_unidosis -= ($claves[$stock_item->clave_insumo_medico]['cantidad_x_envase'] * $cantidades_stock[$stock_item->id]);

                $stock_item->save();

                $movimiento_insumo_item = new MovimientoInsumos();
                $movimiento_insumo_item->tipo_insumo_id = $claves[$stock_item->clave_insumo_medico]['tipo_insumo_id'];
                $movimiento_insumo_item->stock_id = $stock_item->id;
                $movimiento_insumo_item->clave_insumo_medico = $stock_item->clave_insumo_medico;
                $movimiento_insumo_item->modo_salida = 'N';
                $movimiento_insumo_item->cantidad = $cantidades_stock[$stock_item->id];
                $movimiento_insumo_item->cantidad_unidosis = ($claves[$stock_item->clave_insumo_medico]['cantidad_x_envase'] * $cantidades_stock[$stock_item->id]);
                $movimiento_insumo_item->precio_unitario = $claves[$stock_item->clave_insumo_medico]['precio'];
                $movimiento_insumo_item->precio_total = $movimiento_insumo_item->precio_unitario * $movimiento_insumo_item->cantidad;

                $movimiento_insumos[] = $movimiento_insumo_item;
            }

            $movimiento = new Movimiento();
            $movimiento->almacen_id = $pedido->almacen_proveedor;
            $movimiento->tipo_movimiento_id = 3;
            $movimiento->status = 'FI';
            $movimiento->fecha_movimiento = date('Y-m-d');
            
            $movimiento->save();
            $movimiento->insumos()->saveMany($movimiento_insumos);

            /* No lo necesito si tengo el historial
            $movimiento_pedido = new MovimientoPedido();
            $movimiento_pedido->pedido_id = $id;
            $movimiento->movimientoPedido()->save($movimiento_pedido);
            */

            $historial_datos = [
                'almacen_origen'=>$pedido->almacen_proveedor,
                'almacen_destino'=>$pedido->almacen_solicitante,
                'clues_origen'=>$pedido->clues,
                'clues_destino'=>($pedido->clues_destino)?$pedido->clues_destino:$pedido->clues,
                'pedido_id'=>$pedido->id,
                'evento'=>'SURTIO PEA',
                'movimiento_id'=>$movimiento->id,
                'total_unidades'=>$total_cantidad_enviada,
                'total_claves'=>count($claves),
                'total_monto'=>$total_monto_enviado,
                'fecha_inicio_captura'=>$movimiento->created_at,
                'fecha_finalizacion'=>Carbon::now()
            ];

            $historial = HistorialMovimientoTransferencia::create($historial_datos);

            DB::commit();
	        return Response::json(['message'=>'surtido','data'=>$movimiento],200);
        }catch(\Exception $e){
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function actualizarTransferencia($id, Request $request){
        $datos = Input::all();
        try{
            $pedido = Pedido::where('tipo_pedido_id','PEA')->find($id);
            
            if($pedido->status != 'SD' && $pedido->status != 'ET'){
                return Response::json(['error' => 'El pedido ya no puede surtirse.'], 500);
            }
            
            $pedido->load('insumos.conDatosInsumo');

            $stock_ids = [];
            $claves = [];
            $cantidades_stock = [];

            $total_cantidad = 0;
            $total_monto = 0;
            $monto_para_iva = 0;

            foreach($datos['insumos'] as $stock){
                $stock_ids[] = $stock['stock_id'];
                if(!isset($claves[$stock['clave']])){
                    $claves[$stock['clave']] = ['cantidad'=>0,'tipo_insumo_id'=>0,'precio'=>0,'cantidad_x_envase'=>0];
                }
                $claves[$stock['clave']]['cantidad'] += $stock['cantidad'];
                $cantidades_stock[$stock['stock_id']] = $stock['cantidad'];
                $total_cantidad += $stock['cantidad'];
            }

            $stocks = Stock::where('almacen_id',$pedido->almacen_proveedor)->whereIn('id',$stock_ids)->get();
            
            DB::beginTransaction();
            //Harima: Actualizamos el pedido para mostrar lo que se envio
            foreach($pedido->insumos as $insumo){
                if(isset($claves[$insumo->insumo_medico_clave])){
                    $claves[$insumo->insumo_medico_clave]['tipo_insumo_id'] = $insumo->tipo_insumo_id;
                    $claves[$insumo->insumo_medico_clave]['precio'] = $insumo->precio_unitario;
                    $claves[$insumo->insumo_medico_clave]['cantidad_x_envase'] = $insumo->conDatosInsumo->cantidad_x_envase;

                    $insumo->cantidad_enviada -= $claves[$insumo->insumo_medico_clave]['cantidad']; //Lo restamos de cantidad eviada, para que podamos enviar mas en caso de ser necesario
                    $insumo->monto_enviado -= $claves[$insumo->insumo_medico_clave]['cantidad'] * $insumo->precio_unitario;
                    $insumo->save();

                    $total_monto += $claves[$insumo->insumo_medico_clave]['cantidad'] * $insumo->precio_unitario;
                    if($insumo->conDatosInsumo->tipo == 'MC'){
                        $monto_para_iva += $claves[$insumo->insumo_medico_clave]['cantidad'] * $insumo->precio_unitario;
                    }
                }
            }

            if($monto_para_iva > 0){
                $total_monto += $monto_para_iva*16/100;
            }

            //$pedido->status = 'FI-P';
            //$pedido->recepcion_permitida = 0;
            //$pedido->save();

            $movimiento_mas_insumos = [];
            $movimiento_menos_insumos = [];

            foreach($stocks as $stock_item){

                if($datos['accion'] == 'reintegrar'){
                    $stock_item->existencia += $cantidades_stock[$stock_item->id];
                    $stock_item->existencia_unidosis += ($claves[$stock_item->clave_insumo_medico]['cantidad_x_envase'] * $cantidades_stock[$stock_item->id]);
                    $stock_item->save();
                }
                
                if($datos['accion'] == 'reintegrar'){ // || $datos['accion'] == 'eliminar'
                    $insumo_mas = new MovimientoInsumos();
                    $insumo_mas->tipo_insumo_id = $claves[$stock_item->clave_insumo_medico]['tipo_insumo_id'];
                    $insumo_mas->stock_id = $stock_item->id;
                    $insumo_mas->clave_insumo_medico = $stock_item->clave_insumo_medico;
                    //$insumo_mas->modo_salida = 'N';
                    $insumo_mas->cantidad = $cantidades_stock[$stock_item->id];
                    $insumo_mas->cantidad_unidosis = ($claves[$stock_item->clave_insumo_medico]['cantidad_x_envase'] * $cantidades_stock[$stock_item->id]);
                    $insumo_mas->precio_unitario = $claves[$stock_item->clave_insumo_medico]['precio'];
                    $insumo_mas->precio_total = $insumo_mas->precio_unitario * $insumo_mas->cantidad;
    
                    $movimiento_mas_insumos[] = $insumo_mas;
                }
                
                if($datos['accion'] == 'eliminar'){
                    $insumo_menos = new MovimientoInsumos();
                    $insumo_menos->tipo_insumo_id = $claves[$stock_item->clave_insumo_medico]['tipo_insumo_id'];
                    $insumo_menos->stock_id = $stock_item->id;
                    $insumo_menos->clave_insumo_medico = $stock_item->clave_insumo_medico;
                    $insumo_menos->modo_salida = 'N';
                    $insumo_menos->cantidad = $cantidades_stock[$stock_item->id];
                    $insumo_menos->cantidad_unidosis = ($claves[$stock_item->clave_insumo_medico]['cantidad_x_envase'] * $cantidades_stock[$stock_item->id]);
                    $insumo_menos->precio_unitario = $claves[$stock_item->clave_insumo_medico]['precio'];
                    $insumo_menos->precio_total = $insumo_menos->precio_unitario * $insumo_menos->cantidad;
    
                    $movimiento_menos_insumos[] = $insumo_menos;
                }
            }

            if(count($movimiento_mas_insumos) > 0){
                $movimiento_mas = new Movimiento();
                $movimiento_mas->almacen_id = $pedido->almacen_proveedor;
                $movimiento_mas->tipo_movimiento_id = 1;
                $movimiento_mas->status = 'FI';
                $movimiento_mas->observaciones = 'SE REGRESAN AL INVENTARIO INSUMOS NO ENTREGADOS EN EL PEDIDO CON FOLIO: '.$pedido->folio;
                $movimiento_mas->fecha_movimiento = date('Y-m-d');
                
                $movimiento_mas->save();
                $movimiento_mas->insumos()->saveMany($movimiento_mas_insumos);

                $movimiento_id = $movimiento_mas->id;
                $evento = 'REINTEGRACION INVENTARIO';

                /*
                $movimiento_pedido = new MovimientoPedido();
                $movimiento_pedido->pedido_id = $id;
                $movimiento_mas->movimientoPedido()->save($movimiento_pedido);
                */
            }
            
            if(count($movimiento_menos_insumos) > 0){
                $movimiento_menos = new Movimiento();
                $movimiento_menos->almacen_id = $pedido->almacen_proveedor;
                $movimiento_menos->tipo_movimiento_id = 7;
                $movimiento_menos->status = 'FI';
                $movimiento_menos->observaciones = 'SE MARCAN COMO MERMA INSUMOS NO ENTREGADOS EN EL PEDIDO CON FOLIO: '.$pedido->folio;
                $movimiento_menos->fecha_movimiento = date('Y-m-d');
                
                $movimiento_menos->save();
                $movimiento_menos->insumos()->saveMany($movimiento_menos_insumos);

                $movimiento_id = $movimiento_menos->id;
                $evento = 'ELIMINACION INVENTARIO';

                /*
                $movimiento_pedido = new MovimientoPedido();
                $movimiento_pedido->pedido_id = $id;
                $movimiento_menos->movimientoPedido()->save($movimiento_pedido);
                */
            }

            $historial_datos = [
                'almacen_origen'=>$pedido->almacen_proveedor,
                'almacen_destino'=>$pedido->almacen_solicitante,
                'clues_origen'=>$pedido->clues,
                'clues_destino'=>($pedido->clues_destino)?$pedido->clues_destino:$pedido->clues,
                'pedido_id'=>$pedido->id,
                'evento'=>$evento,
                'movimiento_id'=>$movimiento_id,
                'total_unidades'=>$total_cantidad,
                'total_claves'=>count($claves),
                'total_monto'=>$total_monto,
                'fecha_inicio_captura'=>Carbon::now(),
                'fecha_finalizacion'=>Carbon::now()
            ];

            $historial = HistorialMovimientoTransferencia::create($historial_datos);
            
            DB::commit();
	        return Response::json(['message'=>'finalizado','data'=>$pedido],200);
        }catch(\Exception $e){
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function finalizar($id, Request $request){
        $datos = Input::all();

        try{
            $pedido = Pedido::where('tipo_pedido_id','PEA')->find($id);
            if($pedido->status != 'SD' && $pedido->status != 'ET'){
                return Response::json(['error' => 'El pedido ya no puede surtirse.'], 500);
            }
            
            $pedido->load('insumos.conDatosInsumo');

            $stock_ids = [];
            $claves = [];
            $cantidades_stock = [];
            foreach($datos['lista'] as $stock){
                $stock_ids[] = $stock['stock_id'];
                if(!isset($claves[$stock['clave']])){
                    $claves[$stock['clave']] = ['cantidad'=>0,'tipo_insumo_id'=>0,'precio'=>0,'cantidad_x_envase'=>0];
                }
                $claves[$stock['clave']]['cantidad'] += $stock['cantidad'];
                $cantidades_stock[$stock['stock_id']] = $stock['cantidad'];
            }

            $stocks = Stock::where('almacen_id',$pedido->almacen_proveedor)->whereIn('id',$stock_ids)->get();

            DB::beginTransaction();
            //Harima: Actualizamos el pedido para mostrar lo que se envio
            foreach($pedido->insumos as $insumo){
                if(isset($claves[$insumo->insumo_medico_clave])){
                    $claves[$insumo->insumo_medico_clave]['tipo_insumo_id'] = $insumo->tipo_insumo_id;
                    $claves[$insumo->insumo_medico_clave]['precio'] = $insumo->precio_unitario;
                    $claves[$insumo->insumo_medico_clave]['cantidad_x_envase'] = $insumo->conDatosInsumo->cantidad_x_envase;
                }
            }

            $pedido->status = 'FI-P';
            $pedido->recepcion_permitida = 0;
            $pedido->save();

            $movimiento_mas_insumos = [];
            $movimiento_menos_insumos = [];
            //Harima: Creamos los datos para los ajustes
            foreach($stocks as $stock_item){
                $insumo_mas = new MovimientoInsumos();
                $insumo_mas->tipo_insumo_id = $claves[$stock_item->clave_insumo_medico]['tipo_insumo_id'];
                $insumo_mas->stock_id = $stock_item->id;
                $insumo_mas->clave_insumo_medico = $stock_item->clave_insumo_medico;
                $insumo_mas->modo_salida = 'N';
                $insumo_mas->cantidad = $cantidades_stock[$stock_item->id];
                $insumo_mas->cantidad_unidosis = ($claves[$stock_item->clave_insumo_medico]['cantidad_x_envase'] * $cantidades_stock[$stock_item->id]);
                $insumo_mas->precio_unitario = $claves[$stock_item->clave_insumo_medico]['precio'];
                $insumo_mas->precio_total = $insumo_mas->precio_unitario * $insumo_mas->cantidad;

                $movimiento_mas_insumos[] = $insumo_mas;

                $insumo_menos = new MovimientoInsumos();
                $insumo_menos->tipo_insumo_id = $claves[$stock_item->clave_insumo_medico]['tipo_insumo_id'];
                $insumo_menos->stock_id = $stock_item->id;
                $insumo_menos->clave_insumo_medico = $stock_item->clave_insumo_medico;
                $insumo_menos->modo_salida = 'N';
                $insumo_menos->cantidad = $cantidades_stock[$stock_item->id];
                $insumo_menos->cantidad_unidosis = ($claves[$stock_item->clave_insumo_medico]['cantidad_x_envase'] * $cantidades_stock[$stock_item->id]);
                $insumo_menos->precio_unitario = $claves[$stock_item->clave_insumo_medico]['precio'];
                $insumo_menos->precio_total = $insumo_menos->precio_unitario * $insumo_menos->cantidad;

                $movimiento_menos_insumos[] = $insumo_menos;
            }

            $movimiento_mas = new Movimiento();
            $movimiento_mas->almacen_id = $pedido->almacen_proveedor;
            $movimiento_mas->tipo_movimiento_id = 6;
            $movimiento_mas->status = 'FI';
            $movimiento_mas->observaciones = 'SE REGRESAN AL INVENTARIO INSUMOS NO ENTREGADOS EN EL PEDIDO CON FOLIO: '.$pedido->folio;
            $movimiento_mas->fecha_movimiento = date('Y-m-d');
            
            $movimiento_mas->save();
            $movimiento_mas->insumos()->saveMany($movimiento_mas_insumos);

            $movimiento_menos = new Movimiento();
            $movimiento_menos->almacen_id = $pedido->almacen_proveedor;
            $movimiento_menos->tipo_movimiento_id = 7;
            $movimiento_menos->status = 'FI';
            $movimiento_menos->observaciones = 'SE DAN DE BAJA INSUMOS NO ENTREGADOS EN EL PEDIDO CON FOLIO: '.$pedido->folio;
            $movimiento_menos->fecha_movimiento = date('Y-m-d');
            
            $movimiento_menos->save();
            $movimiento_menos->insumos()->saveMany($movimiento_menos_insumos);
            
            DB::commit();
	        return Response::json(['message'=>'finalizado','data'=>$pedido],200);
        }catch(\Exception $e){
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}