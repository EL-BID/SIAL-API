<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Requests;
use App\Models\Avance;
use App\Models\AvanceDetalles;
use App\Models\AvanceUsuarioPrivilegio;
use App\Models\AvanceVisualizacion;
use App\Models\Usuario;
use Carbon\Carbon;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

class AvanceDetalleController extends Controller
{
    public function index(Request $request)
    {

        
        $parametros = Input::only('status','q','page','per_page', 'identificador', 'tipo');
        $usuario = Usuario::find($request->get('usuario_id'));

        $actualizacion = AvanceVisualizacion::where('avance_id', $parametros['identificador'])
                                              ->where('usuario_id', $request->get('usuario_id'))
                                              ->first();

        if($actualizacion)
        {
            $actualizacion->updated_at = date("Y-m-d H:i:s");
            $actualizacion->save();
        }else{
            AvanceVisualizacion::create(['avance_id'=>$parametros['identificador'], 'usuario_id'=>$request->get('usuario_id')]);
        }                                      

        
        $avancedetalle = DB::table('avance_detalles')->where("avance_id", $parametros['identificador']);
        $avance = Avance::find($parametros['identificador']);

        $general = false;
        $permisos = [];
        $usuario_general = Usuario::with(['roles.permisos'=>function($permisos){
            $permisos->where('permisos.id','79B3qKuUbuEiR2qKS0CFgHy2zRWfmO4r');
        }])->find($request->get('usuario_id'));

        foreach ($usuario_general->roles as $index => $rol) {
            foreach ($rol->permisos as $permiso) {
                $permisos[$permiso->id] = true;

                if(count($permisos)){
                    $general = true;
                }
            }
        }
        if(count($permisos)){
            $general = true;
        }

        if($usuario->su !=1 && !$general)
            $avancedetalle = $avancedetalle->whereRaw("avance_detalles.avance_id in (select avance_id from avance_usuario_privilegio where usuario_id='".$request->get('usuario_id')."')" );

        $avancedetalle = $avancedetalle->orderBy('created_at', 'desc');

        if($parametros['tipo'] == 1)
        {
            $registros = $avancedetalle->count();
            if($registros > 0)
                $registros = $registros -1;
            
            $avancedetalle = $avancedetalle->whereRaw("created_at in (select max(created_at) from avance_detalles as avance_consulta where avance_consulta.avance_id=avance_detalles.avance_id and avance_consulta.deleted_at is null)");
            
            
            $avancedetalle = $avancedetalle->paginate(1);
            $avancedetalle->registros = 0;
            $avancedetalle->nombre_tema = $avance->tema;

            $administrador = false;
            if($request->get('usuario_id') == 'salud' || $request->get('usuario_id') == 'nugasoft@gmail.com')
                $administrador = true;
            
            return Response::json([ 'data' => array("registros"=>$avancedetalle, 'datos_tema'=>$avance, 'historial'=>$registros, 'administrador'=>$administrador)],200);

        }else if($parametros['tipo'] == 2)
        {
            $avancedetalle = $avancedetalle->whereRaw("created_at not in (select max(created_at) from avance_detalles as avance_consulta where avance_consulta.avance_id=avance_detalles.avance_id and avance_consulta.deleted_at is null)");
        }
		
		if ($parametros['q']) {
            $avancedetalle =  $avancedetalle->where(function($query) use ($parametros) {
                 $query->where('id','LIKE',"%".$parametros['q']."%")->orWhere('nombre','LIKE',"%".$parametros['q']."%")->orWhere('comentario','LIKE',"%".$parametros['q']."%");
             });
        }

        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
            $registros = abs($avancedetalle->count());
            $avancedetalle = $avancedetalle->paginate($resultadosPorPagina);
            
        } else {
            $avancedetalle = $avancedetalle->get();
        }

        $administrador = false;
        if($request->get('usuario_id') == 'salud' || $request->get('usuario_id') == 'nugasoft@gmail.com')
            $administrador = true;

