<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response as HttpResponse;
use JWTAuth;
use App\Http\Requests;
use Request;
use App\Models\CondicionArticulo;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;


class CondicionArticuloController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
				$order = "id"; $orden = "asc";
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
				$data = CondicionArticulo::orderBy($order, $orden);
				
				$search = trim($valor);
				$keyword = $search;
				$data = $data->whereNested(function($query) use ($keyword){	
						$query->Where("nombre", "LIKE", '%'.$keyword.'%'); 
				});
				
				$total = $data->get();
				$data = $data->skip($pagina-1)->take($datos["limite"])->get();
			}
			else{
				$data = CondicionArticulo::skip($pagina-1)->take($datos["limite"])->orderBy($order, $orden)->get();
				$total =  CondicionArticulo::all();
			}
			
		}
		else{
			$data = CondicionArticulo::get();
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $object = Insumo::conDescripciones()->with('informacionAmpliada')->find($id);
        

        if(!$object){
            return Response::json(['error' => "No se encuentra el insumo que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        
        return Response::json([ 'data' => $object ], HttpResponse::HTTP_OK);
    }
}