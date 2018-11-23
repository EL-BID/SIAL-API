<?php

namespace App\Http\Controllers\AdmisionUnidad;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\Paciente;
use App\Models\Usuario;
use App\Models\Responsable;
use App\Models\AreaResponsable;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class PacienteController extends Controller
{
    public function index()
    {
        
        
        $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
             $paciente =  Paciente::where(function($query) use ($parametros) {
                 $query->where('id','LIKE',"%".$parametros['q']."%")->orWhere(DB::raw("nombre"),'LIKE',"%".$parametros['q']."%");
             })
             ->with("localidad.municipio", "Ingresoactivos.Unidad", "responsable");
        } else {
             $paciente =  Paciente::select('*')->with("localidad.municipio", "Ingresoactivos.Unidad", "responsable");
        }

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $paciente = $paciente->paginate($resultadosPorPagina);
        } else {
            $paciente = $paciente->get();
        }

        
        return Response::json([ 'data' => $paciente ],200);
    }

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
            "fecha_nacimiento"  => 'required|date_format:Y-m-d',
            "domicilio"         => 'required', 
            "colonia"           => 'required', 
            "municipio_id"      => 'required', 
            "localidad_id"      => 'required', 
            "telefono"          => 'required', 
            "no_expediente"     => 'required', 
            "no_afiliacion"     => 'required'
        ];

        $inputs = Input::all();


        DB::beginTransaction();
        try {
            if(!isset($inputs['conocido']) || $inputs['conocido'] == 1 )
            {
                $v = Validator::make($inputs, $reglas, $mensajes);

                if ($v->fails()) {
                    DB::rollBack();
                    return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
                }

                
                $inputs['clues'] = $usuario->usuariounidad->clues;    
               
                $paciente = Paciente::create($inputs);
            }else
            {
                $arreglo_paciente_desconocido = array("nombre"=>"DESCONOCIDO ".$usuario->usuariounidad->clues); 
                $arreglo_paciente_desconocido['clues'] = $usuario->usuariounidad->clues;    
                $arreglo_paciente_desconocido['municipio_id'] = 0;    
                $arreglo_paciente_desconocido['localidad_id'] = 0;    
                $paciente = Paciente::create($arreglo_paciente_desconocido);

                $paciente->nombre = $paciente->nombre." - ".$paciente->id;
                $paciente->save(); 
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }

        if($inputs['responsableconocido'] == 1)
        {
            $reglas_responsable = [
                'nombre_responsable'    => 'required',
                "parentesco"            => 'required',
                "domicilio_responsable" => 'required', 
                "telefono_responsable"  => 'required'
            ];

             $v = Validator::make($inputs, $reglas_responsable, $mensajes);

            if ($v->fails()) {
                DB::rollBack();
                return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
            }

            $arreglo_responsable = ["paciente_id"  => $paciente->id, "nombre"  => $inputs['nombre_responsable'], "parentesco"  => $inputs['parentesco'], "domicilio"  => $inputs['domicilio_responsable'], "telefono"  => $inputs['telefono_responsable']];

            $responsable = Responsable::create($arreglo_responsable);
        }

        if(isset($inputs['conocido']) && isset($inputs['responsableconocido']))
        {
            if($inputs['conocido'] != 1 && $inputs['responsableconocido'] != 1)
            {

               
                $arreglo_area = Array();
                if(isset($inputs['juridico']) && $inputs['juridico']==true)
                    AreaResponsable::create(array("paciente_id"=>$paciente->id, "area_responsable_id"=>2));
                if(isset($inputs['ministerio']) && $inputs['ministerio']==true)
                    AreaResponsable::create(array("paciente_id"=>$paciente->id, "area_responsable_id"=>3));

                if(isset($inputs['trabajo_social']) && $inputs['trabajo_social']==true)
                    AreaResponsable::create(array("paciente_id"=>$paciente->id, "area_responsable_id"=>1));

            }
        }
        DB::commit();
        return Response::json([ 'data' => $paciente ],200);

    }

     public function show($id)
    {
        $paciente = Paciente::where("id", $id)->with("responsable", "areas")->first();
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


        $mensajes = [
            'required'      => "required",
            'date_format'   => "date_format"
        ];

        $reglas = [
            'nombre'            => 'required',
            "sexo"              => 'required',
            "fecha_nacimiento"  => 'required|date_format:Y-m-d',
            "domicilio"         => 'required', 
            "colonia"           => 'required', 
            "municipio_id"      => 'required', 
            "localidad_id"      => 'required', 
            "telefono"          => 'required', 
            "no_expediente"     => 'required', 
            "no_afiliacion"     => 'required'
        ];

        $inputs = Input::all();

        DB::beginTransaction();
        try {
            
            $v = Validator::make($inputs, $reglas, $mensajes);

            if ($v->fails()) {
                DB::rollBack();
                return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
            }
  
            //$inputs['clues'] = $usuario->usuariounidad->clues;    
            $paciente = Paciente::find($id);
            $obj =  JWTAuth::parseToken()->getPayload();
            
            $usuario = Usuario::where("id", $obj->get('id'))->with("usuariounidad")->first();

            
            if($usuario->usuariounidad->clues  == $paciente->clues)
                $paciente->update($inputs);    
            else
               return Response::json(['error' => "Solamente la unidad que dio de alta al paciente puede modificar sus datos"], 500);
            
            
            $paciente->update($inputs);
        
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }

        if($inputs['responsableconocido'] == 1)
        {
            $reglas_responsable = [
                'nombre_responsable'    => 'required',
                "parentesco"            => 'required',
                "domicilio_responsable" => 'required', 
                "telefono_responsable"  => 'required'
            ];

             $v = Validator::make($inputs, $reglas_responsable, $mensajes);

            if ($v->fails()) {
                DB::rollBack();
                return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
            }

            $arreglo_responsable = ["paciente_id"  => $paciente->id, "nombre"  => $inputs['nombre_responsable'], "parentesco"  => $inputs['parentesco'], "domicilio"  => $inputs['domicilio_responsable'], "telefono"  => $inputs['telefono_responsable']];

           

            Responsable::where("paciente_id" ,$paciente->id)->delete();
            Responsable::create($arreglo_responsable);
        }

        
        DB::commit();
        return Response::json([ 'data' => $paciente ],200); 
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
            $object = Paciente::destroy($id);
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
        
    }

}
