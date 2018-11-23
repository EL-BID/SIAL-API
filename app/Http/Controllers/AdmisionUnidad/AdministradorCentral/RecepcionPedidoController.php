<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Usuario, App\Models\Movimiento, App\Models\MovimientoInsumos, App\Models\MovimientoPedido, App\Models\Almacen, App\Models\Proveedor, App\Models\Pedido, App\Models\PedidoInsumo, App\Models\Insumo, App\Models\UnidadMedicaPresupuesto, App\Models\Stock, App\Models\LogRecepcionBorrador ;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;


class RecepcionPedidoController extends Controller
{
	public function borrarRecepcion($id, Request $request){
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

            $parametros = Input::all();
            DB::beginTransaction();
             $movimientos =  Movimiento::with("movimientoInsumosStock", "movimientoPedido.pedido")->find($id);
            $pedido_id = $movimientos->movimientoPedido->pedido->id;
                
            if($movimientos->movimientoPedido->pedido->status != "BR")
            {
                if($parametros['type'] == 2)
                {
                    $pedido = Pedido::with("recepciones.movimiento")->find($pedido_id);
                    $bandera = 0;
                    if (count($pedido->recepciones) >0) {
                        foreach ($pedido->recepciones as $key => $value) {
                            if($value['movimiento']['status'] == "BR")
                                    $bandera++;
                        }
                    }
                    
                    if($bandera > 0)
                    {
                        return Response::json(['error' =>"Error, Debe de finalizar todas las recepciones antes de regresar a borrador una recepcion"], 500);
                    }
                }    
                
                $almacen = Almacen::find($movimientos->almacen_id);

                if(!$almacen){
                    DB::rollBack();
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


                $total_causes               = 0;
                $total_no_causes            = 0;
                $total_material_curacion    = 0;

                $total_rows = 0;
                $total_cantidad_insumos = 0;
                $claves = array();
                $total_claves = 0;


                foreach ($movimientos->movimientoInsumosStock as $key => $value) {
                    $total_rows += $value['cantidad'];
                    if($lista_insumos[$value['stock']['clave_insumo_medico']]['tipo'] == "ME")
                    {
                        if($lista_insumos[$value['stock']['clave_insumo_medico']]['es_causes']== 1)
                        {
                            $total_causes += $value['precio_total'];
                        }else
                        {
                            $total_no_causes += $value['precio_total'];
                        }
                    }else
                    {

                        $total_material_curacion += ($value['precio_total'] * 1.16);
                    }

                    $pedidoInsumo = PedidoInsumo::where("pedido_id", $pedido_id)
                                              ->where("insumo_medico_clave", $value['stock']['clave_insumo_medico'])
                                              ->first();
                                                 
                    $pedidoInsumo->cantidad_recibida  = ($pedidoInsumo->cantidad_recibida - $value['cantidad']); 
                    $pedidoInsumo->monto_recibido     = ($pedidoInsumo->monto_recibido - $value['precio_total']);
                    $pedidoInsumo->save(); 

                    $total_cantidad_insumos +=  $value['cantidad'];
                    $stock = Stock::find($value['stock_id']);
                    $cantidad_actual = $stock->existencia;
                    $cantidad_regresar = $value['cantidad'];
                    $cantidad_restante = $cantidad_actual - $cantidad_regresar;

                    $cantidad_unidosis = 0;
                    if($stock->existencia_unidosis > 0)
                        $cantidad_unidosis = ($stock->existencia_unidosis - ($value['cantidad'] *  floatval($lista_insumos[$value['stock']['clave_insumo_medico']]['cantidad_unidosis'])));
                    
                    if(!array_key_exists($value['stock']['clave_insumo_medico'], $claves))
                        $claves[$value['stock']['clave_insumo_medico']] = 1;
                    
                    if($cantidad_restante >= 0 || $cantidad_unidosis>=0)
                    {
                        $stock->existencia = $cantidad_restante;
                        $stock->existencia_unidosis = $cantidad_unidosis;
                        $stock->save();
                    }
                    else
                    {
                        DB::rollBack();
                        return Response::json(['error' =>"No se puede eliminar la recepción, porque existe insumos que se encuentran utilizados, ".$value['stock']['clave_insumo_medico']." diferencia ".($cantidad_regresar - $cantidad_actual)], 500);
                    }
                }
               
                $fecha = explode("-", $movimientos->movimientoPedido->pedido->fecha);
                $clues = $movimientos->movimientoPedido->pedido->clues;
                $almacen = $movimientos->movimientoPedido->pedido->almacen_solicitante;
                
                $presupuesto = UnidadMedicaPresupuesto::where("clues", $clues)
                                                        ->where("almacen_id", $almacen)            
                                                        ->where("mes", intVal($fecha[1]))
                                                        ->where("anio", intVal($fecha[0]))
                                                        ->where("proveedor_id", $proveedor->id)
                                                        ->first();

                

                if($total_causes > $presupuesto->causes_devengado || $total_no_causes > $presupuesto->no_causes_devengado || round($total_material_curacion,2) > $presupuesto->material_curacion_devengado)
                {
                    DB::rollBack();
                        return Response::json(['error' =>"No se puede eliminar la recepción, porque el monto recibido es mayor al monto solicitado"], 500);
                }



                $presupuesto->causes_devengado               = round((floatval($presupuesto->causes_devengado) - $total_causes),2);
                $presupuesto->material_curacion_devengado    = round((floatval($presupuesto->material_curacion_devengado) - $total_material_curacion),2); 

                $presupuesto->insumos_devengado              = round((floatval($presupuesto->insumos_devengado) - ($total_causes + $total_material_curacion)),2);
                $presupuesto->no_causes_devengado            = round((floatval($presupuesto->no_causes_devengado) - $total_no_causes),2);
                
                $presupuesto->causes_comprometido                 = round((floatval($presupuesto->causes_comprometido) + $total_causes),2);
                $presupuesto->material_curacion_comprometido      = round((floatval($presupuesto->material_curacion_comprometido) + $total_material_curacion),2);

                $presupuesto->insumos_comprometido                = round((floatval($presupuesto->insumos_comprometido) + ($total_causes + $total_material_curacion)),2);
                $presupuesto->no_causes_comprometido              = round((floatval($presupuesto->no_causes_comprometido) + $total_no_causes),2);
                

                $presupuesto->save();

                $pedido = Pedido::find($pedido_id);

                $pedido->total_monto_recibido = $pedido->total_monto_recibido - round(($total_causes + $total_no_causes + $total_material_curacion),2);
                
                $pedidoInsumo = PedidoInsumo::where("pedido_id", $pedido_id)
                                            ->whereNotNull("cantidad_recibida")
                                            ->where("cantidad_recibida", ">", 0);
                                            
                $pedido->total_claves_recibidas = $pedidoInsumo->count();
                $pedido->total_cantidad_recibida = ($pedido->total_cantidad_recibida - $total_rows);

                if($this->valida_fecha($pedido->fecha_expiracion))            
                    $pedido->status = 'PS';   
                else
                    $pedido->status = 'EX';
                
            
                $pedido->save(); 
                
                
                //Eliminacion de la recepcion 
                if($parametros['type'] == 1)
                {
                    MovimientoInsumos::where("movimiento_id", $movimientos->id)->delete();
                    MovimientoPedido::find($movimientos->movimientoPedido->id)->delete();
                    Movimiento::find($id)->delete();
                    $arreglo_log = array("movimiento_id"=>$id,
                                     'ip' =>$request->ip(),
                                     'navegador' =>$request->header('User-Agent'),
                                     "accion"=>"RECEPCION ELIMINADA");
                
                    LogRecepcionBorrador::create($arreglo_log);
                }else if($parametros['type'] == 2)
                {
                    $arreglo_log = array("movimiento_id"=>$id,
                                     'ip' =>$request->ip(),
                                     'navegador' =>$request->header('User-Agent'),
                                     "accion"=>"REGRESO BORRADOR");
                    
                    $movimiento  = Movimiento::find($id);
                    $movimiento->status = "BR";
                    $movimiento->update();
                    LogRecepcionBorrador::create($arreglo_log);
                }         
                DB::commit();
                return Response::json([ 'data' => $movimientos->movimientoPedido->pedido],200);    
                   
            }else
            {
                return Response::json(['error' =>"No se puede eliminar ni mandar a borrador ninguna recepcion si el pedido se encuentra en 'BORRADOR'"], 500);
            }
            
            
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    private function valida_fecha($fecha)
    {
        $date1=date_create($fecha);
        $fecha2 = new \DateTime;
        
        $diff=date_diff($fecha2, $date1, FALSE);

        if($diff->invert == 0)
        {
            return true;
            
        }else
        {
            return false;
        }
    }
}