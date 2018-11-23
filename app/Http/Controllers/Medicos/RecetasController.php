<?php


namespace App\Http\Controllers\Medicos;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\RecetaDigital, App\Models\RecetaDigitalDetalles;

use App\Models\Usuario;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

class RecetasController extends Controller
{
     public function index()
    {
		$recetas = RecetaDigital::with("RecetaDigitalDetalles","Paciente")->where('medico_id',Input::get('medico_id'))->get();
		

		foreach($recetas as $receta){
			$claves = 0;
			$insumos = 0;
			foreach($receta->recetaDigitalDetalles as $detalle){
				$claves++;
				$insumos += $detalle->cantidad;
			}
			$receta->numero_claves = $claves;
			$receta->numero_insumos = $insumos;
		}

    	return Response::json([ 'data' => $recetas],200);

    }

    public function show($id)
    {

    	$receta = RecetaDigital::where('id',$id)->with(['paciente','unidadMedica','recetaDigitalDetalles.insumo','personalMedico'])->first();

    	
    	if(isset($receta))
    	{
			
			//$receta->paciente;
		//	$receta->recetaDigitalDetalles;
	//		$receta->personalMedico;
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
            'tipo_receta_id'           => 'required',
            'fecha_receta'          => 'required',
            'medico_id'             	=> 'required',
            'paciente_id'             	=> 'required',
            'diagnostico'           => 'required'
        ];

        $input = Input::all();
       
        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }
		
         DB::beginTransaction();
        try {
			$input['clues'] = $request->get('clues');
           
            $object = RecetaDigital::create($input);
			
            if(count($input['insumos']) > 0)
            {
				
            	foreach($input['insumos'] as $item){
            		//return Response::json([ 'data' => $item['clave_insumo_medico']],200);
            		$object->RecetaDigitalDetalles()->create([                
		                'clave_insumo_medico' 	=> $item['clave'],
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
            $object = RecetaDigital::destroy($id);
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
