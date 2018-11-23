<?php
namespace App\Http\Controllers\AlmacenGeneral;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Response;
use Illuminate\Support\Facades\Input;
use DB; 
use Session;
use App\Models\AlmacenGeneral\ConfiguracionGeneral;
/**
* Controlador ConfiguracionGeneral
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Controlador `ConfiguracionGeneral`: Manejo de los grupos de usuario
*
*/
class ConfiguracionGeneralController extends Controller {

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
	public function show($id){
		
		$data = []; $configuracion = [];
		$almacen = Request::header('X-Almacen-Id');
		
		$variable = ConfiguracionGeneral::get();

		foreach ($variable as $key => $value) {			
			if($value->clave == 'fondo' || $value->clave == 'logo')
				$configuracion[$value->clave] = $value->valor;
			else
				$configuracion[$value->clave] = json_decode($value->valor);
		}
		$data["id"] = 1*$id;
		$data["configuracion"] = $configuracion;
		$total = $data;
		
		return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $data, "total" => count($total)), 200);						
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
		
		$datos = Input::json()->all();		
		$success = false;

        DB::beginTransaction();
        try{
        	$almacen = Request::header('X-Almacen-Id');
        	
        	if(isset($datos["configuracion"])){
	        	foreach ($datos["configuracion"] as $key => $value) {
	        		$existe = ConfiguracionGeneral::where("clave", $key)->first();
	        		
	        		if($key == "logo" || $key == "fondo"){	
	        			if($value != '' && $value != $existe->valor)
    						$value = $this->convertir_imagen($value, 'configuracion', $key);
	        		}
	        		else
	        			$value = json_encode($value);

	        		if($existe){
	        			DB::table('configuracion_general')
	        			->where("clave", $key)
	        			->update(['valor' => $value]);
	        		}else{
	        			DB::table('configuracion_general')
	        			->insert(['clave' => $key, 'valor' => $value]);
	        		}
	        		$success = true;        	        
	        	}
	        }	
	        		
		} 
		catch (\Exception $e){
			var_dump($e->getMessage());
			return Response::json($e->getMessage(), 500);
        }
        if ($success){
			DB::commit();
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito"), 200);
		} 
		else {
			DB::rollback();
			return Response::json(array("status" => 304, "messages" => "No modificado"),304);
		}
	}

}