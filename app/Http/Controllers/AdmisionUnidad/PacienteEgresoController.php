<?php

namespace App\Http\Controllers\AdmisionUnidad;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\Paciente;
use App\Models\Usuario;
use App\Models\Admision;
use App\Models\UsuarioUnidad;
use App\Models\Responsable;
use App\Models\AreaResponsable;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class PacienteEgresoController extends Controller
{
    public function index()
    {

    	$obj =  JWTAuth::parseToken()->getPayload();
        $usuario = Usuario::where("id", $obj->get('id'))->with("usuariounidad")->first();
        if(count($usuario->usuariounidad) > 0)
        {
            $clues = $usuario->usuariounidad->clues;    

            $usuarios = UsuarioUnidad::where("clues", $clues)->select("usuario_id")->get();



            $pacientes_ingreso_verificador = Paciente::crossJoin("pacientes_admision", "pacientes_admision.paciente_id", "=", "pacientes.id")
                                            ->where("pacientes_admision.estatus_admision", 0)
                                            ->whereIn("pacientes_admision.usuario_id", $usuarios)
                                            ->select("pacientes.id")
                                            ->get();   


            $arreglo_pacientes = array();                                
            foreach ($pacientes_ingreso_verificador as $key => $value) {
           		$arreglo_pacientes[] = $value->id;
            }     

            $pacientes_ingreso = Paciente::whereIn("id", $arreglo_pacientes);                                
            
             $parametros = Input::only('q','page','per_page');
            if ($parametros['q']) {
                 $pacientes_ingreso =  $pacientes_ingreso->where(function($query) use ($parametros) {
                     $query->where('id','LIKE',"%".$parametros['q']."%")->orWhere(DB::raw("nombre"),'LIKE',"%".$parametros['q']."%");
                 })
                 ->with("localidad.municipio", "Ingresoactivos.Unidad", "responsable");
            } else {
                 $pacientes_ingreso =  $pacientes_ingreso->select('*')->with("localidad.municipio", "Ingresoactivos.Unidad", "responsable");
            }
            
            if(isset($parametros['page'])){

                $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
                $pacientes_ingreso = $pacientes_ingreso->paginate($resultadosPorPagina);
            } else {
                $pacientes_ingreso = $pacientes_ingreso->get();
            }
        }else
        {
            $pacientes_ingreso = Paciente::select('*')->with("localidad.municipio", "Ingresoactivos.Unidad", "responsable");
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $pacientes_ingreso = $pacientes_ingreso->paginate($resultadosPorPagina);
        }

        return Response::json([ 'data' => $pacientes_ingreso ],200);
    }

    public function store(Request $request)
    {
        $mensajes = [
            'required'      => "required",
            'date_format'   => 'date_format'
        ];

        $reglas = [
            "motivo_egreso_id"  => 'required',
            "fecha_egreso" => 'required|date_format:Y-m-d',
            "hora_egreso" => 'required',
            "contrareferencia"  => 'required', 
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
            $ingreso->motivo_egreso_id = $Ingreso['motivo_egreso_id'];
            $ingreso->fecha_hora_egreso = $inputs['fecha_egreso']." ".$inputs['hora_egreso'];
            $ingreso->contrareferencia = $inputs['contrareferencia'];
            $ingreso->unidad_contrareferencia = $inputs['unidad_referencia'];
            $ingreso->save();

            DB::commit();
            return Response::json([ 'data' => $egreso ],200);

            
         } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function update(Request $request, $id)
    {

        
        $mensajes = [
            'required'      => "required",
            "date_format"   => "date_format"
        ];

        $reglas = [
            "motivo_egreso_id"  => 'required',
            "fecha_egreso" => 'required|date_format:Y-m-d',
            "hora_egreso" => 'required',
            "contrareferencia"  => 'required', 
        ];

        $inputs = Input::all();

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
            DB::beginTransaction();


            $admision = Admision::where("paciente_id",$id)->where("estatus_admision", 0)->first();
            $admision->estatus_admision = 1;
            $admision->motivo_egreso_id = $inputs['motivo_egreso_id'];
            $admision->fecha_hora_egreso = $inputs['fecha_egreso']." ".$inputs['hora_egreso'];
            $admision->contrareferencia = $inputs['contrareferencia'];
            $admision->unidad_contrareferencia = $inputs['unidad_referencia'];
            $admision->save();

            DB::commit();
            return Response::json([ 'data' => $admision ],200);

            
         } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
