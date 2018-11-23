<?php

namespace App\Http\Middleware;

use Closure, Request, \Exception;
use App\Models\Usuario, App\Models\UnidadMedica, App\Models\Servidor;

class VerificarAlmacen
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
           
            $usuario = Usuario::find($request->get('usuario_id'));
            $almacen_id = Request::header('X-Almacen-Id');
            $clues = Request::header('X-clues');
            if($almacen_id == null){
                throw new Exception('Debes especificar el almacén');
            }

            if(!$usuario->su){
                $almacen = $usuario->almacenes()->where('almacenes.id',$almacen_id)->first();
                if($almacen == null){
                    throw new Exception('No tienes permiso para usar este almacén: '.$almacen_id);
                }
            }
            
            $request->attributes->add(['almacen_id' => $almacen_id]);
            $request->attributes->add(['clues' => $clues]);

            if($request->getMethod() != "GET"){
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
            }
            
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }

        return $next($request);
    }

}