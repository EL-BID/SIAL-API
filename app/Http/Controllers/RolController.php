<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Rol, App\Models\PermisoRol, App\Models\Servidor;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class RolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //return Response::json([ 'data' => []],200);
        //return Response::json(['error' => "NO EXSITE LA BASE"], 500);

        $servidor = Servidor::find(env('SERVIDOR_ID'));

        $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
             $roles =  Rol::where('nombre','LIKE',"%".$parametros['q']."%");
        } else {
             $roles =  Rol::select('*');
        }

        if($servidor->principal == 0){
            $roles = $roles->where('es_offline',1);
        }

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $roles = $roles->paginate($resultadosPorPagina);
        } else {
            $roles = $roles->get();
        }
       
        return Response::json([ 'data' => $roles],200);
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
            'nombre'        => 'required|unique:roles',
            'permisos'        => 'required|array',
        ];

        $inputs = Input::only('nombre','permisos','es_offline');

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
           
            $rol = Rol::create($inputs);
            foreach($inputs['permisos'] as $permiso){
                PermisoRol::create(['permiso_id'=> $permiso,'rol_id'=>$rol->id]);
            }
            //$rol->permisos()->sync($inputs['permisos']);

            DB::commit();
            return Response::json([ 'data' => $rol ],200);

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
        $servidor = Servidor::find(env('SERVIDOR_ID'));

        $object = Rol::find($id);

        if(!$object){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        if($object->es_offline == 0 && $servidor->principal == 0){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        $object->permisos;

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
            'unique'        => "unique"
        ];

        $reglas = [
            'nombre'        => 'required|unique:roles,nombre,'.$id,
            'permisos'        => 'required|array',
        ];

        $inputs = Input::only('nombre','permisos','es_offline');

        $rol = Rol::find($id);

        if(!$rol){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {

            $rol->nombre = $inputs['nombre'];
            $rol->es_offline = $inputs['es_offline'];

            //PermisoRol::where('rol_id',$rol->id)->delete();
            /*foreach($inputs['permisos'] as $permiso){
                PermisoRol::create(['permiso_id'=> $permiso,'rol_id'=>$rol->id]);
            }*/

            $permisos_roles_db = PermisoRol::where('rol_id',$rol->id)->withTrashed()->get();
            if(count($permisos_roles_db) > count($inputs['permisos'])){
                $total_max_permisos = count($permisos_roles_db);
            }else{
                $total_max_permisos = count($inputs['permisos']);
            }

            for ($i=0; $i < $total_max_permisos ; $i++) {
                if(isset($permisos_roles_db[$i])){ //Si existe un registro en la base de datos se edita o elimina.
                    $permiso_rol = $permisos_roles_db[$i];

                    if(isset($inputs['permisos'][$i])){ //Si hay permisos desde el fomulario, editamos el permiso de la base de datos.
                        $permiso_id = $inputs['permisos'][$i];
    
                        $permiso_rol->deleted_at = null; //Por si el elemento ya esta eliminado, lo restauramos
                        $permiso_rol->permiso_id = $permiso_id;
                        
                        $permiso_rol->save();
                    }else{ //de lo contrario eliminamos el permiso de la base de datos.
                        $permiso_rol->delete();
                    }
                }else{ //SI no existe un registro en la base de datos, se crea uno nuevo
                    $permiso_id = $inputs['permisos'][$i];
                    $permiso_rol = new PermisoRol();

                    $permiso_rol->permiso_id = $permiso_id;
                    $permiso_rol->rol_id = $rol->id;

                    $permiso_rol->save();
                }
            }
            //$rol->permisos()->sync($inputs['permisos']);

            $rol->save();

            DB::commit();
            return Response::json([ 'data' => $rol ],200);

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
        DB::beginTransaction();
        try {
            $rol = Rol::find($id);
            $permisos = $rol->permisos;

            foreach($permisos as $permiso){
                PermisoRol::where('permiso_id',$permiso->id)->where('rol_id',$rol->id)->delete();
            }

            $object = Rol::destroy($id);
            DB::commit();
            return Response::json(['data'=>$object],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
       
    }
}
