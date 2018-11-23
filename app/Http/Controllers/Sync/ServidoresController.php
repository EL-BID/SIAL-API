<?php

namespace App\Http\Controllers\Sync;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use \DB,  \Response, \Config;
use Illuminate\Support\Facades\Input;
use \Validator;
use App\Models\Sincronizacion, App\Models\Servidor; 
use App\Models\Rol, App\Models\Usuario;

class ServidoresController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        $usuario = Usuario::find($request->get('usuario_id'));
		//return Response::json([ 'data' => []],200);
		//return Response::json(['error' => "NO EXSITE LA BASE"], 500);
		$parametros = Input::only('q','page','per_page','estatus');
		if ($parametros['q']) {
			$servidores =  Servidor::where('nombre','LIKE',"%".$parametros['q']."%")->orWhere('clues','LIKE',"%".$parametros['q']."%");
		} else {
			if(isset($parametros['estatus'])){
				$servidores =  Servidor::select('*', 
					DB::raw(
						'ABS(TIMESTAMPDIFF(HOUR,NOW(),ultima_sincronizacion)) as horas_sin_sincronizar'
					),
					DB::raw(
						'IF((ABS(TIMESTAMPDIFF(HOUR,NOW(),ultima_sincronizacion)) > periodo_sincronizacion OR  ultima_sincronizacion is NULL) AND principal != 1 , 1, 0) as alerta_retraso'
					)
				)->orderBy('alerta_retraso','desc');
			} else{
				$servidores =  Servidor::select('*');
			}
			
        }

        if(!$usuario->su){
            $servidores = $servidores->where('id',$usuario->servidor_id);
        }
        
		if(isset($parametros['page'])){
			$resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
			$servidores = $servidores->paginate($resultadosPorPagina);
		} else {
			$servidores = $servidores->get();
		}
		
		return Response::json([ 'data' => $servidores],200);
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
		  'unique'        => "unique",
		  'integer'        => "integer"
        ];

        $reglas = [
		  'id'        => 'required|unique:servidores',
		  'nombre'        => 'required',
          'secret_key'        => 'required',
          'clues'        => 'required',
		  'periodo_sincronizacion'        => 'required|integer',
        ];

        $inputs = Input::only('nombre','id','secret_key','periodo_sincronizacion','tiene_internet','principal','ip','clues');

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
           
            $servidor = Servidor::create($inputs);
            DB::commit();
            return Response::json([ 'data' => $servidor ],200);

        } catch (\Exception $e) {
            DB::rollback();
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
        $object = Servidor::find($id);

        if(!$object){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        return Response::json([ 'data' => $object ], HttpResponse::HTTP_OK);
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
			'unique'        => "unique",
			'integer'        => "integer"
		];

		$reglas = [
			'id'        => 'required|unique:servidores,id,'.$id,
			'nombre'        => 'required',
            'secret_key'        => 'required',
            'clues'        => 'required',
			'periodo_sincronizacion'        => 'required|integer',
		];

		$inputs = Input::only('nombre','id','secret_key','periodo_sincronizacion','tiene_internet','principal','ip','clues');

		$servidor = Servidor::find($id);

		if(!$servidor){
			return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
		}
		

		$v = Validator::make($inputs, $reglas, $mensajes);

		if ($v->fails()) {
			return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
		}

		DB::beginTransaction();
		try {

			$servidor->nombre = $inputs['nombre'];
			$servidor->id = $inputs['id'];
			$servidor->secret_key = $inputs['secret_key'];
			$servidor->periodo_sincronizacion = $inputs['periodo_sincronizacion'];
			$servidor->tiene_internet = $inputs['tiene_internet'];
            $servidor->principal = $inputs['principal'];
            $servidor->ip = $inputs['ip'];
            $servidor->clues = $inputs['clues'];
			$servidor->save();

			DB::commit();
			return Response::json([ 'data' => $servidor ],200);

		} catch (\Exception $e) {
			DB::rollback();
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
            $object = Servidor::destroy($id);
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
