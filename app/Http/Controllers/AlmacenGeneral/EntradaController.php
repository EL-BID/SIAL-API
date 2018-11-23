<?php

namespace App\Http\Controllers\v1\AlmacenGeneral;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Response;
use Input;
use DB; 
use DNS1D;

use App\Models\AlmacenGeneral\Movimientos;
use App\Models\AlmacenGeneral\MovimientosArticulos;

use App\Models\AlmacenGeneral\Inventario;
use App\Models\AlmacenGeneral\InventarioMetadatos;

/**
* Controlador Movimientos
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Controlador `SisMovimientos`: Manejo de usuarios del sistema
*
*/
class EntradaController extends Controller {
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
				$data = Movimientos::with("Usuarios", "MovimientosArticulos", "TiposMovimientos")
				->where('tipos_movimientos_id',$datos["tipos_movimientos_id"])->orderBy($order, $orden);
				
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
				$data = Movimientos::with("Usuarios", "MovimientosArticulos", "TiposMovimientos")->where('tipos_movimientos_id',$datos["tipos_movimientos_id"])->skip($pagina-1)->take($datos["limite"])->orderBy($order, $orden)->get();
				$total =  Movimientos::where('tipos_movimientos_id',$datos["tipos_movimientos_id"])->get();
			}			
		}
		else{
			$data = Movimientos::with("Usuarios", "MovimientosArticulos", "TiposMovimientos")->where('tipos_movimientos_id',$datos["tipos_movimientos_id"])->get();
			$total = $data;
		}

		if(!$data){
			return Response::json(array("status" => 204, "messages" => "No hay resultados"),204);
		} 
		else {				
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data, "total" => count($total)), 200);			
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
	public function store(){
		$this->ValidarParametros(Input::json()->all());			
		$datos = (object) Input::json()->all();	
		$success = false;

        DB::beginTransaction();
        try{
            $data = new Movimientos;
            $success = $this->campos($datos, $data);

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
            return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
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
		$this->ValidarParametros(Input::json()->all());	

		$datos = (object) Input::json()->all();		
		$success = false;
        
        DB::beginTransaction();
        try{
        	$data = Movimientos::find($id);

            if(!$data){
                return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
            }
            
            $success = $this->campos($datos, $data);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
        } 
        if($success){
			DB::commit();
			$data = $this->informacion($data->id);
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		} 
		else {
			DB::rollback();
			return Response::json(array("status" => 304, "messages" => "No modificado"),200);
		}
	}

	public function campos($datos, $data){
		$success = false;
		
		$almacen_id = Request::header("X-Almacen-Id");

        $data->almacen_id 		 			= $almacen_id;
        $data->tipos_movimientos_id	 		= property_exists($datos, "tipos_movimientos_id") 		? $datos->tipos_movimientos_id  	: $data->tipos_movimientos_id;	       
        $data->status 			 			= property_exists($datos, "status") 					? $datos->status 					: $data->status;
        $data->fecha_movimiento		 	 	= property_exists($datos, "fecha_movimiento") 			? $datos->fecha_movimiento 			: $data->fecha_movimiento;		
		$data->observaciones 	 			= property_exists($datos, "observaciones") 				? $datos->observaciones 			: $data->observaciones;
		$data->cancelado 					= property_exists($datos, "cancelado")					? $datos->cancelado					: $data->cancelado;
		$data->observaciones_cancelacion	= property_exists($datos, "observaciones_cancelacion")	? $datos->observaciones_cancelacion	: $data->observaciones_cancelacion;

        if ($data->save()) {		
        	if(property_exists($datos, "movimientos_articulos")){
        		$movimientos_articulos = array_filter($datos->movimientos_articulos, function($v){return $v !== null;});
        		MovimientosArticulos::where("movimientos_id", $data->id)->delete();
        		foreach ($movimientos_articulos as $key => $value) {
        			$value = (object) $value;
        			if($value != null){

        				DB::update("update movimientos_articulos set deleted_at = null where movimientos_id = $data->id and articulos_id = '$value->articulos_id'");
						$item = MovimientosArticulos::where("movimientos_id", $data->id)->where("articulos_id", $value->articulos_id)->first();

						if(!$item)
            				$item = new MovimientosArticulos;
            			
            			$item->movimientos_id  	= $data->id;
            			$item->articulos_id    	= property_exists($value, "articulos_id")	? $value->articulos_id	  : $item->articulos_id;
            			$item->inventario_id   	= property_exists($value, "inventario_id")	? $value->inventario_id	  : $item->inventario_id;
            			$item->cantidad    		= property_exists($value, "cantidad")		? $value->cantidad		  : $item->cantidad;
            			$item->precio_unitario 	= property_exists($value, "precio_unitario")? $value->precio_unitario : $item->precio_unitario;
            			$item->iva    			= property_exists($value, "iva")			? $value->iva			  : $item->iva;
            			$item->importe 			= property_exists($value, "importe")		? $value->importe		  : $item->importe;

            			if($item->save()){
	        				if(property_exists($value, "inventarios") && count($value->inventarios) > 0) {
				        		$inventarios = array_filter($value->inventarios, function($v){return $v !== null;});
				        		Inventario::where("movimiento_articulos_id", $item->id)->delete();
				        		foreach ($inventarios as $invk => $invv) {
				        			$invv = (object) $invv;
				        			if($invv != null){
				        				DB::update("update inventario set deleted_at = null where movimiento_articulos_id = $item->id and articulo_id = '$item->articulos_id' and numero_inventario = '$invv->numero_inventario'");
				        				$inventario = Inventario::where("movimiento_articulos_id", $item->id)->where("articulo_id", $item->articulo_id)->where("numero_inventario", $invv->numero_inventario)->first();				        									        					
			        					if(!$inventario)
			        						$inventario = new Inventario;					        			
				        				
			            				$inventario->movimiento_articulos_id = $item->id;
			            				$inventario->articulo_id 			 = $item->articulo_id;
			            				$inventario->existencia 			 = 1;
			            				$inventario->numero_inventario		 = property_exists($invv, "numero_inventario")	? $invv->numero_inventario	: $almacen_id.'-'.time();
			            				$inventario->observaciones 			 = property_exists($invv, "observaciones")		? $invv->observaciones 		: $item->observaciones;
			            				$inventario->baja 			 		 = property_exists($invv, "baja")				? $invv->baja		  		: $item->baja;

			            				if($inventario->save()){
	        								$ma = MovimientosArticulos::find($item->id);
	        								if($ma){
					            			
					            			$ma->inventarios_id = $inventario->id;
					            			$ma->save();

					            			if(property_exists($invv, "inventarios") && count($invv->inventarios) > 0) {
							        		$inve_meta = array_filter($invv->inventarios, function($v){return $v !== null;});
							        		InventarioMetadatos::where("inventario_id", $inventario->id)->delete();
							        		foreach ($inve_meta as $invmk => $invmv) {
							        			$invmv = (object) $invmv;
							        			if($invmv != null){	
							        				DB::update("update inventario_metadatos set deleted_at = null where inventario_id = $inventario->id and campo = '$invmv->campo'");
							        				$inventario_m = InventarioMetadatos::where("inventario_id", $inventario->id)->where("campo", $invmv->campo)->first();				        									        					
						        					if(!$inventario_m)
						        						$inventario_m = new InventarioMetadatos;

						        					$inventario_m->campo = $invmv->campo;
						        					$inventario_m->valor = $invmv->valor;

						        					$inventario_m->save();
							        			}
							        		}				            				
			            				}
				        			}
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

	public function devolucion(){
		$this->ValidarParametros(Input::json()->all());			
		$datos = (object) Input::json()->all();	
		$success = false;

        DB::beginTransaction();
        try{
				
			$sucursales_id = Request::header("sucursal");
			$cajas_id 	   = Request::header("caja");
			$cajas_id      = $cajas_id != "" ? $cajas_id : null;

			$data = new Movimientos;

			$codigo = strtoupper(substr( md5(microtime()), 3, 8));
			$codigo = $datos->tipos_movimientos_id.date("Y").str_pad($data->id, 6, "0", STR_PAD_LEFT).$codigo;			

	        $data->sucursales_id 		 = $sucursales_id;
	        $data->codigo 			 	 = $codigo;

	        $data->folio 			 	 = '';
	        $data->tipos_movimientos_id	 = property_exists($datos, "tipos_movimientos_id") 	? $datos->tipos_movimientos_id  : $data->tipos_movimientos_id;	       
	        $data->personas_id			 = property_exists($datos, "personas_id") 			? $datos->personas_id > 0 ? $datos->personas_id : null : $data->personas_id;           
			$data->total 	 			 = property_exists($datos, "total") 				? $datos->temp_total - $datos->total	: $data->total;
			$data->status_movimientos_id = property_exists($datos, "status_movimientos_id")	? $datos->status_movimientos_id	: $data->status_movimientos_id;
			$data->comentarios			 = property_exists($datos, "comentarios") 			? $datos->comentarios			: $data->comentarios;

	        if ($data->save()) {

	        	$tipo_movimiento = TiposMovimientos::find($data->tipos_movimientos_id);

	        	$devolucion = new Devoluciones;

	        	$devolucion->movimientos_id = $datos->id;
	        	$devolucion->movimientos_id_devolucion = $data->id;
	        	$devolucion->tipo = $tipo_movimiento->tipo;

	        	if($devolucion->save()){
		        	$success = false;
		        	if(property_exists($datos, "movimientos_articulos")){
		        		$movimientos_articulos = array_filter($datos->movimientos_articulos, function($v){return $v !== null;});
		        		foreach ($movimientos_articulos as $key => $value) {
		        			$value = (object) $value;
		        			if($value != null){
		        				if($value->cantidad_devolucion > 0){
			        				$item = new MovimientosArticulos;
							            			
			            			$item->movimientos_id  		= $data->id;
			            			$item->articulos_id    		= $value->articulos_id;
			            			$item->cantidad    			= $value->cantidad_devolucion;
			            			$item->precio_unitario 		= $value->precio_unitario;
			            			if(property_exists($value, "precio_con_descuento"))
			            				$item->precio_con_descuento = $value->precio_con_descuento; 
			            			if(property_exists($value, "precio_con_impuestos"))           			 
			            				$item->precio_con_impuestos = $value->precio_con_impuestos;
			            			$item->articulos_precios_id	= $value->articulos_precios_id;
			            			$item->importe 	   			= $value->cantidad_devolucion * $value->precio_unitario;
			            			$item->inventarios_id			= $value->inventarios_id;

			            			if($item->save()){
			            				$item_mov = MovimientosArticulos::find($value->id);

			            				$item_mov->cantidad = $item_mov->cantidad-$value->cantidad_devolucion;
			            				$item->importe 	    = $value->importe;

			            				if($item_mov->save()){
			            					$inventario_mov = Inventario::find($value->inventarios_id);
			            					if($tipo_movimiento->tipo == "S"){
				            					$inventario_mov->existencia = $inventario_mov->existencia - $value->cantidad_devolucion;            					
				            				}
				            				else{
				            					$inventario_mov->existencia = $inventario_mov->existencia + $value->cantidad_devolucion;            								            						            				
				            				}
				            				$inventario_mov->save();				            				
			            				}
			            			}
			            		}
			            	}
		        		}
		        		$success = true;
		        	}
		        	
		        	if(property_exists($datos, "pagos")){
		        		$success = false;
						$pagos = array_filter($datos->pagos, function($v){return $v !== null;});
						Pagos::where("movimientos_id", $data->id)->delete();
						foreach ($pagos as $key => $value) {
		        			$value = (object) $value;
		        			if($value != null){		        			
		        				DB::update("update pagos set deleted_at = null where movimientos_id = $data->id and cajas_operaciones_id = '$cajas_id' and importe = '$value->importe' ");
		        				$pago = Pagos::where("movimientos_id", $data->id)->where("cajas_operaciones_id", $cajas_id)->where("importe", $value->importe)->first();

		        				if(!$pago)
		        					$pago = new Pagos;

		        				$pago->cajas_operaciones_id	  	= $cajas_id;
		        				$pago->movimientos_id 		  	= $data->id;
		        				$pago->tipos_metodos_pagos_id 	= $value->tipos_metodos_pagos_id;
		        				$pago->importe 					= $value->cambio;
		        				$pago->paga_con 				= $value->cambio;
		        				$pago->cambio 					= 0;
		        				$pago->comentarios 				= "Devolución";

		        				if($pago->save()){
		        					if(property_exists($value, "pagos_tarjetas")){
		        						$tarjeta = new PagosTarjetas;

		        						$tarjeta->pagos_id 	= $pago->id;
		        						$tarjeta->banco 	= $value->pagos_tarjetas->banco;
		        						$tarjeta->folio 	= $value->pagos_tarjetas->folio;
		        						$tarjeta->save();
		        					}
		        				}
		        			}
		        		}
		        		$success = true;
					}
	        	}
			}  
		} catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
        } 
        if($success){
			DB::commit();
			$data = $this->informacion($data->id);
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		} 
		else {
			DB::rollback();
			return Response::json(array("status" => 304, "messages" => "No modificado"),200);
		}     						
	}

	public function informacion($id){
		$data = Movimientos::with("Personas", "Usuarios", "MovimientosArticulos", "TiposMovimientos", "Pagos")->find($id);			
		$data->barcode = DNS1D::getBarcodePNG($data->codigo, "C128");
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
	public function show($id){
		$data = Movimientos::with("Usuarios", "MovimientosArticulos", "TiposMovimientos")->find($id);			
		
		if(!$data){
			return Response::json(array("status"=> 404,"messages" => "No hay resultados"),404);
		} 
		else {	
			$data->barcode = DNS1D::getBarcodePNG($data->codigo, "C128");
			foreach ($data->MovimientosArticulos as $key => $value) {
				$value->cantidad_devolucion = null;
			}
			$data->temp_total = $data->total;
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		}
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
			$data = Movimientos::find($id);
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