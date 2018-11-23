<?php
namespace App\Librerias\Sync;

class ArchivoSync {

    /**
     * Devuelve las variables contenidas en un texto en forma de arreglo.
     *
     * @param  string  $contenido
     * @return Array
     */
    public static function parseVars($contenido){
        $array = explode("\n",$contenido);
        $respuesta = [];
        foreach ($array as $key => $value) {
            if(trim($key)!= ""){
                $par = explode("=",$value);                
                if (trim($par[0]) != ""){
                    $respuesta[$par[0]] = $par[1];
                }                
            }           
        }
        return $respuesta;
    }
}