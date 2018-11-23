<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Usuario;
use App\Models\Servidor;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //return Response::json([ 'data' => []],200);
        //return Response::json(['error' => ""], HttpResponse::HTTP_UNAUTHORIZED);
        $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
             $usuarios =  Usuario::where('su',false)->where(function($query) use ($parametros) {
                 $query->where('id','LIKE',"%".$parametros['q']."%")->orWhere(DB::raw("CONCAT(nombre,' ',apellidos)"),'LIKE',"%".$parametros['q']."%");
             });
        } else {
             $usuarios =  Usuario::where('su',false);
        }
        

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $usuarios = $usuarios->paginate($resultadosPorPagina);
        } else {
            $usuarios = $usuarios->get();
        }
       
        return Response::json([ 'data' => $usuarios, 'server_IP' => $_SERVER],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $servidor = Servidor::find(env('SERVIDOR_ID'));
        //return Response::json(['error' => ""], HttpResponse::HTTP_UNAUTHORIZED);
        $mensajes = [
            'required'      => "required",
            'email'         => "email",
            'unique'        => "unique"
        ];

        $reglas = [
            'id'            => 'required|unique:usuarios',
            'password'      => 'required',
            'nombre'        => 'required',
            'apellidos'     => 'required'
        ];

        $inputs = Input::only('id','servidor_id','password','nombre', 'apellidos','avatar','roles','unidades_medicas','almacenes','medico_id','proveedor_id',"pregunta_secreta","respuesta");

        if(!$servidor->principal){
            $inputs['id'] = env('SERVIDOR_ID') . ':' . $inputs['id'];
        }

        if(isset($inputs['pregunta_secreta']) && trim($inputs['pregunta_secreta']) != ""){
            $reglas['respuesta'] = "required";
        }
        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }
        DB::beginTransaction();
        try {

            if(isset($inputs['medico_id'])){
                if( $inputs['medico_id'] == '-1'){
                   unset($inputs['medico_id']);
                } 
            };

            if(isset($inputs['proveedor_id'])){
                if( $inputs['proveedor_id'] == -1){
                   unset($inputs['proveedor_id']);
                } 
            };

            $inputs['servidor_id'] = env("SERVIDOR_ID");
            $inputs['password'] = Hash::make($inputs['password']);
            $usuario = Usuario::create($inputs);

            $usuario->roles()->sync($inputs['roles']);
            $usuario->unidadesMedicas()->sync($inputs['unidades_medicas']);
            $usuario->almacenes()->sync($inputs['almacenes']);
            DB::commit();
            return Response::json([ 'data' => $usuario ],200);

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
        $object = Usuario::find($id);

        
        
        if(!$object ){
            
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        unset($object->password);
        $object->roles;
        
        $object->unidades_medicas = $object->unidadesMedicas()->with('almacenes')->get();
        $object->almacenes;

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
        $servidor = Servidor::find(env('SERVIDOR_ID'));

        $usuario_edita = Usuario::find($request->get('usuario_id'));

        $mensajes = [
            
            'required'      => "required",
            'email'         => "email",
            'unique'        => "unique"
        ];

        $reglas = [
            'id'            => 'required|unique:usuarios,id,'.$id,
            'password'      => 'required_with:cambiarPassword',
            //'respuesta'      => 'required_if:pregunta_secreta',
            'nombre'        => 'required',
            'apellidos'     => 'required'
        ];
        $object = Usuario::find($id);

        if(!$object){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        if($servidor->id == '0001'){
            if($object->servidor_id != $servidor->id && !$usuario_edita->su){
                return Response::json(['error' => "No se puede editar este usuario, ya que fue creado en un servidor diferente al actual."], 500);
            }
        }else{
            if($object->servidor_id != $servidor->id){
                return Response::json(['error' => "No se puede editar este usuario, ya que fue creado en un servidor diferente al actual."], 500);
            }
        }

        $inputs = Input::only('id','servidor_id','password','nombre', 'apellidos','avatar','roles','cambiarPassword','unidades_medicas','almacenes','medico_id','proveedor_id',"pregunta_secreta","respuesta");

        if(!$servidor->principal){
            $inputs['id'] = env('SERVIDOR_ID') . ':' . $inputs['id'];
        }

        if(isset($inputs['pregunta_secreta']) && trim($inputs['pregunta_secreta']) != ""){
            $reglas['respuesta'] = "required";
        }


        $v = Validator::make($inputs, $reglas, $mensajes);
        
        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
            $object->nombre =  $inputs['nombre'];
            
            if(isset($inputs['medico_id'])){
                if( $inputs['medico_id'] != '-1'){
                    $object->medico_id = $inputs['medico_id'];
                } else {
                    $object->medico_id = null;
                } 
            };

            if(isset($inputs['proveedor_id'])){
                if( $inputs['proveedor_id'] != -1){
                    $object->proveedor_id = $inputs['proveedor_id'];
                } else {
                    $object->proveedor_id = null;
                } 
            };
            
            $object->pregunta_secreta = $inputs['pregunta_secreta'];
            $object->respuesta = $inputs['respuesta'];
            $object->apellidos =  $inputs['apellidos'];
            $object->avatar =  $inputs['avatar'];
            //$object->id =  $inputs['id'];
            if ($inputs['cambiarPassword'] ){
                $object->password = Hash::make($inputs['password']);
            }
            
            $object->save();

            

            $object->roles()->sync($inputs['roles']);
            $object->unidadesMedicas()->sync($inputs['unidades_medicas']);
            $object->almacenes()->sync($inputs['almacenes']);

            /*
            $object->roles;
            $object->almacenes;
            $object->unidadesMedicas()->with('almacenes');
            unset($object->password); 
*/
            DB::commit();
            return Response::json([ 'data' => $object ],200);

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
    //return Response::json(['error' => ""], HttpResponse::HTTP_UNAUTHORIZED);
       try {
			$object = Usuario::destroy($id);
			return Response::json(['data'=>$object],200);
		} catch (Exception $e) {
		   return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		}
    }
}
