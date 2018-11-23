<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Requests;
use App\Models\Pedido;
use App\Models\PedidoInsumo;
use App\Models\PedidoInsumoClues;
use App\Models\Usuario;
use App\Models\Almacen;
use App\Models\Presupuesto;
use App\Models\UnidadMedica;
use App\Models\MovimientoPedido;
use App\Models\Movimiento;
use App\Models\MovimientoInsumos;
use App\Models\Stock;
use App\Models\UnidadMedicaPresupuesto;
use \Excel;
use Carbon\Carbon;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;


class PedidoJurisdiccionalController extends Controller{
    public function obtenerDatosPresupuesto(Request $request){
        try{
            $almacen = Almacen::find($request->get('almacen_id'));

            $parametros = Input::all();

            $presupuesto = Presupuesto::where('activo',1)->first();

            $presupuesto_unidad_medica = UnidadMedicaPresupuesto::select('clues',
                                            DB::raw('sum(insumos_autorizado) as insumos_autorizado'),DB::raw('sum(insumos_modificado) as insumos_modificado'),DB::raw('sum(insumos_comprometido) as insumos_comprometido'),DB::raw('sum(insumos_devengado) as insumos_devengado'),DB::raw('sum(insumos_disponible) as insumos_disponible'),
                                            DB::raw('sum(causes_autorizado) as causes_autorizado'),DB::raw('sum(causes_modificado) as causes_modificado'),DB::raw('sum(causes_comprometido) as causes_comprometido'),DB::raw('sum(causes_devengado) as causes_devengado'),DB::raw('sum(causes_disponible) as causes_disponible'),
                                            DB::raw('sum(no_causes_autorizado) as no_causes_autorizado'),DB::raw('sum(no_causes_modificado) as no_causes_modificado'),DB::raw('sum(no_causes_comprometido) as no_causes_comprometido'),DB::raw('sum(no_causes_devengado) as no_causes_devengado'),DB::raw('sum(no_causes_disponible) as no_causes_disponible'),
                                            DB::raw('sum(material_curacion_autorizado) as material_curacion_autorizado'),DB::raw('sum(material_curacion_modificado) as material_curacion_modificado'),DB::raw('sum(material_curacion_comprometido) as material_curacion_comprometido'),DB::raw('sum(material_curacion_devengado) as material_curacion_devengado'),DB::raw('sum(material_curacion_disponible) as material_curacion_disponible'))
                                            ->where('presupuesto_id',$presupuesto->id)
                                            ->where('clues',$almacen->clues)
                                            ->where('almacen_id',$almacen->id)
                                            //->where('proveedor_id',$almacen->proveedor_id)
                                            ->groupBy('clues');
            if(isset($parametros['mes'])){
                if($parametros['mes']){
                    $presupuesto_unidad_medica = $presupuesto_unidad_medica->where('mes',$parametros['mes']);
                }
            }

            if(isset($parametros['anio'])){
                if($parametros['anio']){
                    $presupuesto_unidad_medica = $presupuesto_unidad_medica->where('anio',$parametros['anio']);
                }
            }

            // Akira estoy comentando esto porque en la base de datos todos tienen en null almacen_id
            /*
            if(isset($parametros['almacen'])){
                if($parametros['almacen']){
                    $presupuesto_unidad_medica = $presupuesto_unidad_medica->where('almacen_id',$parametros['almacen']);
                }
            }*/

            $presupuesto_unidad_medica = $presupuesto_unidad_medica->first();
            return Response::json([ 'data' => $presupuesto_unidad_medica],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function stats(Request $request){
        $almacen = Almacen::find($request->get('almacen_id'));

        // Hay que obtener la clues del usuario
        $pedidos = Pedido::select(DB::raw(
            '
            count(
                1
            ) as todos,
            count(
                case when status = "BR" then 1 else null end
            ) as borradores,
            count(
                case when status = "ET" then 1 else null end
            ) as en_transito,
            count(
                case when status = "PS" then 1 else null end
            ) as por_surtir,
            count(
                case when status = "FI" then 1 else null end
            ) as finalizados,
            count(
                case when status = "EX" then 1 else null end
            ) as expirados,
            count(
                case when status = "EF" then 1 else null end
            ) as farmacia
            '
        //))->where('almacen_solicitante',$almacen->id)->where('clues',$almacen->clues)->first();
        ))->where('clues',$almacen->clues)->where("tipo_pedido_id",'PJS')->first();

        return Response::json($pedidos,200);
    }

    public function index(Request $request){
        $almacen = Almacen::find($request->get('almacen_id'));
        
        $parametros = Input::only('status','q','page','per_page');

        //$pedidos = Pedido::with("insumos", "acta", "tipoInsumo", "tipoPedido","almacenSolicitante","almacenProveedor");
        $pedidos = Pedido::getModel();

       if ($parametros['q']) {
            $pedidos =  $pedidos->where(function($query) use ($parametros) {
                 $query->where('id','LIKE',"%".$parametros['q']."%")->orWhere('descripcion','LIKE',"%".$parametros['q']."%")->orWhere('folio','LIKE',"%".$parametros['q']."%");
             });
        }

        //$pedidos = $pedidos->where('almacen_solicitante',$almacen->id)->where('clues',$almacen->clues);
        $pedidos = $pedidos->where('clues',$almacen->clues);

        if(isset($parametros['status'])) {
            $pedidos = $pedidos->where("pedidos.status",$parametros['status']);
        }

        $pedidos = $pedidos->select('pedidos.*',DB::raw('datediff(fecha_expiracion,current_date()) as expira_en_dias'));
        
          $pedidos = $pedidos->where("pedidos.tipo_pedido_id",'PJS');

        //$pedido = Pedido::with("insumos", "acta", "TipoInsumo", "TipoPedido")->get();
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
            $pedidos = $pedidos->paginate($resultadosPorPagina);
        } else {
            $pedidos = $pedidos->get();
        }

        return Response::json([ 'data' => $pedidos],200);
    }

    public function show(Request $request, $id){
        $almacen = Almacen::find($request->get('almacen_id'));
    	//$pedido = Pedido::where('almacen_solicitante',$request->get('almacen_id'))->find($id);
        $pedido = Pedido::where('clues',$almacen->clues)->find($id);
        
        if(!$pedido){
            return Response::json(['error' => "No se encuentra el pedido que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }else{
            if($pedido->status == 'BR'){
                $pedido = $pedido->load("insumos.tipoInsumo","insumos.listaClues","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos","proveedor");
            }else{
                $pedido = $pedido->load("insumos.tipoInsumo","insumos.listaClues","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos","almacenSolicitante.unidadMedica","proveedor","encargadoAlmacen","director","recepciones.entrada.insumos");
            }
        }

       
        return Response::json([ 'data' => $pedido],200);
    }

    public function store(Request $request){
    //     return Response::json(['error' => "Chuchi"], 500);
        $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'tipo_pedido_id'        => 'required',
            'almacen_solicitante'   => 'required',
            'descripcion'           => 'required',
            'fecha'                 => 'required|date',
            'status'                => 'required'
        ];

        $parametros = Input::all();



        $almacen = Almacen::find($request->get('almacen_id'));

        if($almacen->nivel_almacen == 1 && $almacen->tipo_almacen == 'ALMPAL'){
            $reglas['proveedor_id'] = 'required';
            $parametros['datos']['proveedor_id'] = $almacen->proveedor_id;
            $parametros['datos']['almacen_proveedor'] = null;
        }elseif($almacen->nivel_almacen == 2){
            $reglas['almacen_proveedor'] = 'required';
        }

        $almacen_solicitante = Almacen::find($parametros['datos']['almacen_solicitante']);


       

       
        
        if($almacen_solicitante){
             // Comento esto porque creo que es para identificar los pedidos jurisdiccionales
             /*
            if($almacen_solicitante->nivel_almacen == 1 && $almacen_solicitante->tipo_almacen == 'FARSBR' && $almacen_solicitante->subrogado == 1){
                $tipo_pedido = 'PFS';
            }else{
                $tipo_pedido = 'PA';
            }*/
             $tipo_pedido = 'PJS'; // Pedidos jurisdiccionales
        }else{
            return Response::json(['error' => 'No se encontró el almacen solicitante'], 500);
        }
        
        //$parametros['datos']['almacen_solicitante'] = $almacen->id;
        $parametros['datos']['clues'] = $almacen->clues;
        $parametros['datos']['status'] = 'BR'; //estatus de borrador
        $parametros['datos']['tipo_pedido_id'] = $tipo_pedido; //tipo de pedido Pedido de Abastecimiento

        //$fecha = date($parametros['datos']['fecha']);
        //$fecha_expiracion = strtotime("+20 days", strtotime($fecha));
        //$parametros['datos']['fecha_expiracion'] = date("Y-m-d", $fecha_expiracion);

        //return Response::json(['error' => 'Se necesita capturar al menos un insumo','data'=>$parametros['datos']], 500);

        $v = Validator::make($parametros['datos'], $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        if(count($parametros['insumos']) == 0){
            return Response::json(['error' => 'Se necesita capturar al menos un insumo'], 500);
        }

        try {
            DB::beginTransaction();
            
            $pedido = Pedido::create($parametros['datos']);

            $total_claves = count($parametros['insumos']);
            $total_insumos = 0;
            $total_monto = 0;
            $monto_iva = 0;

            foreach ($parametros['insumos'] as $key => $value) {
                $reglas_insumos = [
                    'clave'           => 'required',
                    'cantidad'        => 'required|integer|min:1'
                ];  

                $v = Validator::make($value, $reglas_insumos, $mensajes);

                if ($v->fails()) {
                    DB::rollBack();
                    return Response::json(['error' => 'El insumo con clave: '.$value['clave'].' tiene un valor incorrecto.'], 500);
                }      
                
                $insumo = [
                    'insumo_medico_clave' => $value['clave'],
                    'cantidad_solicitada' => $value['cantidad'],
                    'monto_solicitado' => $value['cantidad']*$value['precio'], //$value['monto'],
                    'precio_unitario' => $value['precio'],
                    'tipo_insumo_id' => $value['tipo_insumo_id'],
                    'pedido_id' => $pedido->id
                ];
                //$value['pedido_id'] = $pedido->id;

                $total_insumos += $value['cantidad'];
                $total_monto += $value['monto'];

                if($value['tipo'] == 'MC'){
                    $monto_iva += $value['monto'];
                }

                $object_insumo = PedidoInsumo::create($insumo);

                foreach($value['lista_clues'] as $key_clues => $value_clues){
                    $insumo_clues = [
                        'pedido_insumo_id' => $object_insumo->id,
                        'clues' => $value_clues['clues'],
                        'cantidad' => $value_clues['cantidad']
                    ];
                    PedidoInsumoClues::create($insumo_clues);
                    
                }
            }

            if($monto_iva > 0){
                $total_monto += $monto_iva*16/100;
            }
            
            $pedido->total_claves_solicitadas = $total_claves;
            $pedido->total_cantidad_solicitada = $total_insumos;
            $pedido->total_monto_solicitado = $total_monto;
            $pedido->save();

            DB::commit();
            return Response::json([ 'data' => $pedido ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function update(Request $request, $id){
        $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'almacen_solicitante'   => 'required',
            'descripcion'           => 'required',
            'fecha'                 => 'required|date',
            'status'                => 'required'
        ];

        $parametros = Input::all();

        $almacen = Almacen::find($request->get('almacen_id'));

        if($almacen->nivel_almacen == 1 && $almacen->tipo_almacen == 'ALMPAL'){
            //$reglas['proveedor_id'] = 'required';
            //$parametros['datos']['proveedor_id'] = $almacen->proveedor_id;
            $parametros['datos']['almacen_proveedor'] = null;
        }elseif($almacen->nivel_almacen == 2){
            $reglas['almacen_proveedor'] = 'required';
        }
        
        $almacen_solicitante = Almacen::find($parametros['datos']['almacen_solicitante']);

        $tipo_pedido = '';
        if($almacen_solicitante){
             // Comento esto porque creo que es para identificar los pedidos jurisdiccionales
            /*
            if($almacen_solicitante->nivel_almacen == 1 && $almacen_solicitante->tipo_almacen == 'FARSBR' && $almacen_solicitante->subrogado == 1){
                $tipo_pedido = 'PFS';
            }else{
                $tipo_pedido = 'PA';
            }*/

            $tipo_pedido = 'PJS'; // Pedidos jurisdiccionales

       
        }else{
            return Response::json(['error' => 'No se encontró el almacen seleccionado'], 500);
        }
        $parametros['datos']['tipo_pedido_id'] = $tipo_pedido;
        //$parametros['datos']['tipo_pedido_id'] = 1;

        if(!isset($parametros['datos']['status'])){
            $parametros['datos']['status'] = 'BR'; //estatus Borrador
        }elseif($parametros['datos']['status'] == 'CONCLUIR'){
            if($almacen_solicitante->nivel_almacen == 1 && $almacen_solicitante->tipo_almacen == 'ALMPAL'){
                $parametros['datos']['status'] = 'PS';
            }elseif($almacen_solicitante->nivel_almacen == 1 && $almacen_solicitante->tipo_almacen == 'FARSBR' && $almacen_solicitante->subrogado == 1){
                $parametros['datos']['status'] = 'EF';
            }
            /*elseif($almacen_solicitante->nivel_almacen == 2){
                $parametros['datos']['status'] = 'ET';
            }*/
        }else{
            $parametros['datos']['status'] = 'BR';
        }

        if($almacen_solicitante->nivel_almacen == 1 && ($parametros['datos']['status'] == 'PS' || $parametros['datos']['status'] == 'EF')){ //$almacen_solicitante->tipo_almacen == 'ALMPAL' && 
            //$fecha = date($parametros['datos']['fecha']);
            $fecha_concluido = Carbon::now();
            $fecha_expiracion = strtotime("+20 days", strtotime($fecha_concluido));
            $parametros['datos']['fecha_concluido'] = $fecha_concluido;
            $parametros['datos']['fecha_expiracion'] = date("Y-m-d", $fecha_expiracion);
        }else{
            $parametros['datos']['fecha_concluido'] = null;
            $parametros['datos']['fecha_expiracion'] = null;
        }

        $v = Validator::make($parametros['datos'], $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        if(count($parametros['insumos']) == 0){
            return Response::json(['error' => 'Se necesita capturar al menos un insumo'], 500);
        }

        try {
            $pedido = Pedido::find($id);

            if($pedido->status != 'BR'){
                return Response::json(['error' => 'El pedido ya no puede editarse.'], 500);
            }

             DB::beginTransaction();

            $pedido->update($parametros['datos']);

            $arreglo_insumos = Array();
            
            //PedidoInsumo::where("pedido_id", $id)->forceDelete();

            $total_claves = count($parametros['insumos']);
            $total_insumos = 0;
            $total_monto = ['causes' => 0, 'no_causes' => 0, 'material_curacion' => 0];

            /*   Harima: Para editar lista de insumos sin tener que borrar en la base de datos   */
            $lista_insumos_db = PedidoInsumo::where('pedido_id',$id)->withTrashed()->get();
            if(count($lista_insumos_db) > count($parametros['insumos'])){
                $total_max_insumos = count($lista_insumos_db);
            }else{
                $total_max_insumos = count($parametros['insumos']);
            }

            $reglas_insumos = [
                'clave'           => 'required',
                'cantidad'        => 'required|integer|min:1'
            ];  

            for ($i=0; $i < $total_max_insumos ; $i++) {
                if(isset($lista_insumos_db[$i])){ //Si existe un registro en la base de datos se edita o elimina.
                    $insumo_db = $lista_insumos_db[$i];

                    if(isset($parametros['insumos'][$i])){ //Si hay insumos desde el fomulario, editamos el insumo de la base de datos.
                        $insumo_form = $parametros['insumos'][$i];

                        $v = Validator::make($insumo_form, $reglas_insumos, $mensajes);
                        if ($v->fails()) {
                            DB::rollBack();
                            return Response::json(['error' => 'El insumo con clave: '.$insumo_form['clave'].' tiene un valor incorrecto.'], 500);
                        }
    
                        $insumo_db->deleted_at = null; //Por si el elemento ya esta liminado, lo restauramos
                        $insumo_db->insumo_medico_clave = $insumo_form['clave'];
                        $insumo_db->cantidad_solicitada = $insumo_form['cantidad'];
                        $insumo_db->cantidad_recibida = ($insumo_form['cantidad_recibida'])?$insumo_form['cantidad_recibida']:null;
                        $insumo_db->precio_unitario = $insumo_form['precio'];
                        $insumo_db->monto_solicitado = $insumo_form['cantidad']*$insumo_form['precio'];
                        $insumo_db->monto_recibido = ($insumo_form['cantidad_recibida'])?$insumo_form['cantidad_recibida']*$insumo_form['precio']:null;
                        $insumo_db->tipo_insumo_id = $insumo_form['tipo_insumo_id'];
    
                        $insumo_db->save();
                    }else{ //de lo contrario eliminamos el insumo de la base de datos.
                        $insumo_db->delete();
                    }
                }else{ //SI no existe un registro en la base de datos, se crea uno nuevo
                    $insumo_form = $parametros['insumos'][$i];
                    $insumo_db = new PedidoInsumo();

                    $v = Validator::make($insumo_form, $reglas_insumos, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => 'El insumo con clave: '.$insumo_form['clave'].' tiene un valor incorrecto.'], 500);
                    }

                    $insumo_db->deleted_at = null; //Por si el elemento ya esta liminado, lo restauramos
                    $insumo_db->insumo_medico_clave = $insumo_form['clave'];
                    $insumo_db->cantidad_solicitada = $insumo_form['cantidad'];
                    $insumo_db->cantidad_recibida = ($insumo_form['cantidad_recibida'])?$insumo_form['cantidad_recibida']:null;
                    $insumo_db->precio_unitario = $insumo_form['precio'];
                    $insumo_db->monto_solicitado = $insumo_form['cantidad']*$insumo_form['precio'];
                    $insumo_db->monto_recibido = ($insumo_form['cantidad_recibida'])?$insumo_form['cantidad_recibida']*$insumo_form['precio']:null;
                    $insumo_db->tipo_insumo_id = $insumo_form['tipo_insumo_id'];
                    $insumo_db->pedido_id = $pedido->id;

                    $insumo_db->save();
                }

                if(isset($parametros['insumos'][$i])){
                    $insumo_form = $parametros['insumos'][$i];
                    $total_insumos += $insumo_form['cantidad'];
                    
                    if($insumo_form['tipo'] == 'ME' && $insumo_form['es_causes']){
                        $total_monto['causes'] += $insumo_form['monto'];
                    }elseif($insumo_form['tipo'] == 'ME' && !$insumo_form['es_causes']){
                        $total_monto['no_causes'] += $insumo_form['monto'];
                    }else{
                        $total_monto['material_curacion'] += $insumo_form['monto'];
                    }

                    foreach($insumo_form['lista_clues'] as $key_clues => $value_clues){
                        $insumo_clues = [
                            'pedido_insumo_id' => $object_insumo->id,
                            'clues' => $value_clues['clues'],
                            'cantidad' => $value_clues['cantidad']
                        ];
                        PedidoInsumoClues::create($insumo_clues);
                    }
                }
            }

            if($total_monto['material_curacion'] > 0){
                $total_monto['material_curacion'] += $total_monto['material_curacion']*16/100;
            }

            if(!$pedido->folio && $pedido->status != 'BR'){
                $anio = date('Y');

                $folio_template = $almacen->clues . '-' . $anio . '-'.$tipo_pedido.'-';
                $max_folio = Pedido::where('clues',$almacen->clues)->where('folio','like',$folio_template.'%')->max('folio');
                
                if(!$max_folio){
                    $prox_folio = 1;
                }else{
                    $max_folio = explode('-',$max_folio);
                    $prox_folio = intval($max_folio[3]) + 1;
                }
                $pedido->folio = $folio_template . str_pad($prox_folio, 3, "0", STR_PAD_LEFT);
            }

            //Harima: Ajustamos el presupuesto, colocamos los totales en comprometido
            //if($pedido->status == 'PS' || $pedido->status == 'ET'){
            if($pedido->status != 'BR'){
                $fecha = explode('-',$pedido->fecha);
                $presupuesto = Presupuesto::where('activo',1)->first();
                $presupuesto_unidad = UnidadMedicaPresupuesto::where('presupuesto_id',$presupuesto->id)
                                            ->where('clues',$almacen->clues)
                                            //->where('proveedor_id',$almacen->proveedor_id)
                                            //Akira: Comento esta línea porque todos los almacenes estan en null aqui
                                            //->where('almacen_id',$almacen_solicitante->id)
                                            ->where('mes',$fecha[1])
                                            ->where('anio',$fecha[0])
                                            ->first();
                if(!$presupuesto_unidad){
                    DB::rollBack();
                    return Response::json(['error' => 'No existe presupuesto asignado al mes y/o año del pedido'], 500);
                }
                
                $presupuesto_unidad->causes_comprometido = round($presupuesto_unidad->causes_comprometido,2) + round($total_monto['causes'],2);
                //$presupuesto_unidad->causes_disponible = round($presupuesto_unidad->causes_disponible,2) - round($total_monto['causes'],2);

                $presupuesto_unidad->material_curacion_comprometido = round($presupuesto_unidad->material_curacion_comprometido,2) + round($total_monto['material_curacion'],2);
                //$presupuesto_unidad->material_curacion_disponible = round($presupuesto_unidad->material_curacion_disponible,2) - round($total_monto['material_curacion'],2);

                $presupuesto_unidad->insumos_comprometido = round($presupuesto_unidad->insumos_comprometido,2) + round($total_monto['causes'] + $total_monto['material_curacion'],2);
                $presupuesto_unidad->insumos_disponible = round($presupuesto_unidad->insumos_disponible,2) - round($total_monto['causes'] + $total_monto['material_curacion'],2);

                $presupuesto_unidad->no_causes_comprometido = round($presupuesto_unidad->no_causes_comprometido,2) + round($total_monto['no_causes'],2);
                $presupuesto_unidad->no_causes_disponible = round($presupuesto_unidad->no_causes_disponible,2) - round($total_monto['no_causes'],2);
                
                if($presupuesto_unidad->causes_disponible < 0 || $presupuesto_unidad->no_causes_disponible < 0 || $presupuesto_unidad->material_curacion_disponible < 0){
                    DB::rollBack();
                    return Response::json(['error' => 'El presupuesto es insuficiente para este pedido, los cambios no se guardaron.', 'data'=>[$presupuesto_unidad,$total_monto]], 500);
                }else{
                    $presupuesto_unidad->save();
                }

                if($pedido->tipo_pedido_id == 'PFS'){
                    //crear movimiento de entrada y generar stock
                    $recepcion = new MovimientoPedido;

                    $recepcion->recibe = 'FARMACIA SUBROGADA';
                    $recepcion->entrega = 'ALMACEN PRINCIPAL';
                    $recepcion->pedido_id = $pedido->id;

                    $datos_movimiento = [
                        'status' => 'FI',
                        'tipo_movimiento_id' => 8, //Recepcion de pedido
                        'fecha_movimiento' => $pedido->fecha,
                        'almacen_id' => $almacen_solicitante->id,
                        'observaciones' => 'Entrada en base al pedido '.$pedido->folio.' para la Farmacia Subrogada'
                    ];

                    $movimiento = Movimiento::create($datos_movimiento);
				    $recepcion->movimiento_id = $movimiento->id;

                    $recepcion->save();

                    $pedido->load("insumos");

                    //DB::rollBack();
                    //return Response::json(['error'=>'Error calculado', 'data'=>$pedido->insumos],500);

                    //Cargamos los stocks ['clave'=>'stock_id']
                    $stocks = Stock::where('almacen_id',$almacen_solicitante->id)->where('lote','like',$fecha[1].'-'.$fecha[0].'-F-SBRG')->lists('id','clave_insumo_medico');

                    foreach($pedido->insumos as $insumo){
                        if(!isset($stocks[$insumo->insumo_medico_clave])){
                            $nuevo_stock = [
                                'almacen_id'        	=> $almacen_solicitante->id,
                                'clave_insumo_medico'   => $insumo->insumo_medico_clave,
                                'lote'     				=> $fecha[1].'-'.$fecha[0].'-F-SBRG',
                                'existencia'     		=> $insumo->cantidad_solicitada
                            ];
                            $stock = Stock::create($nuevo_stock);
                            $stock_id = $stock->id;
                        }else{
                            $stock_id = $stocks[$insumo->insumo_medico_clave];
                            Stock::where('id',$stock_id)->update(['existencia'=>DB::raw('existencia + '.$insumo->cantidad_solicitada)]);
                        }

                        $nuevo_movimiento_insumo = [
                            'movimiento_id'		=> $movimiento->id,
                            'cantidad'        	=> $insumo->cantidad_solicitada,
                            'precio_unitario'   => $insumo->precio_unitario,
                            'precio_total'     	=> $insumo->monto_solicitado,
                            'stock_id'          => $stock_id
                        ];
                        $movimiento_insumo = MovimientoInsumos::create($nuevo_movimiento_insumo);
                    }
                }
            }

            $almacen_solicitante->load('unidadMedica');

            $pedido->director_id = $almacen_solicitante->unidadMedica->director_id;
            $pedido->encargado_almacen_id = $almacen_solicitante->encargado_almacen_id;

            $pedido->total_claves_solicitadas = $total_claves;
            $pedido->total_cantidad_solicitada = $total_insumos;
            $pedido->total_monto_solicitado = $total_monto['causes'] + $total_monto['no_causes'] + $total_monto['material_curacion'];
            $pedido->save();
             
             DB::commit(); 

            return Response::json([ 'data' => $pedido ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    function destroy(Request $request, $id){
        try {
            //$object = Pedido::destroy($id);
            $almacen = Almacen::find($request->get('almacen_id'));
            $pedido = Pedido::where('clues',$almacen->clues)->where('id',$id)->first();
            if($pedido){
                if($pedido->status == 'BR'){
                    $pedido->insumos()->delete();
                    $pedido->delete();
                }else{
                    return Response::json(['error' => 'Este pedido ya no puede eliminarse'], 500);
                }
            }else{
                return Response::json(['error' => 'No tiene permiso para eliminar este recurso'], 401);
            }
            //$object = Pedido::where('almacen_proveedor',$request->get('almacen_id'))->where('id',$id)->delete();
            return Response::json(['data'=>$pedido],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }

    }

    public function generarExcel($id) {
        $obj =  JWTAuth::parseToken()->getPayload();
        $usuario = Usuario::with('almacenes')->find($obj->get('id'));

        $meses = ['01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'];
        $data = [];

        //$almacen = Almacen::find($request->get('almacen_id'));
        //$pedido = Pedido::where('almacen_proveedor',$almacen->id)->where('clues',$almacen->clues)->where('id',$id)->first();
        $pedido = Pedido::getModel();
        if(!$usuario->su){
            if($usuario->proveedor_id){
                $pedido = $pedido->where('proveedor_id',$usuario->proveedor_id);
            }else{
                $usuario->load('roles.permisos');
                $permisos = [];
                foreach($usuario->roles as $rol){
                    $rol_permisos = $rol->permisos->lists('id','id')->toArray();
                    $permisos = array_merge($permisos,$rol_permisos);
                }
                //$permisos = $usuario->roles->permisos->lists('id','id');
                if(!isset($permisos['bsIbPL3qv6XevcAyrRm1GxJufDbzLOax'])){
                    $unidades = $usuario->almacenes->lists('clues');
                    $pedido = $pedido->whereIn('clues',$unidades);
                }
            }
        }

        $pedido = $pedido->find($id);
        
        if(!$pedido){
            return Response::json(['error' => "No se encuentra el pedido que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        $pedido->load("insumos.tipoInsumo","insumos.insumosConDescripcion.informacion","insumos.listaClues","insumos.insumosConDescripcion.generico.grupos", "tipoPedido", "almacenProveedor","almacenSolicitante.unidadMedica","proveedor","encargadoAlmacen","director");

        $fecha = explode('-',$pedido->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $pedido->fecha = $fecha;

        if($pedido->fecha_concluido){
            $fecha_concluido = substr($pedido->fecha_concluido,0,10);
            $fecha_concluido = explode('-',$fecha_concluido);
            $fecha_concluido[1] = $meses[$fecha_concluido[1]];
            $pedido->fecha_concluido = $fecha_concluido[2]." DE ".$fecha_concluido[1]." DEL ".$fecha_concluido[0];
        }else{
            $pedido->fecha_concluido = 'PEDIDO EN BORRADOR';
        }
        

        $nombre_archivo = 'Pedido '.$pedido->clues;
        if($pedido->folio){
            $nombre_archivo = ' - ' . $pedido->folio;  
        }else{
            $nombre_archivo .= ' - ' . $pedido->id;
        }
       
        Excel::create($nombre_archivo, function($excel) use($pedido) {
            $insumos_tipo = [];

            foreach($pedido->insumos as $insumo){
                    $tipo = '---';

                    $tipo = $insumo->tipoInsumo->nombre;

                    /*if($insumo->insumosConDescripcion->tipo == 'ME' && $insumo->insumosConDescripcion->es_causes){
                        $tipo = 'CAUSES';
                    }else if($insumo->insumosConDescripcion->tipo == 'ME' && !$insumo->insumosConDescripcion->es_causes){
                        $tipo = 'NO CAUSES';
                    }else if($insumo->insumosConDescripcion->tipo == 'MC'){
                        $tipo = 'MATERIAL DE CURACIÓN';
                    }*/

                    if(!isset($insumos_tipo[$tipo])){
                        $insumos_tipo[$tipo] = [];
                    }
                    $insumos_tipo[$tipo][] = $insumo;
                   
            }

            foreach($insumos_tipo as $tipo => $lista_insumos){
                $excel->sheet($tipo, function($sheet) use($pedido,$lista_insumos,$tipo) {
                    $sheet->setAutoSize(true);

                    $clave_folio = '-'.$lista_insumos[0]->tipoInsumo->clave;
                    
                    $sheet->mergeCells('A1:K1');
                    $sheet->row(1, array('FOLIO: '.$pedido->folio.$clave_folio));

                    $sheet->mergeCells('A2:K2');
                    $sheet->row(2, array('ENTREGAR A: '.$pedido->almacenSolicitante->nombre));

                    $sheet->mergeCells('A3:K3');
                    $sheet->row(3, array('NOMBRE DEL PEDIDO: '.$pedido->descripcion));

                    $sheet->mergeCells('A4:K4'); 
                    $sheet->row(4, array('UNIDAD MEDICA: '.$pedido->almacenSolicitante->unidadMedica->nombre));

                    $sheet->mergeCells('A5:K5'); 
                    $sheet->row(5, array('PROVEEDOR: '.$pedido->proveedor->nombre));

                    $sheet->mergeCells('A6:C6');
                    $sheet->mergeCells('D6:K6');
                    $sheet->row(6, array('FECHA DEL PEDIDO: '.$pedido->fecha[2]." DE ".$pedido->fecha[1]." DEL ".$pedido->fecha[0],'','','FECHA DE NOTIFICACIÓN: '.$pedido->fecha_concluido));

                    $sheet->cells("D6:K6", function($cells) {
                        $cells->setAlignment('right');
                    });

                    $sheet->mergeCells('A7:K7'); 
                    $sheet->row(7, array($tipo));

                    $sheet->cells("A7:K7", function($cells) {
                        $cells->setAlignment('center');
                    });

                    $sheet->mergeCells('D8:F8');
                    $sheet->mergeCells('G8:I8');

                    $sheet->mergeCells('A8:A9');
                    $sheet->mergeCells('B8:B9');
                    $sheet->mergeCells('C8:C9');
                    $sheet->mergeCells('J8:J9'); 
                    $sheet->mergeCells('K8:K9');

                    $sheet->row(8, array(
                        'NO.', 'CLAVE','DESCRIPCIÓN DE LOS INSUMOS','SOLICITADO','','','RECIBIDO','','','% UNIDADES','% MONTO'
                    ));

                    $sheet->cells("A8:K8", function($cells) {
                        $cells->setAlignment('center');
                    });

                    $sheet->row(9, array(
                        '','','','CANTIDAD','PRECIO UNITARIO','PRECIO TOTAL','CANTIDAD','PRECIO UNITARIO','PRECIO TOTAL','',''
                    ));

                    $sheet->cells("A9:K9", function($cells) {
                        $cells->setAlignment('center');
                    });

                    $sheet->row(1, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(16);
                    });
                    $sheet->row(2, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(3, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(4, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(5, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(6, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(7, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(8, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(11);
                    });
                    $sheet->row(9, function($row) {
                        // call cell manipulation methods
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(11);
                    });

                    $iva_solicitado = 0;
                    $iva_recibido = 0;

                    $contador_filas = 9;
                    $contador_insumos = 0;
                    foreach($lista_insumos as $insumo){
                        $contador_filas++;
                        $contador_insumos++;
                        $sheet->appendRow(array(
                            $contador_insumos, 
                            $insumo->insumo_medico_clave,
                            $insumo->insumosConDescripcion->descripcion,
                            $insumo->cantidad_solicitada,
                            $insumo->precio_unitario,
                            $insumo->monto_solicitado,
                            $insumo->cantidad_recibida | 0,
                            $insumo->precio_unitario,
                            ($insumo->monto_recibido)?$insumo->monto_recibido:0,
                            '=G'.$contador_filas.'/D'.$contador_filas,
                            '=I'.$contador_filas.'/F'.$contador_filas
                        ));

                        if($insumo->insumosConDescripcion->tipo == 'MC'){
                            $iva_solicitado += $insumo->monto_solicitado;
                            $iva_recibido += $insumo->monto_recibido;
                        }
                    }

                    $iva_solicitado = $iva_solicitado*16/100;
                    $iva_recibido = $iva_recibido*16/100;
                    
                    $sheet->setBorder("A1:K$contador_filas", 'thin');

                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            'SUBTOTAL',
                            '=SUM(F10:F'.($contador_filas).')',
                            '',
                            '',
                            '=SUM(I10:I'.($contador_filas).')',
                        ));
                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            'IVA',
                            $iva_solicitado,
                            '',
                            '',
                            $iva_recibido,
                        ));
                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            'TOTAL',
                            '=SUM(F'.($contador_filas+1).':F'.($contador_filas+2).')',
                            '',
                            '',
                            '=SUM(I'.($contador_filas+1).':I'.($contador_filas+2).')',
                        ));
                    $contador_filas += 3;


                    $phpColor = new \PHPExcel_Style_Color();
                    $phpColor->setRGB('DDDDDD'); 
                    $sheet->getStyle("J10:K$contador_filas")->getFont()->setColor( $phpColor );

                    $sheet->setColumnFormat(array(
                        "D10:D$contador_filas" => '#,##0',
                        "G10:G$contador_filas" => '#,##0',
                        "E10:F$contador_filas" => '"$" #,##0.00_-',
                        "H10:I$contador_filas" => '"$" #,##0.00_-',
                        "J10:K$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                    ));
                });

                // Desglose
                $excel->sheet($tipo. " DESGLOSE", function($sheet) use($pedido,$lista_insumos,$tipo) {
                    $sheet->setAutoSize(true);

                    $clave_folio = '-'.$lista_insumos[0]->tipoInsumo->clave;
                    
                    $sheet->mergeCells('A1:K1');
                    $sheet->row(1, array('FOLIO: '.$pedido->folio.$clave_folio));

                    $sheet->mergeCells('A2:K2');
                    $sheet->row(2, array('ENTREGAR A: '.$pedido->almacenSolicitante->nombre));

                    $sheet->mergeCells('A3:K3');
                    $sheet->row(3, array('NOMBRE DEL PEDIDO: '.$pedido->descripcion));

                    $sheet->mergeCells('A4:K4'); 
                    $sheet->row(4, array('UNIDAD MEDICA: '.$pedido->almacenSolicitante->unidadMedica->nombre));

                    $sheet->mergeCells('A5:K5'); 
                    $sheet->row(5, array('PROVEEDOR: '.$pedido->proveedor->nombre));

                    $sheet->mergeCells('A6:C6');
                    $sheet->mergeCells('D6:K6');
                    $sheet->row(6, array('FECHA DEL PEDIDO: '.$pedido->fecha[2]." DE ".$pedido->fecha[1]." DEL ".$pedido->fecha[0],'','','FECHA DE NOTIFICACIÓN: '.$pedido->fecha_concluido));

                    $sheet->cells("D6:K6", function($cells) {
                        $cells->setAlignment('right');
                    });

                    $sheet->mergeCells('A7:K7'); 
                    $sheet->row(7, array($tipo));

                    $sheet->cells("A7:K7", function($cells) {
                        $cells->setAlignment('center');
                    });

                    $sheet->mergeCells('D8:F8');
                    $sheet->mergeCells('G8:I8');

                    $sheet->mergeCells('A8:A9');
                    $sheet->mergeCells('B8:B9');
                    $sheet->mergeCells('C8:C9');
                    $sheet->mergeCells('J8:J9'); 
                    $sheet->mergeCells('K8:K9');

                    $sheet->row(8, array(
                        'NO.', 'CLAVE','DESCRIPCIÓN DE LOS INSUMOS','SOLICITADO','','','RECIBIDO','','','% UNIDADES','% MONTO'
                    ));

                    $sheet->cells("A8:K8", function($cells) {
                        $cells->setAlignment('center');
                    });

                    $sheet->row(9, array(
                        '','','','CANTIDAD','PRECIO UNITARIO','PRECIO TOTAL','CANTIDAD','PRECIO UNITARIO','PRECIO TOTAL','',''
                    ));

                    $sheet->cells("A9:K9", function($cells) {
                        $cells->setAlignment('center');
                    });

                    $sheet->row(1, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(16);
                    });
                    $sheet->row(2, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(3, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(4, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(5, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(6, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(7, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    $sheet->row(8, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(11);
                    });
                    $sheet->row(9, function($row) {
                        // call cell manipulation methods
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(11);
                    });

                    $iva_solicitado = 0;
                    $iva_recibido = 0;

                    $contador_filas = 9;
                    $contador_insumos = 0;
                    foreach($lista_insumos as $insumo){
                        $contador_filas++;
                        $contador_insumos++;
                        $sheet->appendRow(array(
                            $contador_insumos, 
                            $insumo->insumo_medico_clave,
                            $insumo->insumosConDescripcion->descripcion,
                            $insumo->cantidad_solicitada,
                            $insumo->precio_unitario,
                            $insumo->monto_solicitado,
                            $insumo->cantidad_recibida | 0,
                            $insumo->precio_unitario,
                            ($insumo->monto_recibido)?$insumo->monto_recibido:0,
                            '=G'.$contador_filas.'/D'.$contador_filas,
                            '=I'.$contador_filas.'/F'.$contador_filas
                        ));

                        if($insumo->insumosConDescripcion->tipo == 'MC'){
                            $iva_solicitado += $insumo->monto_solicitado;
                            $iva_recibido += $insumo->monto_recibido;
                        }

                        $sheet->row($contador_filas, function($row) {
                            $row->setBackground('#FFDD00');
                        });

                        $contador_filas++;
                        $sheet->appendRow(array(
                                "", 
                               "CLUES (".count($insumo->listaClues).")",
                                "NOMBRE DE LA UNIDAD",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                "",
                                ""
                        ));
                        
                        $sheet->mergeCells('D'.$contador_filas.':K'.$contador_filas);

                        $sheet->row($contador_filas, function($row) {
                            //$row->setBackground('#FFDD00');
                            $row->setFontWeight('bold');
                            //$row->setFontSize(14);
                        });
                        
                        foreach($insumo->listaClues as $item_clues){
                            $contador_filas++;
                            $sheet->appendRow(array(
                                "", 
                                $item_clues->clues,
                                $item_clues->nombre,
                                $item_clues->cantidad,
                                "",
                                $item_clues->cantidad * $insumo->precio_unitario,
                                "",
                                "",
                                "",
                                "",
                                ""
                            ));
                            $sheet->mergeCells('G'.$contador_filas.':K'.$contador_filas);
                        }

                    }

                    $iva_solicitado = $iva_solicitado*16/100;
                    $iva_recibido = $iva_recibido*16/100;
                    
                    $sheet->setBorder("A1:K$contador_filas", 'thin');

                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            'SUBTOTAL',
                            '=SUM(F10:F'.($contador_filas).')',
                            '',
                            '',
                            '=SUM(I10:I'.($contador_filas).')',
                        ));
                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            'IVA',
                            $iva_solicitado,
                            '',
                            '',
                            $iva_recibido,
                        ));
                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            'TOTAL',
                            '=SUM(F'.($contador_filas+1).':F'.($contador_filas+2).')',
                            '',
                            '',
                            '=SUM(I'.($contador_filas+1).':I'.($contador_filas+2).')',
                        ));
                    $contador_filas += 3;


                    $phpColor = new \PHPExcel_Style_Color();
                    $phpColor->setRGB('DDDDDD'); 
                    $sheet->getStyle("J10:K$contador_filas")->getFont()->setColor( $phpColor );

                    $sheet->setColumnFormat(array(
                        "D10:D$contador_filas" => '#,##0',
                        "G10:G$contador_filas" => '#,##0',
                        "E10:F$contador_filas" => '"$" #,##0.00_-',
                        "H10:I$contador_filas" => '"$" #,##0.00_-',
                        "J10:K$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                    ));
                });
            }

            
        })->setActiveSheetIndex(0)->export('xls');
    }
}
