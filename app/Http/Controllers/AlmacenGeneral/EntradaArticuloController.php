<?php
namespace App\Http\Controllers\AlmacenGeneral;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Response;
use Illuminate\Support\Facades\Input;
use DB; 

use App\Models\AlmacenGeneral\Articulos;
use App\Models\AlmacenGeneral\ArticulosMetadatos;
use App\Models\AlmacenGeneral\Movimiento;
use App\Models\AlmacenGeneral\MovimientoArticulos;
use App\Models\AlmacenGeneral\MovimientoArticulosBorrador;
use App\Models\AlmacenGeneral\MovimientoArticulosInventario;
use App\Models\AlmacenGeneral\MovimientoArticulosInventarioBorrador;
use App\Models\AlmacenGeneral\MovimientoEntradaMetadatosAG;

use App\Models\AlmacenGeneral\Inventario;
use App\Models\AlmacenGeneral\InventarioBorrador;
use App\Models\AlmacenGeneral\InventarioArticulo;
use App\Models\AlmacenGeneral\InventarioMetadato;
use App\Models\AlmacenGeneral\InventarioMetadatoBorrador;
use App\Models\AlmacenGeneral\InventarioArticuloMetadatos;

/**
* Controlador Movimiento
* 
* @package    Plataforma API
* @subpackage Controlador
* @author      
* @created     
*
*  
*
*/
class EntradaArticuloController extends Controller {
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
				$data = Movimiento::with("Programa", "Usuario", "MovimientoEntradaMetadatosAG", "MovimientoArticulos", "TipoMovimiento", "Almacen")
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
				$data = Movimiento::with("Programa", "Usuario", "MovimientoEntradaMetadatosAG", "MovimientoArticulos", "TipoMovimiento", "Almacen")->where('tipo_movimiento_id',$datos["tipo_movimiento_id"])->skip($pagina-1)->take($datos["limite"])->orderBy($order, $orden)->get();
				$total =  Movimiento::where('tipo_movimiento_id',$datos["tipo_movimiento_id"])->get();
			}			
		}
		else{
			$data = Movimiento::select('movimientos.*','memag.donacion','memag.donante','memag.numero_pedido','memag.fecha_referencia','memag.folio_factura','memag.proveedor_id')
			->with("Programa", "Usuario", "MovimientoEntradaMetadatosAG", "MovimientoArticulos", "TipoMovimiento", "Almacen");
			
			$data = $data->leftJoin("movimiento_entrada_metadatos_ag AS memag", "memag.movimiento_id", "=", "movimientos.id");
				
			if ($datos["programa_id"]!=NULL && $datos["programa_id"]!='') {
				$data = $data->where('movimientos.programa_id',$datos["programa_id"]);
			}

			if ($datos["fecha_desde"]!=NULL && $datos["fecha_desde"]!='' && $datos["fecha_hasta"]!=NULL && $datos["fecha_hasta"]!='') {
				$data = $data->where('movimientos.fecha_movimiento','>=',$datos['fecha_desde'])
				->where('movimientos.fecha_movimiento','<=',$datos['fecha_hasta']);
			}

			if ($datos["proveedor_id"]!=NULL && $datos["proveedor_id"]!='') {
				$data = $data->where('memag.proveedor_id',$datos["proveedor_id"]);
			}

			if ($datos["donacion"]==true && $datos["donacion"]=='true') {
				$data = $data->where('memag.donacion',1);
				if ($datos["donante"]!=NULL && $datos["donante"]!='') {
					$keyword = $datos["donante"];
					$data = $data->whereNested(function($query) use ($keyword){	
						$query->Where("donante", "LIKE", "%".$keyword."%"); 
					});
				}
			}

			$data = $data->where('movimientos.tipo_movimiento_id',$datos["tipo_movimiento_id"])
			->get();
			$total = $data;
		}

		if(!$data){
			
			return Response::json(array("status" => 204, "messages" => "No hay resultados"),204);
		} else {	
			foreach ($data as $key => $value) {
				$value->total_importe = 0; $value->total_articulos = 0;
				foreach ($value->MovimientoArticulos as $kam => $vam) {
					$value->total_importe+= $vam->importe;
					$value->total_articulos+= $vam->cantidad;
				}
			}			
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
	public function store()
	{
		//$this->ValidarParametros(Input::json()->all());			  
		$datos = (object) Input::json()->all();	
		$success = false;
		DB::beginTransaction();
        try{
				$almacen_id = Request::header("X-Almacen-Id");

				$programa_id = $datos->programa_id;
				if ($datos->programa_id==NULL || $datos->programa_id=="")
					$programa_id = NULL;

				$data = new Movimiento;
				$data->almacen_id 		 			= $almacen_id;
				$data->tipo_movimiento_id	 		= property_exists($datos, "tipo_movimiento_id") 		? $datos->tipo_movimiento_id  	: $data->tipo_movimiento_id;	       
				$data->status 			 			= property_exists($datos, "status") 					? $datos->status 					: $data->status;
				$data->fecha_movimiento		 	 	= property_exists($datos, "fecha_movimiento") 			? $datos->fecha_movimiento 			: $data->fecha_movimiento;		
				$data->observaciones 	 			= property_exists($datos, "observaciones") 				? $datos->observaciones 			: $data->observaciones;
				$data->programa_id 					= $programa_id;
				$data->cancelado 					= 0;
				$data->observaciones_cancelacion	= property_exists($datos, "observaciones_cancelacion")	? $datos->observaciones_cancelacion	: $data->observaciones_cancelacion;
				$data->save();

				$proveedor_id = $datos->movimiento_entrada_metadatos_a_g['proveedor_id'];
				if ($datos->movimiento_entrada_metadatos_a_g['proveedor_id']==NULL || $datos->movimiento_entrada_metadatos_a_g['proveedor_id']=="")
					$proveedor_id = NULL;

				$mme_ag = new MovimientoEntradaMetadatosAG;
				$mme_ag->movimiento_id    = $data->id;
				$mme_ag->donacion         = $datos->movimiento_entrada_metadatos_a_g['donacion']; 
				$mme_ag->donante          = $datos->movimiento_entrada_metadatos_a_g['donante'];
				$mme_ag->numero_pedido    = $datos->movimiento_entrada_metadatos_a_g['numero_pedido']; 
				$mme_ag->fecha_referencia = $datos->movimiento_entrada_metadatos_a_g['fecha_referencia'];
				$mme_ag->folio_factura    = $datos->movimiento_entrada_metadatos_a_g['folio_factura'];
				$mme_ag->proveedor_id     = $proveedor_id;
				$mme_ag->persona_entrega  = $datos->movimiento_entrada_metadatos_a_g['persona_entrega']; 
				$mme_ag->save();

				$success = true;
			

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
	public function update($id)
	{
		$this->ValidarParametros(Input::json()->all());
		$usuario_id_peticion = Request::header("X-Usuario-Id"); 	

		$datos = (object) Input::json()->all();		
		$success = false;
        
        DB::beginTransaction();
        try{
         	 $data = Movimiento::find($id);
			 if(!$data)
			 {
                 return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
             }
			 	//$success = $this->campos($datos, $data);
			 
			 	$success = false;
		
				$almacen_id = Request::header("X-Almacen-Id");    
				$programa_id = $datos->programa_id;
				if ($datos->programa_id==NULL || $datos->programa_id==""){ $programa_id = NULL; }

				// Guardado del movimiento	
				$data->almacen_id 		 			= $almacen_id;
				$data->tipo_movimiento_id	 		= property_exists($datos, "tipo_movimiento_id") 		? $datos->tipo_movimiento_id  	: $data->tipo_movimiento_id;	       
				$data->status 			 			= property_exists($datos, "status") 					? $datos->status 					: $data->status;
				$data->fecha_movimiento		 	 	= property_exists($datos, "fecha_movimiento") 			? $datos->fecha_movimiento 			: $data->fecha_movimiento;		
				$data->observaciones 	 			= property_exists($datos, "observaciones") 				? $datos->observaciones 			: $data->observaciones;
				$data->programa_id 					= $programa_id;
				$data->cancelado 					= 0;
				$data->observaciones_cancelacion	= property_exists($datos, "observaciones_cancelacion")	? $datos->observaciones_cancelacion	: $data->observaciones_cancelacion;
				$data->save();

				$proveedor_id = $datos->movimiento_entrada_metadatos_a_g['proveedor_id'];
				if ($datos->movimiento_entrada_metadatos_a_g['proveedor_id']==NULL || $datos->movimiento_entrada_metadatos_a_g['proveedor_id']=="")
					$proveedor_id = NULL;

				// Guardado de movimiento_metadatos_ag
				$mome_ag = new MovimientoEntradaMetadatosAG;
				$mome_ag->movimiento_id    = $data->id;
				$mome_ag->donacion         = $datos->movimiento_entrada_metadatos_a_g['donacion']; 
				$mome_ag->donante          = $datos->movimiento_entrada_metadatos_a_g['donante'];
				$mome_ag->numero_pedido    = $datos->movimiento_entrada_metadatos_a_g['numero_pedido']; 
				$mome_ag->fecha_referencia = $datos->movimiento_entrada_metadatos_a_g['fecha_referencia'];
				$mome_ag->folio_factura    = $datos->movimiento_entrada_metadatos_a_g['folio_factura'];
				$mome_ag->proveedor_id     = $proveedor_id;
				$mome_ag->persona_entrega  = $datos->movimiento_entrada_metadatos_a_g['persona_entrega']; 
				$mome_ag->save();

			 if($datos->status == "BR")
			 {

				$movimiento_articulos = array_filter($datos->movimiento_articulos, function($v){return $v !== null;});
				MovimientoArticulos::where("movimiento_id", $data->id)->where('usuario_id',$usuario_id_peticion)->delete();
				foreach ($movimiento_articulos as $key => $movimiento_articulo)
				{
					$movimiento_articulo = (object) $movimiento_articulo;
					$movimiento_articulo_db = NULL;

					if($mov_articulo->id == "")
					{	$movimiento_articulo_db = new MovimientoArticulos();
					}else{	$movimiento_articulo_db = MovimientoArticulos::onlyTrashed()->find($movimiento_articulo->id); }

					$movimiento_articulo_db->movimiento_id  	= $data->id;
					$movimiento_articulo_db->articulo_id    	= property_exists($movimiento_articulo, "articulo_id")	   ? $movimiento_articulo->articulo_id	   : $movimiento_articulo_db->articulo_id;
					$movimiento_articulo_db->inventario_id   	= property_exists($movimiento_articulo, "inventario_id")   ? $movimiento_articulo->inventario_id   : $movimiento_articulo_db->inventario_id;
					$movimiento_articulo_db->cantidad    		= property_exists($movimiento_articulo, "cantidad")		   ? $movimiento_articulo->cantidad		   : $movimiento_articulo_db->cantidad;
					$movimiento_articulo_db->precio_unitario 	= property_exists($movimiento_articulo, "precio_unitario") ? $movimiento_articulo->precio_unitario : $movimiento_articulo_db->precio_unitario;
					$movimiento_articulo_db->iva    			= property_exists($movimiento_articulo, "iva")			   ? $movimiento_articulo->iva			   : $movimiento_articulo_db->iva;
					$movimiento_articulo_db->iva_porcentaje     = property_exists($movimiento_articulo, "iva_porcentaje")  ? $movimiento_articulo->iva_porcentaje  : $movimiento_articulo_db->iva_porcentaje;
					$movimiento_articulo_db->importe 			= property_exists($movimiento_articulo, "importe")		   ? $movimiento_articulo->importe		   : $movimiento_articulo_db->importe;
					$movimiento_articulo_db->save();

					$articulo = Articulos::find($movimiento_articulo->articulo_id);

					$inventarios = array_filter($movimiento_articulo->inventarios, function($v){return $v !== null;});
					Inventario::where("movimiento_articulo_id", $movimiento_articulo_db->id)
							  ->where('usuario_id',$usuario_id_peticion)
							  ->delete();

					DB::table('inventario_movimiento_articulos')
					  ->where('movimiento_articulos_id', $movimiento_articulo_db->id)
					  ->where('usuario_id',$usuario_id_peticion)
					  ->delete();

					$consecutivo = 0;

					foreach ($inventarios as $invk => $inventario)
					{
						$consecutivo++;
						$inventario = (object) $inventario;
						Inventario::withTrashed()
								  ->where('movimiento_articulo_id',$movimiento_articulo_db->id)
								  ->where('articulo_id',$movimiento_articulo_db->articulo_id)
								  ->where('numero_inventario',$inventario->numero_inventario)
								  ->where('usuario_id',$usuario_id_peticion)
								  ->restore();

						//DB::update("update inventario set deleted_at = null where movimiento_articulo_id = $item->id and articulo_id = '$item->articulo_id' and numero_inventario = '$inventario->numero_inventario'");
						$inventario_db = InventarioArticulo::where("movimiento_articulo_id", $movimiento_articulo_db->id)
														->where("articulo_id", $movimiento_articulo_db->articulo_id)
														->where("numero_inventario", $inventario->numero_inventario)
														->first();				        									        					
						if(!$inventario_db)
							$inventario_db = new InventarioArticulo;					        			
											
						$inventario_db->almacen_id 	 		    = $almacen_id;
						$inventario_db->movimiento_articulo_id  = $item->id;
						$inventario_db->programa_id             = $programa_id;
						$inventario_db->articulo_id 			= $item->articulo_id;
						$inventario_db->existencia 			    = $value->cantidad;
						if( $articulo->es_activo_fijo == 1 )
							$inventario_db->existencia 		 = 1;
						$inventario_db->primera_vez_inventario   = property_exists($inventario, "primera_vez_inventario")		? $inventario->primera_vez_inventario 								: $item->primera_vez_inventario;
						$inventario_db->numero_inventario		 = property_exists($inventario, "numero_inventario")			? $inventario->numero_inventario != '' ?  $inventario->numero_inventario 	: $almacen_id.'-'.time().'-'.$consecutivo.$this->getRandomString(2) 			: $almacen_id.'-'.time().'-'.$consecutivo.$this->getRandomString(2);
						$inventario_db->observaciones 			 = property_exists($inventario, "observaciones")				? $inventario->observaciones 											: $item->observaciones;
						$inventario_db->lote  					 = property_exists($inventario, "lote")						? $inventario->lote 													: $item->lote;
						$inventario_db->fecha_caducidad  		 = property_exists($inventario, "fecha_caducidad")			? $inventario->fecha_caducidad 										: $item->fecha_caducidad;
						$inventario_db->es_patrimonio  		     = property_exists($inventario, "es_patrimonio")				? $inventario->es_patrimonio 											: $item->es_patrimonio;
						$inventario_db->baja 			 		 = 0;
						$inventario_db->save();

						$movimiento_articulo_inventariox = new MovimientoArticulosInventario();
						$movimiento_articulo_inventariox->movimiento_articulos_id = $item->id;
						$movimiento_articulo_inventariox->inventario_id           = $inventario_db->id;
						$movimiento_articulo_inventariox->save();

						//InventarioArticuloMetadatos::where("inventario_id", $inventario->id)->delete();

						$inve_meta = array_filter($inventario->inventario_metadato, function($v){return $v !== null;});
						InventarioArticuloMetadatos::where("inventario_id", $inventario_db->id)->delete();

						//foreach ($inve_meta as $invmk => $invmv) 
						foreach ($inve_meta as $invmk => $inventario_metadato) 
						{
							$inventario_metadato = (object) $inventario_metadato;
							if($inventario_metadato != null)
							{
								if ($inventario_metadato->requerido_inventario==1)
								{
									//DB::update("update inventario_metadatos set deleted_at = null where inventario_id = $inventario->id and campo = '$invmv->campo'");
									InventarioArticuloMetadatos::withTrashed()
																->where('inventario_id',$inventario_db->id)
																->where('campo',$inventario_metadato->campo)
																->restore();

									$inventario_metadatos_db = InventarioArticuloMetadatos::where("inventario_id", $inventario_db->id)
																						  ->where("campo", $inventario_metadato->campo)
																						  ->first();				        									        					
									if(!$inventario_metadatos_db)
										$inventario_metadatos_db = new InventarioArticuloMetadatos;
			
									$inventario_metadatos_db->inventario_id = $inventario_db->id;
									$inventario_metadatos_db->metadatos_id  = $inventario_metadato->metadatos_id;
									$inventario_metadatos_db->campo         = $inventario_metadato->campo;
									$inventario_metadatos_db->valor         = $inventario_metadato->valor;
									$inventario_metadatos_db->save();
																																								
									$success = true;												            	
									 
								} else {
											$success = true;	
									   }							
							}
						}	
													
					}// FIN FOREACH inventarios
					
				} // FIN FOREACH movimiento_articulos 

				
			 }else{

			      }

            } catch (\Exception $e) {
             							DB::rollback();
             							return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
									 } 
									 
		 if($success)
		 {
		 	DB::commit();
		 	$data = $this->informacion($data->id);
		 	return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		 }else{
		 		DB::rollback();
		 		return Response::json(array("status" => 304, "messages" => "No modificado"),200);
		 	  }
	}

////*****************************************************************************************************************************/
////*****************************************************************************************************************************/

















	public function campos($datos, $data)
	{ 
		$success = false;
		
		$almacen_id = Request::header("X-Almacen-Id");    

		$programa_id = $datos->programa_id;
		if ($datos->programa_id==NULL || $datos->programa_id=="")
			$programa_id = NULL;

        $data->almacen_id 		 			= $almacen_id;
        $data->tipo_movimiento_id	 		= property_exists($datos, "tipo_movimiento_id") 		? $datos->tipo_movimiento_id  	: $data->tipo_movimiento_id;	       
        $data->status 			 			= property_exists($datos, "status") 					? $datos->status 					: $data->status;
        $data->fecha_movimiento		 	 	= property_exists($datos, "fecha_movimiento") 			? $datos->fecha_movimiento 			: $data->fecha_movimiento;		
		$data->observaciones 	 			= property_exists($datos, "observaciones") 				? $datos->observaciones 			: $data->observaciones;
		$data->programa_id 					= $programa_id;
		$data->cancelado 					= 0;
		$data->observaciones_cancelacion	= property_exists($datos, "observaciones_cancelacion")	? $datos->observaciones_cancelacion	: $data->observaciones_cancelacion;
			if ($data->save()) {

				$proveedor_id = $datos->movimiento_entrada_metadatos_a_g['proveedor_id'];
				if ($datos->movimiento_entrada_metadatos_a_g['proveedor_id']==NULL || $datos->movimiento_entrada_metadatos_a_g['proveedor_id']=="")
					$proveedor_id = NULL;

				$mme_ag = new MovimientoEntradaMetadatosAG;
				$mme_ag->movimiento_id    = $data->id;
				$mme_ag->donacion         = $datos->movimiento_entrada_metadatos_a_g['donacion']; 
				$mme_ag->donante          = $datos->movimiento_entrada_metadatos_a_g['donante'];
				$mme_ag->numero_pedido    = $datos->movimiento_entrada_metadatos_a_g['numero_pedido']; 
				$mme_ag->fecha_referencia = $datos->movimiento_entrada_metadatos_a_g['fecha_referencia'];
				$mme_ag->folio_factura    = $datos->movimiento_entrada_metadatos_a_g['folio_factura'];
				$mme_ag->proveedor_id     = $proveedor_id;
				$mme_ag->persona_entrega  = $datos->movimiento_entrada_metadatos_a_g['persona_entrega']; 
				$mme_ag->save();



				if(property_exists($datos, "movimiento_articulos")  && count($datos->movimiento_articulos) > 0)
				{
					$movimiento_articulos = array_filter($datos->movimiento_articulos, function($v){return $v !== null;});
					MovimientoArticulos::where("movimiento_id", $data->id)->delete();
					foreach ($movimiento_articulos as $key => $value)
					{
						$value = (object) $value;
						if($value != null)
						{

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
							$item->iva_porcentaje    			= property_exists($value, "iva_porcentaje")			? $value->iva_porcentaje			  : $item->iva_porcentaje;
							$item->importe 			= property_exists($value, "importe")		? $value->importe		  : $item->importe;

							if($item->save())
							{
								$art = Articulos::find($value->articulo_id);
								if(property_exists($value, "inventarios") && count($value->inventarios) > 0) 
								{
									$inventarios = array_filter($value->inventarios, function($v){ return $v !== null; });
									InventarioArticulo::where("movimiento_articulo_id", $item->id)->delete();
									DB::table('inventario_movimiento_articulos')->where('movimiento_articulos_id', $item->id)->delete();
									$consecutivo = 0;
									foreach ($inventarios as $invk => $invv)
									{
										$consecutivo++;
										$invv = (object) $invv;
										if($invv != null)
										{
											DB::update("update inventario set deleted_at = null where movimiento_articulo_id = $item->id and articulo_id = '$item->articulo_id' and numero_inventario = '$invv->numero_inventario'");
											$inventario = InventarioArticulo::where("movimiento_articulo_id", $item->id)->where("articulo_id", $item->articulo_id)->where("numero_inventario", $invv->numero_inventario)->first();				        									        					
											if(!$inventario)
												$inventario = new InventarioArticulo;					        			
											
											$inventario->almacen_id 	 		 = $almacen_id;
											$inventario->movimiento_articulo_id  = $item->id;
											$inventario->programa_id             = $programa_id;
											$inventario->articulo_id 			 = $item->articulo_id;
											$inventario->existencia 			 = $value->cantidad;
											if($art->es_activo_fijo==1)
												$inventario->existencia 			 = 1;
											$inventario->primera_vez_inventario  = property_exists($invv, "primera_vez_inventario")		? $invv->primera_vez_inventario 								: $item->primera_vez_inventario;
											$inventario->numero_inventario		 = property_exists($invv, "numero_inventario")			? $invv->numero_inventario != '' ?  $invv->numero_inventario 	: $almacen_id.'-'.time().'-'.$consecutivo.$this->getRandomString(2) 			: $almacen_id.'-'.time().'-'.$consecutivo.$this->getRandomString(2);
											$inventario->observaciones 			 = property_exists($invv, "observaciones")				? $invv->observaciones 											: $item->observaciones;
											$inventario->lote  					 = property_exists($invv, "lote")						? $invv->lote 													: $item->lote;
											$inventario->fecha_caducidad  		 = property_exists($invv, "fecha_caducidad")			? $invv->fecha_caducidad 										: $item->fecha_caducidad;
											$inventario->es_patrimonio  		 = property_exists($invv, "es_patrimonio")				? $invv->es_patrimonio 											: $item->es_patrimonio;
											//$inventario->baja 			 		 = property_exists($invv, "baja")				? $invv->baja		  		: $item->baja;
											$inventario->baja 			 		 = 0;

											if($inventario->save()){			            											        																		         
												
												/*
												DB::table('inventario_movimiento_articulos')->insert(
													['movimiento_articulos_id' => $item->id, 'inventario_id' => $inventario->id]
												);
												*/	

												$movimiento_articulo_inventariox = new MovimientoArticulosInventario();
												//$movimiento_articulo_inventario->almacen_id              = $almacen_id;
												$movimiento_articulo_inventariox->movimiento_articulos_id = $item->id;
												$movimiento_articulo_inventariox->inventario_id           = $inventario->id;
												$movimiento_articulo_inventariox->save();

												/*
												AQUI VAMOS 
												VALIDAR CASO CUANDO ES ACTIVO FIJO-> YA NO ACTUALIZAR INVENTARIO_ID EN MOVIMIENTO_ARTICULOS
												PUES SOLO SE ACTUALIZARÁ 1 REGISTRO Y NO VALE LA PENA
												PARA ESO ESTA LA TABLA PIVOT
												*/
												
												$ma = MovimientoArticulos::find($item->id);
												if($ma){					            			
													$ma->inventario_id = $inventario->id;
													$ma->save();						            			
												}
												if(property_exists($invv, "inventario_metadato") && count($invv->inventario_metadato) > 0)
												{
													$inve_meta = array_filter($invv->inventario_metadato, function($v){return $v !== null;});
													
													InventarioArticuloMetadatos::where("inventario_id", $inventario->id)->delete();
													
													foreach ($inve_meta as $invmk => $invmv) 
													{
														$invmv = (object) $invmv;
														if($invmv != null)
														{
															if ($invmv->requerido_inventario==1)
															{
																// return Response::json(["status" => 500, 'error' => $invmv], 500);
																DB::update("update inventario_metadatos set deleted_at = null where inventario_id = $inventario->id and campo = '$invmv->campo'");
																$inventario_m = InventarioArticuloMetadatos::where("inventario_id", $inventario->id)->where("campo", $invmv->campo)->first();				        									        					
																if(!$inventario_m)
																	$inventario_m = new InventarioArticuloMetadatos;
		
																$inventario_m->inventario_id = $inventario->id;
																$inventario_m->metadatos_id = $invmv->metadatos_id;
																$inventario_m->campo = $invmv->campo;
																$inventario_m->valor = $invmv->valor;
		
																if($inventario_m->save())
																{								        																		           
																	$success = true;												            	
																}
															} else {
																		$success = true;	
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
					}
				}        	
				// $success = true;
			} // END SAVE	

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
	public function show($id){
		$data = Movimiento::with("Programa", "MovimientoEntradaMetadatosAG", "Usuario", "MovimientoArticulos", "TipoMovimiento", "Almacen")->find($id);			
		
		if(!$data){
			return Response::json(array("status"=> 404,"messages" => "No hay resultados"),404);
		} 
		else {	

			//  1    RECORRER MOVIMIENTOS_ARTICULOS
			//  2    DE CADA MOV-ARTICULO RECORRER SUS INVENTARIOS
			//  2.1  EN EL INVENTARIO del articulo TRAER LOS ARTICULOS METADATOS
			//  2.2  VERIFICAR LOS ARTICULOS_METADATOS requeridos YY BUSCARLOS EN INVENTARIO_METADATOS
			//  4 

			// $data = Movimiento::with("Usuario", "TipoMovimiento", "Almacen")->find($id);

			$mov_articulos = MovimientoArticulos::where('movimiento_id',$data->id)->get();
			foreach ($mov_articulos as $x => $mov_articulo)
			{
				$articulo = Articulos::find($mov_articulo->articulo_id);
				$mov_articulos[$x]->articulos = $articulo;

				$mov_articulo_inventarios = Inventario::where('articulo_id',$mov_articulo->articulo_id)
				                                      ->where('movimiento_articulo_id',$mov_articulo->id)
													  ->get();
				foreach ($mov_articulo_inventarios as $y => $mov_articulo_inventario)
				{
					$articulos_metadatos = ArticulosMetadatos::where('articulo_id',$mov_articulo_inventario->articulo_id)->get();
					foreach ($articulos_metadatos as $z => $articulo_metadato)
					{
						if($articulo_metadato->requerido_inventario == 1)
						{
							$inv_metadato = InventarioMetadato::where('inventario_id',$mov_articulo_inventario->id)
															  ->where('metadatos_id',$articulo_metadato->id)
															  ->first();
							$articulos_metadatos[$z]->valor = $inv_metadato->valor;
						}	
					}
					$mov_articulo_inventarios[$y]->inventario_metadato = $articulos_metadatos;
				}
				$mov_articulos[$x]->inventarios = $mov_articulo_inventarios;
			}

			$data->movimiento_articulos = $mov_articulos;


/*

			foreach ($data->MovimientoArticulos as $x => $movimientoArticulo)
			{
				foreach ($movimientoArticulo->inventarios as $y => $inventario)
				{
					/// asignar valores correctos de la tabla INVENTARIO_METADATOS  y NO de articulos_metadatos
					$ims = InventarioMetadato::where('inventario_id',$inventario->id)->get();
					//var_dump(json_encode($ims));
					//var_dump(json_encode($data->MovimientoArticulos[$x]->inventarios[$y]->inventarioMetadato));
					for ($i=0; $i < count($inventario->inventarioMetadato); $i++)
					{ 
						//unset($data->movimientoArticulos[$x]->inventarios[$y]->inventarioMetadato[$i]);
					}
					unset($data->MovimientoArticulos[$x]->inventarios[$y]->inventarioMetadato);
					unset($data->MovimientoArticulos[$x]->inventarios[$y]->inventario_metadato);
					$data->MovimientoArticulos[$x]->inventarios[$y]->inventario_metadato = array();
					$data->MovimientoArticulos[$x]->inventarios[$y]->inventario_metadato = $ims;
					
					$articulosMetadatos = ArticulosMetadatos::where('articulo_id',$inventario->articulo_id)->get();
					foreach ($articulosMetadatos as $z => $articuloMetadato)
					{
						if($articuloMetadato->requerido_inventario == 0) // agregar item base a  inventarioMetadatos
						{
						    $inventario_meta_add = new InventarioMetadato();
							$inventario_meta_add->inventario_id = $inventario->id;
							$inventario_meta_add->metadatos_id  = $articuloMetadato->id;
							$inventario_meta_add->campo         = $articuloMetadato->campo;
							$inventario_meta_add->valor         = $articuloMetadato->valor;
							$inventario_meta_add->tipo          = $articuloMetadato->tipo;
							$inventario_meta_add->longitud      = $articuloMetadato->lonngitud;

							// set value on inventario_metadato from inventario_articulo
							$data->MovimientoArticulos[$x]->inventarios[$y]->inventarioMetadato->push($inventario_meta_add);
						}
					}
				}
			}
			*/
			
			/*		
		foreach ($data->MovimientoArticulos as $key => $movimientoArticulo) {
			foreach ($movimientoArticulo->inventarios as $ki => $inventario) {
				$articulosMetadatos = ArticulosMetadatos::where('articulo_id',$inventario->articulo_id)->get();
				
				for ($i=0; $i < count($inventario->inventarioMetadato); $i++) { 
					unset($data->movimientoArticulos[$key]->inventarios[$ki]->inventarioMetadato[$i]);
				}

				// $data->movimientoArticulos[$key]->inventarios[$ki]->inventario_metadato = collect();
				// $inventario->inventario_metadato = collect();

				foreach ($articulosMetadatos as $kameta => $articuloMetadato) {
					if($articuloMetadato->requerido_inventario==0) {
						// $inventario->inventario_metadato->push($articuloMetadato);
						// $inventario->inventarioMetadato->push($articuloMetadato);
					}
					// $inventario->inventario_metadato->push($articuloMetadato);
					// $inventario->inventario_metadato->push($articuloMetadato);
					// $data->movimientoArticulos[$key]->inventarios[$ki]->inventario_metadato->push($articuloMetadato);
					// $data->movimientoArticulos[$key]->inventarios[$ki]->inventario_metadato = $imeta;
					//$inventario->inventarioMetadato = [];	
				}

				// $articulosMetadatos = ArticulosMetadatos::where('articulo_id',$inventario->articulo_id)->get();
				// foreach ($articulosMetadatos as $kameta => $articuloMetadato)
				// {
				// 	if($articuloMetadato->requerido_inventario == 1)
				// 	{
				// 		$inventario_meta  = InventarioMetadato::where('inventario_id',$inventario->id)
				// 												->where('metadatos_id',$articuloMetadato->id)
				// 												->first();
						 
				// 	}
				// }


				// foreach ($inventario->inventario_metadato as $kam => $inventarioMetadato)
				// {
				// 	if($inventarioMetadato->requerido_inventario==1)
				// 	{ // sacarlo del inventario

			 	// 	}
			 	// }
			}
		}

		*/
		


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

	/**
	 * Crea un random
	 *
	 * @param  Numero  $length número de carácteres del random
	 *
	 * @return Response
	 * <code> Respuesta Error json con los errores encontrados </code>
	 */
	function getRandomString($length)
	{
		$data = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$data_size = strlen($data);
		$random_string = '';
		for ($i = 0; $i < $length; $i++)
		{
			$random_string .= $data[rand(0, $data_size - 1)];
		}
		return $random_string;
	}
}