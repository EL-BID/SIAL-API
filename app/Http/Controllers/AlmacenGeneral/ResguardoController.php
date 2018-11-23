<?php
namespace App\Http\Controllers\AlmacenGeneral;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Input;

use Request;
use Response;
use DB; 
use DNS1D;

use App\Models\AlmacenGeneral\Resguardos;
use App\Models\AlmacenGeneral\ResguardosArticulos;
use App\Models\AlmacenGeneral\ResguardosArticulosDevoluciones;

/**
* Controlador Resguardos
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Controlador `SisResguardos`: Manejo de usuarios del sistema
*
*/
class ResguardoController extends Controller {
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
				$data = Resguardos::with("Usuarios", "ResguardosArticulos", "Almacen")->orderBy($order, $orden);
				
				$search = trim($valor);
				$keyword = $search;
				$data = $data->whereNested(function($query) use ($keyword){	
						$query->Where("id", "LIKE", "%".$keyword."%")
						->orWhere("clues_destino", "LIKE", "%".$keyword."%")
                        ->orWhere("area_resguardante", "LIKE", "%".$keyword."%")
                        ->orWhere("nombre_resguardante", "LIKE", "%".$keyword."%")
                        ->orWhere("apellidos_resguardante", "LIKE", "%".$keyword."%")
                        ->orWhere("status", "LIKE", "%".$keyword."%"); 
				});
				
