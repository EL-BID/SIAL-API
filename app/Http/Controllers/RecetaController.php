<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Requests;
use App\Models\Receta;

use App\Models\Usuario;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

class RecetaController extends Controller
{
     public function index()
    {
    	$receta = Receta::with("RecetaDetalles")->get();
    	return Response::json([ 'data' => $receta],200);

    }

    public function show($id)
    {

    	$receta = Receta::find($id);

    	
    	if(isset($receta))
    	{
    		$receta = $receta->load("RecetaDetalles")->first();
    		return Response::json([ 'data' => $receta],200);	
    	}else
    		return Response::json(['error' => "No se encuentra el insumo que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
    	
    }

    public function store(Request $request)
    {
    	$mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'folio'        			=> 'required',
            'tipo_receta'           => 'required',
            'fecha_receta'          => 'required',
            'doctor'             	=> 'required',
            'paciente'             	=> 'required',
            'diagnostico'           => 'required'
        ];

        $input = Input::all();
       
        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

         DB::beginTransaction();
        try {
           
            $object = Receta::create($input);

            if(count($input['insumos']) > 0)
            {
            	foreach($input['insumos'] as $item){
            		//return Response::json([ 'data' => $item['clave_insumo_medico']],200);
            		$object->RecetaDetalles()->create([                
		                'clave_insumo_medico' 	=> $item['clave_insumo_medico'],
		                'cantidad' 				=> $item['cantidad'],
		                'dosis' 				=> $item['dosis'],
		                'frecuencia' 			=> $item['frecuencia'],
		                'duracion' 				=> $item['duracion']
		            ]);
            	}
            }else
            {
            	DB::rollBack();
            	return Response::json(['error' => "Debe de tener al menos un medicamento"], HttpResponse::HTTP_NOT_FOUND);
            }

            
            DB::commit();

            return Response::json([ 'data' => $object],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function update(Request $request, $id)
    {
    }

     function destroy($id)
    {
    	 try {
            $object = Receta::destroy($id);
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
