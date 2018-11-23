<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;

use App\Models\Usuario, App\Models\Proveedor, App\Models\Presupuesto, App\Models\UnidadMedicaPresupuesto, App\Models\Pedido, App\Models\Insumo, App\Models\Almacen, App\Models\Repositorio, App\Models\LogPedidoBorrador, App\Models\PedidoPresupuestoApartado, App\Models\LogPedidoCancelado, App\Models\AjustePresupuestoPedidoRegresion, App\Models\Servidor, App\Models\AjustePedidoPresupuestoApartado;
use Carbon\Carbon;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class PedidosController extends Controller
{
    public function presupuesto(Request $request){
        try{
          //  $almacen = Almacen::find($request->get('almacen_id'));

            $parametros = Input::all();

            $presupuesto = Presupuesto::where('activo',1)->first();

            $presupuesto_unidad_medica = UnidadMedicaPresupuesto::select(
                                            DB::raw('sum(insumos_autorizado) as insumos_autorizado'),DB::raw('sum(insumos_modificado) as insumos_modificado'),DB::raw('sum(insumos_comprometido) as insumos_comprometido'),DB::raw('sum(insumos_devengado) as insumos_devengado'),DB::raw('sum(insumos_disponible) as insumos_disponible'),
                                            DB::raw('sum(causes_autorizado) as causes_autorizado'),DB::raw('sum(causes_modificado) as causes_modificado'),DB::raw('sum(causes_comprometido) as causes_comprometido'),DB::raw('sum(causes_devengado) as causes_devengado'),DB::raw('sum(causes_disponible) as causes_disponible'),
                                            DB::raw('sum(no_causes_autorizado) as no_causes_autorizado'),DB::raw('sum(no_causes_modificado) as no_causes_modificado'),DB::raw('sum(no_causes_comprometido) as no_causes_comprometido'),DB::raw('sum(no_causes_devengado) as no_causes_devengado'),DB::raw('sum(no_causes_disponible) as no_causes_disponible'),
                                            DB::raw('sum(material_curacion_autorizado) as material_curacion_autorizado'),DB::raw('sum(material_curacion_modificado) as material_curacion_modificado'),DB::raw('sum(material_curacion_comprometido) as material_curacion_comprometido'),DB::raw('sum(material_curacion_devengado) as material_curacion_devengado'),DB::raw('sum(material_curacion_disponible) as material_curacion_disponible'))
                                            ->where('presupuesto_id',$presupuesto->id);
                                            //->where('clues',$almacen->clues)
                                            //->where('proveedor_id',$almacen->proveedor_id)
                                            //->groupBy('clues');
            
            $items = Pedido::select('pedidos.*','unidades_medicas.jurisdiccion_id',DB::raw('month(fecha) as mes'))->leftjoin('unidades_medicas','unidades_medicas.clues','=','pedidos.clues');

            if (isset($parametros['q']) &&  $parametros['q'] != "") {
                $items = $items->where(function($query) use ($parametros){
                    $query
                        ->where('unidades_medicas.nombre','LIKE',"%".$parametros['q']."%")
                        ->orWhere('pedidos.clues','LIKE',"%".$parametros['q']."%")
                        ->orWhere('pedidos.folio','LIKE',"%".$parametros['q']."%")
                        ->orWhere('pedidos.descripcion','LIKE',"%".$parametros['q']."%");
                });
            } 

           if(isset($parametros['status']) && $parametros['status'] != ""){
                $status = explode(',',$parametros['status']);            
                if(count($status)>0){
                    $items = $items->whereIn('status',$status);
                }              
            }

            if(isset($parametros['proveedores']) && $parametros['proveedores'] != ""){
                $proveedores = explode(',',$parametros['proveedores']);            
                if(count($proveedores)>0){
                    $items = $items->whereIn('proveedor_id',$proveedores);
                }              
            }

            if(isset($parametros['jurisdicciones']) && $parametros['jurisdicciones'] != ""){
                $jurisdicciones = explode(',',$parametros['jurisdicciones']);            
                if(count($jurisdicciones)>0){
                    $items = $items->whereIn('jurisdiccion_id',$jurisdicciones);
                }              
            }

            if(isset($parametros['meses']) && $parametros['meses'] != ""){
                $mes_filtro = explode(',',$parametros['meses']);  
                     
                if(count($mes_filtro)>0){
                    $fecha_mes = Carbon::createFromDate(null, $mes_filtro[0],01);
                    $fecha_mes->timezone('America/Mexico_City');
                    
                    $dia_fin_mes = $fecha_mes->daysInMonth;
                    $fecha_inicio = $fecha_mes->year."-".$mes_filtro[0]."-01";
                    $fecha_fin = $fecha_mes->year."-".$mes_filtro[0]."-".$dia_fin_mes;  

                    $items = $items->whereBetween('fecha', array($fecha_inicio, $fecha_fin));
                }              
            }

            if(isset($parametros['statusRecepcion']) && $parametros['statusRecepcion'] != ""){
                $statusRecepcion_filtro = explode(',',$parametros['statusRecepcion']);  
                     
                if(count($statusRecepcion_filtro)>0){
                    if($statusRecepcion_filtro[0] == 1)
                    {
                        $fecha_actual = Carbon::now();
                        $fecha_actual->timezone('America/Mexico_City');
                        
                        $fecha_limite = $fecha_actual->year."-".$fecha_actual->month."-".$fecha_actual->day;
                        
                        $items = $items->where('fecha_expiracion', '<', $fecha_limite);
                        $items = $items->where('total_monto_solicitado', '>', 'total_monto_recibido');
                    }else if($statusRecepcion_filtro[0] == 2)
                    {
                        $items = $items->where('total_monto_solicitado','=', 'total_monto_recibido');
                    }
                }              
            }

            $items = $items->get();

            $meses = $items->lists('mes');
            $clues = $items->lists('clues');

            $presupuesto_unidad_medica = $presupuesto_unidad_medica->whereIn('mes',$meses);
            $presupuesto_unidad_medica = $presupuesto_unidad_medica->whereIn('clues',$clues);
            /*if(isset($parametros['mes'])){
                if($parametros['mes']){
                    $presupuesto_unidad_medica = $presupuesto_unidad_medica->where('mes',$parametros['mes']);
                }
            }

            if(isset($parametros['proveedores'])){
                if($parametros['proveedores']){
                    $proveedores_ids = explode(',',$parametros['proveedores']);
                    $presupuesto_unidad_medica = $presupuesto_unidad_medica->whereIn('proveedor_id',$proveedores_ids);
                }
            }*/

            $presupuesto_unidad_medica = $presupuesto_unidad_medica->first();
            return Response::json([ 'data' => $presupuesto_unidad_medica, 'presupuesto'=>$presupuesto],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lista()
    {
        $parametros = Input::only('q','status','proveedores','jurisdicciones','page','per_page', 'ordenar_causes','ordenar_no_causes','ordenar_material_curacion', 'meses', 'statusRecepcion');

        $items = self::getItemsQuery($parametros);
        
        
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }
       
        return Response::json([ 'data' => $items],200);
    }

    public function listaArchivosProveedor($id, Request $request){
        $repositorio = Repositorio::where("pedido_id", $id)
                    ->select("id",
                            "peso",
                            "nombre_archivo",
                            "created_at",
                            "usuario_id",
                            "usuario_deleted_id",
                            "deleted_at",
                            DB::RAW("(select count(*) from log_repositorio where repositorio_id=repositorio.id and accion='DOWNLOAD') as descargas"))
                    ->withTrashed()
                    ->get();
    	return Response::json([ 'data' => $repositorio],200);	
    }


    public function permitirRecepcion($id, Request $request){
        try {
            $pedido = Pedido::find($id);

            $permitir = Input::all();

            if(isset($permitir['recepcion'])){
                if($permitir['recepcion']){
                    $pedido->recepcion_permitida = 1;
                }else{
                    $pedido->recepcion_permitida = 0;
                }
                $pedido->save();
                return Response::json([ 'data' => $pedido ],200);
            }else{
                return Response::json(['error' => 'Error en los datos mandados'], HttpResponse::HTTP_CONFLICT);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
        return Response::json(['data'=>'']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function recepcion($id, Request $request){
        try{
            $pedido = Pedido::with("recepcionesBorrados.movimientoBorrados", "logPedidoCancelado")->where("id",$id)->first();

            if($pedido->status == "EX-CA")
            {
                //Calculo de causes, no causes y material de curacion
                $almacen = Almacen::find($pedido->almacen_solicitante);

                if(!$almacen){
                    return Response::json(['error' =>"No se encontró el almacen."], 500);
                }
                
                $proveedor = Proveedor::with('contratoActivo')->find($almacen->proveedor_id);

                $contrato_activo = $proveedor->contratoActivo;
                $insumos = Insumo::conDescripcionesPrecios($contrato_activo->id, $proveedor->id)->select("precio", "clave", "insumos_medicos.tipo", "es_causes", "insumos_medicos.tiene_fecha_caducidad", "contratos_precios.tipo_insumo_id", "medicamentos.cantidad_x_envase")->withTrashed()->get();
                $lista_insumos = array();
                foreach ($insumos as $key => $value) {
                    $array_datos = array();
                    $array_datos['precio']              = $value['precio'];
                    $array_datos['clave']               = $value['clave'];
                    $array_datos['tipo']                = $value['tipo'];
                    $array_datos['tipo_insumo_id']      = $value['tipo_insumo_id'];
                    $array_datos['es_causes']           = $value['es_causes'];
                    $array_datos['caducidad']           = $value['tiene_fecha_caducidad'];
                    $array_datos['cantidad_unidosis']   = $value['cantidad_x_envase'];
                    $lista_insumos[$value['clave']]     = $array_datos;
                }

                $pedido = $pedido->load("insumos");

                $total_causes               = 0;
                $total_no_causes            = 0;
                $total_material_curacion    = 0;

                foreach ($pedido->insumos as $key => $value) {
                    if($lista_insumos[$value['insumo_medico_clave']]['tipo'] == "ME")
                    {
                        if($lista_insumos[$value['insumo_medico_clave']]['es_causes']== 1)
                        {
                            $total_causes += ($value['monto_solicitado'] - $value['monto_recibido']);
                        }else
                        {
                            $total_no_causes += ($value['monto_solicitado'] - $value['monto_recibido']);
                        }
                    }else
                    {
                        $total_material_curacion += (($value['monto_solicitado'] - $value['monto_recibido']) * 1.16);
                    }
                }

                //Agregamos los datos
                $pedido->logPedidoCancelado->mes_texto = $this->conversion_mes_texto($pedido->logPedidoCancelado->mes_destino);
                $pedido->logPedidoCancelado->causes = $total_causes;
                $pedido->logPedidoCancelado->no_causes = $total_no_causes;
                $pedido->logPedidoCancelado->material_curacion = round($total_material_curacion,2);
            }


            foreach ($pedido->recepcionesBorrados as $key => $value) {
                
                $arreglo = array();     
                $arreglo = $value;
                
                $claves = DB::table("stock")
                                ->whereRaw("id in (select stock_id from movimiento_insumos where movimiento_id='".$value['movimiento_id']."')")
                                ->select(DB::RAW("count(distinct(clave_insumo_medico)) as cantidad_insumos"))
                                ->first();

                $insumos = DB::table("movimiento_insumos")
                                ->join("movimientos", "movimientos.id", "=", "movimiento_insumos.movimiento_id")
                                ->where("movimiento_id","=",$value['movimiento_id'])
                                ->where("movimientos.status","=","FI")
                                ->select(DB::RAW("sum(cantidad) as cantidad"),
                                        DB::RAW("sum(precio_total + iva) as monto"))
                                ->first();    

                $borrado = DB::table("log_recepcion_borrador")
                                ->where("movimiento_id","=",$value['movimiento_id'])
                                ->where("accion","=","RECEPCION ELIMINADA")
                                ->select("created_at",
                                        "usuario_id")
                                ->first();                                

                $arreglo['total_claves'] = $claves->cantidad_insumos;
                $arreglo['total_cantidad'] = $insumos->cantidad;
                $arreglo['total_monto'] = $insumos->monto;
                if($borrado)
                {
                    $pedido->recepcionesBorrados[$key]['borrado_al'] = $borrado->created_at;
                    $pedido->recepcionesBorrados[$key]['borrado_por'] = $borrado->usuario_id;
                }else{
                    $pedido->recepcionesBorrados[$key]['borrado_al'] = null;
                    $pedido->recepcionesBorrados[$key]['borrado_por'] = null;
                }
                
                $pedido->recepciones[$key]  = $arreglo;
            }     
            return Response::json([ 'data' => $pedido],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }
    
    public function regresarBorrador($id, Request $request){
        try{
            $usuario = Usuario::with(['roles.permisos'=>function($permisos){
                $permisos->where('id','pgDHA25rRlWvMxdb6aH38xG5p1HUFznS');
            }])->find($request->get('usuario_id'));
            
            $tiene_acceso = false;

            if(!$usuario->su){
                $permisos = [];
                foreach ($usuario->roles as $index => $rol) {
                    if(count($rol->permisos) > 0){
                        $tiene_acceso = true;
                        break;
                    }
                }
            }else{
                $tiene_acceso = true;
            }

            if(!$tiene_acceso){
                return Response::json(['error' =>"No tiene permiso para realizar esta acción."], 500);
            }

            DB::beginTransaction();
            
            $pedido = Pedido::with("recepciones.movimiento")->find($id);
            $bandera = 0;
            $validador_recepcion = 0;
            if (count($pedido->recepciones) >0) {
                $validador_recepcion++;
                foreach ($pedido->recepciones as $key => $value) {
                    if($value['movimiento']['status'] == "BR")
                            $bandera++;
                }
            }
            
            if($pedido->status == "BR")
            {
                BD::rollBack();
                return Response::json(['error' =>"El pedido ya se encuentra en borrador, por favor verificar"], 500);
            }
            
            if($servidor->principal){

            $pedido->status = "BR";
            $pedido->save();

            if($validador_recepcion > 0)
            {
                //Harima: Como regresamos el pedido a borrador aun teniendo recepciones, guardamos el presupuesto del pedido que tenemos actualmente en comprometido/devengado para poder hacer los ajustes despues de finalizar el proyecto
                $pedido->load('insumos.insumoDetalle');
                $causes_solicitado = 0;
                $causes_recibido = 0;
                $no_causes_solicitado = 0;
                $no_causes_recibido = 0;
                $material_curacion_solicitado = 0;
                $material_curacion_recibido = 0;

                foreach ($pedido->insumos as $insumo) {
                    if($insumo->insumoDetalle->tipo == "ME"){
                        if($insumo->insumoDetalle->es_causes == 1){
                            $causes_solicitado += $insumo->monto_solicitado;
                            $causes_recibido += ($insumo->monto_recibido+0);
                        }else{
                            $no_causes_solicitado += $insumo->monto_solicitado;
                            $no_causes_recibido += ($insumo->monto_recibido+0);
                        }
                    }else{
                        $material_curacion_solicitado += $insumo->monto_solicitado;
                        $material_curacion_recibido += ($insumo->monto_recibido+0);
                    }
                }

                if($material_curacion_solicitado > 0){
                    $material_curacion_solicitado += $material_curacion_solicitado*16/100;
                }

                if($material_curacion_recibido > 0){
                    $material_curacion_recibido += $material_curacion_recibido*16/100;
                }

                $fecha = explode("-", $pedido->fecha);

                PedidoPresupuestoApartado::create(array(
                    'clues' => $pedido->clues,
                    'pedido_id' => $pedido->id,
                    'almacen_id' => $pedido->almacen_solicitante,
                    'mes' => $fecha[1],
                    'anio' => $fecha[0],
                    'causes_comprometido' => ($causes_solicitado-$causes_recibido),
                    'causes_devengado' => $causes_recibido,
                    'no_causes_comprometido' => ($no_causes_solicitado-$no_causes_recibido),
                    'no_causes_devengado' => $no_causes_recibido,
                    'material_curacion_comprometido' => ($material_curacion_solicitado-$material_curacion_recibido),
                    'material_curacion_devengado' => $material_curacion_recibido
                ));

                AjustePedidoPresupuestoApartado::create(array(
                    'clues' => $pedido->clues,
                    'pedido_id' => $pedido->id,
                    'almacen_id' => $pedido->almacen_solicitante,
                    'mes' => $fecha[1],
                    'anio' => $fecha[0],
                    'causes_comprometido' => ($causes_solicitado-$causes_recibido),
                    'causes_devengado' => $causes_recibido,
                    'no_causes_comprometido' => ($no_causes_solicitado-$no_causes_recibido),
                    'no_causes_devengado' => $no_causes_recibido,
                    'material_curacion_comprometido' => ($material_curacion_solicitado-$material_curacion_recibido),
                    'material_curacion_devengado' => $material_curacion_recibido,
                    'status'=> 'AR'
                ));

                $arreglo_log = array("pedido_id"=>$id,
                                     'ip' =>$request->ip(),
                                     'navegador' =>$request->header('User-Agent'),
                                     "accion"=>"REGRESO BORRADOR CON RECEPCIONES");
                LogPedidoBorrador::create($arreglo_log);
                DB::commit();
                return Response::json([ 'data' => $pedido],200);
            }
            $almacen = Almacen::find($pedido->almacen_solicitante);

            if(!$almacen){
                return Response::json(['error' =>"No se encontró el almacen."], 500);
            }
            
            $proveedor = Proveedor::with('contratoActivo')->find($almacen->proveedor_id);

            $contrato_activo = $proveedor->contratoActivo;
            $insumos = Insumo::conDescripcionesPrecios($contrato_activo->id, $proveedor->id)->select("precio", "clave", "insumos_medicos.tipo", "es_causes", "insumos_medicos.tiene_fecha_caducidad", "contratos_precios.tipo_insumo_id", "medicamentos.cantidad_x_envase")->withTrashed()->get();
            $lista_insumos = array();
            foreach ($insumos as $key => $value) {
                $array_datos = array();
                $array_datos['precio']              = $value['precio'];
                $array_datos['clave']               = $value['clave'];
                $array_datos['tipo']                = $value['tipo'];
                $array_datos['tipo_insumo_id']      = $value['tipo_insumo_id'];
                $array_datos['es_causes']           = $value['es_causes'];
                $array_datos['caducidad']           = $value['tiene_fecha_caducidad'];
                $array_datos['cantidad_unidosis']   = $value['cantidad_x_envase'];
                $lista_insumos[$value['clave']]     = $array_datos;
            }

            $pedido = $pedido->load("insumos");

            $total_causes               = 0;
            $total_no_causes            = 0;
            $total_material_curacion    = 0;

            foreach ($pedido->insumos as $key => $value) {
                if($lista_insumos[$value['insumo_medico_clave']]['tipo'] == "ME")
                {
                    if($lista_insumos[$value['insumo_medico_clave']]['es_causes']== 1)
                    {
                        $total_causes += $value['monto_solicitado'];
                    }else
                    {
                        $total_no_causes += $value['monto_solicitado'];
                    }
                }else
                {
                    $total_material_curacion += ($value['monto_solicitado'] * 1.16);
                }
            }

            $fecha = explode("-", $pedido->fecha);

            $presupuesto = UnidadMedicaPresupuesto::where("clues", $pedido->clues)
                                                    ->where("almacen_id", $pedido->almacen_solicitante)            
                                                    ->where("mes", intVal($fecha[1]))
                                                    ->where("anio", intVal($fecha[0]))
                                                    ->where("proveedor_id", $proveedor->id)
                                                    ->first();

            $servidor = Servidor::find(env('SERVIDOR_ID'));

                                                    
            
                $presupuesto->insumos_disponible                = ($presupuesto->insumos_disponible + $total_causes + $total_material_curacion);
                //$presupuesto->causes_disponible                 = ($presupuesto->causes_disponible + $total_causes);
                //$presupuesto->material_curacion_disponible      = round(($presupuesto->material_curacion_disponible + $total_material_curacion),2);
                $presupuesto->no_causes_disponible              = ($presupuesto->no_causes_disponible + $total_no_causes);
                
                $presupuesto->insumos_comprometido              = ($presupuesto->insumos_comprometido - ($total_causes + $total_material_curacion));
                $presupuesto->causes_comprometido               = ($presupuesto->causes_comprometido - $total_causes);
                $presupuesto->material_curacion_comprometido    = round(($presupuesto->material_curacion_comprometido - $total_material_curacion),2);
                $presupuesto->no_causes_comprometido            = ($presupuesto->no_causes_comprometido - $total_no_causes);

                $presupuesto->save(); 

                $arreglo_log = array("pedido_id"=>$id,
                                        'ip' =>$request->ip(),
                                        'navegador' =>$request->header('User-Agent'),
                                        "accion"=>"REGRESO BORRADOR SIN RECEPCIONES");
                LogPedidoBorrador::create($arreglo_log);
            
                $fecha_pedido = explode('-',$pedido->fecha);

                $pedido_mes = $fecha_pedido[1];
                $pedido_anio = $fecha_pedido[0];
                
                $datos_ajuste = [
                    'unidad_medica_presupuesto_id' => $presupuesto->id,
                    'pedido_id' => $pedido->id,
                    'clues' => $pedido->clues,
                    'mes_origen' => $pedido_mes,
                    'anio_origen' => $pedido_anio,
                    'mes_destino' => $pedido_mes,
                    'anio_destino' => $pedido_anio,
                    'causes' => $total_causes,
                    'no_causes' => $total_no_causes,
                    'material_curacion' => $total_material_curacion,
                    'insumos' => ($total_causes + $total_material_curacion),
                    'status' => 'BA'
                ];

                $ajuste_regresion = AjustePresupuestoPedidoRegresion::create($datos_ajuste);    
            }else{
                return Response::json(['error' =>"No cuenta con el privilegio necesario para realizar esta acción."], 500); 
            }    
            DB::commit();
            
            return Response::json([ 'data' => $presupuesto],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

      public function regresarBorradorCancelado($id, Request $request){
        try{
            DB::beginTransaction();
            
            $pedido = Pedido::with("recepciones.movimiento")->find($id);

            $almacen = Almacen::find($pedido->almacen_solicitante);

            if(!$almacen){
                return Response::json(['error' =>"No se encontró el almacen."], 500);
            }
            
            $proveedor = Proveedor::with('contratoActivo')->find($almacen->proveedor_id);

            
            $contrato_activo = $proveedor->contratoActivo;
            $insumos = Insumo::conDescripcionesPrecios($contrato_activo->id, $proveedor->id)->select("precio", "clave", "insumos_medicos.tipo", "es_causes", "insumos_medicos.tiene_fecha_caducidad", "contratos_precios.tipo_insumo_id", "medicamentos.cantidad_x_envase")->withTrashed()->get();
            $lista_insumos = array();
            foreach ($insumos as $key => $value) {
                $array_datos = array();
                $array_datos['precio']              = $value['precio'];
                $array_datos['clave']               = $value['clave'];
                $array_datos['tipo']                = $value['tipo'];
                $array_datos['tipo_insumo_id']      = $value['tipo_insumo_id'];
                $array_datos['es_causes']           = $value['es_causes'];
                $array_datos['caducidad']           = $value['tiene_fecha_caducidad'];
                $array_datos['cantidad_unidosis']   = $value['cantidad_x_envase'];
                $lista_insumos[$value['clave']]     = $array_datos;
            }

            $pedido = $pedido->load("insumos");

            $total_causes               = 0;
            $total_no_causes            = 0;
            $total_material_curacion    = 0;

            foreach ($pedido->insumos as $key => $value) {
                if($lista_insumos[$value['insumo_medico_clave']]['tipo'] == "ME")
                {
                    if($lista_insumos[$value['insumo_medico_clave']]['es_causes']== 1)
                    {
                        $total_causes += ($value['monto_solicitado'] - $value['monto_recibido']);
                    }else
                    {
                        $total_no_causes += ($value['monto_solicitado'] - $value['monto_recibido']);
                    }
                }else
                {
                    $total_material_curacion += (($value['monto_solicitado'] - $value['monto_recibido']) * 1.16);
                }
            }

            //return Response::json([ 'data' => $total_causes." - ".$total_no_causes." - ".$total_material_curacion],500);
            $logPedidoCancelado = LogPedidoCancelado::where("pedido_id", $id)->first();
            if(!$logPedidoCancelado)
            {
                 DB::rollBack();
                return Response::json(['error' =>"No se encuentra el registro de cancelación, por favor contacte al administrador"], 500);
            }

            $total = $total_causes + $total_no_causes + round($total_material_curacion);

            if($total > $logPedidoCancelado->total_monto_restante)
            {
                DB::rollBack();
                return Response::json(['error' =>"Saldo insuficiente por $".(($total - $logPedidoCancelado->total_monto_restante)).", verifique la disponibilidad del saldo o comuniquese con el administrador"], 500);   
            }

            $unidad_medica = UnidadMedicaPresupuesto::where("almacen_id", $pedido->almacen_solicitante)
                                                      ->where("clues", $pedido->clues)  
                                                      ->where("mes", $logPedidoCancelado->mes_destino)  
                                                      ->where("anio", $logPedidoCancelado->anio_destino)
                                                      ->first();  

            //return Response::json([ 'data' => $unidad_medica->causes_disponible."-".$total_causes." -- ".$unidad_medica->no_causes_disponible."-".$total_no_causes." -- ".$unidad_medica->material_curacion_disponible."-".$total_material_curacion],500);                                          
            //if($unidad_medica->causes_disponible < $total_causes || $unidad_medica->no_causes_disponible < $total_no_causes || $unidad_medica->material_curacion_disponible < $total_material_curacion)
            if($unidad_medica->insumos_disponible < ($total_causes + $total_material_curacion) || $unidad_medica->no_causes_disponible < $total_no_causes)
            {
                DB::rollBack();
                return Response::json(['error' =>"No existe presupuesto suficiente para generar este proceso, por favor contacte al administrador"], 500);
            }                                          
            /*
            $unidad_medica->causes_modificado -= $total_causes;
            $unidad_medica->causes_disponible -= $total_causes;
            $unidad_medica->material_curacion_modificado -= $total_material_curacion;
            $unidad_medica->material_curacion_disponible -= $total_material_curacion;
            */
            $servidor = Servidor::find(env('SERVIDOR_ID'));
            
            $fecha = explode("-", $pedido->fecha);

            $unidad_medica_destino = UnidadMedicaPresupuesto::where("almacen_id", $pedido->almacen_solicitante)
                                                    ->where("clues", $pedido->clues)  
                                                    ->where("mes", intVal($fecha[1]))
                                                    ->where("anio", intVal($fecha[0]))
                                                    ->first();  

                                                    
            if($servidor->principal){
                $unidad_medica->insumos_modificado -= ($total_causes + $total_material_curacion);
                $unidad_medica->insumos_disponible -= ($total_causes + $total_material_curacion);

                $unidad_medica->no_causes_modificado -= $total_no_causes;
                $unidad_medica->no_causes_disponible -= $total_no_causes;

                //Crear Hash de validación
                $secret = env('SECRET_KEY') . 'HASH-' . $unidad_medica->clues . $unidad_medica->mes . $unidad_medica->anio . $unidad_medica->insumos_modificado . $unidad_medica->no_causes_modificado . '-HASH';
                $cadena_validacion = Hash::make($secret);
                $unidad_medica->validation = $cadena_validacion;

                $unidad_medica->save();
                
                //return Response::json([ 'data' => $pedido->almacen_solicitante." - ".$pedido->clues." - ".intVal($fecha[1])." - ".intVal($fecha[0])],500);                                          
                /*
                $unidad_medica_destino->causes_comprometido += $total_causes;
                $unidad_medica_destino->causes_modificado += $total_causes;
                $unidad_medica_destino->material_curacion_comprometido += $total_material_curacion;
                $unidad_medica_destino->material_curacion_modificado += $total_material_curacion;
                */
                $unidad_medica_destino->insumos_comprometido += ($total_causes + $total_material_curacion);
                $unidad_medica_destino->insumos_modificado += ($total_causes + $total_material_curacion);

                $unidad_medica_destino->no_causes_comprometido += $total_no_causes;
                $unidad_medica_destino->no_causes_modificado += $total_no_causes;
                
                //Crear Hash de validación
                $secret = env('SECRET_KEY') . 'HASH-' . $unidad_medica_destino->clues . $unidad_medica_destino->mes . $unidad_medica_destino->anio . $unidad_medica_destino->insumos_modificado . $unidad_medica_destino->no_causes_modificado . '-HASH';
                $cadena_validacion = Hash::make($secret);
                $unidad_medica_destino->validation = $cadena_validacion;

                $unidad_medica_destino->save();
            
                LogPedidoCancelado::where("pedido_id", $id)->delete();

                $pedido->status = "EX";
                $pedido->save();
                
                //ajuste para regresion
                $fecha_pedido = explode('-',$pedido->fecha);

                $pedido_mes = $fecha_pedido[1];
                $pedido_anio = $fecha_pedido[0];

                $datos_ajuste = [
                    'unidad_medica_presupuesto_id' => $unidad_medica->id,
                    'pedido_id' => $pedido->id,
                    'clues' => $pedido->clues,
                    'mes_origen' => $pedido_mes,
                    'anio_origen' => $pedido_anio,
                    'mes_destino' => $logPedidoCancelado->mes_destino,
                    'anio_destino' => $logPedidoCancelado->anio_destino,
                    'causes' => $total_causes,
                    'no_causes' => $total_no_causes,
                    'material_curacion' => $total_material_curacion,
                    'insumos' => ($total_causes + $total_material_curacion),
                    'status' => 'EA'
                ];

                $ajuste_regresion = AjustePresupuestoPedidoRegresion::create($datos_ajuste);
                //fin ajuste regresion
               
            }
            DB::commit();
            return Response::json([ 'data' => $logPedidoCancelado],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    private function conversion_mes_texto($mes){
        $texto_mes = "";
        switch ($mes) {
            case 1:
                $texto_mes = "ENERO";
                break;
            case 2:
                $texto_mes = "FEBRERO";
                break;
            case 3:
                $texto_mes = "MARZO";
                break;
            case 4:
                $texto_mes = "ABRIL";
                break;
            case 5:
                $texto_mes = "MAYO";
                break;
            case 6:
                $texto_mes = "JUNIO";
                break;
            case 7:
                $texto_mes = "JULIO";
                break;
            case 8:
                $texto_mes = "AGOSTO";
                break;
            case 9:
                $texto_mes = "SEPTIEMBRE";
                break;
            case 10:
                $texto_mes = "OCTUBRE";
                break;
            case 11:
                $texto_mes = "NOVIEMBRE";
                break;
            case 12:
                $texto_mes = "DICIEMBRE";
                break;                                            
            
            default:
                $texto_mes = "VERIFICAR MES";
                break;
        }
        return $texto_mes;
    }
    public function excel(){
        $parametros = Input::only('q','status','proveedores','jurisdicciones', 'fecha_desde','fecha_hasta', 'ordenar_causes','ordenar_no_causes','ordenar_material_curacion');

        $items = self::getItemsQuery($parametros);
        $items = $items->get();

         Excel::create("Pedidos reporte ".date('Y-m-d'), function($excel) use($items) {

            $excel->sheet('Reporte de pedidos', function($sheet) use($items) {
                $sheet->setAutoSize(true);
                
                $sheet->mergeCells('A1:F1');

                $sheet->mergeCells('G1:I1');
                $sheet->mergeCells('J1:L1');
                $sheet->mergeCells('M1:O1');
                $sheet->mergeCells('P1:R1');
                $sheet->mergeCells('S1:U1');
                $sheet->mergeCells('V1:X1');
                $sheet->mergeCells('Y1:AA1');
                $sheet->mergeCells('AB1:AD1');
                $sheet->mergeCells('AE1:AG1');
                $sheet->mergeCells('AH1:AJ1');
                $sheet->mergeCells('AK1:AM1');
                $sheet->mergeCells('AN1:AP1');

                $sheet->row(1, array('','','','','','','Total Solicitado','','','Total Recibido','','','% Recibido','','','Causes Solicitado','','','Causes Recibido','','','% Recibido','','','No Causes Solicitado','','','No Causes Recibido','','','% Recibido','','','Material de Curación Solicitado','','','Material de Curación Recibido','','','% Recibido','',''));
                
                $sheet->row(2, array(
                    'Proveedor','Folio','Nombre', 'Clues','Unidad médica','Fecha','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Claves','Cantidad','Monto','Status'
                ));
                $sheet->cells("A1:AQ2", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });
                $sheet->row(2, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });
                $contador_filas = 2;
                foreach($items as $item){
                    $contador_filas++;
                    $status = '';
                    switch($item->status){
                        case 'BR': $status = 'Borrador'; break;
                        case 'TR': $status = 'En transito'; break;
                        case 'PS': $status = 'Por surtir'; break;
                        case 'FI': $status = 'Finalizado'; break;
                        case 'EX': $status = 'Expirado'; break;
                        default: $status = 'Otro';
                    }
                    
                    $sheet->appendRow(array(
                        $item->proveedor,
                        ($item->folio)?$item->folio:'S/F',
                        $item->descripcion,
                        $item->clues,
                        $item->unidad_medica,
                        $item->fecha,

                       $item->total_claves_solicitadas,
                       $item->total_cantidad_solicitada,
                       $item->total_monto_solicitado,

                       $item->total_claves_recibidas,
                       $item->total_cantidad_recibida,
                       $item->total_monto_recibido,

                       (!$item->total_claves_solicitadas)?"0.0":"=J$contador_filas/G$contador_filas",
                       (!$item->total_cantidad_solicitada)?"0.0":"=K$contador_filas/H$contador_filas",
                       (!round($item->total_monto_solicitado,2))?"0.0":"=L$contador_filas/I$contador_filas",

                       $item->total_claves_causes,
                       $item->total_cantidad_causes,
                       $item->total_monto_causes,

                       $item->total_claves_causes_recibidas,
                       $item->total_cantidad_causes_recibida,
                       $item->total_monto_causes_recibido,

                       (!$item->total_claves_causes)?"0.0":"=S$contador_filas/P$contador_filas",
                       (!$item->total_cantidad_causes)?"0.0":"=T$contador_filas/Q$contador_filas",
                       (!round($item->total_monto_causes,2))?"0.0":"=U$contador_filas/R$contador_filas",
                        
                       $item->total_claves_no_causes,
                       $item->total_cantidad_no_causes,
                        $item->total_monto_no_causes,

                        $item->total_claves_no_causes_recibidas,
                        $item->total_cantidad_no_causes_recibida,
                        $item->total_monto_no_causes_recibido,

                        (!$item->total_claves_no_causes)?"0.0":"=AB$contador_filas/Y$contador_filas",
                        (!$item->total_cantidad_no_causes)?"0.0":"=AC$contador_filas/Z$contador_filas",
                        (!round($item->total_monto_no_causes,2))?"0.0":"=AD$contador_filas/AA$contador_filas",

                        $item->total_claves_material_curacion,
                        $item->total_cantidad_material_curacion,
                        $item->total_monto_material_curacion,

                        $item->total_claves_material_curacion_recibidas,
                        $item->total_cantidad_material_curacion_recibida,
                        $item->total_monto_material_curacion_recibido,

                        (!$item->total_claves_material_curacion)?"0.0":"=AK$contador_filas/AH$contador_filas",
                        (!$item->total_cantidad_material_curacion)?"0.0":"=AL$contador_filas/AI$contador_filas",
                        (!round($item->total_monto_material_curacion,2))?"0.0":"=AM$contador_filas/AJ$contador_filas",

                        $status
                    )); 
                }

                $sheet->appendRow(array(
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',

        /*G*/       "=SUM(G3:G$contador_filas)",
        /*H*/       "=SUM(H3:H$contador_filas)",
        /*I*/       "=SUM(I3:I$contador_filas)",
        /*J*/       "=SUM(J3:J$contador_filas)",
        /*K*/       "=SUM(K3:K$contador_filas)",
        /*L*/       "=SUM(L3:L$contador_filas)",
        /*M*/       "=J".($contador_filas+1)."/G".($contador_filas+1),
        /*N*/       "=K".($contador_filas+1)."/H".($contador_filas+1),
        /*O*/       "=L".($contador_filas+1)."/I".($contador_filas+1),
        /*P*/       "=SUM(P3:P$contador_filas)",
        /*Q*/       "=SUM(Q3:Q$contador_filas)",
        /*R*/       "=SUM(R3:R$contador_filas)",
        /*S*/       "=SUM(S3:S$contador_filas)",
        /*T*/       "=SUM(T3:T$contador_filas)",
        /*U*/       "=SUM(U3:U$contador_filas)",
        /*V*/       "=S".($contador_filas+1)."/P".($contador_filas+1),
        /*W*/       "=T".($contador_filas+1)."/Q".($contador_filas+1),
        /*X*/       "=U".($contador_filas+1)."/R".($contador_filas+1),
        /*Y*/       "=SUM(Y3:Y$contador_filas)",
        /*Z*/       "=SUM(Z3:Z$contador_filas)",
        /*AA*/      "=SUM(AA3:AA$contador_filas)",
        /*AB*/      "=SUM(AB3:AB$contador_filas)",
        /*AC*/      "=SUM(AC3:AC$contador_filas)",
        /*AD*/      "=SUM(AD3:AD$contador_filas)",
        /*AE*/      "=AB".($contador_filas+1)."/Y".($contador_filas+1),
        /*AF*/      "=AC".($contador_filas+1)."/Z".($contador_filas+1),
        /*AG*/      "=AD".($contador_filas+1)."/AA".($contador_filas+1),
        /*AH*/      "=SUM(AH3:AH$contador_filas)",
        /*AI*/      "=SUM(AI3:AI$contador_filas)",
        /*AJ*/      "=SUM(AJ3:AJ$contador_filas)",
        /*AK*/      "=SUM(AK3:AK$contador_filas)",
        /*AL*/      "=SUM(AL3:AL$contador_filas)",
        /*AM*/      "=SUM(AM3:AM$contador_filas)",
        /*AN*/      "=AK".($contador_filas+1)."/AH".($contador_filas+1),
        /*AO*/      "=AL".($contador_filas+1)."/AI".($contador_filas+1),
        /*AP*/      "=AM".($contador_filas+1)."/AJ".($contador_filas+1),

                    ''
                ));

                $sheet->setBorder("A1:AQ$contador_filas", 'thin');

                $contador_filas += 1;
                
                $sheet->setColumnFormat(array(
                        "G3:G$contador_filas" => '#,##0',
                        "H3:H$contador_filas" => '#,##0',
                        "I3:I$contador_filas" => '"$" #,##0.00_-',
                        "J3:J$contador_filas" => '#,##0',
                        "K3:K$contador_filas" => '#,##0',
                        "L3:L$contador_filas" => '"$" #,##0.00_-',
                        "M3:M$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "N3:N$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "O3:O$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "P3:P$contador_filas" => '#,##0',
                        "Q3:Q$contador_filas" => '#,##0',
                        "R3:R$contador_filas" => '"$" #,##0.00_-',
                        "S3:S$contador_filas" => '#,##0',
                        "T3:T$contador_filas" => '#,##0',
                        "U3:U$contador_filas" => '"$" #,##0.00_-',
                        "V3:V$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "W3:W$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "X3:X$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "Y3:Y$contador_filas" => '#,##0',
                        "Z3:Z$contador_filas" => '#,##0',
                        "AA3:AA$contador_filas" => '"$" #,##0.00_-',
                        "AB3:AB$contador_filas" => '#,##0',
                        "AC3:AC$contador_filas" => '#,##0',
                        "AD3:AD$contador_filas" => '"$" #,##0.00_-',
                        "AE3:AE$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "AF3:AF$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "AG3:AG$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "AH3:AH$contador_filas" => '#,##0',
                        "AI3:AI$contador_filas" => '#,##0',
                        "AJ3:AJ$contador_filas" => '"$" #,##0.00_-',
                        "AK3:AK$contador_filas" => '#,##0',
                        "AL3:AL$contador_filas" => '#,##0',
                        "AM3:AM$contador_filas" => '"$" #,##0.00_-',
                        "AN3:AN$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "AO3:AO$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                        "AP3:AP$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%'
                    ));
            });
         })->export('xls');
    }

    private function getItemsQuery($parametros){

        $items = DB::table(DB::raw('(
                select
                    PR.id as proveedor_id, 
                    PR.nombre as proveedor,
                    P.id as pedido_id, 
                    P.folio, 
                    P.clues, 
                    UM.nombre as unidad_medica, 
                    UM.jurisdiccion_id,
                    P.fecha, 
                    P.fecha_concluido,
                    P.fecha_expiracion,
                    P.descripcion, 

                    count(REP.id) as numero_archivos,
                    
                    P.total_claves_solicitadas, 
                    P.total_cantidad_solicitada, 
                    P.total_monto_solicitado,

                    IF(P.total_claves_recibidas is null, 0, P.total_claves_recibidas) AS total_claves_recibidas,
                    IF(P.total_cantidad_recibida is null, 0, P.total_cantidad_recibida) AS total_cantidad_recibida,
                    IF(P.total_monto_recibido is null, 0, P.total_monto_recibido) AS total_monto_recibido,

                    IF(IC.total_claves_causes is null, 0, IC.total_claves_causes) AS total_claves_causes, 
                    IF(IC.total_cantidad_causes is null, 0, IC.total_cantidad_causes) AS total_cantidad_causes, 
                    IF(IC.total_monto_causes is null, 0, IC.total_monto_causes) AS total_monto_causes,

                    IF(IC.total_claves_causes_recibidas is null, 0, IC.total_claves_causes_recibidas) AS total_claves_causes_recibidas, 
                    IF(IC.total_cantidad_causes_recibida is null, 0, IC.total_cantidad_causes_recibida) AS total_cantidad_causes_recibida, 
                    IF(IC.total_monto_causes_recibido is null, 0, IC.total_monto_causes_recibido) AS total_monto_causes_recibido,

                    IF(INC.total_claves_no_causes is null, 0, INC.total_claves_no_causes) AS total_claves_no_causes, 
                    IF(INC.total_cantidad_no_causes is null, 0, INC.total_cantidad_no_causes) AS total_cantidad_no_causes, 
                    IF(INC.total_monto_no_causes is null, 0, INC.total_monto_no_causes) AS total_monto_no_causes,

                    IF(INC.total_claves_no_causes_recibidas is null, 0, INC.total_claves_no_causes_recibidas) AS total_claves_no_causes_recibidas, 
                    IF(INC.total_cantidad_no_causes_recibida is null, 0, INC.total_cantidad_no_causes_recibida) AS total_cantidad_no_causes_recibida, 
                    IF(INC.total_monto_no_causes_recibido is null, 0, INC.total_monto_no_causes_recibido) AS total_monto_no_causes_recibido,

                    IF(IMC.total_claves_material_curacion is null, 0, IMC.total_claves_material_curacion) AS total_claves_material_curacion, 
                    IF(IMC.total_cantidad_material_curacion is null, 0, IMC.total_cantidad_material_curacion) AS total_cantidad_material_curacion, 
                    IF(IMC.total_monto_material_curacion is null, 0, (IMC.total_monto_material_curacion+(IMC.total_monto_material_curacion*16/100))) AS total_monto_material_curacion,

                    IF(IMC.total_claves_material_curacion_recibidas is null, 0, IMC.total_claves_material_curacion_recibidas) AS total_claves_material_curacion_recibidas, 
                    IF(IMC.total_cantidad_material_curacion_recibida is null, 0, IMC.total_cantidad_material_curacion_recibida) AS total_cantidad_material_curacion_recibida, 
                    IF(IMC.total_monto_material_curacion_recibido is null, 0, (IMC.total_monto_material_curacion_recibido+(IMC.total_monto_material_curacion_recibido*16/100))) AS total_monto_material_curacion_recibido,

                    P.status

                    from pedidos P

                    left join unidades_medicas UM on UM.clues = P.clues
                    left join proveedores PR on P.proveedor_id = PR.id

                    left join repositorio REP on REP.pedido_id = P.id and REP.deleted_at is null

                    left join (
                        select PC.pedido_id, count(PC.insumo_medico_clave) as total_claves_causes, sum(PC.cantidad_solicitada) as total_cantidad_causes, sum(PC.monto_solicitado) as total_monto_causes,
                        sum(if(PC.cantidad_recibida>0,1,0)) as total_claves_causes_recibidas, sum(PC.cantidad_recibida) as total_cantidad_causes_recibida, sum(PC.monto_recibido) as total_monto_causes_recibido
                        from pedidos_insumos PC
                        join insumos_medicos IM on IM.clave = PC.insumo_medico_clave and IM.tipo = "ME" and IM.es_causes = 1
                        where PC.deleted_at is null
                        group by PC.pedido_id
                    ) as IC on IC.pedido_id = P.id

                    left join (
                        select PNC.pedido_id, count(PNC.insumo_medico_clave) as total_claves_no_causes, sum(PNC.cantidad_solicitada) as total_cantidad_no_causes, sum(PNC.monto_solicitado) as total_monto_no_causes,
                        sum(if(PNC.cantidad_recibida>0,1,0)) as total_claves_no_causes_recibidas, sum(PNC.cantidad_recibida) as total_cantidad_no_causes_recibida, sum(PNC.monto_recibido) as total_monto_no_causes_recibido
                        from pedidos_insumos PNC
                        join insumos_medicos IM on IM.clave = PNC.insumo_medico_clave and IM.tipo = "ME" and IM.es_causes = 0
                        where PNC.deleted_at is null
                        group by PNC.pedido_id
                    ) as INC on INC.pedido_id = P.id

                    left join (
                        select PMC.pedido_id, count(PMC.insumo_medico_clave) as total_claves_material_curacion, sum(PMC.cantidad_solicitada) as total_cantidad_material_curacion, sum(PMC.monto_solicitado) as total_monto_material_curacion,
                        sum(if(PMC.cantidad_recibida>0,1,0)) as total_claves_material_curacion_recibidas, sum(PMC.cantidad_recibida) as total_cantidad_material_curacion_recibida, sum(PMC.monto_recibido) as total_monto_material_curacion_recibido
                        from pedidos_insumos PMC
                        join insumos_medicos IM on IM.clave = PMC.insumo_medico_clave and IM.tipo = "MC"
                        where PMC.deleted_at is null
                        group by PMC.pedido_id
                    ) as IMC on IMC.pedido_id = P.id

                    where P.deleted_at is null

                    group by P.id
                
            ) as pedidos'));
            
        
        if (isset($parametros['q']) &&  $parametros['q'] != "") {
            $items = $items->where(function($query) use ($parametros){
                $query
                    ->where('unidad_medica','LIKE',"%".$parametros['q']."%")
                    ->orWhere('clues','LIKE',"%".$parametros['q']."%")
                    ->orWhere('folio','LIKE',"%".$parametros['q']."%")
                    ->orWhere('descripcion','LIKE',"%".$parametros['q']."%");
            });
        } 

        if(isset($parametros['meses']) && $parametros['meses'] != ""){
            $mes_filtro = explode(',',$parametros['meses']);  
                 
            if(count($mes_filtro)>0){
                $fecha_mes = Carbon::createFromDate(null, $mes_filtro[0],01);
                $fecha_mes->timezone('America/Mexico_City');
                
                $dia_fin_mes = $fecha_mes->daysInMonth;
                $fecha_inicio = $fecha_mes->year."-".$mes_filtro[0]."-01";
                $fecha_fin = $fecha_mes->year."-".$mes_filtro[0]."-".$dia_fin_mes;  

                $items = $items->whereBetween('fecha', array($fecha_inicio, $fecha_fin));

            }              
        }

        if(isset($parametros['statusRecepcion']) && $parametros['statusRecepcion'] != ""){
            $statusRecepcion_filtro = explode(',',$parametros['statusRecepcion']);  
                 
            if(count($statusRecepcion_filtro)>0){
                if($statusRecepcion_filtro[0] == 1)
                {
                    $fecha_actual = Carbon::now();
                    $fecha_actual->timezone('America/Mexico_City');
                    
                    $fecha_limite = $fecha_actual->year."-".$fecha_actual->month."-".$fecha_actual->day;
                    
                    $items = $items->where('fecha_expiracion', '<', $fecha_limite);
                    $items = $items->whereRaw(DB::RAW('total_monto_solicitado > total_monto_recibido'));
                    
                }else if($statusRecepcion_filtro[0] == 2)
                {
                    $items = $items->whereRaw(DB::RAW('total_monto_solicitado = total_monto_recibido'));
                }
            }              
        }

        /*$fecha_desde = isset($parametros['fecha_desde']) ? $parametros['fecha_desde'] : '';
        $fecha_hasta = isset($parametros['fecha_hasta']) ? $parametros['fecha_hasta'] : '';


        if ($fecha_desde != "" && $fecha_hasta != "" ) {
            $items = $items->whereBetween('fecha',[$fecha_desde, $fecha_hasta]);
        } 

        if ($fecha_desde != "" && $fecha_hasta == "" ) {
            $items = $items->where('fecha',">=",$fecha_desde);
        } 

        if ($fecha_desde == "" && $fecha_hasta != "" ) {
            $items = $items->where('fecha',"<=",$fecha_hasta);
        }*/
        
        
        
        if(isset($parametros['status']) && $parametros['status'] != ""){
            $status = explode(',',$parametros['status']);            
            if(count($status)>0){
                $items = $items->whereIn('status',$status);
            }              
        }

        if(isset($parametros['proveedores']) && $parametros['proveedores'] != ""){
            $proveedores = explode(',',$parametros['proveedores']);            
            if(count($proveedores)>0){
                $items = $items->whereIn('proveedor_id',$proveedores);
            }              
        }

        if(isset($parametros['jurisdicciones']) && $parametros['jurisdicciones'] != ""){
            $jurisdicciones = explode(',',$parametros['jurisdicciones']);            
            if(count($jurisdicciones)>0){
                $items = $items->whereIn('jurisdiccion_id',$jurisdicciones);
            }              
        }

        if(isset($parametros['ordenar_causes'])){
            if($parametros['ordenar_causes'] == 'ASC'){
                $items = $items->orderBy('porcentaje_causes','ASC');
            }
            if($parametros['ordenar_causes'] == 'DESC'){
                $items = $items->orderBy('porcentaje_causes','DESC');
            }
        }

        if(isset($parametros['ordenar_no_causes'])){
            if($parametros['ordenar_no_causes'] == 'ASC'){
                $items = $items->orderBy('porcentaje_no_causes','ASC');
            }
            if($parametros['ordenar_no_causes'] == 'DESC'){
                $items = $items->orderBy('porcentaje_no_causes','DESC');
            }
        }
        if(isset($parametros['ordenar_material_curacion'])){
            if($parametros['ordenar_material_curacion'] == 'ASC'){
                $items = $items->orderBy('porcentaje_material_curacion','ASC');
            }
            if($parametros['ordenar_material_curacion'] == 'DESC'){
                $items = $items->orderBy('porcentaje_material_curacion','DESC');
            }
        }
        return $items;
    }
    
    public function mesDisponible()
    {
        $meses = array("ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE");
        $mes = [];
        for($month = 1; $month <= Carbon::now()->month; $month++)
        {
            $mes[] = array('id'=>$month, 'descripcion' => $meses[$month-1]." ".Carbon::now()->year);
        }
        
        return Response::json([ 'data' => $mes],200);
    }
}
