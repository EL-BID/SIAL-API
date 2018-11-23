<?php
namespace App\Http\Controllers\AlmacenGeneral;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Response;
use Illuminate\Support\Facades\Input;
use DB; 

use App\Models\AlmacenGeneral\Movimiento;
use App\Models\AlmacenGeneral\MovimientoArticulos;
use App\Models\AlmacenGeneral\MovimientoArticulosInventario;
use App\Models\AlmacenGeneral\MovimientoSalidaMetadatosAG;

use App\Models\AlmacenGeneral\Articulos;
use App\Models\AlmacenGeneral\Inventario;
use App\Models\AlmacenGeneral\InventarioArticulo;
use App\Models\AlmacenGeneral\InventarioArticuloMetadatos; 

/**
* Controlador Movimiento
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Controlador `SisMovimiento`: Manejo de usuarios del sistema
*
*/
class SalidaArticuloController extends Controller {
	/**
	 * Muestra una lista de los recurso según los parametros a procesar en la petición.
	 *
	 * <h3>Lista de parametros Request:</h3>
	 * <Ul>Paginación
	 * <Li> <code>$pagina</code> numero del puntero(offset) para la sentencia limit </ li>
	 * <Li> <code>$limite</code> numero de filas a mostrar por página</ li>	 
	 * </Ul>
	 * <Ul>Busqueda
	 * <Li> <code>$valor</code> string con el valor para hacer la busqueda</ li>
	 * <Li> <code>$order</code> campo de la base de datos por la que se debe ordenar la información. Por Defaul es ASC, pero si se antepone el signo - es de manera DESC</ li>	 
	 * </Ul>
	 *
	 * Ejemplo ordenamiento con respecto a id:
	 * <code>
	 * http://url?pagina=1&limite=5&order=id ASC 
	 * </code>
	 * <code>
	 * http://url?pagina=1&limite=5&order=-id DESC
	 * </code>
	 *
	 * Todo Los parametros son opcionales, pero si existe pagina debe de existir tambien limite
	 * @return Response 
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 404, "messages": "No hay resultados"),status) </code>
	 */
	public function index(){
		$datos = Request::all();
		
		// Si existe el paarametro pagina en la url devolver las filas según sea el caso
		// si no existe parametros en la url devolver todos las filas de la tabla correspondiente
		// esta opción es para devolver todos los datos cuando la tabla es de tipo catálogo
		if(array_key_exists("pagina", $datos)){
			$pagina = $datos["pagina"];
			if(isset($datos["order"])){
				$order = $datos["order"];
				if(strpos(" ".$order,"-"))
					$orden = "desc";
				else
					$orden = "asc";
				$order=str_replace("-", "", $order); 
			}
			else{
				$order = "id"; $orden = "desc";
			}
			
			if($pagina == 0){
				$pagina = 1;
			}
			if($pagina == 1)
				$datos["limite"] = $datos["limite"] - 1;
			// si existe buscar se realiza esta linea para devolver las filas que en el campo que coincidan con el valor que el usuario escribio
			// si no existe buscar devolver las filas con el limite y la pagina correspondiente a la paginación
			if(array_key_exists("buscar", $datos)){
				$columna = $datos["columna"];
				$valor   = $datos["valor"];
				$data = Movimiento::with("Usuario", "MovimientoSalidaMetadatosAG", "MovimientoArticulos", "TipoMovimiento", "Almacen")
				->where('tipo_movimiento_id',$datos["tipo_movimiento_id"])->orderBy($order, $orden);
				
				$search = trim($valor);
				$keyword = $search;
				$data = $data->whereNested(function($query) use ($keyword){	
						$query->Where("id", "LIKE", "%".$keyword."%")
						->orWhere("status", "LIKE", "%".$keyword."%")
						->orWhere("fecha_movimiento", "LIKE", "%".$keyword."%"); 
				});
				
				$total = $data->get();
				$data = $data->skip($pagina-1)->take($datos["limite"])->get();
			}
			else{
				$data = Movimiento::with("Usuario", "MovimientoSalidaMetadatosAG", "MovimientoArticulos", "TipoMovimiento", "Almacen")->where('tipo_movimiento_id',$datos["tipo_movimiento_id"])->skip($pagina-1)->take($datos["limite"])->orderBy($order, $orden)->get();
				$total =  Movimiento::where('tipo_movimiento_id',$datos["tipo_movimiento_id"])->get();
			}			
		}
		else{
			$data = Movimiento::select('movimientos.*','msmag.clues_destino','msmag.persona_recibe')
			->with("Usuario", "MovimientoSalidaMetadatosAG", "MovimientoArticulos", "TipoMovimiento", "Almacen");
			$data = $data->leftJoin("movimiento_salida_metadatos_ag AS msmag", "msmag.movimiento_id", "=", "movimientos.id");
			
			if ($datos["fecha_desde"]!=NULL && $datos["fecha_desde"]!='' && $datos["fecha_hasta"]!=NULL && $datos["fecha_hasta"]!='') {
				$data = $data->where('movimientos.fecha_movimiento','>=',$datos['fecha_desde'])
				->where('movimientos.fecha_movimiento','<=',$datos['fecha_hasta']);
			}

			if ($datos["clues_destino"]!=NULL && $datos["clues_destino"]!='') {
				$data = $data->where('msmag.clues_destino',$datos["clues_destino"]);
			}

			if ($datos["persona_recibe"]!=NULL && $datos["persona_recibe"]!='') {
				$keyword = $datos["persona_recibe"];
				$data = $data->whereNested(function($query) use ($keyword){	
					$query->Where("persona_recibe", "LIKE", "%".$keyword."%"); 
				});
			}

			$data = $data->where('tipo_movimiento_id',$datos["tipo_movimiento_id"])->get();
			$total = $data;
		}

		if(!$data){
			return Response::json(array("status" => 204, "messages" => "No hay resultados"),204);
		} 
		else {	
			$salidas = collect();
			
			foreach ($data as $key => $value) {
				$value->total_importe = 0; $value->total_articulos = 0;
				foreach ($value->MovimientoArticulos as $kam => $vam) {
					$value->total_importe+= $vam->importe;
					$value->total_articulos+= $vam->cantidad;
				}
				if(array_key_exists("activo_fijo", $datos) && $datos["activo_fijo"]==1){
					//	VER SI LOS ARTICULOS SON ACTIVO FIJO
					$ma = collect();
					foreach ($value->MovimientoArticulos as $keyma => $valuema) {
						if($valuema->Articulos->es_activo_fijo==1){
							$ma->push($valuema);
						}
					}

					unset($value->MovimientoArticulos);
					if(count($ma)>0)	{						
						// $salidas->push($ma);
						$value->movimiento_articulos = $ma; 
						$salidas->push($value);
					}
				} else {
					$salidas->push($value);
				}
			}				
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $salidas, "total" => count($salidas)), 200);			
		}
	}

	/**
	 * Crear un nuevo registro en la base de datos con los datos enviados
	 *
	 * <h4>Request</h4>
	 * Recibe un input request tipo json de los datos a almacenar en la tabla correspondiente
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 201, "messages": "Creado", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 500, "messages": "Error interno del servidor"),status) </code>
	 */
	public function store()
	{

	$this->ValidarParametros(Input::json()->all());	

	$data = new Movimiento;
	$success = true;
	$errors = array();
	
	DB::beginTransaction();
    try{

		$datos = (object) Input::json()->all();	

		$items_salir = 0;

		foreach ($datos->movimiento_articulos as $x => $movimiento_articulo)
		{
			$movimiento_articulo = (object) $movimiento_articulo;
			foreach ($movimiento_articulo->inventarios as $key => $inventario)
			{
				$inventario = (object) $inventario;
				if($inventario->cantidad > 0)
				{
					$items_salir ++;
				}
			}
		}
		if($items_salir == 0)
		{
			return Response::json(array("status" => 409,"messages" => "Conflicto","errors"=>array("Especifique al menos un articulo para salida !")), 200);
		}

		$array_movimientos_articulos_db = array();
		$almacen_id = Request::header("X-Almacen-Id");

        $data->almacen_id 		 			= $almacen_id;
        $data->tipo_movimiento_id	 		= property_exists($datos, "tipo_movimiento_id") 		? $datos->tipo_movimiento_id  	    : $data->tipo_movimiento_id;	       
        $data->status 			 			= property_exists($datos, "status") 					? $datos->status 					: $data->status;
        $data->fecha_movimiento		 	 	= property_exists($datos, "fecha_movimiento") 			? $datos->fecha_movimiento 			: $data->fecha_movimiento;		
		$data->observaciones 	 			= property_exists($datos, "observaciones") 				? $datos->observaciones 			: $data->observaciones;
		$data->cancelado 					= 0;
		$data->observaciones_cancelacion	= property_exists($datos, "observaciones_cancelacion")	? $datos->observaciones_cancelacion	: $data->observaciones_cancelacion;
		$data->save();

		$mms_ag = new MovimientoSalidaMetadatosAG;
		$mms_ag->movimiento_id  = $data->id;
		$mms_ag->clues_destino  = $datos->movimiento_salida_metadatos_a_g['clues_destino'];
		$mms_ag->persona_recibe = $datos->movimiento_salida_metadatos_a_g['persona_recibe'];
		$mms_ag->save();

		foreach ($datos->movimiento_articulos as $x => $movimiento_articulo)
		{
			$cantidad_articulos     = 0;
			$total_iva_articulo     = 0;
			$total_importe_articulo = 0;

			$movimiento_articulo = (object) $movimiento_articulo;
			foreach ($movimiento_articulo->inventarios as $key => $inventario)
			{
					
				$inventario = (object) $inventario;
				if($inventario->cantidad > 0)
				{
				
				$inventario_db = Inventario::find($inventario->id); 
				
				//var_dump(json_encode($inventario_db));

				if($inventario_db)
				{ 
					if($inventario_db->existencia >= $inventario->cantidad)
					 {
						$inventario_db->existencia = ($inventario_db->existencia - $inventario->cantidad);
						$inventario_db->save();

						$cantidad_articulos += $inventario->cantidad;
						$movimiento_articulo_db = MovimientoArticulos::find($inventario_db->movimiento_articulo_id);
						if($movimiento_articulo_db)
						{
							// PONER IF PARA CUANDO IVA SEA CERO Y PUEDA CAUSAR NAN ERROR
							$total_iva_articulo = ( ($movimiento_articulo_db->iva / $movimiento_articulo_db->cantidad ) * $inventario->cantidad );
							$total_importe_articulo = ( $movimiento_articulo_db->precio_unitario * $inventario->cantidad );

							$movimiento_articulo_insertar = new MovimientoArticulos();
							$movimiento_articulo_insertar->movimiento_id   = $data->id;
							$movimiento_articulo_insertar->articulo_id     = $inventario_db->articulo_id;
							$movimiento_articulo_insertar->inventario_id   = $inventario_db->id;
							$movimiento_articulo_insertar->cantidad        = $inventario->cantidad;
							$movimiento_articulo_insertar->precio_unitario = $movimiento_articulo_db->precio_unitario;
							$movimiento_articulo_insertar->iva             = $total_iva_articulo;
							$movimiento_articulo_insertar->iva_porcentaje  = $movimiento_articulo_db->iva_porcentaje;
							$movimiento_articulo_insertar->importe         = $total_importe_articulo + $total_iva_articulo;
							$movimiento_articulo_insertar->save();

							$movimiento_articulo_inventario = new MovimientoArticulosInventario();
							//$movimiento_articulo_inventario->almacen_id              = $almacen_id;
							$movimiento_articulo_inventario->movimiento_articulos_id = $movimiento_articulo_insertar->id;
							$movimiento_articulo_inventario->inventario_id           = $inventario_db->id;
							$movimiento_articulo_inventario->save();
							
							/*
							DB::table('inventario_movimiento_articulos')->insert(
													['movimiento_articulos_id' => $movimiento_articulo_insertar->id, 'inventario_id' => $inventario_db->id]
												);
												*/

						}else{
								$success = false;
								array_push($errors,"El inventario solicitado no tiene precio de compra/entrada !");
						     }
					 }else{
						     $success = false;
                             array_push($errors,"La cantidad solicitada no esta disponible en inventario !");
					      }
				}else{
					    $success = false;
						array_push($errors,"El inventario solicitado no existe !");
					 }
			    }// if cantidad_solicitada > 0

			} // fin foreach inventarios

		}// fin foreach articulos

       
        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
        } 
        if ($success){
            DB::commit();
            $data = $this->informacion($data->id);
            return Response::json(array("status" => 201,"messages" => "Creado","data" => $data), 201);
        } 
        else{
            DB::rollback();
            return Response::json(array("status" => 409,"messages" => "Conflicto","errors"=>$errors), 200);
        }
		
	}

	
	/**
	 * Actualizar el  registro especificado en el la base de datos
	 *
	 * <h4>Request</h4>
	 * Recibe un Input Request con el json de los datos
	 *
	 * @param  int  $id que corresponde al identificador del dato a actualizar 	 
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 304, "messages": "No modificado"),status) </code>
	 */
	public function update($id){
		// $this->ValidarParametros(Input::json()->all());	

		// $datos = (object) Input::json()->all();		
		// $success = false;
        
        // DB::beginTransaction();
        // try{
        // 	$data = Movimiento::find($id);

        //     if(!$data){
        //         return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        //     }
            
        //     $success = $this->campos($datos, $data);

        // } catch (\Exception $e) {
        //     DB::rollback();
        //     return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
        // } 
        // if($success){
		// 	DB::commit();
		// 	$data = $this->informacion($data->id);
		// 	return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		// } 
		// else {
		// 	DB::rollback();
		// 	return Response::json(array("status" => 304, "messages" => "No modificado"),200);
		// }
	}	

	public function campos($datos, $data){
		$success = false;
		
		$almacen_id = Request::header("X-Almacen-Id");

        $data->almacen_id 		 			= $almacen_id;
        $data->tipo_movimiento_id	 		= property_exists($datos, "tipo_movimiento_id") 		? $datos->tipo_movimiento_id  	: $data->tipo_movimiento_id;	       
        $data->status 			 			= property_exists($datos, "status") 					? $datos->status 					: $data->status;
        $data->fecha_movimiento		 	 	= property_exists($datos, "fecha_movimiento") 			? $datos->fecha_movimiento 			: $data->fecha_movimiento;		
		$data->observaciones 	 			= property_exists($datos, "observaciones") 				? $datos->observaciones 			: $data->observaciones;
		$data->cancelado 					= 0;
		$data->observaciones_cancelacion	= property_exists($datos, "observaciones_cancelacion")	? $datos->observaciones_cancelacion	: $data->observaciones_cancelacion;

        if ($data->save()) {		
        	if(property_exists($datos, "movimiento_articulos")){
        		$movimiento_articulos = array_filter($datos->movimiento_articulos, function($v){return $v !== null;});
        		MovimientoArticulos::where("movimiento_id", $data->id)->delete();
        		foreach ($movimiento_articulos as $key => $value) {
        			$value = (object) $value;
        			if($value != null){

        				DB::update("update movimiento_articulos set deleted_at = null where movimiento_id = $data->id and articulo_id = '$value->articulo_id'");
						$item = MovimientoArticulos::where("movimiento_id", $data->id)->where("articulo_id", $value->articulo_id)->first();

						if(!$item)
            				$item = new MovimientoArticulos;
            			
            			$item->movimiento_id  	= $data->id;
            			$item->articulo_id    	= property_exists($value, "articulo_id")	? $value->articulo_id	  : $item->articulo_id;
            			$item->inventario_id   	= property_exists($value, "inventario_id")	? $value->inventario_id	  : $item->inventario_id;
            			$item->cantidad    		= property_exists($value, "cantidad")		? $value->cantidad		  : $item->cantidad;
            			$item->precio_unitario 	= property_exists($value, "precio_unitario")? $value->precio_unitario : $item->precio_unitario;
						$item->iva    			= property_exists($value, "iva")			? $value->iva			  : $item->iva;
						$item->iva_porcentaje   = property_exists($value, "iva_porcentaje")	? $value->iva_porcentaje  : $item->iva_porcentaje;
            			$item->importe 			= property_exists($value, "importe")		? $value->importe		  : $item->importe;

            			if($item->save()){				        		
			        		DB::table('inventario_movimiento_articulos')->where('movimiento_articulos_id', $item->id)->delete();					        		
	        				$inventario = InventarioArticulo::where("articulo_id", $item->articulo_id)
									        				->where("numero_inventario", $value->numero_inventario)
									        				->first();				        									        					
        					if($inventario){				        					     
	            				$inventario->existencia = $inventario->existencia - $value->cantidad;		            							 		
	            				if($inventario->save()){
									$ma = MovimientoArticulos::find($item->id);
									if($ma){					            			
											$ma->inventario_id = $inventario->id;
											$ma->save();						            			
									}
	            					DB::table('inventario_movimiento_articulos')->insert(
									    ['movimiento_articulos_id' => $item->id, 'inventario_id' => $inventario->id]
									);
    								$success = true;		            			
	            				}	
            				}			        			
        				}	        			
            		}
        		}
        	}        	
			$success = true;
		}  
		return $success;     						
	}

	public function informacion($id){
		$data = Movimiento::with("Usuario", "MovimientoArticulos", "TipoMovimiento", "Almacen")->find($id);				
		return $data;
	}

	/**
	 * Devuelve la información del registro especificado.
	 *
	 * @param  int  $id que corresponde al identificador del recurso a mostrar
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 404, "messages": "No hay resultados"),status) </code>
	 */
	public function show($id)
	{
		//$data = Movimiento::with("Usuario",'MovimientoArticulos', "TipoMovimiento", "Almacen")->find($id);
		$data = Movimiento::with("MovimientoSalidaMetadatosAG","Usuario", "TipoMovimiento", "Almacen")->find($id);
		if(!$data){
			return Response::json(array("status"=> 404,"messages" => "No hay resultados"),404);
		}	

		$subtotal = 0;
		$iva      = 0;
		$total    = 0;

		$movimiento_articulos_response = array();
		$movimiento_articulos = MovimientoArticulos::where('movimiento_id',$data->id)->groupBy('articulo_id')->get();

		foreach ($movimiento_articulos as $x => $ma)
		{
			$ma = (object) $ma;
			$articulo = Articulos::with('Categoria','ArticulosMetadatos')->find($ma->articulo_id);
			$ma->articulos = $articulo;

			$mas = MovimientoArticulos::where('movimiento_id',$data->id)->where('articulo_id',$ma->articulo_id)->get();
			$inventarios = array();

 			$iva_total     = 0;
			$importe_total = 0;

			foreach ($mas as $y => $ma_individual)
			{
				$ma_individual = (object) $ma_individual;
				$mai = MovimientoArticulosInventario::where('movimiento_articulos_id',$ma_individual->id)->first();

				$inventario_individual = Inventario::with('Articulo','InventarioMetadatoUnico','Programa')->find($mai->inventario_id);
				$inventario_individual->cantidad            = $ma_individual->cantidad;
				$inventario_individual->movimiento_articulo = $ma_individual;

 				$iva_total     += $ma_individual->iva;
				$importe_total += $ma_individual->importe;

				$subtotal += $ma_individual->precio_unitario;
				$iva      += $ma_individual->iva;
				$total    += $ma_individual->importe;

				array_push($inventarios,$inventario_individual);
			}

			$ma->iva         = $iva_total;
			$ma->importe     = $importe_total; 
			$ma->inventarios = $inventarios;
			array_push($movimiento_articulos_response,$ma);	
		}	

		$data->movimiento_articulos = $movimiento_articulos_response;
		$data->subtotal = $subtotal;
		$data->iva      = $iva;
		$data->total    = $total;		
		
		return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		
	}
	
	/**
	 * Elimine el registro especificado del la base de datos (softdelete).
	 *
	 * @param  int  $id que corresponde al identificador del dato a eliminar
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 500, "messages": "Error interno del servidor"),status) </code>
	 */
	public function destroy($id){
		$success = false;
        DB::beginTransaction();
        try {
			$data = Movimiento::find($id);
			$grupos = $data->Grupos();
			if(count($grupos)>0){
				foreach ($grupos as $grupo) {
					$data->removeGroup($grupo);				
				}
			}
			$data->delete();
			
			$success=true;
		} 
		catch (\Exception $e) {
			return Response::json($e->getMessage(), 500);
        }
        if ($success){
			DB::commit();
			return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data), 200);
		} 
		else {
			DB::rollback();
			return Response::json(array("status" => 404, "messages" => "No se encontro el registro"), 404);
		}
	}	

	/**
	 * Validad los parametros recibidos, Esto no tiene ruta de acceso es un metodo privado del controlador.
	 *
	 * @param  Request  $request que corresponde a los parametros enviados por el cliente
	 *
	 * @return Response
	 * <code> Respuesta Error json con los errores encontrados </code>
	 */
	private function ValidarParametros($request){
		$rules = [
			"total" => "required"
		];
		$v = \Validator::make(Request::json()->all(), $rules );

		if ($v->fails()){
			return Response::json($v->errors());
		}
	}
}