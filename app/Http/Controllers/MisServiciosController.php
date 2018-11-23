<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;


use App\Models\CluesServicio;
use App\Models\UnidadMedica;


/** 
* Controlador MisServicios
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-03-22
*
* Controlador `MisServicios`: Controlador  para el manejo de mis servicios disponibles en la unidad medica
*
*/
class MisServiciosController extends Controller
{ 
///***************************************************************************************************************************
///***************************************************************************************************************************
   
    public function index(Request $request)
    {
        $clues = $request->get('clues');

        if(!$clues){
            return Response::json(array("status" => 404,"messages" => "Debe especificar una Unidad Médica."), 404);
        }      
 
        $data = UnidadMedica::where('clues',$clues)->first();
        if(!$data){
            return Response::json(array("status" => 404,"messages" => "No se encuentra la Unidad médica"), 200);
        } 
        else{
              
            $data->clues_servicios = DB::table("clues_servicios as cs")
            ->leftJoin('servicios as s', 's.id', '=' , 'cs.servicio_id')
            ->select('s.id','s.nombre','cs.updated_at')
            ->where("clues", $clues)
            ->where('cs.deleted_at',NULL)
            ->get();
            return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $data), 200);
        }      
    }

 ///***************************************************************************************************************************
///***************************************************************************************************************************
 
 public function store(Request $request)
    {
        $clues = $request->get('clues');
        if(!$clues){
            return Response::json(array("status" => 404,"messages" => "Debe especificar una Unidad Médica."), 404);
        } 

        $data = UnidadMedica::where('clues',$clues)->first();
        $datos = (object) Input::json()->all();     

        $success = false;
        DB::beginTransaction();
        try{
            if(!$data)
            {
                return Response::json(['error' => "No se encuentra la unidad médica solicitada."], HttpResponse::HTTP_NOT_FOUND);
            }
            $success = $this->ejecutarTransaccionServicios($datos, $data);
        } catch (\Exception $e) {
                                    DB::rollback();
                                    return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                                } 
        if($success){
                        DB::commit();
                        return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
                    }else{
                            DB::rollback();
                            return Response::json(array("status" => 304, "messages" => "No modificado"),200);
                         }
    }

 ///***************************************************************************************************************************
///***************************************************************************************************************************
 
    public function show($id)
    {

        
    }

///***************************************************************************************************************************
///***************************************************************************************************************************

    public function update(Request $request,$id)
    {
        
    }
///***************************************************************************************************************************
///***************************************************************************************************************************
      
    public function destroy($id)
    {
        
    }
///***************************************************************************************************************************
///***************************************************************************************************************************
 
 private function ejecutarTransaccionServicios($datos, $data)
 {
        $success = false;
        //comprobar que el servidor id no me lo envian por parametro, si no poner el servidor por default de la configuracion local, si no seleccionar el servidor del parametro
        $servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');
        
        //agregar al modelo los datos
        $data->nombre               =  $datos->nombre;
        $data->clues                =  $datos->clues;
                
            $success = true;
        
            if(property_exists($datos, "clues_servicios"))
            {
                //limpiar el arreglo de posibles nullos
                $detalle = array_filter($datos->clues_servicios, function($v){return $v !== null;});

                //borrar los datos previos de articulo para no duplicar información
                CluesServicio::where("clues", $data->clues)->delete(); 
                //recorrer cada elemento del arreglo
                foreach ($detalle as $key => $value) 
                {
                    //validar que el valor no sea null
                    if($value != null)
                    {
                        //comprobar si el value es un array, si es convertirlo a object mas facil para manejar.
                        if(is_array($value))
                            $value = (object) $value;
                        //comprobar que el dato que se envio no exista o este borrado, si existe y esta borrado poner en activo nuevamente
                        DB::update("update clues_servicios set deleted_at = null where clues = '".$data->clues."' and servicio_id = '".$value->id."'");
                        
                        //si existe el elemento actualizar
                        $item = CluesServicio::where("clues", $data->clues)->where("servicio_id", $value->id)->first();
                        //si no existe crear
                        if(!$item)
                            $item = new CluesServicio;

                        //llenar el modelo con los datos
                        $item->servicio_id     = $value->id; 
                        $item->clues  = $data->clues; 

                        $item->save();         
                    }
                }
            }           
        
        return $success;
    }
	 
///****************************************************************************************************************************
 
///****************************************************************************************************************************
}