				$total = $data->get();
				$data = $data->skip($pagina-1)->take($datos["limite"])->get();
			}
			else{
				$data = Resguardos::with("Usuarios", "ResguardosArticulos", "Almacen")->skip($pagina-1)->take($datos["limite"])->orderBy($order, $orden)->get();
				$total =  Resguardos::get();
			}			
		} else	{
			$data = Resguardos::with("Usuarios", "ResguardosArticulos", "Almacen")->get();
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
            $data = new Resguardos;
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
        	$data = Resguardos::find($id);

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

		$clues_destino = NULL;
		if ($datos->clues_destino)
			$clues_destino = $datos->clues_destino['clues'];

        $data->almacen_id 		 		= $almacen_id;
        $data->clues_destino	 		= property_exists($datos, "clues_destino") 		        ? $clues_destino  	                : $data->clues_destino;	       
        $data->status 			 		= property_exists($datos, "status") 					? $datos->status 					: $data->status;
        $data->area_resguardante		= property_exists($datos, "area_resguardante") 			? $datos->area_resguardante 		: $data->area_resguardante;		
		$data->nombre_resguardante 	 	= property_exists($datos, "nombre_resguardante") 		? $datos->nombre_resguardante 		: $data->nombre_resguardante;
		$data->apellidos_resguardante 	= property_exists($datos, "apellidos_resguardante")	    ? $datos->apellidos_resguardante	: $data->apellidos_resguardante;

        if ($data->save()) {		
        	if(property_exists($datos, "resguardos_articulos")){
        		$resguardos_articulos = array_filter($datos->resguardos_articulos, function($v){return $v !== null;});
        		ResguardosArticulos::where("resguardos_id", $data->id)->delete();
        		foreach ($resguardos_articulos as $key => $value) {
        			$value = (object) $value;
        			if($value != null){

        				DB::update("update resguardo_articulos set deleted_at = null where resguardos_id = $data->id and inventario_id = '$value->inventario_id'");
						$item = ResguardosArticulos::where("resguardos_id", $data->id)->where("inventario_id", $value->inventario_id)->first();

						if(!$item)
            				$item = new ResguardosArticulos;
            			
            			$item->resguardos_id  	        = $data->id;
            			$item->inventario_id            = property_exists($value, "inventario_id")	            ? $value->inventario_id	            : $item->inventario_id;
            			$item->condiciones_articulos_id = property_exists($value, "condiciones_articulos_id")   ? $value->condiciones_articulos_id  : $item->condiciones_articulos_id;
            			$item->status 	                = property_exists($value, "status")                     ? $value->status                    : $item->status;
                        
                        if($item->save()){
	        				$success = true;
	        			}
            		}
        		}
        	}        	
		}  
		return $success;     						
	}

	public function devolucion(){
		$this->ValidarParametros(Input::json()->all());			
		$datos = (object) Input::json()->all();	
		$success = false;

        DB::beginTransaction();
        try{

			if(property_exists($datos, "resguardos_articulos")){
        		$resguardos_articulos = array_filter($datos->resguardos_articulos, function($v){return $v !== null;});
        		// ResguardosArticulos::where("resguardos_id", $data->id)->delete();
        		foreach ($resguardos_articulos as $key => $value) {
        			$value = (object) $value;
        			if($value != null){
						$data = new ResguardosArticulosDevoluciones;
						$data->resguardo_articulos_id	=  $value->id;	       
						$data->persona_recibe			= property_exists($datos, "persona_recibe") 			? $datos->persona_recibe            : $data->persona_recibe;           						// $data->nombre 	 			    = property_exists($datos, "nombre") 				    ?  $datos->nombre	                : $data->nombre;
			
						if ($data->save()){
							$item = ResguardosArticulos::find($value->id);
							$item->status = "DEVUELTO";
							if($item->save())
								$success = true;
							// {
							// 	$devuelto = ResguardosArticulos::where("resguardos_id", $item->resguardos_id)->where("status", "ACTIVO")->get();
							// 	if (count($devuelto)>0) {
							// 	$resguardo = Resguardos::find($devuelto->resguardos_id); 
							// 	var_dump($resguardo);
							// 		if(!$devuelto){                                   
							// 			$resguardo->status = "DEVUELTO";
							// 		}else{
							// 			$resguardo->status = "DEVUELTO PARCIAL";
							// 		}
							// 		if ($resguardo->save())
							// 			$success = true;
							// 	} else {
							// 		$success = true;
							// 	}
							// }
						}
            		}
				}
				
				if($success){
					$resguardo = Resguardos::find($datos->id);
					$resguardo->status = "DEVUELTO";
					if ($resguardo->save())
						$success = true;
				}
			}
			

			// $data = new ResguardosArticulosDevoluciones;
			
	        // $data->resguardos_articulos_id	= property_exists($datos, "resguardos_articulos_id") 	? $datos->resguardos_articulos_id  : $data->resguardos_articulos_id;	       
	        // $data->persona_recibe			= property_exists($datos, "persona_recibe") 			? $datos->persona_recibe            : $data->persona_recibe;           
			// // $data->nombre 	 			    = property_exists($datos, "nombre") 				    ?  $datos->nombre	                : $data->nombre;

	        // if ($data->save()) {

            //     $item = ResguardosArticulos::where("resguardos_articulos_id", $data->resguardos_articulos_id)->first();
               
            //     $item->status = "DEVUELTO";
                
            //     if($item->save()){
            //         $devuelto = ResguardosArticulos::where("resguardos_id", $item->resguardos_id)->where("status", "ACTIVO")->get();
                   
            //         $resguardo = Resguardos::where("id", $item->resguardos_id)->first(); 
            //         if(!$devuelto){                                   
            //             $resguardo->status = "DEVUELTO";
            //         }else{
            //             $resguardo->status = "DEVUELTO PARCIAL";
            //         }
            //         $resguardo->save();
            //     }
			// }  
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
		$data = Resguardos::with("Usuarios", "ResguardosArticulos", "Almacen")->find($id);		
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
		$data = Resguardos::with("Usuarios", "ResguardosArticulos", "Almacen")->find($id);			
		
		if(!$data){
			return Response::json(array("status"=> 404,"messages" => "No hay resultados"),404);
		} 
		else {	
			// $data->barcode = DNS1D::getBarcodePNG($data->codigo_barra, "C128");
			$iva = 0; $subtotal = 0;
			foreach ($data->ResguardosArticulos as $key => $value) {
				$value->articulos = $value->Inventarios->articulo;
				$value->numero_inventario = $value->Inventarios->numero_inventario;
				$value->cantidad = 1;
				$value->importe = $value->Inventarios->MovimientoArticulo->precio_unitario;
				$value->iva = $value->Inventarios->MovimientoArticulo->precio_unitario * ($value->Inventarios->MovimientoArticulo->iva_porcentaje / 100);
				$value->cantidad_devolucion = null;

				$iva+= $value->iva; 
				$subtotal+= $value->importe;
			}
			$data->iva = $iva;
			$data->total = $subtotal + $iva;
			$data->subtotal = $subtotal;
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
			$data = Resguardos::find($id);
			$resguardos_articulos = $data->ResguadosArticulos();
			if(count($resguardos_articulos)>0){
				foreach ($resguardos_articulos as $grupo) {
					$grupo->delete();			
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