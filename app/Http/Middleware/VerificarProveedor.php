<?php

namespace App\Http\Middleware;

use Closure, Request, \Exception;
use App\Models\Usuario;
use Illuminate\Support\Facades\Input;

class VerificarProveedor
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

            if(!$usuario->su){
                $proveedor_id = $usuario->proveedor_id;
                if($proveedor_id == null){
                    throw new Exception('No tienes permiso para usar este modulo');
                }
            }else{
                $proveedor_id = Request::header('X-Proveedor-Id');
                if(!$proveedor_id){
                    $proveedor_id = Input::get('proveedor');
                }
                if($proveedor_id == null){
                    throw new Exception('Debes especificar un proveedor');
                }
            }
            
            $request->attributes->add(['proveedor_id' => $proveedor_id]);
            
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }

        return $next($request);
    }

}