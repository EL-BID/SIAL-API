<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;


use App\Models\CluesClave;
use App\Models\UnidadMedica;


/** 
* Controlador Mis Claves
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-03-22
*
* Controlador `Mis Claves`: Controlador  para el manejo de claves de insumos manejados en la unidad
*
*/
class MisClavesController extends Controller
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
              
            $data->clues_claves = DB::table("clues_claves")
            ->leftJoin('insumos_medicos', 'insumos_medicos.clave', '=' , 'clues_claves.clave_insumo_medico')
            ->select('clues_claves.clues','clues_claves.clave_insumo_medico','clues_claves.usuario_id','clues_claves.created_at','clues_claves.updated_at',
                     'insumos_medicos.clave','insumos_medicos.tipo','insumos_medicos.es_causes','insumos_medicos.descripcion')
            ->where("clues", $clues)
            ->where("clues_claves.deleted_at", NULL)
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
            $success = $this->ejecutarTransaccionClaves($datos, $data);
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
 

    public function update(Request $request, $id)
    {
 
    }
///***************************************************************************************************************************
///***************************************************************************************************************************
      
    public function destroy($id)
    {
        
    }
///***************************************************************************************************************************
///***************************************************************************************************************************
 
	 private function ejecutarTransaccionClaves($datos, $data)
 {
        $success = false;
        //comprobar que el servidor id no me lo envian por parametro, si no poner el servidor por default de la configuracion local, si no seleccionar el servidor del parametro
        $servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');
        
        //agregar al modelo los datos
        $data->nombre               =  $datos->nombre;
        $data->clues                =  $datos->clues;
                
            $success = true;
        
            if(property_exists($datos, "clues_claves"))
            {
                //limpiar el arreglo de posibles nullos
                $detalle = array_filter($datos->clues_claves, function($v){return $v !== null;});

                //borrar los datos previos de articulo para no duplicar información
                CluesClave::where("clues", $data->clues)->delete();
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
                        DB::update("update clues_claves set deleted_at = null where clues = '".$data->clues."' and clave_insumo_medico = '".$value->clave_insumo_medico."'");
                        
                        //si existe el elemento actualizar
                        $item = CluesClave::where("clues", $data->clues)->where("clave_insumo_medico", $value->clave_insumo_medico)->first();
                        //si no existe crear
                        if(!$item)
                            $item = new CluesClave;

                        //llenar el modelo con los datos
                        $item->clave_insumo_medico      = $value->clave_insumo_medico; 
                        $item->clues                    = $data->clues; 

                        $item->save();         
                    }
                }
            }           
        
        return $success;
    }
	 
///****************************************************************************************************************************
 

}
