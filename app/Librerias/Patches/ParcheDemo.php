<?php
namespace App\Librerias\Patches;
use \DB;
 /*
 Clase de ejemplo para aplicación en parches
 */
class ParcheDemo {

    /**
     * Se ejecuta antes del parche
     *
     * @return Void
     */
    public static function ejecutar(){
       try{
           
            return "sumami";

       } catch(\Exception $e){
            return false;
       }
        
        
	}
}