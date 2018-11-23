<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Turno;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response;



class TurnoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parametros = Input::only('q','page','per_page','bid');
        if ($parametros['q'])
        {
             $data =  Turno::where(function($query) use ($parametros) {
                        $query->where('id','LIKE',"%".$parametros['q']."%")
                              ->orWhere('nombre','LIKE',"%".$parametros['q']."%")
                              ->orderBy('nombre', 'asc');
             });
        } else {
             $data =  Turno::where("id","!=", "")->orderBy('nombre', 'asc');
        }
        

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $data = $data->paginate($resultadosPorPagina);
        } else {
            $data = $data->get();
        }

        if($parametros['bid']== '1')
        {
            if( count($data)>0)
            {
                return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $data), 200);
            }else{
                    return Response::json(array("status" => 404,"messages" => "No se encontraron resultados","data"=>[]), 200);
                 }
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

        $inputs = Input::only('id', 'nombre','descripcion');

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
           
            $data = Turno::create($inputs);

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
        $data = Turno::find($id);

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
                    'nombre'    => 'required',
                  ];

        $inputs = Input::only('id', 'nombre','descripcion');

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
                $data = Turno::find($id);
                $data->id =  $inputs['id'];
                $data->nombre =  $inputs['nombre'];
                $data->descripcion =  $inputs['descripcion'];
            
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
			$data = Turno::destroy($id);
			return Response::json(['data'=>$data],200);
		} catch (Exception $e) {
		   return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		}
    }
}
