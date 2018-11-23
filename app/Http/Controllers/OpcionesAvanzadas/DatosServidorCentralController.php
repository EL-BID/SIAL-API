<?php

namespace App\Http\Controllers\OpcionesAvanzadas;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \Storage, \Artisan, \Config, \ZipArchive;
use App\Models\Usuario, App\Models\UnidadMedica, App\Models\Servidor, App\Models\LogSync;
use App\Librerias\Sync\ArchivoSync;
use Carbon\Carbon;

class DatosServidorCentralController extends Controller{

    public function exportar(Request $request){
        ini_set('memory_limit', '-1');
        // 1. Debemos generar el link de descarga en una carpeta con una cadena aleatoria
        // 2. Cuando generemos el link en el controlador debe existir un middleware que al descargar el archivo lo borre del sistema

        $params = Input::all();

        $usuario = Usuario::find($request->get('usuario_id'));

        if($usuario->su){
            //$almacen_id = Request::header('X-Almacen-Id');
            $clues = $params['clues'];
            $servidor = Servidor::where('clues',$clues)->first();
            $unidad_medica = UnidadMedica::where('clues',$clues)->first();
        }else{
            $servidor = Servidor::find($usuario->servidor_id);
            $unidad_medica = UnidadMedica::where('clues',$servidor->clues)->first();
            $clues = $servidor->clues;
        }
        
        if($servidor->id == '0001' && !$usuario->su){
            return Response::json(['error' => 'Opción no valida para usarios del servidor central'], HttpResponse::HTTP_CONFLICT);
        }

        if(!$unidad_medica->es_offline){
            return Response::json(['error' => 'La unidad medica no esta configurada como offline'], HttpResponse::HTTP_CONFLICT);
        }

        $servidor_id = $servidor->id;

        Storage::deleteDirectory("datos-central");

        Storage::delete("datos.".$servidor_id.".zip");
        Storage::makeDirectory("datos-central");
        
        // Creamos o reseteamos archivo de respaldo
        Storage::put('datos-central/header.sync',"ID=".$servidor_id);
        Storage::append('datos-central/header.sync',"CLUES=".$clues);
        Storage::append('datos-central/header.sync',"SECRET_KEY=".$servidor->sercret_key);
        Storage::append('datos-central/header.sync',"VERSION=".Config::get("sync.api_version"));
        Storage::append('datos-central/header.sync',"FECHA_DESCARGA=".Carbon::now());

        Storage::put('datos-central/datos.sync', "");        
        //Storage::append('datos-central/datos.sync', "INSERT INTO sincronizaciones (servidor_id,fecha_generacion) VALUES ('".$servidor_id."','".$fecha_generacion."'); \n");
        
        try {
            // Generamos archivo de sincronización de registros actualizados o creados a la fecha de corte
            $tablas_datos = [
                    'actas',
                    'ajuste_presupuesto_pedidos_cancelados',
                    'almacenes_servicios',
                    'almacen_tipos_movimientos',
                    'pedidos',
                    'pedidos_insumos',
                    'clues_claves',
                    'clues_servicios',
                    'clues_turnos',
                    'consumos_promedios',
                    'cuadros_distribucion',
                    'firmas_organismos',
                    'historial_movimientos_transferencias',
                    'inicializacion_inventario',
                    'inicializacion_inventario_detalle',
                    'insumos_maximos_minimos',
                    'inventarios',
                    'inventarios_detalles',
                    'log_ejecucion_parches',
                    'log_inicio_sesion',
                    'log_pedidos_cancelados',
                    'log_pedido_borrador',
                    'log_recepcion_borrador',
                    'log_repositorio',
                    'log_sync',
                    'log_transferencias_canceladas',
                    'movimientos',
                    'movimiento_ajustes',
                    'movimiento_articulos',
                    'movimiento_detalles',
                    'movimiento_insumos',
                    'movimiento_insumos_borrador',
                    'movimiento_metadatos',
                    'movimiento_pedido',
                    'negaciones_insumos',
                    'pacientes',
                    'pacientes_admision',
                    'pacientes_area_responsable',
                    'pacientes_responsable',
                    'pedidos',
                    'pedidos_alternos',
                    'pedidos_insumos',
                    'pedidos_insumos_clues',
                    'pedido_cc_clues',
                    'pedido_metadatos_cc',
                    'pedido_metadatos_sincronizaciones',
                    'pedido_proveedor_insumos',
                    'personal_clues',
                    'personal_clues_metadatos',
                    'personal_clues_puesto',
                    'puestos',
                    'recetas',
                    'recetas_digitales',
                    'receta_detalles',
                    'receta_digital_detalles',
                    'receta_movimientos',
                    'repositorio',
                    'resguardos',
                    'sincronizaciones',
                    'sincronizaciones_proveedores',
                    'sincronizacion_movimientos',
                    'stock',
                    'stock_borrador',
                    'usuarios'
                ];
            
                $pedidos_ids = [];
            foreach($tablas_datos as $key){

                $rows = DB::table($key)->where('servidor_id',$servidor_id)->get();
                
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
                            
                            Storage::append('datos-central/sumami.sync', $item);                       
                            $query .= $item;
                        }
                        $query .= "; \n";
                        Storage::append('datos-central/sumami.sync', "; \n");
                    }
                    Storage::append('datos-central/datos.sync', $query);
                }else{
                    //Storage::append('datos-central/datos.sync', 'sin rows');
                }
            }
            //return \Response::json(['message' => 'exito'], 200);   
            
            $storage_path = storage_path();
            $zip = new ZipArchive();
            $zippath = $storage_path."/app/";
            $zipname = "datos-central.".$servidor->id.".zip";
           
            exec("zip -P ".$servidor->secret_key." -j -r ".$zippath.$zipname." \"".$zippath."datos-central/\"");
            
            $zip_status = $zip->open($zippath.$zipname);

            if ($zip_status === true) {
                
                $zip->close();
                Storage::deleteDirectory("datos-central");
                
                ///Then download the zipped file.
                header('Content-Type: application/zip');
                header('Content-disposition: attachment; filename='.$zipname);
                header('Content-Length: ' . filesize($zippath.$zipname));
             
                readfile($zippath.$zipname);
                Storage::delete($zipname);

                /*LogSync::create([
                    "clues" => env("CLUES"),
                    "descripcion" => "Generó archivo de sincronización manual."
                ]);*/
                
                exit();
            } else {                
                throw new \Exception("No se pudo crear el archivo");
            }
            
        } catch (\Exception $e) {    
            /*LogSync::create([
                "clues" => env("CLUES"),
                "descripcion" => "Intentó generar archivo de sincronización manual pero hubo un error."
            ]);*/
            //echo " Sync Manual Excepción: ".$e->getMessage();
            //Storage::append('log.sync', $fecha_generacion." Sync Manual Excepción: ".$e->getMessage());  
            //Storage::deleteDirectory("sync");     
            return \Response::json(['error' => $e->getMessage(), 'line'=>$e->getLine()], HttpResponse::HTTP_CONFLICT);   
        }
	}
	
	public function importar(Request $request){
        ini_set('memory_limit', '-1');
        $conexion_local = DB::connection('mysql');
        $conexion_local->beginTransaction();
        $clues_que_hace_sync = env('CLUES');
        $servidor_id = env('SERVIDOR_ID');

        try {
            Storage::makeDirectory("importar-datos");
            //echo php_ini_loaded_file();
            //var_dump($request);
            if($servidor_id == '0001'){
                throw new \Exception("No se puede importar este archivo en el servidor central.");
            }

            if ($request->hasFile('zip')){
                
                $file = $request->file('zip');
                if ($file->isValid()) {
                     
                    Storage::put(
                        "importar-datos/".$file->getClientOriginalName(),
                        file_get_contents($file->getRealPath())
                    );

                    $nombreArray = explode(".",$file->getClientOriginalName());

                    $servidor_id = $nombreArray[1];                    
                    $servidor = Servidor::find($servidor_id);                    

                    if($servidor){
                        $storage_path = storage_path();
                        $zip = new ZipArchive();
                        $zippath = $storage_path."/app/importar-datos/";
                        $zipname = "datos-central.".$servidor_id.".zip";

                        $zip_status = $zip->open($zippath.$zipname) ;

                        if ($zip_status === true) {

                            if ($zip->setPassword($servidor->secret_key)){
                                Storage::makeDirectory("importar-datos/".$servidor->id);
                                if ($zip->extractTo($zippath."/".$servidor->id)){
                                    $zip->close();

                                    //Borramos el ZIP y nos quedamos con los archivos extraidos
                                    Storage::delete("importar-datos/".$file->getClientOriginalName());

                                    //Storage::makeDirectory("importar-datos/".$servidor->id."/confirmacion");
                                    
                                    //Obtenemos información del servidor que está sincronizando
                                    $contents_header = Storage::get("importar-datos/".$servidor->id."/header.sync");
                                    $header_vars = ArchivoSync::parseVars($contents_header);
                                    $clues_que_hace_sync = $header_vars['CLUES'];
                                    // Obtenemos las fechas de actualizacion de sus catálogos
                                    //$contents_catalogos = Storage::get("importar-datos/".$servidor->id."/catalogos.sync");
                                    //$catalogos_vars = ArchivoSync::parseVars($contents_catalogos);
                                
                                    /*
                                    // Verificamos que esta actualiacion no se haya aplicado antes                     
                                    if(Sincronizacion::where('servidor_id',$servidor->id)->where('fecha_generacion',$header_vars['FECHA_SYNC'])->count() > 0){
                                        //Storage::deleteDirectory("importar-datos/".$servidor->id);
                                        throw new \Exception("No se puede importar más de una vez el mismo archivo de sincronización. La fecha del archivo indica que ya fue cargado previamente.");
                                    }                                   
                                    */

                                    /*
                                    $actualizar_catalogos = "false";
                                    Storage::put("importar-datos/".$servidor->id."/confirmacion/catalogos.sync","");
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
                                                        //Storage::append("importar-datos/".$servidor->id."/confirmacion/catalogos.sync", "REPLACE INTO ".$key." VALUES ");
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

                                                    Storage::append("importar-datos/".$servidor->id."/confirmacion/catalogos.sync", $query);                                                    
                                                } 

                                            }
                                        }
                                    }
                                    */
                                   
                                    // Registramos la version del servidor y si los catálogos estan actualizados
                                    $servidor->version = $header_vars['VERSION'];
                                    /*if ($actualizar_catalogos == "true") {
                                        $servidor->catalogos_actualizados = false;
                                    } else {
                                        $servidor->catalogos_actualizados = true;
                                    }*/
                                    //$servidor->ultima_sincronizacion = $header_vars['FECHA_SYNC'];
                                    $servidor->save();

                                    // Comparamos la version del servidor principal y si es diferente le indicamos que tiene que actualizar
                                    if($servidor->version != Config::get("sync.api_version")) {
                                        $actualizar_software = "true";
                                    } else {
                                        $actualizar_software = "false";
                                    }
                                   
                                    // Se ejecuta la sincronización
                                    $contents = Storage::get("importar-datos/".$servidor->id."/datos.sync");
                                    $conexion_local->statement('SET FOREIGN_KEY_CHECKS=0');
                                    $conexion_local->getpdo()->exec($contents);
                                    $conexion_local->statement('SET FOREIGN_KEY_CHECKS=1');

                                    /*
                                    // Agregamos las tablas pivote al archivo de confirmación para su descarga en offline
                                    Storage::put("importar-datos/".$servidor->id."/confirmacion/pivotes.sync","");
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
                                            Storage::append("importar-datos/".$servidor->id."/confirmacion/pivotes.sync", $query);
                                        }
                                    }
                                    */

                                    // Registramos la sincronización en la base de datos. 
                                    /*
                                    $sincronizacion = new Sincronizacion;
                                    $sincronizacion->servidor_id = $servidor->id;
                                    $sincronizacion->fecha_generacion = $header_vars['FECHA_SYNC'];
                                    $sincronizacion->save();
                                    */

                                    /*
                                    $confirmacion_file = "importar-datos/".$servidor->id."/confirmacion/confirmacion.sync";
                                    Storage::put($confirmacion_file,"ID=".$servidor->id);
                                    Storage::append($confirmacion_file,"FECHA_SYNC=".$header_vars['FECHA_SYNC']);                                   
                                    Storage::append($confirmacion_file,"ACTUALIZAR_SOFTWARE=".$actualizar_software);
                                    Storage::append($confirmacion_file,"VERSION_ACTUAL_SOFTWARE=".Config::get("sync.api_version"));
                                    Storage::append($confirmacion_file,"ACTUALIZAR_CATALOGOS=".$actualizar_catalogos);
                                    $storage_path = storage_path();
                                    
                                    $zip = new ZipArchive();
                                    $zippath = $storage_path."/app/";
                                    $zipname = "sync.confirmacion.".$servidor->id.".zip";                                   

                                    exec("zip -P ".$servidor->secret_key." -j -r ".$zippath.$zipname." \"".$zippath."/importar-datos/".$servidor->id."/confirmacion\"");
                                    //exec("zip  -j -r ".$zippath.$zipname." \"".$zippath."/importar/".$servidor->id."/confirmacion\"");
                                    $zip_status = $zip->open($zippath.$zipname);
                                    */

                                    $conexion_local->commit();

                                    LogSync::create([
                                        "clues" => $clues_que_hace_sync,
                                        "descripcion" => "Importó archivo de datos del servidor central."
                                    ]);
                                    exit();

                                    /*
                                    if ($zip_status === true) {

                                        $zip->close();  

                                        ///Then download the zipped file.
                                        header('Content-Type: application/zip');
                                        header('Content-disposition: attachment; filename='.$zipname);
                                        header('Content-Length: ' . filesize($zippath.$zipname));
                                        readfile($zippath.$zipname);
                                        Storage::delete($zipname);
                                        Storage::deleteDirectory("importar-datos/".$servidor->id);

                                        $conexion_local->commit();

                                        LogSync::create([
                                            "clues" => $clues_que_hace_sync,
                                            "descripcion" => "Importó archivo de sincronización manual."
                                        ]);
                                        exit();
                                    } else {            
                                        Storage::deleteDirectory("importar-datos/".$servidor->id);    
                                        throw new \Exception("No se pudo crear el archivo");
                                    }
                                    */

                                } else {
                                    Storage::delete("importar-datos/".$file->getClientOriginalName());
                                    Storage::deleteDirectory("importar-datos/".$servidor->id);
                                    throw new \Exception("No se pudo desencriptar el archivo, es posible que la llave de descriptación sea incorrecta, o que el nombre del archivo no corresponda al servidor correcto."); 
                                }

                            } else {
                                $zip->close();
                                Storage::delete("importar-datos/".$file->getClientOriginalName());
                                throw new \Exception("Ocurrió un error al desencriptar el archivo");
                            }                            
                            exit;
                        } else {   
                            Storage::delete("importar-datos/".$file->getClientOriginalName());             
                            throw new \Exception("No se pudo leer el archivo");
                        }

                    } else{
                        Storage::delete("importar-datos/".$file->getClientOriginalName());
                        throw new \Exception("Archivo inválido, es posible que el nombre haya sido alterado o el servidor que desea sincronizar no se encuentra registrado.");
                    }
                }
            } else {
                throw new \Exception("No hay archivo.  datos-central.".$servidor_id.".zip");
            }
        } catch (\Illuminate\Database\QueryException $e){            
            //echo " Sync Importación Excepción: ".$e->getMessage();
            Storage::append('log.sync', $fecha_generacion." Sync Importación Excepción: ".$e->getMessage());
            $conexion_local->rollback();
            
            LogSync::create([
                "clues" => $clues_que_hace_sync,
                "descripcion" => "Intentó importar archivo de datos del servidor central pero hubo un error."
            ]);
            
            return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
        catch(\Exception $e ){
            LogSync::create([
                "clues" => $clues_que_hace_sync,
                "descripcion" => "Intentó importar archivo de datos del servidor central pero hubo un error."
            ]);
            return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }
}
