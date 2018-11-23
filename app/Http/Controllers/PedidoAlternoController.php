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


class PedidoAlternoController extends Controller{
    
    public function update(Request $request, $id){  //$id seria el pedido_id a partir del cual vamos a crear el pedido alterno
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

        $pedido = Pedido::find($id);

        if(!$pedido){
            return Response::json(['error' => 'No se encuentra el pedido seleccionado.'], 500);
        }

        $tipo_pedido = 'PALT';
        $status = 'PV';
        
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

                $presupuesto_unidad->insumos_comprometido = $presupuesto_unidad->insumos_comprometido + round($total_monto['causes'] + $total_monto['material_curacion'],2);
                $presupuesto_unidad->insumos_disponible = $presupuesto_unidad->insumos_disponible - round($total_monto['causes'] + $total_monto['material_curacion'],2);

                $presupuesto_unidad->no_causes_comprometido = $presupuesto_unidad->no_causes_comprometido + round($total_monto['no_causes'],2);
                $presupuesto_unidad->no_causes_disponible = $presupuesto_unidad->no_causes_disponible - round($total_monto['no_causes'],2);

                //if($presupuesto_unidad->causes_disponible < 0 || $presupuesto_unidad->no_causes_disponible < 0 || $presupuesto_unidad->material_curacion_disponible < 0){
                if(($presupuesto_unidad->causes_disponible + $presupuesto_unidad->material_curacion_disponible) < 0 || $presupuesto_unidad->no_causes_disponible < 0){
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
        
        if($pedido->tipo_pedido_id != 'PJS'){
            $pedido->load("insumos.tipoInsumo","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos", "tipoPedido", "almacenProveedor","almacenSolicitante.unidadMedica","proveedor","encargadoAlmacen","director");
        } else {
            $pedido->load("insumos.tipoInsumo","insumos.insumosConDescripcion.informacion","insumos.listaClues","insumos.insumosConDescripcion.generico.grupos", "tipoPedido", "almacenProveedor","almacenSolicitante.unidadMedica","proveedor","encargadoAlmacen","director");
        }

        //return Response::json(['data'=>$pedido],200);

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

        $pedido->status_descripcion = 'SIN ESPECIFICAR';

        switch ($pedido->status) {
            case 'BR':
                $pedido->status_descripcion = 'EN BORRADOR';
                break;
            case 'PS':
                $pedido->status_descripcion = 'POR SURTIR';
                break;
            case 'FI':
                $pedido->status_descripcion = 'FINALIZADO';
                break;
            case 'EF':
                $pedido->status_descripcion = 'EN FARMACIA';
                break;
            case 'EX':
                $pedido->status_descripcion = 'EXPIRADO';
                break;
            case 'EX-CA':
                $pedido->status_descripcion = 'CANCELADO';
                break;
        }


        if($pedido->tipo_pedido_id != 'PJS'){
            self::formatoExcelPedidoGeneral($nombre_archivo, $pedido);
        } else {
            self::formatoExcelPedidoJurisdiccional($nombre_archivo, $pedido);
        }
        
    }

    function formatoExcelPedidoGeneral($nombre_archivo, $pedido){
        Excel::create($nombre_archivo, function($excel) use($pedido) {
            $insumos_tipo = [];
            $insumos_no_surtidos = [];

            foreach($pedido->insumos as $insumo){
                    $tipo = '---';

                    $tipo = $insumo->tipoInsumo->nombre;

                    if(!isset($insumos_tipo[$tipo])){
                        $insumos_tipo[$tipo] = [];
                    }
                    $insumos_tipo[$tipo][] = $insumo;

                    if(!$insumo->cantidad_recibida || $insumo->cantidad_recibida < $insumo->cantidad_solicitada){
                        if(!isset($insumos_no_surtidos[$insumo->tipoInsumo->clave])){
                            $insumos_no_surtidos[$insumo->tipoInsumo->clave] = [];
                        }
                        $insumos_no_surtidos[$insumo->tipoInsumo->clave][] = $insumo;
                    }
            }

            foreach($insumos_tipo as $tipo => $lista_insumos){
                $excel->sheet($tipo, function($sheet) use($pedido,$lista_insumos,$tipo) {
                    //$sheet->setAutoSize(true);
                    $estilo_cancelado = array(
                        'font'  => array(
                            'bold'  => true,
                            'color' => array('rgb' => 'FF0000')
                        )
                    );

                    $clave_folio = '-'.$lista_insumos[0]->tipoInsumo->clave;
                    
                    $sheet->mergeCells('A1:C1');
                    $sheet->mergeCells('D1:K1');
                    $sheet->row(1, array('FOLIO: '.$pedido->folio.$clave_folio,'','','PEDIDO '.$pedido->status_descripcion));

                    $sheet->cells("D1:K1", function($cells) {
                        $cells->setAlignment('right');
                    });

                    if($pedido->status == 'EX-CA'){
                        $sheet->mergeCells('A2:C2');
                        $sheet->mergeCells('D2:K2');
                        $sheet->row(2, array('ENTREGAR A: '.$pedido->almacenSolicitante->nombre,'','','FECHA DE CANCELACIÓN: '.$pedido->fecha_cancelacion));

                        $sheet->cells("D2:K2", function($cells) {
                            $cells->setAlignment('right');
                        });

                        $sheet->getStyle('D1')->applyFromArray($estilo_cancelado);
                        $sheet->getStyle('D2')->applyFromArray($estilo_cancelado);
                    }else if($pedido->status == 'EX'){
                        $sheet->mergeCells('A2:C2');
                        $sheet->mergeCells('D2:K2');
                        $sheet->row(2, array('ENTREGAR A: '.$pedido->almacenSolicitante->nombre,'','','FECHA DE EXPIRACIÓN: '.$pedido->fecha_expiracion));

                        $sheet->cells("D2:K2", function($cells) {
                            $cells->setAlignment('right');
                        });

                        $sheet->getStyle('D1')->applyFromArray($estilo_cancelado);
                        $sheet->getStyle('D2')->applyFromArray($estilo_cancelado);
                    }else{
                        $sheet->mergeCells('A2:K2');
                        $sheet->row(2, array('ENTREGAR A: '.$pedido->almacenSolicitante->nombre));
                    }
                    

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
                    foreach($lista_insumos as $insumo){
                        $contador_filas++;
                        $sheet->appendRow(array(
                            ($contador_filas-9), 
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

                    $sheet->cells("A10:K".$contador_filas, function($cells) {
                        $cells->setValignment('center');
                    });

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

                    $sheet->getStyle('C10:C'.$contador_filas)->getAlignment()->setWrapText(true);

                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','DIRECTOR DE LA UNIDAD MÉDICA','','ENCARGADO DE ALMACEN','','','',''));
                    $sheet->appendRow(array('', '',(isset($pedido->director->nombre)? $pedido->director->nombre : "" ),'',(isset($pedido->encargadoAlmacen->nombre)? $pedido->encargadoAlmacen->nombre : ""),'','','',''));

                    $sheet->mergeCells('E'.($contador_filas+7).':I'.($contador_filas+7));
                    $sheet->mergeCells('E'.($contador_filas+8).':I'.($contador_filas+8));

                    $sheet->cells("A".($contador_filas+6).":K9".($contador_filas+7), function($cells) {
                        $cells->setAlignment('center');
                    });
                });
                //$excel->getActiveSheet()->setAutoSize(false);
                //Columna No.
                $excel->getActiveSheet()->getColumnDimension('A')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
                //Columna Clave
                $excel->getActiveSheet()->getColumnDimension('B')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('B')->setWidth(18);
                //Columan Descripción
                $excel->getActiveSheet()->getColumnDimension('C')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('C')->setWidth(100);
                //Columnas: Cantidad, Precio Unitario, Total de lo Solicitado
                $excel->getActiveSheet()->getColumnDimension('D')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
                $excel->getActiveSheet()->getColumnDimension('E')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('E')->setWidth(18);
                $excel->getActiveSheet()->getColumnDimension('F')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                //Columnas: Cantidad, Precio Unitario, Total de lo Recibido
                $excel->getActiveSheet()->getColumnDimension('G')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
                $excel->getActiveSheet()->getColumnDimension('H')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('H')->setWidth(18);
                $excel->getActiveSheet()->getColumnDimension('I')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                //Columnas: % Unidades, % Monto
                $excel->getActiveSheet()->getColumnDimension('J')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('J')->setWidth(13);
                $excel->getActiveSheet()->getColumnDimension('K')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('K')->setWidth(13);

                $excel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_LEGAL);
                $excel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);

                $excel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(7,9);

                $excel->getActiveSheet()->getPageSetup()->setFitToPage(true);
                $excel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
                $excel->getActiveSheet()->getPageSetup()->setFitToHeight(0);

                $excel->getActiveSheet()->getHeaderFooter()->setDifferentOddEven(false);
                $excel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L FOLIO: '.$pedido->folio.'-'.$lista_insumos[0]->tipoInsumo->clave.' - FECHA DEL PEDIDO: '.$pedido->fecha[2]." DE ".$pedido->fecha[1]." DEL ".$pedido->fecha[0].' &R PÁGINA &P DE &N');

                $excel->getActiveSheet()->getPageMargins()->setTop(0.3543307);
                $excel->getActiveSheet()->getPageMargins()->setBottom(0.3543307);

                $excel->getActiveSheet()->getPageMargins()->setRight(0.1968504);
                $excel->getActiveSheet()->getPageMargins()->setLeft(0.2755906);
            }

            if(count($insumos_no_surtidos) > 0){
                $excel->sheet('Insumos Faltantes', function($sheet) use($pedido,$insumos_no_surtidos) {
                    //$sheet->setAutoSize(true);
                    $estilo_cancelado = array(
                        'font'  => array(
                            'bold'  => true,
                            'color' => array('rgb' => 'FF0000')
                        )
                    );

                    $sheet->mergeCells('A1:D1');
                    $sheet->mergeCells('E1:G1');
                    $sheet->row(1, array('FOLIO DEL PEDIDO: '.$pedido->folio,'','','','PEDIDO '.$pedido->status_descripcion));

                    $sheet->cells("E1:G1", function($cells) {
                        $cells->setAlignment('right');
                    });

                    if($pedido->status == 'EX-CA'){
                        $sheet->mergeCells('A2:D2');
                        $sheet->mergeCells('E2:G2');
                        $sheet->row(2, array('ENTREGAR A: '.$pedido->almacenSolicitante->nombre,'','','','FECHA DE CANCELACIÓN: '.$pedido->fecha_cancelacion));

                        $sheet->cells("E2:G2", function($cells) {
                            $cells->setAlignment('right');
                        });

                        $sheet->getStyle('E1')->applyFromArray($estilo_cancelado);
                        $sheet->getStyle('E2')->applyFromArray($estilo_cancelado);
                    }else if($pedido->status == 'EX'){
                        $sheet->mergeCells('A2:D2');
                        $sheet->mergeCells('E2:G2');
                        $sheet->row(2, array('ENTREGAR A: '.$pedido->almacenSolicitante->nombre,'','','','FECHA DE EXPIRACIÓN: '.$pedido->fecha_expiracion));

                        $sheet->cells("E2:G2", function($cells) {
                            $cells->setAlignment('right');
                        });

                        $sheet->getStyle('D1')->applyFromArray($estilo_cancelado);
                        $sheet->getStyle('D2')->applyFromArray($estilo_cancelado);
                    }else{
                        $sheet->mergeCells('A2:G2');
                        $sheet->row(2, array('ENTREGAR A: '.$pedido->almacenSolicitante->nombre));
                    }
                    

                    $sheet->mergeCells('A3:G3');
                    $sheet->row(3, array('NOMBRE DEL PEDIDO: '.$pedido->descripcion));

                    $sheet->mergeCells('A4:G4'); 
                    $sheet->row(4, array('UNIDAD MEDICA: '.$pedido->almacenSolicitante->unidadMedica->nombre));

                    $sheet->mergeCells('A5:G5'); 
                    $sheet->row(5, array('PROVEEDOR: '.$pedido->proveedor->nombre));

                    $sheet->mergeCells('A6:D6');
                    $sheet->mergeCells('E6:G6');
                    $sheet->row(6, array('FECHA DEL PEDIDO: '.$pedido->fecha[2]." DE ".$pedido->fecha[1]." DEL ".$pedido->fecha[0],'','','','FECHA DE NOTIFICACIÓN: '.$pedido->fecha_concluido));

                    $sheet->cells("E6:G6", function($cells) {
                        $cells->setAlignment('right');
                    });

                    $sheet->mergeCells('A7:G7'); 
                    $sheet->row(7, array('INSUMOS NO SURTIDOS'));

                    $sheet->cells("A7:G7", function($cells) {
                        $cells->setAlignment('center');
                    });

                    $sheet->row(8, array(
                        'NO.', 'TIPO', 'CLAVE','DESCRIPCIÓN','CANTIDAD FALTANTE','PRECIO UNITARIO','MONTO'
                    ));

                    $sheet->cells("A8:G8", function($cells) {
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
                    
                    $iva_solicitado = 0;

                    $contador_filas = 8;
                    foreach($insumos_no_surtidos as $tipo => $insumos){
                        foreach($insumos as $insumo){
                            $contador_filas++;
                            $sheet->appendRow(array(
                                ($contador_filas-8),
                                $insumo->tipoInsumo->nombre, 
                                $insumo->insumo_medico_clave,
                                $insumo->insumosConDescripcion->descripcion,
                                $insumo->cantidad_solicitada - ($insumo->cantidad_recibida | 0),
                                $insumo->precio_unitario,
                                $insumo->precio_unitario * ($insumo->cantidad_solicitada - ($insumo->cantidad_recibida | 0))
                            ));

                            if($insumo->insumosConDescripcion->tipo == 'MC'){
                                $iva_solicitado += ($insumo->monto_solicitado - ($insumo->monto_recibido | 0));
                            }
                        }
                    }

                    $sheet->cells("A9:G".$contador_filas, function($cells) {
                        $cells->setValignment('center');
                    });

                    $iva_solicitado = $iva_solicitado*16/100;
                    
                    $sheet->setBorder("A1:G$contador_filas", 'thin');

                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            '=SUM(E9:E'.($contador_filas).')',
                            'SUBTOTAL',
                            '=SUM(G9:G'.($contador_filas).')',
                        ));
                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            '',
                            'IVA',
                            $iva_solicitado,
                        ));
                    $sheet->appendRow(array(
                            '', 
                            '',
                            '',
                            '',
                            '',
                            'TOTAL',
                            '=SUM(G'.($contador_filas+1).':G'.($contador_filas+2).')',
                        ));
                    $contador_filas += 3;

                    /*$phpColor = new \PHPExcel_Style_Color();
                    $phpColor->setRGB('DDDDDD'); 
                    $sheet->getStyle("J10:K$contador_filas")->getFont()->setColor( $phpColor );*/

                    $sheet->setColumnFormat(array(
                        "E9:E$contador_filas" => '#,##0',
                        "F9:G$contador_filas" => '"$" #,##0.00_-'
                    ));

                    $sheet->getStyle('D9:D'.$contador_filas)->getAlignment()->setWrapText(true);
                    /*
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','DIRECTOR DE LA UNIDAD MÉDICA','','ENCARGADO DE ALMACEN','','','',''));
                    $sheet->appendRow(array('', '',(isset($pedido->director->nombre)? $pedido->director->nombre : "" ),'',(isset($pedido->encargadoAlmacen->nombre)? $pedido->encargadoAlmacen->nombre : ""),'','','',''));

                    $sheet->mergeCells('E'.($contador_filas+7).':I'.($contador_filas+7));
                    $sheet->mergeCells('E'.($contador_filas+8).':I'.($contador_filas+8));

                    $sheet->cells("A".($contador_filas+6).":K".($contador_filas+7), function($cells) {
                        $cells->setAlignment('center');
                    });
                    */
                });
                //$excel->getActiveSheet()->setAutoSize(false);
                //Columna No.
                $excel->getActiveSheet()->getColumnDimension('A')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
                //Columna Tipo
                $excel->getActiveSheet()->getColumnDimension('B')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('B')->setWidth(18);
                //Columna Clave
                $excel->getActiveSheet()->getColumnDimension('C')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('C')->setWidth(18);

                //Columan Descripción
                $excel->getActiveSheet()->getColumnDimension('D')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('D')->setWidth(100);

                //Columnas: Cantidad, Precio Unitario, Total de lo Solicitado
                $excel->getActiveSheet()->getColumnDimension('E')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('E')->setWidth(21);
                $excel->getActiveSheet()->getColumnDimension('F')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('F')->setWidth(18);
                $excel->getActiveSheet()->getColumnDimension('G')->setAutoSize(false);
                $excel->getActiveSheet()->getColumnDimension('G')->setWidth(21);

                $excel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_LEGAL);
                $excel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);

                $excel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(7,9);

                $excel->getActiveSheet()->getPageSetup()->setFitToPage(true);
                $excel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
                $excel->getActiveSheet()->getPageSetup()->setFitToHeight(0);

                $excel->getActiveSheet()->getHeaderFooter()->setDifferentOddEven(false);
                $excel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L FOLIO: '.$pedido->folio.' - FECHA DEL PEDIDO: '.$pedido->fecha[2]." DE ".$pedido->fecha[1]." DEL ".$pedido->fecha[0].' &R PÁGINA &P DE &N');

                $excel->getActiveSheet()->getPageMargins()->setTop(0.3543307);
                $excel->getActiveSheet()->getPageMargins()->setBottom(0.3543307);

                $excel->getActiveSheet()->getPageMargins()->setRight(0.1968504);
                $excel->getActiveSheet()->getPageMargins()->setLeft(0.2755906);
            }

        })->export('xls');
    }

    function formatoExcelPedidoJurisdiccional($nombre_archivo, $pedido){
        Excel::create($nombre_archivo, function($excel) use($pedido) {
            $insumos_tipo = [];

            foreach($pedido->insumos as $insumo){
                    $tipo = '---';

                    $tipo = $insumo->tipoInsumo->nombre;

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

                    $sheet->getStyle('C10:C'.$contador_filas)->getAlignment()->setWrapText(true);

                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','','','','','','',''));
                    $sheet->appendRow(array('', '','DIRECTOR DE LA UNIDAD MÉDICA','','ENCARGADO DE ALMACEN','','','',''));
                    $sheet->appendRow(array('', '',(isset($pedido->director->nombre)? $pedido->director->nombre : "" ),'',(isset($pedido->encargadoAlmacen->nombre)? $pedido->encargadoAlmacen->nombre : ""),'','','',''));

                    $sheet->mergeCells('E'.($contador_filas+7).':I'.($contador_filas+7));
                    $sheet->mergeCells('E'.($contador_filas+8).':I'.($contador_filas+8));

                    $sheet->cells("A".($contador_filas+6).":K9".($contador_filas+7), function($cells) {
                        $cells->setAlignment('center');
                    });
                    
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