        return Response::json([ 'data' => array("registros"=>$avancedetalle, 'datos_tema'=>$avance, 'historial'=>$registros, 'administrador'=>$administrador)],200);
    }

    public function store(Request $request){
        $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'porcentaje'        => 'required',
            'comentario'   => 'required',
            
        ];

        $parametros = Input::all();

        

        $v = Validator::make($parametros, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {

        	$directorio_destino_path = "avances";
       		$extension = explode(".", strtolower($_FILES['file']['name']));
            DB::beginTransaction();
            $parametros['nombre'] = $extension[0];
            $parametros['extension'] = $extension[1];
            $parametros['peso'] = $_FILES['file']['size'];

            $usuario = Usuario::find($request->get('usuario_id'));

            $privilegios = AvanceUsuarioPrivilegio::where("usuario_id", $request->get('usuario_id'))->where("avance_id", $parametros['avance_id'])->first();
            
            $general = false;
            $permisos = [];
            $usuario_general = Usuario::with(['roles.permisos'=>function($permisos){
                $permisos->where('permisos.id','79B3qKuUbuEiR2qKS0CFgHy2zRWfmO4r');
            }])->find($request->get('usuario_id'));

            foreach ($usuario_general->roles as $index => $rol) {
                foreach ($rol->permisos as $permiso) {
                    $permisos[$permiso->id] = true;

                    if(count($permisos)){
                        $general = true;
                    }
                }
            }
            if(count($permisos)){
                $general = true;
            }

            if($general || $usuario->su == 1 || (isset($privilegios)  && $privilegios->agregar == "1") )
            {
                $avance_detalle = AvanceDetalles::create($parametros);

                 \Request::file('file')->move($directorio_destino_path, $avance_detalle->id.".".$extension[1]);
            }else
            {
                DB::rollBack();
                return Response::json('No tiene privilegios para realizar esta accion.', 500);
            }    
            DB::commit();
            return Response::json([ 'data' => $avance_detalle ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }
    public function descargar($id, Request $request)
     {
        try{

            $variables = Input::all();

            /*$usuario = Usuario::with(['roles.permisos'=>function($permisos){
                $permisos->where('permisos.id','MrL06vIO12iNhchP14h57Puvg71eUmYb')->orWhere('id','bsIbPL3qv6XevcAyrRm1GxJufDbzLOax');
            }])->find($request->get('usuario_id'));
            
            $tiene_acceso = false;

            if(!$usuario->su){
                $permisos = [];
                foreach ($usuario->roles as $index => $rol) {
                    foreach ($rol->permisos as $permiso) {
                        $permisos[$permiso->id] = true;
                    }
                }
                if(count($permisos)){
                    $tiene_acceso = true;
                }else{
                    $tiene_acceso = false;
                }
            }else{
                $tiene_acceso = true;
            }
            
            if($tiene_acceso){
               $arreglo_log = array('repositorio_id' => $id,
                            'ip' =>$request->ip(),
                            'navegador' =>$request->header('User-Agent'),
                            'accion' => 'DOWNLOAD'); 

                $log_repositorio = LogRepositorio::create($arreglo_log);

                if(!$log_repositorio)
                {
                    return Response::json(['error' => "Error al descargar el archivo"], 500); 
                }*/

                $avancedetalle = AvanceDetalles::find($id);
                if($avancedetalle)
                {
                    $directorio_path = "avances";
                    $pathToFile = $directorio_path."//".$id.".".$avancedetalle->extension;
                    
                    $headers = array(
                        'Content-Type: application/pdf',
                    );

                    
                    if(!file_exists($pathToFile))
                        return Response::make("No se encontro el recurso solicitado, por favor comuniquese al área de soporte", 500);
                    else
                        return response()->download($pathToFile, $avancedetalle->nombre_archivo, $headers);
                }else
                {
                    return Response::make("No se encontro el recurso solicitado, por favor comuniquese al área de soporte", 500);
                }
            /*}else{
                return Response::json(['error' =>"No tiene permisos para ingresar a este modulo" ], 500);
            }*/
        }catch(Exception $e){
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
    public function view($id, Request $request)
     {
        try{

            $variables = Input::all();

            /*$usuario = Usuario::with(['roles.permisos'=>function($permisos){
                $permisos->where('permisos.id','MrL06vIO12iNhchP14h57Puvg71eUmYb')->orWhere('id','bsIbPL3qv6XevcAyrRm1GxJufDbzLOax');
            }])->find($request->get('usuario_id'));
            
            $tiene_acceso = false;

            if(!$usuario->su){
                $permisos = [];
                foreach ($usuario->roles as $index => $rol) {
                    foreach ($rol->permisos as $permiso) {
                        $permisos[$permiso->id] = true;
                    }
                }
                if(count($permisos)){
                    $tiene_acceso = true;
                }else{
                    $tiene_acceso = false;
                }
            }else{
                $tiene_acceso = true;
            }
            
            if($tiene_acceso){
               $arreglo_log = array('repositorio_id' => $id,
                            'ip' =>$request->ip(),
                            'navegador' =>$request->header('User-Agent'),
                            'accion' => 'DOWNLOAD'); 

                $log_repositorio = LogRepositorio::create($arreglo_log);

                if(!$log_repositorio)
                {
                    return Response::json(['error' => "Error al descargar el archivo"], 500); 
                }*/
                try {
	                $avancedetalle = AvanceDetalles::withTrashed()->find($id);
	                if($avancedetalle)
                    {
                        $directorio_path = "avances";
    	                $pathToFile = $directorio_path."//".$id.".".$avancedetalle->extension;
    	                
    	                $headers = array(
    	                    'Content-Type: application/pdf',
    	                );
                        if(!file_exists($pathToFile))
                            return Response::make("No se encontro el recurso solicitado, por favor comuniquese al área de soporte", 500);
                        else
    	                   return Response::make(file_get_contents($pathToFile), 200, [
    					    'Content-Type' => 'application/pdf',
    					    'Content-Disposition' => 'inline; filename="'.$id.".".$avancedetalle->extension.'"'
    					]);
                    }else
                    {
                        return Response::make("No se encontro el recurso solicitado, por favor comuniquese al área de soporte", 500);
                    }
				 } catch (Exception $e) {
		           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		        }	
            /*}else{
                return Response::json(['error' =>"No tiene permisos para ingresar a este modulo" ], 500);
            }*/
        }catch(Exception $e){
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }

    function destroy(Request $request, $id){
        try {
            $avanceDetalle = AvanceDetalles::find($id);
            $privilegios = AvanceUsuarioPrivilegio::where("usuario_id", $request->get('usuario_id'))->where("avance_id", $avanceDetalle->avance_id)->first();

            $usuario = Usuario::find($request->get('usuario_id'));
            $general = false;
            $permisos = [];
            $usuario_general = Usuario::with(['roles.permisos'=>function($permisos){
                $permisos->where('permisos.id','79B3qKuUbuEiR2qKS0CFgHy2zRWfmO4r');
            }])->find($request->get('usuario_id'));

            foreach ($usuario_general->roles as $index => $rol) {
                foreach ($rol->permisos as $permiso) {
                    $permisos[$permiso->id] = true;

                    if(count($permisos)){
                        $general = true;
                    }
                }
            }
            if(count($permisos)){
                $general = true;
            }

            if($usuario->su == 1 || $general || (isset($privilegios) && $privilegios->eliminar == "1"))
            {
                
                if($avanceDetalle){
                       $avanceDetalle->delete();
                    }else{
                        return Response::json(['error' => 'Este avance ya no puede eliminarse'], 500);
                    }
               
                //$object = Pedido::where('almacen_proveedor',$request->get('almacen_id'))->where('id',$id)->delete();
                return Response::json(['data'=>$avanceDetalle],200);
            }else
            {
                return Response::json(['error'=>"No tiene privilegios para eliminar este avance"],401);
            }
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }

    }
}
