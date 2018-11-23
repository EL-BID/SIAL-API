<?php
namespace App\Librerias\Patches;
use \DB, \Config;
use Carbon\Carbon;
use App\Models\LogEjecucionParche;

 /*
 Clase de ejemplo para aplicación en parches
 */
class Parches {

    /**
     * Se ejecuta despues del parche
     *
     * @return Void
     */
    public static function ejecutar($nombre_parche = null){
       try{
            \Artisan::call('migrate');
            $parches = Config::get("patches");

            $fecha_parche = '';

            if(!$nombre_parche){
                $ultimo_parche = $parches[0];
                $nombre_parche = $ultimo_parche['nombre'];
                $fecha_parche = $ultimo_parche['fecha'];
            }else{
                foreach($parches as $parche){
                    if($parche['nombre'] == $nombre_parche){
                        $fecha_parche = $parche['fecha'];
                        break;
                    }
                }
            }

            $parche_log = LogEjecucionParche::where('nombre_parche',$nombre_parche)->first();

            if(!$parche_log){
                $parche_log = LogEjecucionParche::create(['clues'=>env('CLUES'),'nombre_parche'=>$nombre_parche,'tipo_parche'=>'api','fecha_liberacion'=>$fecha_parche]);
            }

            $parche_log->fecha_ejecucion = Carbon::now();
            $parche_log->save();
            
            return "Parche Ejecutado: ".$nombre_parche;
       } catch(\Exception $e){
            return false;
       }
    }
    
    public static function ejecutarParche4($nombre_parche = null){
        try{
            \Artisan::call('migrate');

            $path = base_path('.env');

            if (file_exists($path)) {
                file_put_contents($path, str_replace('DB_HOST_SYNC=5.196.110.162', 'DB_HOST_SYNC=5.196.110.172', file_get_contents($path)));
            }

            $parches = Config::get("patches");
            $fecha_parche = '';

            if(!$nombre_parche){
                $ultimo_parche = $parches[0];
                $nombre_parche = $ultimo_parche['nombre'];
                $fecha_parche = $ultimo_parche['fecha'];
            }else{
                foreach($parches as $parche){
                    if($parche['nombre'] == $nombre_parche){
                        $fecha_parche = $parche['fecha'];
                        break;
                    }
                }
            }

            $parche_log = LogEjecucionParche::where('nombre_parche',$nombre_parche)->first();

            if(!$parche_log){
                $parche_log = LogEjecucionParche::create(['clues'=>env('CLUES'),'nombre_parche'=>$nombre_parche,'tipo_parche'=>'api','fecha_liberacion'=>$fecha_parche]);
            }

            $parche_log->fecha_ejecucion = Carbon::now();
            $parche_log->save();
            
            return "Parche Ejecutado: ".$nombre_parche;
        }catch(\Exception $e){
            return false;
        }
    }
}