<?php

namespace App\Http\Controllers\AdmisionUnidad;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\Admision;
use App\Models\Paciente;
use App\Models\AreaResponsable;
use App\Models\Usuario;


use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AdmisionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ingreso = Admision::all();
        return Response::json([ 'data' => $ingreso ],200);
    }

    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $preingreso = Paciente::where("id",$id)->with("localidad.municipio","Responsable", "ingresoactivos.EstadoTriage")->first();
        return Response::json([ 'data' => $preingreso ],200);
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
            "date_format"   => "date_format"
        ];

        $reglas = [            
            "referido"              => 'required',
            "unidad_referido"       => 'required',
            "urgencia_calificada"   => 'required', 
            "registro_triage"   => 'required',
            "estado_triage_id"  => 'required',
            "grado_lesion_id"   => 'required', 
            "fecha_ingreso"   => 'required|date_format:Y-m-d', 
            "hora_ingreso"   => 'required', 
        ];

        $inputs = Input::all();

        //return Response::json([ 'data' => $inputs ],500);
        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
            DB::beginTransaction();
            
            $obj =  JWTAuth::parseToken()->getPayload();
            $usuario = Usuario::where("id", $obj->get('id'))->with("usuariounidad")->first();
            $clues = $usuario->usuariounidad->clues;  

            $inputs['paciente_id'] = $id;
            $inputs['clues'] = $clues;
            
            if($inputs['referido'] == 2)
            	unset($inputs['unidad_referido']);
            unset($inputs['id']);

            $inputs['fecha_hora_ingreso'] = $inputs['fecha_ingreso']." ".$inputs['hora_ingreso'];

            $ingreso = Admision::create($inputs);
            
            
            DB::commit();
            return Response::json([ 'data' => $ingreso ],200);

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
            $object = array();

            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
        
    }
}
