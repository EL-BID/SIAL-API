<?php

namespace App\Http\Controllers\Patches;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use App\Http\Requests;
use App\Exceptions\PatchException as PatchException, \DB, \Storage, \ZipArchive, \Hash, \Response, \Config;
use Illuminate\Support\Facades\Input;
use App\Librerias\Patches;
use Carbon\Carbon;
use App\Models\LogEjecucionParche;

class PatchesController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lista()
    {
		try{
			$items = [];
			$parches_aplicados = LogEjecucionParche::where('tipo_parche','api')->get();
			$parches_api_aplicados = [];

			foreach ($parches_aplicados as $parche) {
				$parches_api_aplicados[$parche->nombre_parche] = $parche->toArray();
			}
			
			foreach(Config::get("patches") as $item){
				$item['fecha_ejecucion'] = null;
				$item['fecha_aplicacion'] = null;
				if(isset($parches_api_aplicados[$item['nombre']])){
					$item['fecha_aplicacion'] = $parches_api_aplicados[$item['nombre']]['created_at'];
					$item['fecha_ejecucion'] = $parches_api_aplicados[$item['nombre']]['fecha_ejecucion'];
				}
				$items[] = $item;
			}

			$parches_cliente_aplicados = LogEjecucionParche::where('tipo_parche','cliente')->get();

			return Response::json([ 'data' => ['api'=>$items,'cliente'=>$parches_cliente_aplicados->pluck('fecha_ejecucion','nombre_parche')]],200);
		}catch (\Exception $e) {
			return Response::json(['error' => $e->getMessage()], 500);
		} catch (\FatalErrorException $e) {
			return Response::json(['error' => $e->getMessage()], 500);
		}
	}

	public function ejecutarParche(Request $request){
		try{
			$parametros = Input::all();
			$output = '';
			foreach(Config::get("patches") as $item){
				if($item['nombre'] == $parametros['nombre']){
					if($item['ejecutar'] != ''){
						//$o = call_user_func($item['ejecutar']);
						$o = call_user_func_array($item['ejecutar'],array($item['nombre']));
						if($o == false){
							throw new \Exception("No se pudieron ejecutar las instrucciones del parche, debido a un error en la función configurada.", 1);
						}
						$output .= "Instrucciones del parche ejecutadas correctamente.";
						break;
					}  
				}
			}
			return Response::json([ 'data' => $output],200);
		}catch (\Exception $e) {
			return Response::json(['error' => $e->getMessage()], 500);
		} catch (\FatalErrorException $e) {
			return Response::json(['error' => $e->getMessage()], 500);
		}
	}

	public function ejecutar(Request $request){
		
		ini_set('memory_limit', '-1');
        try{
			Storage::makeDirectory("patches");
			if ($request->hasFile('patch')){
				$file = $request->file('patch');

				if ($file->isValid()) {
					Storage::put(
                        "patches/".$file->getClientOriginalName(),
                        file_get_contents($file->getRealPath())
					);
					$filename =  $file->getClientOriginalName();
					$array_filename = explode(".",$filename);
					
					$output = "No se aplicó ningún parche.";

					$patch_name = "";
					for($i = 1; $i < count($array_filename); $i++){
						if($i>1){
							$patch_name .= ".";
						}
						$patch_name .= $array_filename[$i];
					}

					if(strtoupper($array_filename[0]) != strtoupper(md5($patch_name))){
						throw new \Exception("El nombre del archivo fue alterado");
					}
					
					if($array_filename[2]=="cliente"){
						$output = "Ejecutando parche No. ".$array_filename[3]." al cliente...\n\n";
						$api_base_path = base_path();	
						$base_path = env("PATH_CLIENTE");	
						
					
						$script = $api_base_path."/app/Scripts/ApplyGitPatch.sh ".$base_path." ".$api_base_path."/storage/app/patches/".$filename." 2>&1";

						$preout =  shell_exec($script);
						
						$output.= $preout;

						$patchSumami = strpos($preout,"Patch error sumami");
						$patchFailed = strpos($preout,"Patch failed");
						$patchEmpty = strpos($preout,"Patch is empty");

							
						if($patchFailed !== false  || $patchFailed !== false || $patchEmpty !== false || trim($preout) == "" ){						
							$script = $api_base_path."/app/Scripts/AbortGitPatch.sh ".$base_path." 2>&1";
							$preout = shell_exec($script);
							$output .= $preout;
							$output .= "\nEl parche no se pudo ejecutar :(";
							throw new PatchException($output);
						} else {
							$output .= "\n¡Parche ejecutado correctamente!";

							$parche_log = LogEjecucionParche::create(['clues'=>env('CLUES'),'nombre_parche'=>$filename,'tipo_parche'=>'cliente','fecha_liberacion'=>Carbon::now()]);
							$parche_log->fecha_ejecucion = Carbon::now();
							$parche_log->save();
						}
						
					
					}

					if($array_filename[2]=="api"){
						$output = "Ejecutando parche No. ".$array_filename[3]." a la API...\n\n";
						$base_path = base_path();
						$script = $base_path."/app/Scripts/ApplyGitPatch.sh ".$base_path." ".$base_path."/storage/app/patches/".$filename." 2>&1";

						$preout =  shell_exec($script);
						$output.= $preout;

						$patchSumami = strpos($preout,"Patch error sumami");
						$patchFailed = strpos($preout,"Patch failed");
						$patchEmpty = strpos($preout,"Patch is empty");

						if($patchFailed !== false  || $patchFailed !== false || $patchEmpty !== false || trim($preout) == ""){
							$script = $base_path."/app/Scripts/AbortGitPatch.sh ".$base_path." 2>&1";
							$preout = shell_exec($script);
							$output .= $preout;
							$output .= "\nEl parche no se pudo ejecutar :(";
							throw new PatchException($output);
						} 

						foreach(Config::get("patches") as $item){
							if($item['nombre'] == $filename){
								if($item['ejecutar'] != ''){
									//$o = call_user_func($item['ejecutar']);
									$o = call_user_func_array($item['ejecutar'],array($filename));
									if($o == false){
										$output .= "\nEl parche se aplicó, pero no se pudieron ejecutar las instrucciones posteriores, debido a un error en la función configurada.";
									} 
									$output .= "\n".$o;
									break;
								}  
							}
						}

						$output .= "\n¡Parche ejecutado correctamente!";
					}
					
					return Response::json([ 'data' => $output, 'parche'=>$filename],200);

				} else {
					throw new \Exception("Archivo inválido.");
				}
			} else {
				throw new \Exception("No hay archivo.");
			}

		} 
		catch (PatchException $e) {
            return Response::json(['error' => $e->getMessage(), 'parche'=>$filename], HttpResponse::HTTP_CONFLICT);
        }
		catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage(), 'parche'=>$filename], 500);
        } catch (\FatalErrorException $e) {
            return Response::json(['error' => $e->getMessage(), 'parche'=>$filename], 500);
        } 
    }
	
}

