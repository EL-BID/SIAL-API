<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Requests;
use App\Models\Movimiento;
use App\Models\MovimientoInsumos;
use App\Models\Stock;
use App\Models\Pedido;
use App\Models\MovimientoPedido;
use App\Models\Proveedor;
use App\Models\Insumo;
use App\Models\Almacen;
use App\Models\PedidoInsumo;
use App\Models\Presupuesto;
use App\Models\UnidadMedicaPresupuesto;
use App\Models\HistorialMovimientoTransferencia;

use App\Models\Usuario;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;
use DateTime;
use Carbon\Carbon;

class RecepcionPedidoController extends Controller
{

	public function obtenerDatosPresupuesto($clues,$presupuesto_id,$mes,$anio,$almacen_id){
        try{
            //$parametros = Input::all();
            //$presupuesto = Presupuesto::where('activo',1)->first();
            $presupuesto_unidad_medica = UnidadMedicaPresupuesto::where('clues',$clues)
                                            ->where('presupuesto_id',$presupuesto_id)
											->where('mes',$mes)
											->where('anio',$anio)
											->where('almacen_id',$almacen_id)
                                            ->groupBy('clues')
											->first();
			
            return $presupuesto_unidad_medica;
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function index(){
		/*
    	$pedidos = Movimiento::with("movimientoInsumos.stock")->get();
    	return Response::json([ 'data' => $pedidos],200);
		*/
    }

    public function show(Request $request, $id){
		
    	$pedido = Pedido::with(['recepciones'=>function($recepciones){
			$recepciones->has('entradaAbierta')->with('entradaAbierta.insumos');
		}])->find($id);
        
        if(!$pedido){
            return Response::json(['error' => "No se encuentra el pedido que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

		if($pedido->status == 'BR'){
			return Response::json(['error' => "Este pedido se encuentra en borrador."], 500);
		}

		if(!$pedido->recepcion_permitida){
			return Response::json(['error' => "Este pedido no admite captura de recepciones."], 500);
		}

		$pedido = $pedido->load("insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos", "tipoPedido", "almacenProveedor","almacenSolicitante.unidadMedica","proveedor");
		
		if($pedido->tipo_pedido_id == 'PEA'){
			$pedido = $pedido->load("historialTransferenciaCompleto");
		}
        

        return Response::json([ 'data' => $pedido],200);
    }

    public function update(Request $request, $id){
		$mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'almacen_id'        	=> 'required',
            'tipo_movimiento_id'    => 'required',
            'fecha_movimiento'     	=> 'required'
        ];

        $parametros = Input::all();
		
		/*Verifica de primera instancia que no se sobrepase las cantidades solicitadas con la recibida, por error*/
		$pedido_verificar = PedidoInsumo::where("pedido_id", $id)
							->select( DB::raw('SUM(cantidad_solicitada) as cantidad_solicitada'), DB::raw('SUM(cantidad_recibida) as cantidad_recibida') )
							->groupBy("pedido_id")
							->first();
		

		if($pedido_verificar->cantidad_solicitada < intval($pedido_verificar->cantidad_recibida))
		{
			return Response::json(['error' =>"Existe un error al comprobar la cantidad recibida, por favor contactese con el area de soporte" ], 500);
		}
		/*Fin validador*/


		$almacen = Almacen::find($request->get('almacen_id'));

		if(!$almacen){
			return Response::json(['error' =>"No se encontró el almacen."], 500);
		}

		$pedido = Pedido::find($id);

		if(!$pedido){
			return Response::json(['error' => 'No se encontró el pedido.'],500);
		}

		if($almacen->id != $pedido->almacen_solicitante){
			return Response::json(['error' => 'El almacen solicitante del pedido no corresponde al seleccionado.'],500);
		}
        
        /*Recepcion de precios por insumo*/
		$proveedor = Proveedor::with('contratoActivo')->find($almacen->proveedor_id);

		$contrato_activo = $proveedor->contratoActivo;
        
        if(!$contrato_activo){
            return Response::json(['error' => 'No se encontraron contratos activos para este proveedor'], 500);
        }

        //cargamos en un arreglo los insumos, para poder obtener datos, de los insumos que envie 


        $insumos = Insumo::conDescripcionesPrecios($contrato_activo->id, $proveedor->id)->select("precio", "clave", "insumos_medicos.tipo", "es_causes", "insumos_medicos.tiene_fecha_caducidad", "contratos_precios.tipo_insumo_id", "medicamentos.cantidad_x_envase")->withTrashed()->get();
        $lista_insumos = array();
        foreach ($insumos as $key => $value) {
        	$array_datos = array();
        	$array_datos['precio'] 				= $value['precio'];
        	$array_datos['clave'] 				= $value['clave'];
        	$array_datos['tipo'] 				= $value['tipo'];
        	$array_datos['tipo_insumo_id'] 		= $value['tipo_insumo_id'];
        	$array_datos['es_causes'] 			= $value['es_causes'];
        	$array_datos['caducidad'] 			= $value['tiene_fecha_caducidad'];
        	$array_datos['cantidad_unidosis'] 	= $value['cantidad_x_envase'];
        	$lista_insumos[$value['clave']] 	= $array_datos;
        }
		/**/
		DB::beginTransaction();

		/*$pedido = Pedido::where('almacen_solicitante',$almacen->id)->with(['recepciones'=>function($recepciones){
			$recepciones->has('entradaAbierta')->with('entradaAbierta.insumos');
		}])->whereIn('status',['PS','EX', 'BR'])->find($id);*/
		
		if($pedido->almacen_proveedor != $almacen->id && $pedido->almacen_solicitante != $almacen->id  && $pedido->clues_destino != $almacen->clues && $pedido->clues != $almacen_clues){
			DB::rollBack();
            return Response::json(['error' => "No se encuentra el pedido que esta buscando."], HttpResponse::HTTP_CONFLICT);
		}
		
		if($pedido->status ==  "BR"){
			DB::rollBack();
			return Response::json(['error' => 'No se puede guardar la recepción porque el pedido se ha modificado a borrador, contactese con el administrador para mayor información'],500);
		}

		if($pedido->tipo_pedido_id != 'PEA'){
			$pedido = $pedido->load(['recepciones'=>function($recepciones){
				$recepciones->has('entradaAbierta')->with('entradaAbierta.insumos');
			}]);
			$recepciones_pedido = $pedido->recepciones;
		}else{
			$pedido = $pedido->load(['movimientos'=>function($movimientos){
				$movimientos->has('transferenciaRecibidaBorrador')->with('transferenciaRecibidaBorrador.insumos');
			}]);
			$recepciones_pedido = $pedido->movimientos;
		}
		
		/*$pedido = Pedido::where('clues',$almacen->clues)->with(['recepciones'=>function($recepciones){
			$recepciones->has('entradaAbierta')->with('entradaAbierta.insumos');
		}])->whereIn('status',['PS','EX', 'BR'])->find($id);*/

		if(count($recepciones_pedido) > 1){
			return Response::json(['error' => 'El pedido tiene mas de una recepción abierta'], 500);
		}elseif(count($recepciones_pedido) == 1){
			$recepcion = $recepciones_pedido[0];
		}else{

			//$movimiento_validador = MovimientoPedido::where() 
			$recepcion = new MovimientoPedido;

			$recepcion->recibe = 'RECIBE';
			$recepcion->entrega = 'ENTREGA';
			$recepcion->pedido_id = $pedido->id;
		}

		if(!isset($parametros['fecha_movimiento'])){
			$parametros['fecha_movimiento'] = date('Y-m-d');
		}

		/* Validador de  movimientos, se verifica que no exista un movimiento con las mismas característicasa*/
				
		if($parametros['status'] == 'FI') //Actualizamod datos en caso de ser necesario
		{												
			$movimiento_validador = Movimiento::where("fecha_movimiento", $parametros['fecha_movimiento'])
											->where("observaciones", ($parametros['observaciones'])?$parametros['observaciones']:null) 
											->where("almacen_id", $almacen->id);
								
			if($movimiento_validador->count() > 1){
				return Response::json(['error' => "Error, se ha encontrado una recepcion con los mismos datos, por favor sustituya alguno de los valores (entrega, recibe, fecha uu observacion) y vuelva a intentarlo"], 500);		
			}									
		}
		/**/

		if(!isset($parametros['observaciones'])){
			$parametros['observaciones'] = null;
		}

		if($pedido->tipo_pedido_id != 'PEA'){
			$tipo_movimiento = 4; #Recepción de pedido
		}else{
			$tipo_movimiento = 9; #Recepción de transferencia
		}

		$datos_movimiento = [
			'status' => $parametros['status'],
			'tipo_movimiento_id' => $tipo_movimiento, //Recepcion de pedido
			'fecha_movimiento' => $parametros['fecha_movimiento'],
			'almacen_id' => $almacen->id,
			'observaciones' => ($parametros['observaciones'])?$parametros['observaciones']:null
		];

		if(count($parametros['stock']) == 0){
            return Response::json(['error' => 'Se necesita capturar al menos un lote'], 500);
        }
		
        try {
            
	        $v = Validator::make($datos_movimiento, $reglas, $mensajes);

	        if ($v->fails()) {
	        	DB::rollBack();
	            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
	        }
			
			if($pedido->tipo_pedido_id != 'PEA'){
				$movimiento = $recepcion->entradaAbierta;
			}else{
				$movimiento = $recepcion->transferenciaRecibidaBorrador;
			}
			

			if($movimiento){
				if($pedido->tipo_pedido_id == 'PEA'){
					$historial = HistorialMovimientoTransferencia::where('movimiento_id',$movimiento->id)->where('pedido_id',$pedido->id)->first();
				}

				$movimiento->update($datos_movimiento);
				
				/*En caso de Existir una abierta actualizamos datos*/
				if($parametros['status'] == 'FI') //Actualizamod datos en caso de ser necesario
				{
					$reglas_movimiento_pedido = [
			            'entrega'        		=> 'required',
			            'recibe'    			=> 'required',
			            'fecha_movimiento'     	=> 'required'
			        ];

			        $v = Validator::make($parametros, $reglas_movimiento_pedido, $mensajes);

			        if ($v->fails()) {
			        	DB::rollBack();
			            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
			        }

			        $recepcion->recibe = $parametros['entrega']; // Se actualiza la persona que entrega
					$recepcion->entrega = $parametros['recibe']; // Se actualiza la perosna que recibe

					$recepcion->save();

			    }
				/**/
			}else{
				$movimiento = Movimiento::create($datos_movimiento);

				if($pedido->tipo_pedido_id == 'PEA'){
					$historial_datos = [
						'almacen_origen'=>$pedido->almacen_proveedor,
						'almacen_destino'=>$pedido->almacen_solicitante,
						'clues_origen'=>$pedido->clues,
						'clues_destino'=>($pedido->clues_destino)?$pedido->clues_destino:$pedido->clues,
						'pedido_id'=>$pedido->id,
						'evento'=>'RECEPCION PEA',
						'movimiento_id'=>$movimiento->id,
						'total_unidades'=>0,
						'total_claves'=>0,
						'total_monto'=>0,
						'fecha_inicio_captura'=>Carbon::now(),
						'fecha_finalizacion'=>null
					];
					$historial = HistorialMovimientoTransferencia::create($historial_datos);
				}

				$recepcion->movimiento_id = $movimiento->id;
				
				if($parametros['status'] == 'FI') //Actualizamod datos en caso de ser necesario
				{
					$reglas_movimiento_pedido = [
			            'entrega'        	=> 'required',
			            'recibe'    		=> 'required',
			            'fecha_movimiento'  => 'required'
			        ];

			        $v = Validator::make($parametros, $reglas_movimiento_pedido, $mensajes);

			        if ($v->fails()) {
			        	DB::rollBack();
			            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
			        }

			        $recepcion->recibe = $parametros['entrega']; // Se actualiza la persona que entrega
					$recepcion->entrega = $parametros['recibe']; // Se actualiza la perosna que recibe

					$movimiento->fecha_movimiento 	= $parametros['fecha_movimiento']; //Se actualizan datos de movimiento (fecha)
					$movimiento->observaciones 		= $parametros['observaciones']; //Se actualizan datos de movimiento (observaciones)

					$movimiento->update($datos_movimiento);
			    }

				$recepcion->save();
			}

	        
			$stock = $parametros['stock'];
			
			/*   Harima: Para editar lista de insumos sin tener que borrar en la base de datos   */
            $lista_insumos_db = MovimientoInsumos::where('movimiento_id',$movimiento->id)->withTrashed()->get();
            if(count($lista_insumos_db) > count($parametros['stock'])){
                $total_max_insumos = count($lista_insumos_db);
            }else{
                $total_max_insumos = count($parametros['stock']);
            }

	        /*Variable para ir sumando lo devengado y actualizar la tabla de unidad presupuesto*/
	        $causes_unidad_presupuesto 				= 0;
	        $no_causes_unidad_presupuesto 			= 0;
	        $material_curacion_unidad_presupuesto 	= 0;
			/*                                                                                  */
			
			/*Variable para ir actualizando el historial de transferencias, en caso de ser necesario*/
			$movimiento_monto_recibido = 0;
			$movimiento_claves_recibidas = [];
			$movimiento_cantidad_recibida = 0;
			$movimiento_monto_para_iva = 0;

	        //foreach ($stock as $key => $value) {
			/////######################## Inicia
			$insumos_db_a_borrar = [];
			for ($i=0; $i < $total_max_insumos ; $i++) {
				//Si existe dato en el stock que recibimos del formulario corremos el proceso
				if(isset($stock[$i])){
					$value = $stock[$i];

					$reglas_stock = [
						'almacen_id'        	=> 'required',
						'clave_insumo_medico'   => 'required',
						'lote'     				=> 'required',
						'existencia'     		=> 'required'
					];

					/*Obtenemos variables del insumo a procesar. de acuerdo a la lista de insumos cargada anteriormente*/
					$tipo_insumo 	= $lista_insumos[$value['clave_insumo_medico']]['tipo'];
					$es_causes 		= $lista_insumos[$value['clave_insumo_medico']]['es_causes'];
					$caducidad 		= $lista_insumos[$value['clave_insumo_medico']]['caducidad'];
					$tipo_insumo_id = $lista_insumos[$value['clave_insumo_medico']]['tipo_insumo_id'];
					$unidosis 		= $lista_insumos[$value['clave_insumo_medico']]['cantidad_unidosis'];
					/**/

					if($tipo_insumo == "ME")
						$reglas_stock['fecha_caducidad'] = 'required';

					$value['almacen_id'] = $almacen->id;

					$v = Validator::make($value, $reglas_stock, $mensajes);

					if ($v->fails()) {
						DB::rollBack();
						return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
					}

					if(!isset($value['fecha_caducidad']) or $this->limpia_espacios($value['fecha_caducidad']) == "")
						$value['fecha_caducidad'] = null;

					
					if($this->validacion_fecha_caducidad($pedido->fecha ,$value['fecha_caducidad'], $caducidad)) //Validacion de Fecha de caducidad
					{
						if(!isset($value['fecha_caducidad'])){
							$value['fecha_caducidad'] = null;
						}elseif(!$value['fecha_caducidad']){
							$value['fecha_caducidad'] = null;
						}
					
						if(isset($value['codigo_barras'])){
							$insert_stock = Stock::where('codigo_barras',$value['codigo_barras'])->where('fecha_caducidad',$value['fecha_caducidad'])->where('lote',$value['lote'])->where('clave_insumo_medico',$value['clave_insumo_medico'])->where('almacen_id', $almacen->id)->first(); //Verifica si existe el medicamento en el stock
						}else{
							$insert_stock = Stock::where('fecha_caducidad',$value['fecha_caducidad'])->where('lote',$value['lote'])->where('clave_insumo_medico',$value['clave_insumo_medico'])->where('almacen_id', $almacen->id)->Where(function ($query) {
								$query->whereNull('codigo_barras')
									->orWhere('codigo_barras', '');
							})->first(); //Verifica si existe el medicamento en el stock
						}

						if($parametros['status'] == 'FI'){
							if($tipo_insumo == "ME"){ //Verifico si es medicamento o material de curación, para agregar el IVA
								$pedido_insumo = PedidoInsumo::where("pedido_id", $pedido->id)->where("insumo_medico_clave", $value['clave_insumo_medico'])->first(); //modificamos el insumo de los pedidos

								if($pedido_insumo->cantidad_solicitada >= intval(($pedido_insumo->cantidad_recibida + $value['existencia']))){
									$pedido_insumo->cantidad_recibida += $value['existencia'];
									$cantidad_recibida = ( $value['existencia'] * $pedido_insumo->precio_unitario );

									$pedido_insumo->monto_recibido 	  += $cantidad_recibida;
									$pedido_insumo->update();  //Actualizamos existencia y  monto de pedidos insumo

									if($insert_stock){
										$insert_stock->existencia 			+= $value['existencia'];
										$insert_stock->existencia_unidosis 	= ($insert_stock->existencia * $unidosis);//***************************
										$insert_stock->save();
									}else{
										$insert_stock->existencia_unidosis 	= ($value['existencia'] * $unidosis);				
										$insert_stock = Stock::create($value);
									}		

									if($es_causes == 1)
										$causes_unidad_presupuesto 				+= $cantidad_recibida;
									else
										$no_causes_unidad_presupuesto 			+= $cantidad_recibida;
								}else{
									DB::rollBack();
									return Response::json(['error' => 'Existe un error, el insumo '.$value['clave_insumo_medico'].' ha sobrepasado el monto solicitando, por favor contactese con soporte de la aplicacion'], 500);
								}   
									
							}else{
								$pedido_insumo = PedidoInsumo::where("pedido_id", $pedido->id)->where("insumo_medico_clave", $value['clave_insumo_medico'])->first(); //modificamos el insumo de los pedidos
								
								if(($pedido_insumo->cantidad_solicitada) >= intval(($pedido_insumo->cantidad_recibida + $value['existencia']))){
									$pedido_insumo->cantidad_recibida += $value['existencia'];

									$cantidad_recibida 						= ( $value['existencia'] * $pedido_insumo->precio_unitario ) * (1.16);
									$cantidad_recibida_sin_iva				= ( $value['existencia'] * $pedido_insumo->precio_unitario );

									$pedido_insumo->monto_recibido 	  		+= $cantidad_recibida_sin_iva;
									$pedido_insumo->update();  //Actualizamos existencia y  monto de pedidos insumo

									$material_curacion_unidad_presupuesto 	+= $cantidad_recibida; //Se suma el monto de material de curazion

									if($insert_stock){
										$insert_stock->existencia += $value['existencia'];
										$insert_stock->existencia_unidosis 	= ($insert_stock->existencia * $unidosis);
										$insert_stock->save();
										
									}else{					
										$insert_stock->existencia_unidosis 	= ($value['existencia'] * $unidosis);
										$insert_stock = Stock::create($value);
										
									}
								}else{
									DB::rollBack();
									return Response::json(['error' => 'Existe un error,el insumo '.$value['clave_insumo_medico'].' se ha sobrepasado el monto solicitando, por favor contactese con soporte de la aplicación'], 500);
								}
							}
						}else{
							if($insert_stock){
								$insert_stock->existencia += 0;
								$insert_stock->save();
							}else{		
								$insert_stock = Stock::create($value);
								$insert_stock->existencia = 0;
								$insert_stock->update();
							}
						}
					}else{
						DB::rollBack();
						return Response::json(['error' => 'El medicamento con clave '.$value['clave_insumo_medico']."  con número de lote ".$value['lote']." tiene fecha de caducidad menor a 6 meses o es invalido"], 500);
					}

					$reglas_movimiento_insumos = [
						'movimiento_id'		=> 'required',
						'cantidad'        	=> 'required',
						'precio_unitario'   => 'required',
						'precio_total'     	=> 'required'
					];

					$value['precio_unitario'] 	= $lista_insumos[$value['clave_insumo_medico']]['precio'];
					$value['iva'] = 0;

					if($tipo_insumo != "ME")  //Verifico si es material de curación, para agregar el IVA
					{
						$value['iva'] = ($value['precio_unitario'] * $value['cantidad']) * (0.16);
					}	
					$value['precio_total'] 		= ($value['precio_unitario'] * $value['cantidad']);

					$value['movimiento_id'] = $movimiento->id;

					$v = Validator::make($value, $reglas_movimiento_insumos, $mensajes);

					if ($v->fails()) {
						DB::rollBack();
						return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
					}
					$value['stock_id'] = $insert_stock->id;

					$value['tipo_insumo_id'] = $tipo_insumo_id;

					if(isset($lista_insumos_db[$i])){
						$movimiento_insumo = $lista_insumos_db[$i];
						$movimiento_insumo->tipo_insumo_id = $value['tipo_insumo_id'];
						$movimiento_insumo->stock_id = $value['stock_id'];
						$movimiento_insumo->clave_insumo_medico = $value['clave_insumo_medico'];
						$movimiento_insumo->cantidad = $value['cantidad'];
						$movimiento_insumo->precio_unitario = $value['precio_unitario'];
						$movimiento_insumo->precio_total = $value['precio_total'];
						$movimiento_insumo->deleted_at = null;
						$movimiento_insumo->save();
					}else{
						$movimiento_insumo = MovimientoInsumos::create($value);	  //Aqui debo de verificar     
					}
					
					//sumas
					$movimiento_cantidad_recibida += $value['cantidad'];
					$movimiento_monto_recibido += $value['precio_total'];
					$movimiento_claves_recibidas[$value['clave_insumo_medico']] = true;
					if($tipo_insumo == 'MC'){
						$movimiento_monto_para_iva += $value['precio_total'];
					}
				}else if(isset($lista_insumos_db[$i])){
					//De lo contrario checamos si aun hay datos en la lista de registros de la base de datos y los eliminamos
					$insumos_db_a_borrar[] = $lista_insumos_db[$i]->id;
				}
			}

			if(count($insumos_db_a_borrar)){
				MovimientoInsumos::whereIn("id", $insumos_db_a_borrar)->delete();
			}
			/////######################## Termina $i
			
			if($movimiento_monto_para_iva > 0){
				$movimiento_monto_recibido += $movimiento_monto_para_iva*16/100;
			}
		     

	        if($parametros['status'] == 'FI') //Verificamos si se finaliza la recepcion (parcial o completa)
	        {
	        	$total_cantidad_solicitado  = 0;
	        	$total_monto_recibido 		= 0;
		        $total_claves_recibido 		= 0;
		        $total_cantidad_recibido 	= 0;

	        	$pedido_totales = PedidoInsumo::where("pedido_id", $pedido->id)->get(); //Recorremos los insumos del pedido para actualizar los montos (cantidad, claves montos)
	        	$validador_cantidad_solicitada = 0;
	        	foreach ($pedido_totales as $key => $value) {
	        		$validador_cantidad_solicitada += $value['cantidad_solicitada'];
	        		if($value['cantidad_recibida'] != null)
	        		{
	        			$tipo_insumo 	= $lista_insumos[$value['insumo_medico_clave']]['tipo'];
		        
	        			$total_cantidad_solicitado 	+= $value['cantidad_solicitada'];	        			
	        			$total_cantidad_recibido 	+= $value['cantidad_recibida'];
	        			if($tipo_insumo == "ME")
	        				$total_monto_recibido 		+= $value['monto_recibido'];
	        				else
	        				$total_monto_recibido 		+= ( $value['monto_recibido'] * 1.16);
	        							
	        			$total_claves_recibido++;
	        		}
	        	}

	        	$pedido->total_monto_recibido 		= $total_monto_recibido;
	        	$pedido->total_claves_recibidas 	= $total_claves_recibido;
	        	$pedido->total_cantidad_recibida 	= $total_cantidad_recibido;

	        	if($validador_cantidad_solicitada == $total_cantidad_recibido)
					$pedido->status = "FI";
					
				if($pedido->status == 'EX'){
					$pedido->recepcion_permitida = 0;
				}
	        	
	        	$pedido->update();

				if($pedido->tipo_pedido_id == 'PEA'){
					$historial->total_unidades = $movimiento_cantidad_recibida;
					$historial->total_claves = count($movimiento_claves_recibidas);
					$historial->total_monto = $movimiento_monto_recibido;
					$historial->fecha_finalizacion = Carbon::now();
					$historial->save();
				}else{
					/*Calculo de unidad presupuesto*/
					$fecha = explode('-',$pedido->fecha);
					$unidad_presupuesto = $this->obtenerDatosPresupuesto($almacen->clues,$pedido->presupuesto_id,$fecha[1],$fecha[0],$almacen->id);
	
					$unidad_presupuesto->causes_comprometido 				-= $causes_unidad_presupuesto;
					$unidad_presupuesto->causes_devengado 					+= $causes_unidad_presupuesto;
					$unidad_presupuesto->material_curacion_comprometido 	-= $material_curacion_unidad_presupuesto;
					$unidad_presupuesto->material_curacion_devengado 		+= $material_curacion_unidad_presupuesto;

					$unidad_presupuesto->insumos_comprometido 				-= ($causes_unidad_presupuesto + $material_curacion_unidad_presupuesto);
					$unidad_presupuesto->insumos_devengado 					+= ($causes_unidad_presupuesto + $material_curacion_unidad_presupuesto);
					
					$unidad_presupuesto->no_causes_comprometido 			-= $no_causes_unidad_presupuesto;
					$unidad_presupuesto->no_causes_devengado 				+= $no_causes_unidad_presupuesto;
					
					$unidad_presupuesto->update();
					/*Fin calculo de unidad presupuesto*/
				}
	        }
	        DB::commit();
	        return Response::json([ 'data' => $movimiento ],200);

	    } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        } 
    }
    
    function destroy($id){
    	 /*
		 try {
            $object = Movimiento::destroy($id);
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
		*/
    }


    private function validacion_fecha_caducidad($fecha_inicio, $fecha_validar, $caducidad){
    	if($caducidad == 1){

    		if($this->valida_fecha($fecha_validar))
    		{
    			return $this->valida_caducidad($fecha_inicio, $fecha_validar);
	   	    	
		    }else{
		    	return false;
		    }	
	    }else if($caducidad == 0)
	    {
	    	if($fecha_validar != null)
	    	{
		    	if($this->valida_fecha($fecha_validar))
	    		{
	    			return $this->valida_caducidad($fecha_inicio, $fecha_validar);
		   	   
			    }else{
			    	return false;
			    }
			}else
				return true;
    	}
	}

	private function valida_caducidad($fecha_inicio, $fecha)
	{
		$date1 = date_create($fecha);
		$fecha2 = date_create($fecha_inicio);
		$fecha2->modify("+6 month");

		$diff=date_diff($fecha2, $date1, FALSE);

		if($diff->invert == 0)
		{
			return true;
			
		}else
		{
			return false;
		}
	}


	private function valida_fecha($fecha){
		$mes = substr($fecha, 5,2);
		$dia = substr($fecha, 8,2);
		$anio = substr($fecha, 0,4);

		if(checkdate ( $mes , $dia , $anio ))
		{
			if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$fecha))
		    {
		        return true;
		    }else{
		        return false;
		    }
		}else
			return false;
		
	}

	private function limpia_espacios($cadena){
		$cadena = str_replace(' ', '', $cadena);
		return $cadena;
	}
}
