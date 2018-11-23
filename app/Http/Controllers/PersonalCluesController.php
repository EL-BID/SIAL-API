<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Input;
use Request;
use Response;
use DB; 
use App\Models\PersonalClues;
use App\Models\PersonalCluesMetadatos;

/**
* Controlador PersonalClues
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Controlador `PersonalClues`: Manejo de usuarios del sistema
*
*/
class PersonalCluesController extends Controller {
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

		$parametros = Input::only('term','tipo_personal','clues','page','per_page');


		if(!$parametros['clues']){
            return Response::json(array("status" => 404,"messages" => "Debe especificar una clues."), 200);
        } 

		$clues = $parametros['clues'];
		
		// Si existe el paarametro pagina en la url devolver las filas según sea el caso
		// si no existe parametros en la url devolver todos las filas de la tabla correspondiente
		// esta opción es para devolver todos los datos cuando la tabla es de tipo catálogo
		if(array_key_exists("pagina", $datos))
		{
			$pagina = $datos["pagina"];
			if(isset($datos["order"]))
			{
				$order = $datos["order"];
				if(strpos(" ".$order,"-"))
					$orden = "desc";
				else
					$orden = "asc";
				$order=str_replace("-", "", $order); 
			}
			else{
					$order = "id"; $orden = "asc";
				}
			
			if($pagina == 0){
				$pagina = 1;
			}
			if($pagina == 1)
				$datos["limite"] = $datos["limite"] - 1;
			// si existe buscar se realiza esta linea para devolver las filas que en el campo que coincidan con el valor que el usuario escribio
			// si no existe buscar devolver las filas con el limite y la pagina correspondiente a la paginación
			if(array_key_exists("q", $datos))
			{
				$columna = $datos["columna"];
				$valor   = $datos["valor"];
				$data = PersonalClues::with("PersonalCluesMetadatos", "TiposPersonal")
									 ->where('clues',$parametros['clues'])->orderBy($order, $orden);
				
				$search = trim($valor);
				$keyword = $search;
				$data = $data->whereNested(function($query) use ($keyword){	
						$query->Where("nombre", "LIKE", '%'.$keyword.'%'); 
				});
				
				$total = $data->get();
				$data  = $data->skip($pagina-1)->take($datos["limite"])->get();
			}
			else{
					$data = PersonalClues::where('clues',$parametros['clues'])->with("PersonalCluesMetadatos", "TiposPersonal")->skip($pagina-1)->take($datos["limite"])->orderBy($order, $orden)->get();
					$total =  PersonalClues::all();
				}
			
		}else{
				$data = PersonalClues::where('clues',$parametros['clues'])->with("PersonalCluesMetadatos", "TiposPersonal")->get();
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
            $data = new PersonalClues;
            $success = $this->campos($datos, $data);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
        } 
        if ($success){
            DB::commit();
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
        	$data = PersonalClues::find($id);

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
		$clues = Request::header("X-Almacen-Id");
		$servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');
		

		$data->tipo_personal_id	= property_exists($datos, "tipo_personal_id") 	? $datos->tipo_personal_id 		: $data->tipo_personal_id;
        $data->clues			= property_exists($datos, "clues") 				? $datos->clues 				: $data->clues;
        $data->nombre			= property_exists($datos, "nombre") 			? $datos->nombre 				: $data->nombre;
        $data->celular			= property_exists($datos, "celular") 			? $datos->celular 				: $data->celular;
        $data->email			= property_exists($datos, "email") 				? $datos->email 				: $data->email;	
        
        if ($data->save()) { 

        	//verificar si existe contacto, en caso de que exista proceder a guardarlo
            if(property_exists($datos, "personal_clues_metadatos")){
                
                //limpiar el arreglo de posibles nullos
                $personal_clues_metadatos = array_filter($datos->personal_clues_metadatos, function($v){return $v !== null;});

                //borrar los datos previos de articulo para no duplicar información
                PersonalCluesMetadatos::where("servidor_id", $servidor_id)->where("personal_clues_id", $data->id)->delete();

                //recorrer cada elemento del arreglo
                foreach ($personal_clues_metadatos as $key => $value) {
                    //validar que el valor no sea null
                    if($value != null){
                        //comprobar si el value es un array, si es convertirlo a object mas facil para manejar.
                        if(is_array($value))
                            $value = (object) $value;

                        if($value->valor != ""){
	                        //comprobar que el dato que se envio no exista o este borrado, si existe y esta borrado poner en activo nuevamente
	                        DB::update("update personal_clues_metadatos set deleted_at = null where servidor_id = '$servidor_id' and personal_clues_id = ".$data->id." and metadatos_id = '".$value->metadatos_id."'");
	                        
	                        //si existe el elemento actualizar
	                        $item = PersonalCluesMetadatos::where("servidor_id", $servidor_id)->where("personal_clues_id", $data->id)->where("metadatos_id", $value->metadatos_id)->first();
	                        //si no existe crear
	                        if(!$item)
	                            $item = new PersonalCluesMetadatos;

	                        //llenar el modelo con los datos
	                        
	                        $item->personal_clues_id   		= $data->id; 
	                        $item->metadatos_id    		= $value->metadatos_id;
	                        $item->campo          		= $value->campo; 
	                        $item->valor    			= $value->valor; 
	                        
	                        $item->save(); 
                        }        
                    }
                }
            }       	
			$success = true;
		}  
		return $success;     						
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
		$data = PersonalClues::with("PersonalCluesMetadatos", "TiposPersonal")->find($id);			
		
		if(!$data){
			return Response::json(array("status"=> 404,"messages" => "No hay resultados"),404);
		} 
		else {				
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
			$data = PersonalClues::find($id);
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
			"nombre" => "required|min:3"
		];
		$v = \Validator::make(Request::json()->all(), $rules );

		if ($v->fails()){
			return Response::json($v->errors());
		}
	}
}