<?php

namespace App\Http\Controllers\Medicos;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Paciente;
use App\Models\Usuario;
use App\Models\UsuarioUnidad;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class PacientesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $parametros = Input::only('term','clues');
		
		if(!isset($parametros['clues'])){
			return Response::json([ 'data' => []],200);
		}
		if ($parametros['term']) 
		{			
			$data =  Paciente::where('clues',$parametros['clues'])->where(function($query) use ($parametros) {
				$query->where('id','LIKE',"%".$parametros['term']."%")->orWhere(DB::raw("nombre"),'LIKE',"%".$parametros['term']."%");
			});
		} else {
			$data =  Paciente::where('clues',$parametros['clues']);
		}		

		$data = $data->get();
		
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

        $obj =  JWTAuth::parseToken()->getPayload();
        $usuario = Usuario::where("id", $obj->get('id'))->with("usuariounidad")->first();
       
        $mensajes = [
            'required'      => "required",
            "date_format"   => "date_format",
            "date"          => "date"
        ];

        $reglas = [
            'nombre'            => 'required',
            "sexo"              => 'required',
            "fecha_nacimiento"  => 'required|date_format:Y-m-d'           
        ];

        $inputs = Input::all();


        DB::beginTransaction();
        try {
            $v = Validator::make($inputs, $reglas, $mensajes);
			
			if ($v->fails()) {
				DB::rollBack();
				return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
			}

			
			$inputs['clues'] = $usuario->usuariounidad->clues;    
			
							$paciente = Paciente::create($inputs);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }

        DB::commit();
        return Response::json([ 'data' => $paciente ],200);

    }

     public function show($id)
    {
        $paciente = Paciente::find($id);
        return Response::json([ 'data' => $paciente ],200);
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
        $parametros = Input::all();

        $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            "motivo_egreso_id" => 'required',
            "fecha"       => 'required',
            "hora"       => 'required',
            "contrareferencia" => 'required', 
        ];

        $inputs = Input::all();

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
            DB::beginTransaction();

            $pacientes_ingreso_verificador = Paciente::crossJoin("ingreso", "ingreso.paciente_id", "=", "paciente.id")
                                        ->where("ingreso.estatus_ingreso_id", 0)
                                        ->where("paciente.id", $id)
                                        ->select("ingreso.id")
                                        ->first();  

            $inputs['fecha_hora'] = $inputs['fecha']." ".$inputs['hora'];
            $inputs['ingreso_id'] = $pacientes_ingreso_verificador->id;                           
            $ingreso = Ingreso::find($inputs['ingreso_id']);
            $ingreso->estatus_ingreso_id = 1;
            $ingreso->save();

            $egreso = Egreso::create($inputs);

            DB::commit();
            return Response::json([ 'data' => $egreso ],200);

        } catch (\Exception $e) {
            DB::rollBack();
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
            $object = Egreso::find($id)->delete();
            
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
