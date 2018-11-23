<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Controllers\Controller;
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
use App\Models\PedidoAlterno;
use \Excel;
use Carbon\Carbon;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;


class PedidosAlternosController extends Controller{

    public function lista(Request $request){
        
        $parametros = Input::only('status','sin_proveedor','q','page','per_page');

        
        $pedidos = Pedido::getModel();            
        $pedidos = $pedidos->leftjoin('unidades_medicas','pedidos.clues','=','unidades_medicas.clues');
        
        $pedidos = $pedidos->where('tipo_pedido_id','PALT');
        
     
		if ($parametros['q']) {
			$pedidos =  $pedidos->where(function($query) use ($parametros) {
                $query->where('id','LIKE',"%".$parametros['q']."%")
                        ->orWhere('descripcion','LIKE',"%".$parametros['q']."%")
                        ->orWhere('folio','LIKE',"%".$parametros['q']."%")
                        ->orWhere('unidades_medicas.nombre','LIKE',"%".$parametros['q']."%");
			});
		}


        

		if(isset($parametros['status'])) {
			$pedidos = $pedidos->where("pedidos.status",$parametros['status']);
        }
        if(isset($parametros['sin_proveedor'])) {
            if($parametros['sin_proveedor'] == 'true'){
                $pedidos = $pedidos->whereNull("pedidos.proveedor_id");
            } else {
                $pedidos = $pedidos->whereNotNull("pedidos.proveedor_id");
            }
        }

		$pedidos = $pedidos->select('pedidos.*','unidades_medicas.nombre as unidad_medica',DB::raw('datediff(fecha_expiracion,current_date()) as expira_en_dias'))->orderBy('updated_at','desc');        
		
		// Akira: dejo comentada esta instrucción por que las juris solo deberían ver este tipo de pedidos
		// Peeeeeero como ya hay pedidos capturados en el sistema pues no se puede manejar así
		//$pedidos = $pedidos->where("pedidos.tipo_pedido_id",'PJS');

		//$pedido = Pedido::with("insumos", "acta", "TipoInsumo", "TipoPedido")->get();
		if(isset($parametros['page'])){
			$resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
			$pedidos = $pedidos->paginate($resultadosPorPagina);
		} else {
			$pedidos = $pedidos->get();
		}

		return Response::json([ 'data' => $pedidos],200);
    }

