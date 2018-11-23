<?php

namespace App\Http\Controllers\OpcionesAvanzadas;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class ActualizarPlataformaController extends Controller
{
    

    public function git(Request $request){
        try{
            
			$base_path = base_path();
			$output = "LOG API:\n";
			$script_api = "
				cd $base_path
				pwd
				git pull origin master
				php artisan migrate
			"; 
			$output .= shell_exec($script_api);
			$output .= "\nLOG CLIENTE:\n";	
			if(env("CLIENTE_MISMO_SERVIDOR") == "true"){
				
				$base_path = env("PATH_CLIENTE");		
				$script_cliente = "
					cd $base_path
					pwd
					git pull origin master
				";
				$output .= shell_exec($script_cliente) ;
			} else {	
				$output .= "\nEl cliente no se encuentra en el mismo servidor, por lo cual no puede actualizarse por este medio, consulte con soporte técnico para mas detalle.\n";	
			}
			
      
            return Response::json([ 'data' => $output],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }




}
