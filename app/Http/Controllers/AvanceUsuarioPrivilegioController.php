<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Requests;
use App\Models\Avance;
use App\Models\AvanceUsuarioPrivilegio;
use App\Models\Usuario;
use Carbon\Carbon;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

class AvanceUsuarioPrivilegioController extends Controller
{
    public function index(Request $request)
    {
        
        $parametros = Input::only('status','q','page','per_page', 'identificador');
        $usuario = Usuario::find($request->get('usuario_id'));
        $avance = Avance::find($parametros['identificador']);
        $avanceusuario = AvanceUsuarioPrivilegio::with('usuario')->where("avance_id", $parametros['identificador'])->whereNotIn("usuario_id", [$request->get('usuario_id'), $avance->usuario_id])->get();
        
        $usuarios = DB::table("usuarios")->join("rol_usuario", "rol_usuario.usuario_id", "=", "usuarios.id")
        							->WhereIn("rol_usuario.rol_id", [9,10])
        							->whereRaw("usuarios.id not in (select usuario_id from avance_usuario_privilegio where avance_id='".$parametros['identificador']."' and deleted_at is null)")
        							->whereNull("usuarios.deleted_at")
        							->get();

        $general = false;
        $permisos = [];
        $usuario_general = Usuario::with(['roles.permisos'=>function($permisos){
            $permisos->where('permisos.id','79B3qKuUbuEiR2qKS0CFgHy2zRWfmO4r');
        }])->find($request->get('usuario_id'));

        foreach ($usuario_general->roles as $index => $rol) {
            foreach ($rol->permisos as $permiso) {
                $permisos[$permiso->id] = true;

                if(count($permisos)){
                    $general = true;
                }
            }
        }
        if(count($permisos)){
            $general = true;
        }                            

        if($usuario->su == 1 || $general || $avance->usuario_id == $request->get('usuario_id') )
        	$privilegio = true;
        else
        	$privilegio = false;

        return Response::json([ 'data' => array('usuarios'=>$usuarios, "data_lista"=>$avanceusuario, "privilegio"=> $privilegio)],200);
    }

    public function store(Request $request){
        $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'usuario'        	=> 'required',
        ];

        $parametros = Input::all();
        try {
            DB::beginTransaction();
            $privilegios = Avance::where("id",$parametros['avance_id'])->where("usuario_id", $request->get('usuario_id'))->first();

            $usuario = Usuario::find($request->get('usuario_id'));

            $general = false;
            $permisos = [];
            $usuario_general = Usuario::with(['roles.permisos'=>function($permisos){
                $permisos->where('permisos.id','79B3qKuUbuEiR2qKS0CFgHy2zRWfmO4r');
            }])->find($request->get('usuario_id'));

            foreach ($usuario_general->roles as $index => $rol) {
                foreach ($rol->permisos as $permiso) {
                    $permisos[$permiso->id] = true;

                    if(count($permisos)){
                        $general = true;
                    }
                }
            }
            if(count($permisos)){
                $general = true;
            }     

            if($privilegios || $general || $usuario->su == 1)
            {
                $avance_busqueda = AvanceUsuarioPrivilegio::where("usuario_id", $parametros['usuario_id'])->where("avance_id", $parametros['avance_id'])->first();
                if(!$avance_busqueda)
            	   $avance = AvanceUsuarioPrivilegio::create($parametros);
            }else{
            	return Response::json(['error'=>"No tiene privilegios para agregar usuarios"],500);
            }	
           
            DB::commit();
            return Response::json([ 'data' => $avance ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    function destroy(Request $request, $id){
        try {
        	$usuarios = AvanceUsuarioPrivilegio::find($id);
            $usuario = Usuario::find($request->get('usuario_id'));
            $privilegios = Avance::where("id",$usuarios->avance_id)->where("usuario_id", $request->get('usuario_id'))->first();

            $general = false;
            $permisos = [];
            $usuario_general = Usuario::with(['roles.permisos'=>function($permisos){
                $permisos->where('permisos.id','79B3qKuUbuEiR2qKS0CFgHy2zRWfmO4r');
            }])->find($request->get('usuario_id'));

            foreach ($usuario_general->roles as $index => $rol) {
                foreach ($rol->permisos as $permiso) {
                    $permisos[$permiso->id] = true;

                    if(count($permisos)){
                        $general = true;
                    }
                }
            }
            if(count($permisos)){
                $general = true;
            }     

            if($privilegios || $general || $usuario->su==1)
            {
                $usuarios->delete();
                return Response::json(['data'=>$usuarios],200);
            }else
            {
                return Response::json(['error'=>"No tiene privilegios para eliminar este usuario"],500);
            }
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }

    }
}
