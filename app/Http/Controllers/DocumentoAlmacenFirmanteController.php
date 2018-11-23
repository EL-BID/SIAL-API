<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Input;

use Illuminate\Http\Request;

use Response;
use DB; 
use \stdClass;
use App\Models\PersonalClues;
use App\Models\PersonalCluesMetadatos;

use App\Models\Almacen;
use App\Models\DocumentoSistema;
use App\Models\DocumentoSistemaCargo;
use App\Models\DocumentoSistemaFirmante;


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
class DocumentoAlmacenFirmanteController extends Controller {
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
	public function index(Request $request)
	{
		//$input_data = Request::all();
		$parametros = Input::only('q','page','per_page','almacen');
        $parametros['almacen'] = $request->get('almacen_id');

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 409,"messages" => "Debe especificar un almacen."), 200);
        }  
        $almacen = Almacen::find($parametros['almacen']);

		$data = new stdClass();
		$documentos = DocumentoSistema::with("documentoCargos")->get();

		$documentos_array = array();

		foreach ($documentos as $key => $documento)
		{
			$documento = (object)$documento;

			$cargos_array = array();
			$cargos = $documento->documentoCargos;

			foreach ($documento->documentoCargos as $key2 => $cargo)
			{
				$cargo    = (object)$cargo;
				$firmante =  DocumentoSistemaFirmante::where("documento_sistema_cargo_id",$cargo->id)
														 ->where("almacen_id",$almacen->id)->first();
					
				$cargo->firmante = $firmante; 
				array_push($cargos_array,$cargo);
			}

			$documento->documento_cargos = $cargos_array;
			array_push($documentos_array,$documento);
		}

		$data->documentos = $documentos_array; 
 
		return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $data), 200);
		
		
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
	public function store(Request $request){

		//$this->ValidarParametros(Input::json()->all());	
		$success = false;

		$parametros = Input::only('q','page','per_page','almacen','tipo');
        $parametros['almacen'] = $request->get('almacen_id');

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 409,"messages" => "Debe especificar un almacen."), 200);
        }  
        $almacen = Almacen::find($parametros['almacen']);


		$input_data = (object) Input::json()->all();
 
        DB::beginTransaction();
        try{

			foreach ($input_data->documentos as $key => $documento)
			{
				$documento = (object)$documento;
				foreach ($documento->documento_cargos as $key => $cargo)
				{
					$cargo = (object) $cargo;
					if($cargo->firmante != NULL)
					{
						$firmante = (object)$cargo->firmante;

						if($firmante->id == NULL)
						{
							$firmante_db = new DocumentoSistemaFirmante;
						}else{
								$firmante_db = DocumentoSistemaFirmante::find($firmante->id);
						     }
								$firmante_db->almacen_id                 = $almacen->id;
								$firmante_db->documento_sistema_cargo_id = $cargo->id;
								$firmante_db->nombre                     = $firmante->nombre;
						        $firmante_db->save();
						
					}
				}
			}
            $success = true;

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
        } 
        if ($success){
            DB::commit();
            return Response::json(array("status" => 201,"messages" => "Creado","data" => true), 201);
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
	public function show(Request $request ,$id)
	{
		$parametros = Input::only('q','page','per_page','almacen');
        $parametros['almacen'] = $request->get('almacen_id');

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 409,"messages" => "Debe especificar un almacen."), 200);
        }  
        $almacen = Almacen::find($parametros['almacen']);

		$data = new stdClass();
		$documento = DocumentoSistema::with("documentoCargos")->find($id);
		if(!$documento){
			return Response::json(array("status"=> 404,"messages" => "No hay resultados"),404);
		} 

		$documento = (object)$documento;
		$cargos_array = array();

		foreach ($documento->documentoCargos as $key2 => $cargo)
		{
			$cargo    = (object)$cargo;
			$firmante =  DocumentoSistemaFirmante::where("documento_sistema_cargo_id",$cargo->id)
														 ->where("almacen_id",$almacen->id)->first();
					
			$cargo->firmante = $firmante; 
			array_push($cargos_array,$cargo);
		}

		$documento->documento_cargos = $cargos_array;
		

		$data->documento = $documento; 			
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