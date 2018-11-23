<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\UnidadMedica;
use App\Models\CluesServicio;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;



class UnidadMedicaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
             $data =  UnidadMedica::with("almacenes", "director", "clues_servicios")->where(function($query) use ($parametros) {
                 $query->where('clues','LIKE',"%".$parametros['q']."%")
                 ->orWhere('nombre','LIKE',"%".$parametros['q']."%");
             });
        } else {
             $data =  UnidadMedica::with("almacenes", "director", "clues_servicios")->where("clues","!=", "");
        }
        
        $data = $data->orderBy('nombre','ASC');

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $data = $data->paginate($resultadosPorPagina);
        } else {
            $data = $data->get();
        }
       
        return Response::json([ 'data' => $data],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $mensajes = [
            
            'required'      => "required",
            'unique'        => "unique"
        ];

        $reglas = [
            'nombre'        => 'required',
        ];

        $inputs = Input::all();

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
           
            $data = UnidadMedica::create($inputs);
            if($data){
                if(array_key_exists("clues_servicios", $inputs)){
                    $clues_servicios = array_filter($inputs["clues_servicios"], function($v){return $v !== null;});
                    CluesServicio::where("clues", $data->clues)->delete();
                    foreach ($clues_servicios as $key => $value) {
                        $value = (object) $value;
                        if($value != null){
                            DB::update("update clues_servicios set deleted_at = null where clues = $data->clues and servicio_id = '$value->servicio_id' ");
                            $item = CluesServicio::where("clues", $data->clues)->where("servicio_id", $value->servicio_id)->first();

                            if(!$item)
                                $item = new CluesServicio;

                            $item->clues = $data->clues;
                            $item->servicio_id = $value->$servicio_id;
                        }
                    }
                }
            }
            return Response::json([ 'data' => $data ],200);

        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
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
        $data = UnidadMedica::with("almacenes", "director", "clues_servicios")->where("clues", $id)->first();

        if(!$data){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

         return Response::json([ 'data' => $data ], HttpResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $mensajes = [
            
            'required'      => "required",
            'unique'        => "unique"
        ];

        $reglas = [
            'nombre'        => 'required',
        ];

        $inputs = Input::all();

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
           $data = UnidadMedica::find($id);
           $data->clues =  $inputs['clues'];
           $data->nombre =  $inputs['nombre'];
            
            if($data->save()){
                if(array_key_exists("clues_servicios", $inputs)){
                    $clues_servicios = array_filter($inputs["clues_servicios"], function($v){return $v !== null;});
                    CluesServicio::where("clues", $data->clues)->delete();
                    foreach ($clues_servicios as $key => $value) {

                        $value = (object) $value;
                        if($value != null){
                            DB::update("update clues_servicios set deleted_at = null where clues = $data->clues and servicio_id = '$value->servicio_id' ");
                            $item = CluesServicio::where("clues", $data->clues)->where("servicio_id", $value->servicio_id)->first();

                            if(!$item)
                                $item = new CluesServicio;

                            $item->clues = $data->clues;
                            $item->servicio_id = $value->$servicio_id;
                        }
                    }
                }
            }
            return Response::json([ 'data' => $data ],200);

        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
			$data = UnidadMedica::destroy($id);
			return Response::json(['data'=>$data],200);
		} catch (Exception $e) {
		   return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		}
    }
}
