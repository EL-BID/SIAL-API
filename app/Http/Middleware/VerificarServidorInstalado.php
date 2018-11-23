<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Usuario;
use App\Models\UnidadMedica;


class VerificarServidorInstalado
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
			if(env('SERVIDOR_INSTALADO') == 'true' ){
				return redirect('/instalado');
				//abort(403, 'El servidor ya fue instalado y configurado.');
			}

			/*
			$path_env = base_path('.env');


			if (file_exists($path_env)) {
				$env = file_get_contents($path_env);
				$array = explode("\n",$env);
				$keys = [];
				foreach ($array as $key => $value) {
					if(trim($key)!= ""){
						$par = explode("=",$value);                
						if (trim($par[0]) != ""){
							$keys[$par[0]] = $par[1];
						}                
					}           
				}

				if(isset($keys['SERVIDOR_INSTALADO']) && $keys['SERVIDOR_INSTALADO'] == 'true'){
					abort(403, 'El servidor ya fue instalado y configurado.');
				} 

				
			} else {
				abort(500, 'El archivo .env no existe');
			}
			*/

          
        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }

        return $next($request);;
    }

}