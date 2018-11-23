<?php

namespace App\Http\Controllers\OpcionesAvanzadas;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use Carbon\Carbon;

class FechaHoraServidorController extends Controller
{
    

    public function get(Request $request){
        try{
            
			
			
			$datetime = Carbon::now();
			return Response::json([ 'data' => $datetime->toDateTimeString()],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        } 
    }

	public function update(Request $request){
		$mensajes = [
			'required'  => "required",
			'date'      => "date",
			'date_format'		=> "date_format"
        ];

        $reglas = [
            'fecha'               => 'required|date',
            'hora'                => 'required|date_format:H:m:i'
		];
		
		try{
			$parametros = Input::all();
			$v = Validator::make($parametros, $reglas, $mensajes);

			if ($v->fails()) {
				return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
			}


			$base_path = base_path();
			$script = $base_path."/app/Scripts/CambiarFechaYHora.sh \"".$parametros["fecha"]."\" \"".$parametros["hora"]."\" 2>&1";
			$output = "";
			$preout =  shell_exec($script);
			$output.= $preout;

			$fecha_actualizada = strpos($preout,$parametros["fecha"]." ".$parametros["hora"]);
			if($fecha_actualizada !== false ){
				return Response::json([ 'data' => $output],200);
			} else {
				throw new \Exception($output);
			}

			
		} catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        } catch (\FatalErrorException $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        } 
	}



}
