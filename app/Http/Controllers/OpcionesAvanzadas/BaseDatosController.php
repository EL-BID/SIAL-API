<?php

namespace App\Http\Controllers\OpcionesAvanzadas;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \Storage, \Artisan;

class BaseDatosController extends Controller
{
    

    public function exportar(Request $request){
        try{
			Storage::makeDirectory("db/backup");
			$filename = "sial.".date('Ymd.His').".sql";			

			$base_path = base_path();
			Storage::put('db/credenciales.cnf',"[client]");
			Storage::append('db/credenciales.cnf',"user=".env('DB_USERNAME'));
			Storage::append('db/credenciales.cnf',"password=".env('DB_PASSWORD'));
			Storage::append('db/credenciales.cnf',"host=".env('DB_HOST'));

			$output = "";
			$script = "
				cd $base_path				
				cd storage/app/db							
				".env('PATH_MYSQL')."/mysqldump --defaults-extra-file=credenciales.cnf --add-drop-database --opt  ".env('DB_DATABASE')." > 'backup/$filename' --databases 2>&1				
			"; 
			$output .= shell_exec($script);
		
			
			///Then download the zipped file.
			header('Content-Type: application/sql');
			header('Content-disposition: attachment; filename='.$filename);
			header('Content-Length: ' . filesize($base_path."/storage/app/db/backup/".$filename));
		 
			readfile($base_path."/storage/app/db/backup/".$filename);
			\File::delete($base_path."/storage/app/db/backup/".$filename);
			exit();

      
            //return Response::json([ 'data' => $output],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
	}
	
	public function importar(Request $request){
		// Abrá que implementar algún tipo de seguridad para que no suban otro script que por ejemplo borre toda la bases de datos		
		ini_set('memory_limit', '-1');
        try{
			Storage::makeDirectory("db/importar");
			if ($request->hasFile('sql')){
				$file = $request->file('sql');

				if ($file->isValid()) {
					Storage::put(
                        "db/importar/".$file->getClientOriginalName(),
                        file_get_contents($file->getRealPath())
					);
					$base_path = base_path();

					$filename = $file->getClientOriginalName();
					
					Storage::put('db/credenciales.cnf',"[client]");
					Storage::append('db/credenciales.cnf',"user=".env('DB_USERNAME'));
					Storage::append('db/credenciales.cnf',"password=".env('DB_PASSWORD'));
					Storage::append('db/credenciales.cnf',"host=".env('DB_HOST'));

					$output = "";
					$script = "
						cd $base_path				
						cd storage/app/db							
						".env('PATH_MYSQL')."/mysql --defaults-extra-file=credenciales.cnf -D ".env('DB_DATABASE')." < 'importar/$filename' 				
					"; 
					$output .= shell_exec($script);
					return Response::json([ 'data' => $output],200);

				} else {
					throw new \Exception("Archivo inválido.");
				}
			} else {
				throw new \Exception("No hay archivo.");
			}

			/*
			$filename = "sial.".date('Ymd.His').".sql";			

			$base_path = base_path();
			Storage::put('db/backup/credenciales.cnf',"[client]");
			Storage::append('db/backup/credenciales.cnf',"user=".env('DB_USERNAME'));
			Storage::append('db/backup/credenciales.cnf',"password=".env('DB_PASSWORD'));
			Storage::append('db/backup/credenciales.cnf',"host=".env('DB_HOST'));

			$output = "";
			$script = "
				cd $base_path				
				cd storage/app/db/backup							
				".env('PATH_MYSQL')."/mysqldump --defaults-extra-file=credenciales.cnf --add-drop-database --opt  ".env('DB_DATABASE')." > '$filename' --databases 2>&1				
			"; 
			$output .= shell_exec($script);
		
			
			///Then download the zipped file.
			header('Content-Type: text/plain');
			header('Content-disposition: attachment; filename='.$filename);
			header('Content-Length: ' . filesize($base_path."/storage/app/db/backup/".$filename));
		 
			readfile($base_path."/storage/app/db/backup/".$filename);
			\File::delete($base_path."/storage/app/db/backup/".$filename);
			exit();
*/
      
            //return Response::json([ 'data' => $output],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }
}
