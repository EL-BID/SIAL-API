<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Servidor;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response;



class ServidorController extends Controller
{

    public function informacionServidorLocal(){
        $servidor = Servidor::find(env('SERVIDOR_ID'));

        if($servidor->principal){
            $servidores = Servidor::all();
            return Response::json([ 'data' => ['servidor'=>$servidor,'lista_servidores'=>$servidores]],200);
        }else{
            return Response::json([ 'data' => ['servidor'=>$servidor]],200);
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
             $data =  Servidor::where(function($query) use ($parametros) {
                 $query->where('id','LIKE',"%".$parametros['q']."%")
                 ->orWhere('nombre','LIKE',"%".$parametros['q']."%")
                 ->orWhere('principal','LIKE',"%".$parametros['q']."%");
             });
        } else {
             $data =  Servidor::where("id","!=", "");
        }
        

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
            'principal'        => 'required',
        ];

        $inputs = Input::only("id", "nombre", "secret_key", "tiene_internet", "catalogos_actualizados", "version", "periodo_sincronizacion", "principal");

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
           
            $data = Servidor::create($inputs);

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
        $data = Servidor::find($id);

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
            'principal'        => 'required',
        ];

        $inputs = Input::only("id", "nombre", "secret_key", "tiene_internet", "catalogos_actualizados", "version", "periodo_sincronizacion", "principal");

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
           $data = Servidor::find($id);
           $data->id                        =  $inputs['id'];           
           $data->nombre                    =  $inputs['nombre'];
           $data->secret_key                =  $inputs['secret_key'];
           $data->tiene_internet            =  $inputs['tiene_internet'];
           $data->catalogos_actualizados    =  $inputs['catalogos_actualizados'];
           $data->version                   =  $inputs['version'];
           $data->periodo_sincronizacion    =  $inputs['periodo_sincronizacion'];
           $data->principal                 =  $inputs['principal'];
            
            $data->save();
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
			$data = Servidor::destroy($id);
			return Response::json(['data'=>$data],200);
		} catch (Exception $e) {
		   return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		}
    }
}
