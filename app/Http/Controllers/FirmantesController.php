<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Requests;
use App\Models\UnidadMedica, App\Models\Almacen, App\Models\PersonalClues, App\Models\Pedido;


use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

class FirmantesController extends Controller
{
    public function index(Request $request)
    {
        try{
	        $unidad = UnidadMedica::with("director")->where('clues',$request->get('clues'))->first();
	        if(!$unidad)
	        {
	        	return Response::json(array("status" => 404,"messages" => "Ocurrio un errror al ingresar a los datos de su unidad"), 500);
	        }

	        $almacen = Almacen::with("encargado")->find($request->get('almacen_id'));

	        if(!$almacen){
	            return Response::json(array("status" => 404,"messages" => "No se encontro un almacen para la unidad."), 200);
	        }

	        return Response::json([ 'data' => array("unidad"=>$unidad , "almacen"=> $almacen)],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function store(Request $request){
       
        $parametros = Input::all();
        //return Response::json([ 'data' => $parametros ],200);
        try {
            DB::beginTransaction();
            
            if($parametros['firma_director'] != "")
            {
            	if(intval($parametros['id_firma_director']) == 0)
            	{
            		$personal_busqueda = PersonalClues::where("nombre", $parametros['firma_director'])->first();
            		if($personal_busqueda)
            			$parametros['id_firma_director'] = $personal_busqueda->id;
            		else
            		{
	            		$personal = PersonalClues::create(array("clues"=>$request->get('clues'), "nombre"=>$parametros['firma_director']));
	            		$parametros['id_firma_director'] = $personal->id;

	            	}
            	}
            	$unidad = UnidadMedica::where('clues',$request->get('clues'))->first();
            	$unidad->director_id = $parametros['id_firma_director'];
            	$unidad->save();
            }else if($parametros['id_firma_director'] == 'NULL')
            {
            	$unidad = UnidadMedica::where('clues',$request->get('clues'))->first();
            	$parametros['id_firma_director'] = NULL;
            	$unidad->director_id = $parametros['id_firma_director'];
            	$unidad->save();
            }

            if($parametros['firma_almacen'] != "")
            {
            	if(intval($parametros['id_firma_almacen']) == 0)
            	{
            		$personal_busqueda = PersonalClues::where("nombre", $parametros['firma_almacen'])->first();
            		if($personal_busqueda)
            			$parametros['id_firma_almacen'] = $personal_busqueda->id;
            		else
            		{
	            		$personal = PersonalClues::create(array("clues"=>$request->get('clues'), "nombre"=>$parametros['firma_almacen']));
	            		$parametros['id_firma_almacen'] = $personal->id;

	            	}

	            	//return Response::json([ 'data' => $parametros['id_firma_almacen'] ],200);
            	}
            	$almacen = Almacen::find($request->get('almacen_id'));
            	$almacen->encargado_almacen_id = $parametros['id_firma_almacen'];
            	$almacen->save();
            }else if($parametros['id_firma_almacen'] == 'NULL')
            {
            	$almacen = Almacen::find($request->get('almacen_id'));
            	$parametros['id_firma_almacen'] = NULL;
            	$almacen->encargado_almacen_id = $parametros['id_firma_almacen'];
            	$almacen->save();
            }
             
            foreach ($parametros['pedidos'] as $key => $value) {
             	 $pedido = Pedido::find($value);
             	 if($pedido)
             	 {
	             	 //if(intval($parametros['id_firma_almacen']) > 0)
	             	 	$pedido->encargado_almacen_id = $parametros['id_firma_almacen'];
	             	 //if(intval($parametros['id_firma_director']) > 0)
	             	 	$pedido->director_id = $parametros['id_firma_director'];

	             	 $pedido->save();
	             }
             } 
            
            DB::commit();
            return Response::json([ 'data' => $unidad ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

}
