<?php

namespace App\Http\Controllers;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Http\Request, DB;
use \Hash, \Config, Carbon\Carbon;
use App\Models\Usuario, App\Models\Permiso, App\Models\Almacen, App\Models\AlmacenGeneral\ConfiguracionGeneral, App\Models\UnidadMedica, App\Models\Proveedor, App\Models\LogInicioSesion, App\Models\Servidor;

class AutenticacionController extends Controller
{
    public function autenticar(Request $request)
    {
        
        // grab credentials from the request
        $credentials = $request->only('id', 'password');

        try {

            //$usuarios = Usuario::where('su',0)->get();
            /*$nuevos_usuarios = DB::table('_usuarios_caravanas')->select('clues','nombre')->get();
            foreach ($nuevos_usuarios as $usuario) {
                $pass = str_replace(['á','é','í','ó','ú',' ','.','(',')','´'],['a','e','i','o','u'], mb_strtolower($usuario->nombre,'UTF-8'));
                DB::table('_usuarios_caravanas')->where('clues',$usuario->clues)->update(['pass'=>$pass]);
            }*/
            /*$nuevos_usuarios = DB::table('_usuarios_caravanas')->select('clues','pass')->get();
            foreach ($nuevos_usuarios as $usuario) {
                $usuarios_hash = Hash::make($usuario->pass);
                DB::table('_usuarios_caravanas')->where('clues',$usuario->clues)->update(['hash'=>$usuarios_hash]);
            }*/
            /*
            $usuarios_hash = [
                'hbcoxchuc' => 'hbcoxchuc',
                'hbcberriozabal' => 'hbcberriozabal',
                'hbcchiapacorzo' => 'hbcchiapacorzo',
                'hbcrosas' => 'hbcrosas'
            ];
            
            foreach ($usuarios_hash as $usuario => $hash) {
                //$usuario->password = str_replace(['á','é','í','ó','ú',' ','.','(',')'],['a','e','i','o','u'], mb_strtolower($usuario->nombre,'UTF-8'));
                //$usuario->password = Hash::make($usuario->password);
                //$usuario->save();
                $usuarios_hash[$usuario] = Hash::make($hash);
            }

            return response()->json(['error' => 'invalid_credentials', 'data'=>$usuarios_hash], 401); 
            */
           
            $usuario = Usuario::where('id',$credentials['id'])->first();

            if(!$usuario) {                
                return response()->json(['error' => 'invalid_credentials'], 401); 
            }

            $log_usuario = new LogInicioSesion();
            $log_usuario->usuario_id = $usuario->id;
            $log_usuario->servidor_id = $usuario->servidor_id;
            $log_usuario->ip = $request->ip();
            $log_usuario->navegador = $request->header('User-Agent');
            $log_usuario->updated_at = Carbon::now();

            if(Hash::check($credentials['password'], $usuario->password)){
                $lista_permisos = "";
                $modulo_inicio = null;
                if ($usuario->su) {
                    $permisos = Permiso::all();
                    foreach ( $permisos as $permiso){
                        if ($lista_permisos != "") {
                            $lista_permisos .= "|";
                        }
                        $lista_permisos.=$permiso->id;
                    }
                } else {
                    $roles = $usuario->roles;
                    
                    foreach ( $roles as $rol){
                        $modulo_inicio = $rol->modulo_inicio;
                        $permisos = $rol->permisos;
                        foreach ( $permisos as $permiso){
                            if ($lista_permisos != "") {
                                $lista_permisos .= "|";
                            }
                            $lista_permisos.=$permiso->id;
                        }
                    }

                    if($usuario->modulo_inicio){
                        $modulo_inicio = $usuario->modulo_inicio;
                    }
                }
                
                $claims = [
                    "sub" => 1,
                    "id" => $usuario->id,
                    //"nombre" => $usuario->nombre,
                    //"apellidos" => $usuario->apellidos,
                    //"permisos" => $lista_permisos
                ];

                $unidades_medicas = [];
               
                if( $usuario->su ){
                    //buscar que tengan almacenes y almacenes externos
                    //$ums = UnidadMedica::where('activa',1)->orderBy('unidades_medicas.nombre')->get();
                    
                    $ums_ext = UnidadMedica::whereIn('clues', function($query){
                        $query->select('clues_perteneciente')->from('almacenes')->where('externo',1);
                    });

                    $ums = UnidadMedica::has('almacenes')
                        ->union($ums_ext)
                        ->orderBy('nombre')
                        ->get();

                    foreach($ums as $um){
                        
                        $almacenes_externos = Almacen::where('clues_perteneciente',$um->clues)->get();
                        
                        $um->almacenes; // Obtenemos los almacenes
                        $um->almacenes_externos = $almacenes_externos; // Obtenemos los almacenes externos

                       

                    }
                    $unidades_medicas = $ums;

                    //$unidades_medicas = UnidadMedica::has('almacenes')->with('almacenes')->orderBy('unidades_medicas.nombre')->get();
                } else {
                    $ums = $usuario->unidadesMedicas;
                    $almacenes = $usuario->almacenes()->lists("almacenes.id");
                    foreach($ums as $um){
                        $almacenes_res = $um->almacenes()->whereIn('almacenes.id',$almacenes)->get(); // aqui inserta almaces que dependande mi
                        $um->almacenes = $almacenes_res;
                        $almacenes_externos = Almacen::where('clues_perteneciente',$um->clues)->get();
                        $um->almacenes_externos = $almacenes_externos; // Obtenemos los almacenes externos
                        //$almacenes = $um->almacenes()->has('usuarios')->where('usuario_id',$usuario->id)->get();
                    }
                    $unidades_medicas = $ums;
                }

                $servidor_usuario = Servidor::find($usuario->servidor_id);
                

                $usuario_data = [
                    "id" => $usuario->id,
                    "nombre" => $usuario->nombre,
                    "apellidos" => $usuario->apellidos,
                    "su" => $usuario->su,
                    "avatar" => $usuario->avatar,
                    "medico_id" =>$usuario->medico_id,
                    "permisos" => $lista_permisos,                    
                    "unidades_medicas" =>  $unidades_medicas,
                    "servidor" => $servidor_usuario,
                    "modulo_inicio" => $modulo_inicio
                ];

                if($usuario->su){ //Harima: se agrego para los modulos de los proveedores
                    $usuario_data["proveedores"] = Proveedor::all();
                }

                $server_info = [
                    "server_datetime_snap" => getdate(),
                    "token_refresh_ttl" => Config::get("jwt.refresh_ttl"),
                    "api_version" => Config::get("sync.api_version"),
                    "data" => Servidor::find(env('SERVIDOR_ID')),
                ];

                $log_usuario->login_status = 'OK';
                $log_usuario->save();

                $payload = JWTFactory::make($claims);
                $token = JWTAuth::encode($payload);


                // CONFIGURACIÓN GENERAL
                $variable = ConfiguracionGeneral::get();
                $configuracion = [];

                foreach ($variable as $key => $value) {			
                    if($value->clave == 'fondo' || $value->clave == 'logo')
                        $configuracion[$value->clave] = $value->valor;
                    else
                        $configuracion[$value->clave] = json_decode($value->valor);
                }
                // CONFIGURACIÓN GENERAL
                

                $data_configuracion =  ["iva"=>16];
                return response()->json(['token' => $token->get(), 'configuracion_general'=>$configuracion, 'usuario'=>$usuario_data, 'server_info'=> $server_info], 200);
            } else {
                $log_usuario->login_status = 'ERR_PSW';
                $log_usuario->save();
                return response()->json(['error' => 'invalid_credentials'], 401); 
            }

        } catch (JWTException $e) {
            $log_usuario->login_status = 'ERR_TKN';
            $log_usuario->save();
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
    }
    public function refreshToken(Request $request){
        try{
            $token =  JWTAuth::parseToken()->refresh();
            $server_info = [
                    "server_datetime_snap" => getdate(),
                    "token_refresh_ttl" => Config::get("jwt.refresh_ttl"),
                    "api_version" => Config::get("sync.api_version"),
                    "data" => Servidor::find(env('SERVIDOR_ID')),
            ];
            return response()->json(['token' => $token, 'server_info'=> $server_info], 200);

        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'token_expirado'], 401);  
        } catch (JWTException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function verificar(Request $request)
    {   
        try{
            $obj =  JWTAuth::parseToken()->getPayload();
            return $obj;
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'no_se_pudo_validar_token'], 500);
        }
        
    }
}