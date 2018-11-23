<?php

namespace App\Http\Controllers\Sync;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use \DB, \Storage, \ZipArchive, \Hash, \Response, \Config;
use Illuminate\Support\Facades\Input;
use App\Models\Sincronizacion, App\Models\Servidor, App\Models\LogSync, App\Models\Usuario; 
use App\Librerias\Sync\ArchivoSync;
use Carbon\Carbon;

class SincronizacionController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lista(Request $request)
    {
        $usuario = Usuario::find($request->get('usuario_id'));

        $parametros = Input::only('page','per_page','q');
        $items = Sincronizacion::select('sincronizaciones.*', 'servidores.nombre as servidor_nombre')->leftjoin("servidores","servidores.id","=","sincronizaciones.servidor_id")->orderBy('created_at','desc');
        
        if(!$usuario->su){
            $servidores = $items->where('servidor_id',$usuario->servidor_id);
        }
        
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }
       
        return Response::json([ 'data' => $items],200);
    }


    /**
     * Display a listing of the log.
     *
     * @return \Illuminate\Http\Response
     */
    public function log()
    {

        $parametros = Input::only('page','per_page','q');
        $items = LogSync::select('log_sync.*', 'servidores.nombre as servidor_nombre')->leftjoin("servidores","servidores.id","=","log_sync.servidor_id")->orderBy('created_at','desc');
        

        if ($parametros['q']) {
			$items =  $items->where('servidores.nombre','LIKE',"%".$parametros['q']."%")->orWhere('log_sync.descripcion','LIKE',"%".$parametros['q']."%")->orWhere('log_sync.usuario_id','LIKE',"%".$parametros['q']."%");
		}
        
        
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }
       
        return Response::json([ 'data' => $items],200);
    }

    
    /**
     * Crea un archivo comprimido para sincronización protegido con SECRET KEY 
     *
     * @return \Illuminate\Http\Response
     */
    public function manual()
    {

       
        ini_set('memory_limit', '-1');
        // 1. Debemos generar el link de descarga en una carpeta con una cadena aleatoria
        // 2. Cuando generemos el link en el controlador debe existir un middleware que al descargar el archivo lo borre del sistema

        $ultima_sincronizacion =  Sincronizacion::select('fecha_generacion')->where("servidor_id",env("SERVIDOR_ID"))->orderBy('fecha_generacion','desc')->first();
        $fecha_generacion = date('Y-m-d H:i:s');

        $clues_que_hace_sync = env('CLUES');

        Storage::delete("sync.".env('SERVIDOR_ID').".zip");
        Storage::makeDirectory("sync");
        
        // Creamos o reseteamos archivo de respaldo
        Storage::put('sync/header.sync',"ID=".env('SERVIDOR_ID'));
        Storage::put('sync/header.sync',"CLUES=".env('CLUES'));
        Storage::append('sync/header.sync',"SECRET_KEY=".env('SECRET_KEY'));
        Storage::append('sync/header.sync',"VERSION=".Config::get("sync.api_version"));
        Storage::append('sync/header.sync',"FECHA_SYNC=".$fecha_generacion);

        Storage::put('sync/sumami.sync', "");        
        Storage::append('sync/sumami.sync', "INSERT INTO sincronizaciones (servidor_id,fecha_generacion) VALUES ('".env('SERVIDOR_ID')."','".$fecha_generacion."'); \n");
        
        try {

            // Generamos archivo de sincronización de registros actualizados o creados a la fecha de corte
         
            foreach(Config::get("sync.tablas") as $key){
                
                if ($ultima_sincronizacion) {
                    $rows = DB::table($key)->where("servidor_id",env("SERVIDOR_ID"))->whereBetween('updated_at',[$ultima_sincronizacion->fecha_generacion,$fecha_generacion])->get();
                } else {             
                    $rows = DB::table($key)->where("servidor_id",env("SERVIDOR_ID"))->get();
                }
               
                if($rows){
                    $query = "";
                    $rows_chunks = array_chunk($rows, 50);
                    $columnas = DB::getSchemaBuilder()->getColumnListing($key);

                    foreach($rows_chunks as $row_chunk){
                        
                        $query .= "REPLACE INTO ".$key." VALUES ";

                        $index_replace = 0;
                        
                        foreach($row_chunk as $row){
                            if ($index_replace!=0){
                                $item = ", (";
                            } else {
                                $item = "(";
                            }
                            
                            $index_items = 0;
                            foreach($columnas as $nombre){
                                if ($index_items!=0){
                                    $item .= ",";
                                }

                                $tipo  = gettype($row->$nombre);
                                
                                switch($tipo){
                                    case "string": $item .= "\"".addslashes($row->$nombre)."\""; break;
                                    case "NULL": $item .= "NULL"; break;
                                    default: $item .= addslashes($row->$nombre);
                                }
                                
                                $index_items += 1;
                            }
                            $item .= ") ";
                            $index_replace += 1;
                            
                            //Storage::append('sync/sumami.sync', $item);                       
                            $query .= $item;
                        }
                        $query .= "; \n";
                        //Storage::append('sync/sumami.sync', "; \n");
                    }
                    Storage::append('sync/sumami.sync', $query);

                } 
            }

            // Incluimos las tablas pivotes en la sincronización
            foreach(Config::get("sync.pivotes") as $tabla => $parametrosTabla){
            
                if ($ultima_sincronizacion) {
                    
                    $rows = DB::table($tabla)->whereBetween('updated_at',[$ultima_sincronizacion->fecha_generacion,$fecha_generacion]);

                } else {             
                    $rows = DB::table($tabla);
                }

                if($parametrosTabla['condicion_subida'] != ''){

                    // Aqui vamos a hacer esto en el caso de que alguien haya puesto la palbra clave: {CLUES_QUE_SINCRONIZA} en la condicion
                    $condicion = str_replace("{CLUES_QUE_SINCRONIZA}", $clues_que_hace_sync, $parametrosTabla['condicion_subida']);
                    $rows = $rows->whereRaw($condicion);
                    
                        
                }
                $rows = $rows->get();
               
                if($rows){
                    $query = "";
                    
                    $columnas = DB::getSchemaBuilder()->getColumnListing($tabla);

                    foreach($rows as $row){
                        
                        $query .= "INSERT INTO ".$tabla."  VALUES (";
                        $update = "";
                        
                        $index_items = 0;
                        $index_items_update = 0;
                        
                        foreach($columnas as $nombre){
                            $up_flag = in_array($nombre,$parametrosTabla['campos_subida']);
                            
                            if ($index_items!=0){
                                $query .= ",";
                            }

                            if ($index_items_update!=0 && $up_flag){
                                $update .= ",";
                            }
                            if($up_flag){
                                $update .= $nombre.'=';
                            }
                            $tipo  = gettype($row->$nombre);
                            
                            switch($tipo){
                                case "string": 
                                    $text = "\"".addslashes($row->$nombre)."\""; 
                                    $query .= $text;
                                    if($up_flag){
                                        $update .= $text;
                                    } 
                                    break;
                                case "NULL": 
                                    $query .= "NULL"; 
                                    if($up_flag){
                                        $update .= "NULL"; 
                                    }
                                    break;
                                default: 
                                    $text = addslashes($row->$nombre);
                                    $query .= $text;
                                    if($up_flag){
                                        $update .= $text;
                                    }
                            }                                
                            $index_items += 1;
                            if($up_flag){
                                $index_items_update += 1;
                            }
                            
                        }
                        $query .= ") ON DUPLICATE KEY UPDATE ".$update."; \n";
                    }
                  
                    Storage::append('sync/sumami.sync', $query);
                }
            } 
           
            // Generamos archivo de catalogos para que cuando se sincronize en el servidor principal se sepa si están actualizados o no
          
            if(Config::get("sync.catalogos")){   
                     
                $contador = 0;  
                
                foreach (Config::get("sync.catalogos") as $key) {
                   
                    $ultima_actualizacion = DB::table($key)->max("updated_at"); 
                    
                    if($contador==0){
                        Storage::put('sync/catalogos.sync', $key."=".$ultima_actualizacion);
                    } else {
                        Storage::append('sync/catalogos.sync', $key."=".$ultima_actualizacion);
                    }                    
                    $contador++;
                }
            } else {
                Storage::put('sync/catalogos.sync','');
            }
            $storage_path = storage_path();
            $zip = new ZipArchive();
            $zippath = $storage_path."/app/";
            $zipname = "sync.".env('SERVIDOR_ID').".zip";
           
            exec("zip -P ".env('SECRET_KEY')." -j -r ".$zippath.$zipname." \"".$zippath."sync/\"");
            
            $zip_status = $zip->open($zippath.$zipname);

            if ($zip_status === true) {

                $zip->close();
                Storage::deleteDirectory("sync");
                
                ///Then download the zipped file.
                header('Content-Type: application/zip');
                header('Content-disposition: attachment; filename='.$zipname);
                header('Content-Length: ' . filesize($zippath.$zipname));
             
                readfile($zippath.$zipname);
                Storage::delete($zipname);

                LogSync::create([
                    "clues" => env("CLUES"),
                    "descripcion" => "Generó archivo de sincronización manual."
                ]);

                exit();
            } else {                
                throw new \Exception("No se pudo crear el archivo");
            }
        } catch (\Exception $e) {    
            LogSync::create([
                "clues" => env("CLUES"),
                "descripcion" => "Intentó generar archivo de sincronización manual pero hubo un error."
            ]);
            //echo " Sync Manual Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Manual Excepción: ".$e->getMessage());  
            Storage::deleteDirectory("sync");     
            return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);   
        }
        
    }

    /**
     * Importa archivo comprimido para sincronización protegido con SECRET KEY y devuelve archivo de confirmacion
     *
     * @return \Illuminate\Http\Response
     */
    public function importarSync(Request $request)
    {
       
        ini_set('memory_limit', '-1');
        $conexion_local = DB::connection('mysql');
        $conexion_local->beginTransaction();
        $clues_que_hace_sync = '';

        try {
             
            Storage::makeDirectory("importar");
            //echo php_ini_loaded_file();
            //var_dump($request);
            if ($request->hasFile('sync')){
                
                $file = $request->file('sync');
                if ($file->isValid()) {
                     
                    Storage::put(
                        "importar/".$file->getClientOriginalName(),
                        file_get_contents($file->getRealPath())
                    );

                    $nombreArray = explode(".",$file->getClientOriginalName());

                    $servidor_id = $nombreArray[1];                    
                    $servidor = Servidor::find($servidor_id);                    
                    
                    if($servidor){
                        $storage_path = storage_path();
                        $zip = new ZipArchive();
                        $zippath = $storage_path."/app/importar/";
                        $zipname = "sync.".$servidor_id.".zip";

                        $zip_status = $zip->open($zippath.$zipname) ;

                        if ($zip_status === true) {

                            if ($zip->setPassword($servidor->secret_key)){
                                Storage::makeDirectory("importar/".$servidor->id);
                                if ($zip->extractTo($zippath."/".$servidor->id)){
                                    $zip->close();

                                    //Borramos el ZIP y nos quedamos con los archivos extraidos
                                    Storage::delete("importar/".$file->getClientOriginalName());

                                    Storage::makeDirectory("importar/".$servidor->id."/confirmacion");
                                    
                                    //Obtenemos información del servidor que está sincronizando
                                    $contents_header = Storage::get("importar/".$servidor->id."/header.sync");
                                    $header_vars = ArchivoSync::parseVars($contents_header);
                                    $clues_que_hace_sync = $header_vars['CLUES'];
                                    // Obtenemos las fechas de actualizacion de sus catálogos
                                    $contents_catalogos = Storage::get("importar/".$servidor->id."/catalogos.sync");
                                    $catalogos_vars = ArchivoSync::parseVars($contents_catalogos);
                                

                                    // Verificamos que esta actualiacion no se haya aplicado antes                     
                                    if(Sincronizacion::where('servidor_id',$servidor->id)->where('fecha_generacion',$header_vars['FECHA_SYNC'])->count() > 0){
                                        //Storage::deleteDirectory("importar/".$servidor->id);
                                        throw new \Exception("No se puede importar más de una vez el mismo archivo de sincronización. La fecha del archivo indica que ya fue cargado previamente.");
                                    }                                   


                                   
                                    $actualizar_catalogos = "false";
                                    Storage::put("importar/".$servidor->id."/confirmacion/catalogos.sync","");
                                    foreach ($catalogos_vars as $key => $cat_ultima_actualizacion) {
                                       
                                        $principal_ultima_actualizacion = $conexion_local->table($key)->max("updated_at"); 
                                       
                                        if ($principal_ultima_actualizacion) {
                                           
                                            if ($principal_ultima_actualizacion != $cat_ultima_actualizacion) {
                                                
                                                $actualizar_catalogos = "true";
                                               
                                                $rows = $conexion_local->table($key)->whereBetween('updated_at',[$cat_ultima_actualizacion,$principal_ultima_actualizacion])->get();                                               
                                                 
                                                if($rows){
                                                    $query = "";
                                                    $rows_chunks = array_chunk($rows, 50);    
                                                    $columnas = $conexion_local->getSchemaBuilder()->getColumnListing($key);

                                                    foreach($rows_chunks as $row_chunk){
                                                        //Storage::append("importar/".$servidor->id."/confirmacion/catalogos.sync", "REPLACE INTO ".$key." VALUES ");
                                                        $query .= "REPLACE INTO ".$key." VALUES ";
                                                        
                                                        $index_replace = 0;
                                                        foreach($row_chunk as $row){
                                                            if ($index_replace!=0){
                                                                $item = ", (";
                                                            } else {
                                                                $item = "(";
                                                            }
                                                            
                                                            $index_items = 0;
                                                            foreach($columnas as $nombre){
                                                                if ($index_items!=0){
                                                                    $item .= ",";
                                                                }

                                                                $tipo  = gettype($row->$nombre);
                                                                
                                                                switch($tipo){
                                                                    case "string": $item .= "\"".addslashes($row->$nombre)."\""; break;
                                                                    case "NULL": $item .= "NULL"; break;
                                                                    default: $item .= addslashes($row->$nombre);
                                                                }
                                                                
                                                                $index_items += 1;
                                                            }
                                                            $item .= ") ";
                                                            $index_replace += 1;                                                            
                                                            
                                                            $query .= $item;
                                                        }
                                                        $query .= "; \n";
                                                        
                                                    }

                                                    Storage::append("importar/".$servidor->id."/confirmacion/catalogos.sync", $query);                                                    
                                                } 

                                            }
                                        }
                                    }
                                   
                                    // Registramos la version del servidor y si los catálogos estan actualizados
                                    $servidor->version = $header_vars['VERSION'];

                                    if ($actualizar_catalogos == "true") {
                                        $servidor->catalogos_actualizados = false;
                                    } else {
                                        $servidor->catalogos_actualizados = true;
                                    }
                                    $servidor->ultima_sincronizacion = $header_vars['FECHA_SYNC'];
                                    $servidor->save();

                                    // Comparamos la version del servidor principal y si es diferente le indicamos que tiene que actualizar
                                    if($servidor->version != Config::get("sync.api_version")) {
                                        $actualizar_software = "true";
                                    } else {
                                        $actualizar_software = "false";
                                    }
                                   
                                    // Se ejecuta la sincronización
                                    $contents = Storage::get("importar/".$servidor->id."/sumami.sync");
                                    $conexion_local->statement('SET FOREIGN_KEY_CHECKS=0');
                                    $conexion_local->getpdo()->exec($contents);
                                    $conexion_local->statement('SET FOREIGN_KEY_CHECKS=1');
                                
                                    // Agregamos las tablas pivote al archivo de confirmación para su descarga en offline
                                    Storage::put("importar/".$servidor->id."/confirmacion/pivotes.sync","");
                                    foreach(Config::get("sync.pivotes") as $tabla => $parametrosTabla){                                    
                                        
                                        // Pero antes  ejecutamos las funciones de cálculo de las tablas pivotes
                                        // para actualizar campos de ser necesario en el servidor princiapl
                                        
                                        $calculoSubidaFunction = $parametrosTabla['calculo_subida'];
                                        if($calculoSubidaFunction != ''){
                                            if(!call_user_func($calculoSubidaFunction,$conexion_local)){
                                                throw new \Exception("No se pudo hacer uno de los calculos de subida");
                                            }
                                        }

                                        // Buscamos las tablas pivotes para que los offline actualicen
                                        $rows =$conexion_local->table($tabla);
                                        
                                        if($parametrosTabla['condicion_bajada'] != ''){

                                            // Aqui vamos a hacer esto en el caso de que alguien haya puesto la palbra clave: {CLUES_QUE_SINCRONIZA} en la condicion
                                            $condicion = str_replace("{CLUES_QUE_SINCRONIZA}", $clues_que_hace_sync, $parametrosTabla['condicion_bajada']);
                                            $rows = $rows->whereRaw($condicion);
                                        }

                                        $rows = $rows->whereBetween('updated_at',[$servidor->ultima_sincronizacion,date('Y-m-d H:i:s')])->get();
                                    
                                        if($rows){
                                            $query = "";
                                            
                                            $columnas = $conexion_local->getSchemaBuilder()->getColumnListing($tabla);

                                            foreach($rows as $row){
                        
                                                $query .= "INSERT INTO ".$tabla."  VALUES (";
                                                $update = "";
                                                
                                                $index_items = 0;
                                                $index_items_update = 0;
                                                
                                                foreach($columnas as $nombre){
                                                    // Solo los campos de bajada
                                                    $up_flag = in_array($nombre,$parametrosTabla['campos_bajada']);
                                                    
                                                    if ($index_items!=0){
                                                        $query .= ",";
                                                    }
                        
                                                    if ($index_items_update!=0 && $up_flag){
                                                        $update .= ",";
                                                    }
                                                    if($up_flag){
                                                        $update .= $nombre.'=';
                                                    }
                                                    $tipo  = gettype($row->$nombre);
                                                    
                                                    switch($tipo){
                                                        case "string": 
                                                            $text = "\"".addslashes($row->$nombre)."\""; 
                                                            $query .= $text;
                                                            if($up_flag){
                                                                $update .= $text;
                                                            } 
                                                            break;
                                                        case "NULL": 
                                                            $query .= "NULL"; 
                                                            if($up_flag){
                                                                $update .= "NULL"; 
                                                            }
                                                            break;
                                                        default: 
                                                            $text = addslashes($row->$nombre);
                                                            $query .= $text;
                                                            if($up_flag){
                                                                $update .= $text;
                                                            }
                                                    }                                
                                                    $index_items += 1;
                                                    if($up_flag){
                                                        $index_items_update += 1;
                                                    }
                                                    
                                                }
                                                $query .= ") ON DUPLICATE KEY UPDATE ".$update."; \n";
                                                
                                                                      
                                                   
                                            }
                                            Storage::append("importar/".$servidor->id."/confirmacion/pivotes.sync", $query);
                                        }
                                    }

                              

                                    // Registramos la sincronización en la base de datos. 
                                    $sincronizacion = new Sincronizacion;
                                    $sincronizacion->servidor_id = $servidor->id;
                                    $sincronizacion->fecha_generacion = $header_vars['FECHA_SYNC'];
                                    $sincronizacion->save();

                                    $confirmacion_file = "importar/".$servidor->id."/confirmacion/confirmacion.sync";
                                    Storage::put($confirmacion_file,"ID=".$servidor->id);
                                    Storage::append($confirmacion_file,"FECHA_SYNC=".$header_vars['FECHA_SYNC']);                                   
                                    Storage::append($confirmacion_file,"ACTUALIZAR_SOFTWARE=".$actualizar_software);
                                    Storage::append($confirmacion_file,"VERSION_ACTUAL_SOFTWARE=".Config::get("sync.api_version"));
                                    Storage::append($confirmacion_file,"ACTUALIZAR_CATALOGOS=".$actualizar_catalogos);
                                    $storage_path = storage_path();
                                    
                                    $zip = new ZipArchive();
                                    $zippath = $storage_path."/app/";
                                    $zipname = "sync.confirmacion.".$servidor->id.".zip";                                   

                                    exec("zip -P ".$servidor->secret_key." -j -r ".$zippath.$zipname." \"".$zippath."/importar/".$servidor->id."/confirmacion\"");
                                    //exec("zip  -j -r ".$zippath.$zipname." \"".$zippath."/importar/".$servidor->id."/confirmacion\"");
                                    $zip_status = $zip->open($zippath.$zipname);

                                    if ($zip_status === true) {

                                        $zip->close();  

                                        ///Then download the zipped file.
                                        header('Content-Type: application/zip');
                                        header('Content-disposition: attachment; filename='.$zipname);
                                        header('Content-Length: ' . filesize($zippath.$zipname));
                                        readfile($zippath.$zipname);
                                        Storage::delete($zipname);
                                        Storage::deleteDirectory("importar/".$servidor->id);

                                        $conexion_local->commit();

                                        LogSync::create([
                                            "clues" => $clues_que_hace_sync,
                                            "descripcion" => "Importó archivo de sincronización manual."
                                        ]);
                                        exit();
                                    } else {            
                                        Storage::deleteDirectory("importar/".$servidor->id);    
                                        throw new \Exception("No se pudo crear el archivo");
                                    }

                                } else {
                                    Storage::delete("importar/".$file->getClientOriginalName());
                                    Storage::deleteDirectory("importar/".$servidor->id);
                                    throw new \Exception("No se pudo desencriptar el archivo, es posible que la llave de descriptación sea incorrecta, o que el nombre del archivo no corresponda al servidor correcto."); 
                                }

                            } else {
                                $zip->close();
                                Storage::delete("importar/".$file->getClientOriginalName());
                                throw new \Exception("Ocurrió un error al desencriptar el archivo");
                            }                            
                            exit;
                        } else {   
                            Storage::delete("importar/".$file->getClientOriginalName());             
                            throw new \Exception("No se pudo leer el archivo");
                        }

                    } else{
                        Storage::delete("importar/".$file->getClientOriginalName());
                        throw new \Exception("Archivo inválido, es posible que el nombre haya sido alterado o el servidor que desea sincronizar no se encuentra registrado.");
                    }
                }
            } else {
                 
                throw new \Exception("No hay archivo.");
            }
        } catch (\Illuminate\Database\QueryException $e){            
            //echo " Sync Importación Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Importación Excepción: ".$e->getMessage());
            $conexion_local->rollback();
            
            LogSync::create([
                "clues" => $clues_que_hace_sync,
                "descripcion" => "Intentó importar archivo de sincronización manual pero hubo un error."
            ]);
            
            return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
        catch(\Exception $e ){
            LogSync::create([
                "clues" => $clues_que_hace_sync,
                "descripcion" => "Intentó importar archivo de sincronización manual pero hubo un error."
            ]);
            return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Importa archivo comprimido para sincronización protegido con SECRET KEY y devuelve archivo de confirmacion
     *
     * @return \Illuminate\Http\Response
     */
    public function confirmarSync(Request $request)
    {
        ini_set('memory_limit', '-1');
        $conexion_local = DB::connection('mysql');
        $conexion_local->beginTransaction();
        try {
             
            Storage::makeDirectory("confirmacion");
            if ($request->hasFile('sync')){
                $file = $request->file('sync');
                if ($file->isValid()) {
                    
                    
                    $nombreArray = explode(".",$file->getClientOriginalName());
                    
                    $servidor_id = $nombreArray[2];                    
                    
                    if($servidor_id == env('SERVIDOR_ID')){

                        Storage::put(
                            "confirmacion/".$file->getClientOriginalName(),
                            file_get_contents($file->getRealPath())
                        );
                        
                        $storage_path = storage_path();
                        $zip = new ZipArchive();
                        $zippath = $storage_path."/app/confirmacion/";
                        $zipname = "sync.confirmacion.".$servidor_id.".zip";


                        $zip_status = $zip->open($zippath.$zipname) ;            
                         
                        if ($zip_status === true) {

                            if ($zip->setPassword(env('SECRET_KEY'))){
                                if ($zip->extractTo($zippath)){
                                    $zip->close();

                                    //Borramos el ZIP y nos quedamos con los archivos extraidos
                                    Storage::delete("confirmacion/".$file->getClientOriginalName());
                                   
                                    //Obtenemos información de la respuesta del  servidor remoto
                                    $contents_confirmacion = Storage::get("confirmacion/confirmacion.sync");     
                                                                    
                                    $confirmacion_vars = ArchivoSync::parseVars($contents_confirmacion);
                                    
                                    //Verificamos que el nombre del archivo en verdad corresponda al que dice el archivo de sincronizacion
                                    // A este punto creo innecesario hacer esta confirmación pues si no funciono la contraseña pues no es el servidor
                                    // pero suponiendo que alguien repita claves de servidores pues serviria para esto
                                    if ($confirmacion_vars["ID"] != env('SERVIDOR_ID')){
                                        Storage::delete("confirmacion/confirmacion.sync");
                                        Storage::delete("confirmacion/catalogos.sync");
                                        Storage::delete("confirmacion/pivotes.sync");
                                        throw new Exception("El contenido del archivo de confirmación no corresponde al nombre del archivo, no se debe cambiar el nombre del archivo de confirmación que el servidor remoto genera.");
                                    }                                

                                    // Verificamos que esta actualizacion no se haya aplicado antes                     
                                    if(Sincronizacion::where('servidor_id',env('SERVIDOR_ID'))->where('fecha_generacion',$confirmacion_vars['FECHA_SYNC'])->count() > 0){
                                        Storage::delete("confirmacion/confirmacion.sync");
                                        Storage::delete("confirmacion/catalogos.sync");
                                        throw new \Exception("Este archivo ya fue utilizado previamente para confirmar la sincronización con el servidor remoto.");
                                    }          
                                    
                                    $contents = Storage::get("confirmacion/catalogos.sync");
                                    if($contents != ""){
                                        $conexion_local->statement('SET FOREIGN_KEY_CHECKS=0');
                                        $conexion_local->getpdo()->exec($contents);
                                        $conexion_local->statement('SET FOREIGN_KEY_CHECKS=1');
                                    }

                                    // Tablas pivote
                                    $contents = Storage::get("confirmacion/pivotes.sync");
                                    if($contents != ""){
                                        $conexion_local->statement('SET FOREIGN_KEY_CHECKS=0');
                                        $conexion_local->getpdo()->exec($contents);
                                        $conexion_local->statement('SET FOREIGN_KEY_CHECKS=1');
                                    }

                                    foreach(Config::get("sync.pivotes") as $tabla => $parametrosTabla){
                                    
                                        
                                        // Ejecutamos las funciones de cálculo de las tablas pivotes
                                        // para actualizar campos de ser necesario en el servidor local
                                        $calculoBajadaFunction = $parametrosTabla['calculo_bajada'];
                                        if($calculoBajadaFunction  != ''){
                                            if(!call_user_func($calculoBajadaFunction,$conexion_local)){
                                                throw new \Exception("No se pudo hacer uno de los calculos de bajada");
                                            }
                                        }
                                        
                                    }
                                    
                                    // Registramos la sincronización en la base de datos. 
                                    $sincronizacion = new Sincronizacion;
                                    $sincronizacion->servidor_id = env('SERVIDOR_ID');
                                    $sincronizacion->fecha_generacion = $confirmacion_vars['FECHA_SYNC'];
                                    $sincronizacion->save();

                                    $servidor = Servidor::find(env('SERVIDOR_ID'));
                                    if($servidor){
                                        $servidor->ultima_sincronizacion = $confirmacion_vars['FECHA_SYNC'];
                                        $servidor->save();
                                    }
                                    
                                    Storage::delete("confirmacion/confirmacion.sync");
                                    Storage::delete("confirmacion/catalogos.sync");

                                    $conexion_local->commit();
                                    LogSync::create([
                                        "clues" => env("CLUES"),
                                        "descripcion" => "Confirmó sincronización manual exitosa con servidor remoto."
                                    ]);
                                    return Response::json([ 'data' => "Sincronización con servidor remoto confirmada." ],200);

                                } else {
                                    $zip->close();
                                    Storage::delete("confirmacion/".$file->getClientOriginalName());
                                    throw new \Exception("No se pudo desencriptar el archivo, es posible que la llave de descriptación sea incorrecta, o que el nombre del archivo no corresponda al servidor correcto."); 
                                }

                            } else {
                                $zip->close();
                                Storage::delete("confirmacion/".$file->getClientOriginalName());
                                throw new \Exception("Ocurrió un error al desencriptar el archivo");
                            }

                            
                            exit;
                        } else {   
                            Storage::delete("confirmacion/".$file->getClientOriginalName());             
                            throw new \Exception("No se pudo leer el archivo");
                        }


                    } else{
                        throw new \Exception("Archivo inválido, es posible que el nombre haya sido alterado o el archivo de confirmación corresponda a otro servidor.");
                    }
                }
            } else {
                throw new \Exception("No hay archivo.");
            }
        } catch (\Illuminate\Database\QueryException $e){            
            Storage::append('log.sync', $fecha_generacion." Sync Confirmación Excepción: ".$e->getMessage());
            $conexion_local->rollback();            
            LogSync::create([
                "clues" => env("CLUES"),
                "descripcion" => "Intentó confirmar sincronización manual con servidor remoto pero hubo un error."
            ]);
            return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
        catch(\Exception $e ){
            LogSync::create([
                "clues" => env("CLUES"),
                "descripcion" => "Intentó confirmar sincronización manual con servidor remoto pero hubo un error."
            ]);
            return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }


    /**
     * Sincroniza la base de datos con servidor remoto
     *
     */
    public function auto()
    {
        ini_set('memory_limit', '-1');
        $log = "";
        $ultima_sincronizacion =  Sincronizacion::select('fecha_generacion')->where("servidor_id",env("SERVIDOR_ID"))->orderBy('fecha_generacion','desc')->first();
        $fecha_generacion = date('Y-m-d H:i:s');
        
        try {
            $conexion_remota = DB::connection('mysql_sync');
            $conexion_local = DB::connection('mysql');
            //DB::beginTransaction();

            $conexion_local->beginTransaction();
            $conexion_remota->beginTransaction();

            //DB::statement('SET GLOBAL max_allowed_packet=134217728');//128MB
            //DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $conexion_local->statement('SET FOREIGN_KEY_CHECKS=0');

            //$conexion_remota->statement('SET GLOBAL max_allowed_packet=134217728');//128MB
            $conexion_remota->statement('SET FOREIGN_KEY_CHECKS=0');
        } 
        catch (\Exception $e) {     
            Storage::append('log.sync', $fecha_generacion." Sync Auto Excepción: ".$e->getMessage());
            return " Sync Auto Excepción: ".$e->getMessage();            
        }
        
        try {
            $log .= "[#] Inicia Sincronización al: $fecha_generacion [#] \n\n";

            // Sincronizamos tablas de local a remoto 
            $log .= "### Tablas [local -> remoto]: ----------------------- ### \n";
            foreach(Config::get("sync.tablas")as $key){
                
                if ($ultima_sincronizacion) {
                    $rows = $conexion_local->table($key)->where("servidor_id",env("SERVIDOR_ID"))->whereBetween('updated_at',[$ultima_sincronizacion->fecha_generacion,$fecha_generacion])->get();
                } else {             
                    $rows = $conexion_local->table($key)->where("servidor_id",env("SERVIDOR_ID"))->get();
                }                

                if($rows){                    

                    // Separamos los registros porque cuando son demasiados marca un error de ejecución
                    $rows_chunks = array_chunk($rows, 50);

                    $columnas = $conexion_local->getSchemaBuilder()->getColumnListing($key);

                    foreach($rows_chunks as $row_chunk){

                        $statement = "REPLACE INTO ".$key." VALUES ";                    
                       
                        $index_replace = 0;


                        foreach($row_chunk as $row){
                            if ($index_replace!=0){
                                $item = ", (";
                            } else {
                                $item = "(";
                            }
                            
                            $index_items = 0;
                            foreach($columnas as $nombre){
                                if ($index_items!=0){
                                    $item .= ",";
                                }

                                $tipo  = gettype($row->$nombre);
                                
                                switch($tipo){
                                    case "string": $item .= "\"".addslashes($row->$nombre)."\""; break;
                                    case "NULL": $item .= "NULL"; break;
                                    default: $item .= addslashes($row->$nombre);
                                }
                                
                                $index_items += 1;
                            }
                            $item .= ") ";
                            $index_replace += 1;
                            
                            $statement.= $item;                      
                        }
                        $statement .= ";";
                        $conexion_remota->statement($statement);
                    }
                    $log .= "Tabla: ".$key."\t=> ".count($rows)." registros sincronizados \n";
                } else {
                    $log .= "Tabla: ".$key."\t=> 0 registros sincronizados \n";
                }
            }   
            //  Sincronizamos catálogos de remoto a local
            $log .= "\n### Catálogos [remoto -> local]: -------------------- ### \n";
            foreach (Config::get("sync.catalogos") as $key) {
                   
                $ultima_actualizacion_local = $conexion_local->table($key)->max("updated_at");                 
                $ultima_actualizacion_remoto = $conexion_remota->table($key)->max("updated_at");

                if ($ultima_actualizacion_local) {
                    if ($ultima_actualizacion_local != $ultima_actualizacion_remoto) {
                        $rows = $conexion_remota->table($key)->whereBetween('updated_at',[$ultima_actualizacion_local, $ultima_actualizacion_remoto])->get();
                        
                    } else {
                        $rows = null;
                    }
                } else {
                    $rows = $conexion_remota->table($key)->get();
                }

                if ($rows) {

                    // Separamos los registros porque cuando son demasiados marca un error de ejecución
                    $rows_chunks = array_chunk($rows, 50);
                    $columnas = $conexion_local->getSchemaBuilder()->getColumnListing($key);

                    foreach($rows_chunks as $row_chunk){
                        $statement = "REPLACE INTO ".$key." VALUES ";   

                        $index_replace = 0;
                        foreach($row_chunk as $row){
                            if ($index_replace!=0){
                                $item = ", (";
                            } else {
                                $item = "(";
                            }
                            
                            $index_items = 0;
                            foreach($columnas as $nombre){
                                if ($index_items!=0){
                                    $item .= ",";
                                }

                                $tipo  = gettype($row->$nombre);
                                
                                switch($tipo){
                                    case "string": $item .= "\"".$row->$nombre."\""; break;
                                    case "NULL": $item .= "NULL"; break;
                                    default: $item .= $row->$nombre;
                                }
                                
                                $index_items += 1;
                            }
                            $item .= ") ";
                            $index_replace += 1;
                            
                            $statement.= $item;                      
                        }
                        $statement .= ";";
                    
                        $conexion_local->statement($statement);
                    }
                    $log .= "Tabla: ".$key."\t=> ".count($rows)." registros sincronizados \n";
                } else {
                    $log .= "Tabla: ".$key."\t=> 0 registros sincronizados \n";
                }        
            }

            // Tablas pivote

            $log .= "\n### Pivotes: -------------------- ### \n";
            foreach(Config::get("sync.pivotes") as $tabla => $parametrosTabla){
                
                // Inicia offline a remoto
                if ($ultima_sincronizacion) {                    
                    $rows = $conexion_local->table($tabla)->whereBetween('updated_at',[$ultima_sincronizacion->fecha_generacion,$fecha_generacion]);
                } else {             
                    $rows = $conexion_local->table($tabla);
                }

                if($parametrosTabla['condicion_subida'] != ''){

                    // Aqui vamos a hacer esto en el caso de que alguien haya puesto la palbra clave: {CLUES_QUE_SINCRONIZA} en la condicion
                    $condicion = str_replace("{CLUES_QUE_SINCRONIZA}", env("CLUES"), $parametrosTabla['condicion_subida']);
                    $rows = $rows->whereRaw($condicion);


                    //$rows = $rows->whereRaw($parametrosTabla['condicion_subida']);
                }
                $rows = $rows->get();
               
                if($rows){
                                      
                    $columnas = $conexion_local->getSchemaBuilder()->getColumnListing($tabla);

                    foreach($rows as $row){
                        
                        $query = "INSERT INTO ".$tabla."  VALUES (";
                        $update = "";                       
                        
                        $index_items = 0;
                        $index_items_update = 0;
                        
                        foreach($columnas as $nombre){
                            $up_flag = in_array($nombre,$parametrosTabla['campos_subida']);
                            
                            if ($index_items!=0){
                                $query .= ",";
                            }

                            if ($index_items_update!=0 && $up_flag){
                                $update .= ",";
                            }
                            if($up_flag){
                                $update .= $nombre.'=';
                            }
                            $tipo  = gettype($row->$nombre);
                            
                            switch($tipo){
                                case "string": 
                                    $text = '"'.addslashes($row->$nombre).'"'; 
                                    $query .= $text;
                                    if($up_flag){
                                        $update .= $text;
                                    } 
                                    break;
                                case "NULL": 
                                    $query .= "NULL"; 
                                    if($up_flag){
                                        $update .= "NULL"; 
                                    }
                                    break;
                                default: 
                                    $text = addslashes($row->$nombre);
                                    $query .= $text;
                                    if($up_flag){
                                        $update .= $text;
                                    }
                            }                                
                            $index_items += 1;
                            if($up_flag){
                                $index_items_update += 1;
                            }                            
                        }
                        $query .= ") ON DUPLICATE KEY UPDATE ".$update." ; ";
                        $conexion_remota->statement($query);    
                    }
                    

                    $calculoSubidaFunction = $parametrosTabla['calculo_subida'];
                    if($calculoSubidaFunction != ''){
                        
                        
                        if(!call_user_func($calculoSubidaFunction,  $conexion_remota)){
                            throw new \Exception("No se pudo hacer uno de los calculos de subida");
                        }
                    }   
                    
                    $log .= "Tabla pivote [subida] : ".$tabla."\t=> ".count($rows)." registros sincronizados \n";
                } else{
                    $log .= "Tabla pivote [subida] : ".$tabla."\t=> 0 registros sincronizados \n";
                }
                // Fin offline a remoto

                // Inicia remoto a offline

                // Buscamos las tablas pivotes para que los offline actualicen
                //$rows = $conexion_remota->table($tabla);

                // Inicia offline a remoto
                if ($ultima_sincronizacion) {                    
                    $rows = $conexion_remota->table($tabla)->whereBetween('updated_at',[$ultima_sincronizacion->fecha_generacion,$fecha_generacion]);
                } else {             
                    $rows = $conexion_remota->table($tabla);
                }

                if($parametrosTabla['condicion_bajada'] != ''){

                    // Aqui vamos a hacer esto en el caso de que alguien haya puesto la palbra clave: {CLUES_QUE_SINCRONIZA} en la condicion
                    $condicion = str_replace("{CLUES_QUE_SINCRONIZA}", env("CLUES"), $parametrosTabla['condicion_bajada']);
                    $rows = $rows->whereRaw($condicion);
                    //$rows = $rows->whereRaw($parametrosTabla['condicion_bajada']);
                }
                $rows = $rows->get();
            

                ////##### Este método funciona más optimo pero lo descubri de ultimo momento lo dejo comentado por si hay que usarlo
                /*
                if($rows){                    

                    // Separamos los registros porque cuando son demasiados marca un error de ejecución
                    $rows_chunks = array_chunk($rows, 50);

                    $columnas = DB::getSchemaBuilder()->getColumnListing($tabla);

                    $on_duplicate_key_update_fields = "";
                    $index_duplicate = 0;
                    foreach($parametrosTabla['campos_bajada'] as $campo){
                        if($index_duplicate >0){
                            $on_duplicate_key_update_fields .= ", ";
                        }
                        $on_duplicate_key_update_fields.= $campo." =  VALUES(".$campo.")";
                        $index_duplicate++;
                    }
                    

                    foreach($rows_chunks as $row_chunk){

                        $statement = "INSERT INTO ".$tabla." VALUES ";                    
                       
                        $index_replace = 0;


                        foreach($row_chunk as $row){
                            if ($index_replace!=0){
                                $item = ", (";
                            } else {
                                $item = "(";
                            }
                            
                            $index_items = 0;
                            foreach($columnas as $nombre){
                                if ($index_items!=0){
                                    $item .= ",";
                                }

                                $tipo  = gettype($row->$nombre);
                                
                                switch($tipo){
                                    case "string": $item .= "\"".addslashes($row->$nombre)."\""; break;
                                    case "NULL": $item .= "NULL"; break;
                                    default: $item .= addslashes($row->$nombre);
                                }
                                
                                $index_items += 1;
                            }
                            $item .= ") ";
                            $index_replace += 1;
                            
                            $statement.= $item;                      
                        }
                        $statement .= "   ON DUPLICATE KEY UPDATE ".$on_duplicate_key_update_fields.";";
                        $conexion_remota->statement($statement);
                    }

                    $calculoBajadaFunction = $parametrosTabla['calculo_bajada'];
                    if($calculoBajadaFunction != ''){
                        
                        if(!call_user_func($calculoBajadaFunction)){
                            throw new \Exception("No se pudo hacer uno de los calculos de bajada");
                        }
                    }    
                    $log .= "Tabla pivote [bajada] : ".$key."\t=> ".count($rows)." registros sincronizados \n";
                } else{
                    $log .= "Tabla pivote [bajada] : ".$key."\t=> 0 registros sincronizados \n";
                }*/



                ///#######################
                if($rows){

                    $columnas = $conexion_local->getSchemaBuilder()->getColumnListing($tabla);

                    foreach($rows as $row){

                        $query = "INSERT INTO ".$tabla."  VALUES (";
                        $update = "";
                        
                        $index_items = 0;
                        $index_items_update = 0;
                        
                        foreach($columnas as $nombre){
                            // Solo los campos de bajada
                            $up_flag = in_array($nombre,$parametrosTabla['campos_bajada']);
                            
                            if ($index_items!=0){
                                $query .= ",";
                            }

                            if ($index_items_update!=0 && $up_flag){
                                $update .= ",";
                            }
                            if($up_flag){
                                $update .= $nombre.'=';
                            }
                            $tipo  = gettype($row->$nombre);
                            
                            switch($tipo){
                                case "string": 
                                    $text = "\"".addslashes($row->$nombre)."\""; 
                                    $query .= $text;
                                    if($up_flag){
                                        $update .= $text;
                                    } 
                                    break;
                                case "NULL": 
                                    $query .= "NULL"; 
                                    if($up_flag){
                                        $update .= "NULL"; 
                                    }
                                    break;
                                default: 
                                    $text = addslashes($row->$nombre);
                                    $query .= $text;
                                    if($up_flag){
                                        $update .= $text;
                                    }
                            }                                
                            $index_items += 1;
                            if($up_flag){
                                $index_items_update += 1;
                            }
                            
                        }
                        $query .= ") ON DUPLICATE KEY UPDATE ".$update."; ";
                        $conexion_local->statement($query);    
                    }
                   

                    $calculoBajadaFunction = $parametrosTabla['calculo_bajada'];
                    if($calculoBajadaFunction != ''){
                        
                        if(!call_user_func($calculoBajadaFunction, $conexion_local)){
                            throw new \Exception("No se pudo hacer uno de los calculos de bajada");
                        }
                    }                                      
                    $log .= "Tabla pivote [bajada] : ".$tabla."\t=> ".count($rows)." registros sincronizados \n";
                } else{
                    $log .= "Tabla pivote [bajada] : ".$tabla."\t=> 0 registros sincronizados \n";
                }

                // Fin remoto a offline
            } 


            $servidor_remoto = Servidor::on('mysql_sync')->find(env('SERVIDOR_ID'));
            $servidor_remoto->version = Config::get("sync.api_version");
            $servidor_remoto->catalogos_actualizados = true;
            $servidor_remoto->ultima_sincronizacion = Carbon::now();
            $servidor_remoto->save();

            $servidor = Servidor::find(env('SERVIDOR_ID'));
            $servidor->version = Config::get("sync.api_version");
            $servidor->catalogos_actualizados = true;
            $servidor->ultima_sincronizacion = Carbon::now();
            $servidor->save();

            $sincronizacion_remoto = new Sincronizacion;
            $sincronizacion_remoto->setConnection("mysql_sync");
            $sincronizacion_remoto->servidor_id = env('SERVIDOR_ID');
            $sincronizacion_remoto->fecha_generacion = $fecha_generacion;
            $sincronizacion_remoto->save();

            $sincronizacion = new Sincronizacion;
            $sincronizacion->servidor_id = env('SERVIDOR_ID');
            $sincronizacion->fecha_generacion = $fecha_generacion;
            $sincronizacion->save();

           

            LogSync::create([
                "clues" => env("CLUES"),
                "descripcion" => "Realizó sincronización automática online."
            ]);

            $conexion_local->statement('SET FOREIGN_KEY_CHECKS=1');
            $conexion_remota->statement('SET FOREIGN_KEY_CHECKS=1');

            
            $conexion_local->commit();
            $conexion_remota->commit();



            $log .= "\n[#] Fin de Sincronización [#] \n";


            return \Response::json([ 'data' => $log],200);

        } catch (\Illuminate\Database\QueryException $e){

            


            $log .= " Sync Auto Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Auto Excepción: ".$e->getMessage());
            //DB::statement('SET FOREIGN_KEY_CHECKS=1');
            //$conexion_remota->statement('SET FOREIGN_KEY_CHECKS=1');
            $conexion_local->rollback();
            $conexion_remota->rollback();

            
            
            LogSync::create([
                "clues" => env("CLUES"),
                "descripcion" => "Intentó realizar sincronización automática online pro hubo un error."
            ]);
/*
            $log_sync_remoto = new LogSync;
            $log_sync_remoto->setConnection("mysql_sync");
            $log_sync_remoto->clues = env("CLUES");
            $log_sync_remoto->descripcion =  "Intentó realizar sincronización automática online pro hubo un error.";
            $log_sync_remoto->save();*/

            return \Response::json([ 'data' => $log],500);
        }
        catch (\ErrorException $e) {
            $log .= " Sync Auto Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Auto Excepción: ".$e->getMessage());
            $conexion_local->statement('SET FOREIGN_KEY_CHECKS=1');
            $conexion_remota->statement('SET FOREIGN_KEY_CHECKS=1');
            $conexion_local->rollback();
            $conexion_remota->rollback();

            $log_sync_remoto = new LogSync;
            $log_sync_remoto->setConnection("mysql_sync");
            $log_sync_remoto->clues = env("CLUES");
            $log_sync_remoto->descripcion =  "Intentó realizar sincronización automática online pro hubo un error.";
            $log_sync_remoto->save();
            
            LogSync::create([
                "clues" => env("CLUES"),
                "descripcion" => "Intentó realizar sincronización automática online pro hubo un error."
            ]);

            return \Response::json([ 'data' => $log],500);
        } 
        catch (\Exception $e) {            
            $log .= " Sync Auto Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Auto Excepción: ".$e->getMessage());
            $conexion_local->statement('SET FOREIGN_KEY_CHECKS=1');
            $conexion_remota->statement('SET FOREIGN_KEY_CHECKS=1');
            $conexion_local->rollback();
            $conexion_remota->rollback();

            $log_sync_remoto = new LogSync;
            $log_sync_remoto->setConnection("mysql_sync");
            $log_sync_remoto->clues = env("CLUES");
            $log_sync_remoto->descripcion =  "Intentó realizar sincronización automática online pro hubo un error.";
            $log_sync_remoto->save();
            
            LogSync::create([
                "clues" => env("CLUES"),
                "descripcion" => "Intentó realizar sincronización automática online pro hubo un error."
            ]);
            
            return \Response::json([ 'data' => $log],500);
        }
    }
}