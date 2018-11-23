<?php

namespace App\Http\Controllers\AdmisionUnidad;

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

class EgresoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $obj =  JWTAuth::parseToken()->getPayload();
        $usuario = Usuario::where("id", $obj->get('id'))->with("usuariounidad")->first();
        $clues = $usuario->usuariounidad->clues;    

        $usuarios = UsuarioUnidad::where("clues", $clues)->select("usuario_id")->get();

        $pacientes_ingreso_verificador = Paciente::crossJoin("ingreso", "ingreso.paciente_id", "=", "paciente.id")
                                        ->where("ingreso.estatus_ingreso_id", 0)
                                        ->whereIn("ingreso.usuario_id", $usuarios)
                                        ->select("paciente.id")
                                        ->get();                  

        $pacientes_ingreso = Paciente::whereIn("id", $pacientes_ingreso_verificador);                                
        
        $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
             $pacientes_ingreso =  $pacientes_ingreso->where(function($query) use ($parametros) {
                 $query->where('paciente.id','LIKE',"%".$parametros['q']."%")->orWhere(DB::raw("paciente.nombre"),'LIKE',"%".$parametros['q']."%");
             })
             ->with("Ingresoactivos.EstadoTriage","localidad", "localidad.municipio");
        } else {
             $pacientes_ingreso = $pacientes_ingreso->select('*')->with("Ingresoactivos.EstadoTriage","localidad", "localidad.municipio");
        }

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $pacientes_ingreso = $pacientes_ingreso->paginate($resultadosPorPagina);
        } else {
            $pacientes_ingreso = $pacientes_ingreso->get();
        }

        return Response::json([ 'data' => $pacientes_ingreso ],200);

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
        ];

        $reglas = [
            'ingreso_id'       => 'required',
            "motivo_egreso_id" => 'required',
            "fecha_hora"       => 'required',
            "contrareferencia" => 'required', 
        ];

        $inputs = Input::all();

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
            DB::beginTransaction();

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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pacientes_ingreso = Paciente::where("id", $id)->with("Ingresoactivos.EstadoTriage", "localidad", "localidad.municipio")->first();                                
        
        return Response::json([ 'data' => $pacientes_ingreso ],200);
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