    public function ver(Request $request, $id){
       
    	//$pedido = Pedido::where('almacen_solicitante',$request->get('almacen_id'))->find($id);
        $pedido = Pedido::where('tipo_pedido_id','PALT')->find($id);
        
        if(!$pedido){
            return Response::json(['error' => "No se encuentra el pedido que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }else{

            if($pedido->tipo_pedido_id != 'PJS'){
                
                if($pedido->status == 'BR'){
                    $pedido = $pedido->load("unidadMedica","insumos.tipoInsumo","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos","proveedor","presupuestoApartado");
                }else{
                    $pedido = $pedido->load("unidadMedica","insumos.tipoInsumo","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos","almacenSolicitante.unidadMedica","proveedor","encargadoAlmacen","director","recepciones.entrada.insumos");
                }
            } 
            // ######### PEDIDOS JURISDICCIONALES #########
            else {
                
                if($pedido->status == 'BR'){
                    $pedido = $pedido->load("unidadMedica","insumos.tipoInsumo","insumos.listaClues","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos","proveedor","presupuestoApartado");
                }else{
                    $pedido = $pedido->load("unidadMedica","insumos.tipoInsumo","insumos.listaClues","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos","almacenSolicitante.unidadMedica","proveedor","encargadoAlmacen","director","recepciones.entrada.insumos");
                }
            }
            // ############################################
        }
        return Response::json([ 'data' => $pedido],200);
    }

    public function validar(Request $request, $id){
        

        try {
            $pedido = Pedido::find($id);

            if($pedido->status != 'PV'){
                return Response::json(['error' => 'El pedido ya no puede validarse.'], 500);
            }

            $pedido->status = 'VAL';
            $pedido->save();

            return Response::json([ 'data' => $pedido ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function asignarProveedor(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $inputs = Input::all();
            $parametros = $inputs['data'];
            $pedido_alterno = PedidoAlterno::where("pedido_id", $id)->first();
            $pedido_alterno_save =PedidoAlterno::find($pedido_alterno->id);
            
            $pedido_alterno_save->firma_1_id = $parametros['asignacion_firmante_1'];
            $pedido_alterno_save->firma_2_id = $parametros['asignacion_firmante_2'];
            $pedido_alterno_save->fecha_asignacion_proveedor = date("Y-m-d H:i:s");
            $pedido_alterno_save->usuario_asigno_proveedor_id = $request->get('usuario_id');
            $pedido_alterno_save->save();

            $pedido = Pedido::find($id);
            $pedido->proveedor_id = $parametros['proveedor_id'];
            $pedido->status = 'PS';
            $pedido->recepcion_permitida = 1;
            $pedido->fecha_concluido = date("Y-m-d H:i:s");
            
            $fecha2 = date_create(date("Y-m-d H:i:s"));
            $fecha2->modify("+2 day");
            $pedido->fecha_expiracion = $fecha2->format('Y-m-d H:i:s');
            $pedido->save();

            DB::commit();
            return Response::json([ 'data' => $pedido ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
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
        $um = UnidadMedica::find( $almacen->clues);

        if($almacen->nivel_almacen == 1 && ($almacen->tipo_almacen == 'ALMPAL' || $almacen->tipo_almacen == 'FARSBR')){
            //$reglas['proveedor_id'] = 'required';
            $parametros['datos']['proveedor_id'] = $almacen->proveedor_id;
            $parametros['datos']['almacen_proveedor'] = null;
        }elseif($almacen->nivel_almacen == 2){
            $reglas['almacen_proveedor'] = 'required';
        }
        
        $almacen_solicitante = Almacen::find($parametros['datos']['almacen_solicitante']);

        $tipo_pedido = '';
        if($almacen_solicitante){
            if($um->tipo == 'OA' && $almacen_solicitante->subrogado == 0 && $almacen_solicitante->nivel_almacen == 1){  // ######### PEDIDOS JURISDICCIONALES #########
                $tipo_pedido = 'PJS'; // Pedidos jurisdiccionales, solo cuando el almacen solictante no sea subrogado, sea de nivel 1 y la clues sea Oficina Administrativa
            }else{ // ############################################
                if($almacen_solicitante->nivel_almacen == 1 && $almacen_solicitante->tipo_almacen == 'FARSBR' && $almacen_solicitante->subrogado == 1){
                    $tipo_pedido = 'PFS';
                }else{
                    $tipo_pedido = 'PA';
                }
            }
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

            //Harima:checamos si el pedido tiene presupuetso apartado, esto para ver si cambio almacen o mes del pedido, por lo pronto mandamos error al intentar hacer este cambio.
            $pedido->load('presupuestoApartado');
            if($pedido->presupuestoApartado){
                $fecha = explode('-',$parametros['datos']['fecha']);
                if($parametros['datos']['almacen_solicitante'] != $pedido->presupuestoApartado->almacen_id || $fecha[1] != $pedido->presupuestoApartado->mes || $fecha[0] != $pedido->presupuestoApartado->anio){
                    return Response::json(['error' => 'El cambio de mes y almacen para este pedido no se encuentra autorizado'], 500);
                }
            }

            if($almacen_solicitante->nivel_almacen == 1 && ($parametros['datos']['status'] == 'PS' || $parametros['datos']['status'] == 'EF')){ //$almacen_solicitante->tipo_almacen == 'ALMPAL' && 
                //$fecha = date($parametros['datos']['fecha']);
                if(!$pedido->fecha_concluido){
                    $fecha_concluido = Carbon::now();
                    $fecha_expiracion = strtotime("+20 days", strtotime($fecha_concluido));
                    $parametros['datos']['fecha_concluido'] = $fecha_concluido;
                    $parametros['datos']['fecha_expiracion'] = date("Y-m-d", $fecha_expiracion);
                }
            }/*else{
                $parametros['datos']['fecha_concluido'] = null;
                $parametros['datos']['fecha_expiracion'] = null;
            }*/

            DB::beginTransaction();

            $pedido->update($parametros['datos']);

            $arreglo_insumos = Array();
            
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

                    // ######### PEDIDOS JURISDICCIONALES #########
                    if($um->tipo == 'OA' && $tipo_pedido == 'PJS'){
                        /*foreach($insumo_form['lista_clues'] as $key_clues => $value_clues){
                            $insumo_clues = [
                                'pedido_insumo_id' => $object_insumo->id,
                                'clues' => $value_clues['clues'],
                                'cantidad' => $value_clues['cantidad']
                            ];
                            PedidoInsumoClues::create($insumo_clues);
                        }*/
                    }
                    // ############################################
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

            $almacen_solicitante->load('unidadMedica');

            $pedido->director_id = $almacen_solicitante->unidadMedica->director_id;
            $pedido->encargado_almacen_id = $almacen_solicitante->encargado_almacen_id;

            $pedido->total_claves_solicitadas = $total_claves;
            $pedido->total_cantidad_solicitada = $total_insumos;
            $pedido->total_monto_solicitado = round($total_monto['causes'],2) + round($total_monto['no_causes'],2) + round($total_monto['material_curacion'],2);
            $pedido->save();

            //Harima: Ajustamos el presupuesto, colocamos los totales en comprometido
            //if($pedido->status == 'PS' || $pedido->status == 'ET'){ //OJO falta checar si cambian almacen y mes
            if($pedido->status != 'BR'){

                if($pedido->total_monto_solicitado == $pedido->total_monto_recibido){
                    $pedido->status = 'FI';
                    $pedido->save();
                }

                //Harima: Cargamos presupuesto apartado, en caso de que el pedido se este corrigiendo, y ya tenga recepciones
                //$pedido->load('presupuestoApartado');
                if($pedido->presupuestoApartado){
                    $presupuesto_apartado = $pedido->presupuestoApartado;
                    $total_monto['causes'] -= ($presupuesto_apartado->causes_comprometido + $presupuesto_apartado->causes_devengado);
                    $total_monto['no_causes'] -= ($presupuesto_apartado->no_causes_comprometido + $presupuesto_apartado->no_causes_devengado);
                    $total_monto['material_curacion'] -= ($presupuesto_apartado->material_curacion_comprometido + $presupuesto_apartado->material_curacion_devengado);
                    $pedido->presupuestoApartado->delete();
                }

                $fecha = explode('-',$pedido->fecha);
                $presupuesto = Presupuesto::where('activo',1)->first();
                $presupuesto_unidad = UnidadMedicaPresupuesto::where('presupuesto_id',$presupuesto->id)
                                            ->where('clues',$almacen->clues)
                                            //->where('proveedor_id',$almacen->proveedor_id)
                                            ->where('almacen_id',$almacen_solicitante->id)
                                            ->where('mes',$fecha[1])
                                            ->where('anio',$fecha[0])
                                            ->first();
                if(!$presupuesto_unidad){
                    DB::rollBack();
                    return Response::json(['error' => 'No existe presupuesto asignado al mes y/o año del pedido'], 500);
                }
                
                $presupuesto_unidad->causes_comprometido = $presupuesto_unidad->causes_comprometido + round($total_monto['causes'],2);
                //$presupuesto_unidad->causes_disponible = $presupuesto_unidad->causes_disponible - round($total_monto['causes'],2);

                $presupuesto_unidad->material_curacion_comprometido = $presupuesto_unidad->material_curacion_comprometido + round($total_monto['material_curacion'],2);
                //$presupuesto_unidad->material_curacion_disponible = $presupuesto_unidad->material_curacion_disponible - round($total_monto['material_curacion'],2);

                $presupuesto_unidad->no_causes_comprometido = $presupuesto_unidad->no_causes_comprometido + round($total_monto['no_causes'],2);
                $presupuesto_unidad->no_causes_disponible = $presupuesto_unidad->no_causes_disponible - round($total_monto['no_causes'],2);
                
                //Agregando insumos(causes+material_curacion)
                $presupuesto_unidad->insumos_comprometido = $presupuesto_unidad->insumos_comprometido + round($total_monto['causes']+$total_monto['material_curacion'],2);
                $presupuesto_unidad->insumos_disponible = $presupuesto_unidad->insumos_disponible - round($total_monto['causes']+$total_monto['material_curacion'],2);
                
                //if($presupuesto_unidad->causes_disponible < 0 || $presupuesto_unidad->no_causes_disponible < 0 || $presupuesto_unidad->material_curacion_disponible < 0){
                //if(($presupuesto_unidad->causes_disponible + $presupuesto_unidad->material_curacion_disponible) < 0 || $presupuesto_unidad->no_causes_disponible < 0){
                if($presupuesto_unidad->insumos_disponibles < 0 || $presupuesto_unidad->no_causes_disponible < 0){
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
             
             DB::commit(); 

            return Response::json([ 'data' => $pedido ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        } 
    }
}
