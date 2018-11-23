<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Presupuesto, App\Models\UnidadMedicaPresupuesto,  App\Models\TransferenciaPresupuesto, App\Models\Pedido, App\Models\Almacen, App\Models\LogPedidoCancelado, App\Models\LogTransferenciaCancelada, App\Models\Servidor, App\Models\AjustePresupuestoPedidoCancelado, App\Models\Usuario;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;
use Carbon\Carbon;

class CancelarPedidosController extends Controller
{
    /**
     * Transfiere recursos de una clus origen a una destino.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cancelarYTransferir(Request $request, $id){
        $input = Input::only('transferir_a_mes','transferir_a_anio');

        $servidor = Servidor::find(env('SERVIDOR_ID'));

        $pedido = Pedido::with("insumos.insumoDetalle","recepciones.entrada")->find($id);

        $usuario = Usuario::find($request->get('usuario_id'));
        $usuario->load('roles.permisos');
        
        $tiene_permiso_cancelar_pedido = false;
        if(count($usuario->roles)){
            foreach($usuario->roles as $rol){
                foreach ($rol->permisos as $permiso) {
                    if($permiso->id == 'pVJrPewkPFwidvmECcgg8BqVXn7FtH7E'){
                        $tiene_permiso_cancelar_pedido = true;
                        break 2;
                    }
                }
            }
        }

        if(!$tiene_permiso_cancelar_pedido){
            return Response::json([ 'data' => $pedido, 'error' => 'No tiene permiso para cancelar este pedido.' ],500);
        }

        $presupuesto = Presupuesto::find($pedido->presupuesto_id);
        if(!$presupuesto->activo){
            return Response::json([ 'data' => $pedido, 'error' => 'No se puede cancelar el pedido, ya que el presupuesto asignado ya no se encuentra activo.' ],500);
        }

        $recepion_abierta = false;
        
        foreach($pedido->recepciones as $recepcion){
            if($recepcion->entrada->status == 'BR'){
                $recepion_abierta = true;
                break;
            }
        }

        if($recepion_abierta){
            return Response::json([ 'data' => $pedido, 'error' => 'No se puede cancelar el pedido, ya que tiene una recepión abierta.' ],500);
        }

        //return Response::json([ 'data' => $pedido, 'error' => 'error en el servidor bla bla bla' ],500);
        try {
            DB::beginTransaction();
            
            $total_causes_disponible = 0.00;
            $total_no_causes_disponible = 0.00;
            $total_material_curacion_disponible = 0.00;

            foreach($pedido->insumos as $insumo){
                if($insumo->insumoDetalle->tipo == 'ME' && $insumo->insumoDetalle->es_causes){
                    $total_causes_disponible += ($insumo->monto_solicitado - $insumo->monto_recibido);
                }elseif($insumo->insumoDetalle->tipo == 'ME' && !$insumo->insumoDetalle->es_causes){
                    $total_no_causes_disponible += ($insumo->monto_solicitado - $insumo->monto_recibido);
                }else{
                    $total_material_curacion_disponible += ($insumo->monto_solicitado - $insumo->monto_recibido);
                }
            }

            $total_material_curacion_disponible += round($total_material_curacion_disponible*16/100,2);

            $fecha_pedido = explode('-',$pedido->fecha);

            $pedido_mes = $fecha_pedido[1];
            $pedido_anio = $fecha_pedido[0];

            $pedido_almacen = $pedido->almacen_solicitante;
            // Akira: ESO ES PARA PEDIDOS DE ALMACENES EXTERNOS. En el presupuesto se debe de poner la clues del almacen porque
            // es la que tiene el presupuesto, no la clues del pedido porque este fue capturado con otra clues, porque 
            // los almacenes externos no pueden capturar sus propios pedidos.

            $almacen = Almacen::find($pedido_almacen);
            //$pedido_clues = $pedido->clues;
            $pedido_clues = $almacen->externo == 1 ? $almacen->clues : $pedido->clues;

            $pedido->status = 'EX-CA';
            $pedido->recepcion_permitida = 0;
            $pedido->fecha_cancelacion = Carbon::now();

            //DB::rollBack();
            //return Response::json([ 'data' => ['total_causes_disponible'=>$total_causes_disponible, 'total_no_causes_disponible'=>$total_no_causes_disponible, 'total_material_curacion_disponible'=>$total_material_curacion_disponible], 'error' => 'error en el servidor bla bla bla' ],500);
            //TODO:Agregar hash validacion
            if($pedido->save()){
                if($pedido_mes == $input['transferir_a_mes'] && $pedido_anio == $input['transferir_a_anio']){
                    $presupuesto_pedido = UnidadMedicaPresupuesto::where('clues',$pedido_clues)->where('almacen_id',$pedido_almacen)->where('mes',$pedido_mes)->where('anio',$pedido_anio)->where('presupuesto_id',$pedido->presupuesto_id)->first();
                    $presupuesto_pedido->causes_comprometido -= $total_causes_disponible;
                    //$presupuesto_pedido->causes_disponible += $total_causes_disponible;

                    $presupuesto_pedido->material_curacion_comprometido -= $total_material_curacion_disponible;
                    //$presupuesto_pedido->material_curacion_disponible += $total_material_curacion_disponible;

                    $presupuesto_pedido->insumos_comprometido -= ($total_causes_disponible + $total_material_curacion_disponible);
                    $presupuesto_pedido->insumos_disponible += ($total_causes_disponible + $total_material_curacion_disponible);

                    $presupuesto_pedido->no_causes_comprometido -= $total_no_causes_disponible;
                    $presupuesto_pedido->no_causes_disponible += $total_no_causes_disponible;

                    $presupuesto_pedido->save();
                }else{
                    if($servidor->principal){
                        
                        $unidad_medica_origen_presupuesto = UnidadMedicaPresupuesto::where('clues',$pedido_clues)->where('almacen_id',$pedido_almacen)->where('mes',$pedido_mes)->where('anio',$pedido_anio)->where('presupuesto_id',$pedido->presupuesto_id)->first();
                        $unidad_medica_destino_presupuesto = UnidadMedicaPresupuesto::where('clues',$pedido_clues)->where('almacen_id',$pedido_almacen)->where('mes',$input['transferir_a_mes'])->where('anio',$input['transferir_a_anio'])->where('presupuesto_id',$pedido->presupuesto_id)->first();
                        
                        if(!$unidad_medica_origen_presupuesto || !$unidad_medica_destino_presupuesto){
                            throw new \Exception("Uno de los meses no tiene presupuesto configurado para los valores proporcionados.");
                        }

                        //$unidad_medica_origen_presupuesto->causes_modificado   -= $total_causes_disponible;
                        $unidad_medica_origen_presupuesto->causes_comprometido -= $total_causes_disponible;

                        //$unidad_medica_destino_presupuesto->causes_modificado += $total_causes_disponible;
                        //$unidad_medica_destino_presupuesto->causes_disponible += $total_causes_disponible;

                        //$unidad_medica_origen_presupuesto->material_curacion_modificado -= $total_material_curacion_disponible;
                        $unidad_medica_origen_presupuesto->material_curacion_comprometido -= $total_material_curacion_disponible;

                        //$unidad_medica_destino_presupuesto->material_curacion_modificado += $total_material_curacion_disponible;
                        //$unidad_medica_destino_presupuesto->material_curacion_disponible += $total_material_curacion_disponible;

                        $unidad_medica_origen_presupuesto->insumos_modificado -= ($total_causes_disponible + $total_material_curacion_disponible);
                        $unidad_medica_origen_presupuesto->insumos_comprometido -= ($total_causes_disponible + $total_material_curacion_disponible);

                        $unidad_medica_destino_presupuesto->insumos_modificado += ($total_causes_disponible + $total_material_curacion_disponible);
                        $unidad_medica_destino_presupuesto->insumos_disponible += ($total_causes_disponible + $total_material_curacion_disponible);

                        $unidad_medica_origen_presupuesto->no_causes_modificado -= $total_no_causes_disponible;
                        $unidad_medica_origen_presupuesto->no_causes_comprometido -= $total_no_causes_disponible;

                        $unidad_medica_destino_presupuesto->no_causes_modificado += $total_no_causes_disponible;
                        $unidad_medica_destino_presupuesto->no_causes_disponible += $total_no_causes_disponible;

                        //Crear Hash de validación
                        $secret = env('SECRET_KEY') . 'HASH-' . $unidad_medica_origen_presupuesto->clues . $unidad_medica_origen_presupuesto->mes . $unidad_medica_origen_presupuesto->anio . $unidad_medica_origen_presupuesto->insumos_modificado . $unidad_medica_origen_presupuesto->no_causes_modificado . '-HASH';
                        $cadena_validacion = Hash::make($secret);
                        $unidad_medica_origen_presupuesto->validation = $cadena_validacion;

                        //Crear Hash de validación
                        $secret = env('SECRET_KEY') . 'HASH-' . $unidad_medica_destino_presupuesto->clues . $unidad_medica_destino_presupuesto->mes . $unidad_medica_destino_presupuesto->anio . $unidad_medica_destino_presupuesto->insumos_modificado . $unidad_medica_destino_presupuesto->no_causes_modificado . '-HASH';
                        $cadena_validacion = Hash::make($secret);
                        $unidad_medica_destino_presupuesto->validation = $cadena_validacion;
                            
                        $unidad_medica_origen_presupuesto->save();
                        $unidad_medica_destino_presupuesto->save();

                        $datos_transferencia = [];
                        $datos_transferencia['presupuesto_id'] = $unidad_medica_origen_presupuesto->presupuesto_id;
                        $datos_transferencia['clues_origen'] = $pedido_clues;
                        $datos_transferencia['almacen_origen'] = $pedido_almacen;
                        $datos_transferencia['mes_origen'] = $pedido_mes;
                        $datos_transferencia['anio_origen'] = $pedido_anio;
                        $datos_transferencia['insumos'] = $total_causes_disponible + $total_material_curacion_disponible;
                        $datos_transferencia['causes'] = $total_causes_disponible;
                        $datos_transferencia['no_causes'] = $total_no_causes_disponible;
                        $datos_transferencia['material_curacion'] = $total_material_curacion_disponible;
                        $datos_transferencia['clues_destino'] = $pedido_clues;
                        $datos_transferencia['almacen_destino'] = $pedido_almacen;
                        $datos_transferencia['mes_destino'] = $input['transferir_a_mes'];
                        $datos_transferencia['anio_destino'] = $input['transferir_a_anio'];

                        $transferencia = TransferenciaPresupuesto::create($datos_transferencia);
                    }else{
                        $unidad_medica_origen_presupuesto = UnidadMedicaPresupuesto::where('clues',$pedido_clues)->where('almacen_id',$pedido_almacen)->where('mes',$pedido_mes)->where('anio',$pedido_anio)->first();
                        $unidad_medica_origen_presupuesto->causes_comprometido -= $total_causes_disponible;
                        $unidad_medica_origen_presupuesto->material_curacion_comprometido -= $total_material_curacion_disponible;
                        $unidad_medica_origen_presupuesto->insumos_comprometido -= ($total_causes_disponible + $total_material_curacion_disponible);
                        $unidad_medica_origen_presupuesto->no_causes_comprometido -= $total_no_causes_disponible;
                        $unidad_medica_origen_presupuesto->save();

                        $datos_ajuste = [
                            'unidad_medica_presupuesto_id' => $unidad_medica_origen_presupuesto->id,
                            'pedido_id' => $pedido->id,
                            'clues' => $pedido_clues,
                            'mes_origen' => $pedido_mes,
                            'anio_origen' => $pedido_anio,
                            'mes_destino' => $input['transferir_a_mes'],
                            'anio_destino' => $input['transferir_a_anio'],
                            'causes' => $total_causes_disponible,
                            'no_causes' => $total_no_causes_disponible,
                            'material_curacion' => $total_material_curacion_disponible,
                            'insumos' => ($total_causes_disponible + $total_material_curacion_disponible),
                            'status' => 'P'
                        ];

                        $ajuste_cancelacion = AjustePresupuestoPedidoCancelado::create($datos_ajuste);
                    }
                }

                $datos_log_pedido_cancelado = [
                    'pedido_id'=> $pedido->id,
                    'total_monto_restante'=> $total_causes_disponible + $total_no_causes_disponible + $total_material_curacion_disponible,
                    'mes_destino'=> $input['transferir_a_mes'],
                    'anio_destino'=> $input['transferir_a_anio'],
                    'ip'=> $request->ip(),
                    'navegador'=> $request->header('User-Agent'),
                    'updated_at'=> Carbon::now()
                ];

                $log = LogPedidoCancelado::create($datos_log_pedido_cancelado);

                DB::commit();
                return Response::json([ 'data' => $pedido ],200);
            }else{
                throw new \Exception("No se pudieron guardar los cambios en el pedido.");
            }

        }catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelarTransferencia(Request $request, $id){
        $input = Input::all();

        $pedido = Pedido::with("insumos.insumoDetalle")->where('tipo_pedido_id','PEA')->find($id);

        if(!$pedido){
            return Response::json([ 'data' => $pedido, 'error' => 'No se encontro el pedido.' ],500);
        }

        if(!$input['motivos']){
            return Response::json([ 'data' => $pedido, 'error' => 'No se puede cancelar la transferencia, ya que no se especificó ningún motivo.' ],500);
        }

        $pedido->load('historialTransferenciaCompleto');

        $se_puede_cancelar = true;

        foreach ($pedido->historialTransferenciaCompleto as $historial) {
            if($historial->evento == 'RECEPCION PEA'){
                $se_puede_cancelar = true;
            }else if($historial->evento == 'SURTIO PEA'){
                $se_puede_cancelar = false;
            }
        }

        $cantidad_enviada = $pedido->insumos->sum('cantidad_enviada');
        $cantidad_recibida = $pedido->insumos->sum('cantidad_recibida');

        if($cantidad_enviada != $cantidad_recibida){
            $se_puede_cancelar = False;
        }

        if(!$se_puede_cancelar){
            return Response::json([ 'data' => $pedido, 'error' => 'No es posible cancelar la transferencia en estos momentos.' ],500);
        }
        
        //return Response::json([ 'data' => $pedido, 'error' => 'error en el servidor bla bla bla' ],500);
        try {
            DB::beginTransaction();
            
            //DB::rollBack();
            //return Response::json([ 'data' => ['total_causes_disponible'=>$total_causes_disponible, 'total_no_causes_disponible'=>$total_no_causes_disponible, 'total_material_curacion_disponible'=>$total_material_curacion_disponible], 'error' => 'error en el servidor bla bla bla' ],500);

            $pedido->status = 'EX-CA';
            $pedido->recepcion_permitida = 0;
            $pedido->fecha_cancelacion = Carbon::now();

            if($pedido->save()){
                $datos_log_transferencia_cancelada = [
                    'pedido_id'=> $pedido->id,
                    'motivos' => $input['motivos'],
                    'ip'=> $request->ip(),
                    'navegador'=> $request->header('User-Agent'),
                    'updated_at'=> Carbon::now()
                ];

                $log = LogTransferenciaCancelada::create($datos_log_transferencia_cancelada);

                DB::commit();
                return Response::json([ 'data' => $pedido ],200);
            }else{
                throw new Exception("No se pudieron guardar los cambios en el pedido.");
            }

        }catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
