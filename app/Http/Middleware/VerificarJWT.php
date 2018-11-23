<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Usuario, App\Models\UnidadMedica, App\Models\Servidor;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class VerificarJWT
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        try{
            $obj =  JWTAuth::parseToken()->getPayload();
            $usuario = Usuario::find($obj->get('id'));
            
            if(!$usuario){
                return response()->json(['error' => 'formato_token_invalido'], 401);                
            }

            //Harima: Si la ruta es para sincronizar, checamos que el usuario tenga permisos para sincronizar
            if($request->path() == 'sync/importar'){
                if($usuario->su && $usuario->servidor_id == env('SERVIDOR_ID')){
                    $tiene_permiso_sincronizar = true;
                }else{
                    $tiene_permiso_sincronizar = false;
                }
                
                $usuario->load('roles.permisos');
                if(count($usuario->roles)){
                    foreach($usuario->roles as $rol){
                        foreach ($rol->permisos as $permiso) {
                            if($permiso->id == '3DMVRdBv4cLGzdfAqXO7oqTvAMbEdhI7'){
                                $tiene_permiso_sincronizar = true;
                                break;
                            }
                        }
                        if($tiene_permiso_sincronizar){
                            break;
                        }
                    }
                }
                if(!$tiene_permiso_sincronizar){
                    return response()->json(['error' => 'usuario_no_tiene_permiso_sincronizar'], 403); 
                }
            }/*else if($request->getMethod() != "GET"){
                $clues = $request->header('X-clues');

                $unidad_medica = UnidadMedica::where('clues',$clues)->first();
                $servidor = Servidor::where('clues',$clues)->first();

                //Harima: primero se valida sila clues es offline, debería tener un servidor dado de alta en el sistema
                if($unidad_medica->es_offline && !$servidor){
                    return response()->json(['error' => 'servidor_no_encontrado'], 404); 
                }

                //Harima:Checamos si la clues seleccionada es offline y el servidor en el que se esta ejecutando es diferente al asignado a la clues, no permitimos ejecutar otra cosa que no sea GET, aun si el usuario es root
                if($unidad_medica->es_offline && env('SERVIDOR_ID') != $servidor->id && $request->getMethod() != "GET"){
                    return response()->json(['error' => 'clues_offline_solo_lectura', 'unidad'=>$unidad_medica, 'servidor'=>$servidor], 403); 
                }

                if($usuario->servidor_id != env('SERVIDOR_ID') && $request->getMethod() != "GET"){
                    // Esta linea es para que si un usuario quiere editar/agregar/eliminar información
                    // en un servidor al cual no corresponde se le deniegue la petición.
                    // Digamos que la información de un servidor offline sincronizada en el principal quiera ser editada
                    // si un usuario entra e inicia sesión. Así que solo le permitimos lectura.
                    return response()->json(['error' => 'usuario_servidor_invitado_solo_lectura'], 403); 
                }
            }*/
            
            // Pasamos el usuario id como verificado
            $request->attributes->add(['usuario_id' => $usuario->id]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'token_expirado'], 401);  
        } catch (JWTException $e) {
            return response()->json(['error' => 'token_invalido'], 500);
        }

        //return $next($request);
        $response = $next($request);
        
        $response->header('Api-Version','1.0');

        return $response;
    }

}