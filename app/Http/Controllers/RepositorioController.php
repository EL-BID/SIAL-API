<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Requests;
use App\Models\Repositorio;
use App\Models\LogRepositorio;


use App\Models\Usuario;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

class RepositorioController extends Controller
{
    public function index()
    {
    	$repositorio = Repositorio::all();
    	return Response::json([ 'data' => $repositorio],200);

    }

    public function show($id)
    {

    	$repositorio = Repositorio::where("pedido_id", $id)
                    ->select("id",
                            "peso",
                            "nombre_archivo",
                            "created_at",
                            "usuario_id",
                            "usuario_deleted_id",
                            "deleted_at",
                            DB::RAW("(select count(*) from log_repositorio where repositorio_id=repositorio.id and accion='DOWNLOAD') as descargas"))
                    ->withTrashed()
                    ->get();
    	return Response::json([ 'data' => $repositorio],200);	
    	
    }

    public function store(Request $request)
    {
       $paramentros = Input::all();
       
       $directorio_destino_path = "repositorio";
       $extension = explode(".", strtolower($_FILES['file']['name']));

       $arreglo_datos = array(
                            'pedido_id' => $paramentros['id_pedido'],
                            'peso' => $_FILES['file']['size'],
                            'nombre_archivo' => $_FILES['file']['name'],
                            'ubicacion' => $directorio_destino_path,
                            'extension' => $extension[1]);

       $repositorio = Repositorio::create($arreglo_datos);
       
       $arreglo_log = array('repositorio_id' => $repositorio->id,
                            'ip' =>$request->ip(),
                            'navegador' =>$request->header('User-Agent'),
                            'accion' => 'UPLOAD'); 

       $log_repositorio = LogRepositorio::create($arreglo_log);

       
       \Request::file('file')->move($directorio_destino_path, $repositorio->id.".".$extension[1]);


       return Response::json([ 'data' => $repositorio ],200);
    }

     public function destroy($id, Request $request)
     {
        try {
            $repositorio = Repositorio::find($id);
            
            $obj =  JWTAuth::parseToken()->getPayload();
            $repositorio->usuario_deleted_id = $obj->get('id');
            $repositorio->save();

            $object = Repositorio::destroy($id);
            $arreglo_log = array('repositorio_id' => $id,
                            'ip' =>$request->ip(),
                            'navegador' =>$request->header('User-Agent'),
                            'accion' => 'DELETE'); 
            $log_repositorio = LogRepositorio::create($arreglo_log);

            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }

    }

    public function registro_descarga($id, Request $request)
    {
        $arreglo_log = array('repositorio_id' => $id,
                            'ip' =>$request->ip(),
                            'navegador' =>$request->header('User-Agent'),
                            'accion' => 'DOWNLOAD'); 
        $log_repositorio = LogRepositorio::create($arreglo_log);

        $repositorio = Repositorio::find($id);

        return Response::json(['data'=>$repositorio],200);
        $repositorio = Repositorio::find($id);

        return Response::json(['data'=>$repositorio],200);
    }

    public function descargar($id, Request $request)
     {
        try{

            $variables = Input::all();

            $usuario = Usuario::with(['roles.permisos'=>function($permisos){
                $permisos->where('id','MrL06vIO12iNhchP14h57Puvg71eUmYb')->orWhere('id','bsIbPL3qv6XevcAyrRm1GxJufDbzLOax');
            }])->find($request->get('usuario_id'));
            
            $tiene_acceso = false;

            if(!$usuario->su){
                $permisos = [];
                foreach ($usuario->roles as $index => $rol) {
                    foreach ($rol->permisos as $permiso) {
                        $permisos[$permiso->id] = true;
                    }
                }
                if(count($permisos)){
                    $tiene_acceso = true;
                }else{
                    $tiene_acceso = false;
                }
            }else{
                $tiene_acceso = true;
            }
            
            if($tiene_acceso){
               $arreglo_log = array('repositorio_id' => $id,
                            'ip' =>$request->ip(),
                            'navegador' =>$request->header('User-Agent'),
                            'accion' => 'DOWNLOAD'); 

                $log_repositorio = LogRepositorio::create($arreglo_log);

                if(!$log_repositorio)
                {
                    return Response::json(['error' => "Error al descargar el archivo"], 500); 
                }

                $repositorio = Repositorio::find($id);
                $directorio_path = "repositorio";
                $pathToFile = $directorio_path."//".$id.".".$repositorio->extension;
                
                $headers = array(
                    'Content-Type: application/pdf',
                );
                return response()->download($pathToFile, $repositorio->nombre_archivo, $headers);
            }else{
                return Response::json(['error' =>"No tiene permisos para ingresar a este modulo" ], 500);
            }
        }catch(Exception $e){
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
