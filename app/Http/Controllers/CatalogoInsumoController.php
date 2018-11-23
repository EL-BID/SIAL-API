<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use JWTAuth;
use App\Http\Requests;
use App\Models\Insumo;
use App\Models\Usuario;
use App\Models\Contrato;
use App\Models\Proveedor;
use App\Models\Almacen;
use App\Models\GrupoInsumo;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

use App\Models\Stock, App\Models\ClavesBasicas, App\Models\ClavesBasicasDetalle,App\Models\ClavesBasicasUnidadMedica;


class CatalogoInsumoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        $almacen = Almacen::find($request->get('almacen_id'));

        if(Input::get('con_precios')){
            if(!$almacen->proveedor_id){
                $almacen = Almacen::where('clues',$almacen->clues)->whereNotNull('proveedor_id')->first();
            }

            if(!$almacen->proveedor_id){
                return Response::json(['error' => 'No hay proveedores asignados a ningun almacen de esta clues'], HttpResponse::HTTP_CONFLICT);
            }
            //Harima: obtenemos el contrato activo 
            $proveedor = Proveedor::with('contratoActivo')->find($almacen->proveedor_id);

            $contrato_activo = $proveedor->contratoActivo;
            
            if(!$contrato_activo){
                return Response::json(['error' => 'No se encontraron contratos activos para este proveedor'], HttpResponse::HTTP_CONFLICT);
            }
            
            //Se carga un scope con el cual obtenemos los nombres o descripciones de los catalogos que utiliza insumos_medicos
            $insumos = Insumo::conDescripcionesPrecios($contrato_activo->id,$proveedor->id)->with('informacion','generico.grupos');
        }else{
            $insumos = Insumo::conDescripciones()->with('informacion','generico.grupos');
        }
        
        if(!isset($parametros['con_descontinuados'])){
            $insumos = $insumos->where('descontinuado',0);
        }

        if(Input::get('disponible_pedidos') != null){
            if(Input::get('disponible_pedidos') == true){
                $insumos = $insumos->where('no_disponible_pedidos',0);
            }else{
                $insumos = $insumos->where('no_disponible_pedidos',1);
            }
        }

        //return Response::json([ 'data' => []],200);
        //return Response::json(['error' => ""], HttpResponse::HTTP_UNAUTHORIZED);

        $parametros = Input::only('q','page','per_page','tipo');
        //buscar una cadena en clave, descripcion, nombre del grupo o nombre del generico
        if ($parametros['q']) {
            //Hacemos una busqueda sobre grupos_insumos, para ver si hay grupos que conincidan con el criterio de busqueda, esto reemplaza el leftjoin que estaba en grupos_insumos
            $ids_genericos = [];
            $grupos = GrupoInsumo::where('nombre','LIKE',"%".$parametros['q']."%")->get(); //->genericos()->lists('id');
            if(count($grupos)){
                $grupos->load('genericos');
                foreach($grupos as $grupo){
                    $ids = $grupo->genericos->lists('id','id')->toArray();
                    $ids_genericos = array_merge($ids_genericos,$ids);
                }
            }

             $insumos =  $insumos->where(function($query) use ($parametros,$ids_genericos) {
                 $query->where('clave','LIKE',"%".$parametros['q']."%")->orWhere('descripcion','LIKE',"%".$parametros['q']."%")//->orWhere('grupos_insumos.nombre','LIKE',"%".$parametros['q']."%") //Se cambio a muchos a muchos
                        ->orWhere('genericos.nombre','LIKE',"%".$parametros['q']."%");
                 if(count($ids_genericos)){
                     $query->orWhereIn('genericos.id',$ids_genericos);
                 }
             });
        }
        
        if(isset($parametros['tipo'])){
            switch($parametros['tipo']){
                case 'CA': $insumos = $insumos->where('es_causes',true)->where("insumos_medicos.tipo","ME"); break;
                case 'NCA': $insumos = $insumos->where('es_causes',false)->where("insumos_medicos.tipo","ME"); break;
                case 'MC': $insumos = $insumos->where("insumos_medicos.tipo","MC"); break;
            }
        }
        /*//////////////////////////////////////////////////*/
        // AKIRA: CLAVES BÁSICAS Asignadas a la clues
        /*//////////////////////////////////////////////////*/
        
        $claves_basicas_clues = ClavesBasicasUnidadMedica::where('clues',$request->get('clues'))->get();
        // Si tiene asignada una  o más listas aplicamos el filtro, si no tiene se devolverían todos los insumos
        // a menos que se quiera restringir podemos devolver una lista vacía si debe tener asignada una lista a la fuerza        
        if(count($claves_basicas_clues)>0){
            // Obtenemos los ids de todas las listas que tenga
            $claves_basicas_ids = [];
            foreach($claves_basicas_clues as $item){
                $claves_basicas_ids[] = $item->claves_basicas_id;
            }

            $insumos_medicos = ClavesBasicasDetalle::whereIn('claves_basicas_id', $claves_basicas_ids)->groupBy('insumo_medico_clave')->get();
            // Obtenemos las claves
            $claves = [];
            foreach($insumos_medicos as $item){
                $claves[] = $item->insumo_medico_clave;
            }
            $insumos = $insumos->whereIn('clave',$claves);
        }
        /*//////////////////////////////////////////////////*/

        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
            $insumos = $insumos->paginate($resultadosPorPagina);
        } else {
            $insumos = $insumos->get();
        }

        /// se consigue stock en el almacen actual
        foreach($insumos as $insumo_temp)
        {
            $stocks = array();  

            //var_dump($request->get('almacen_id'));
            //die();

            $stocks = Stock::where('clave_insumo_medico',$insumo_temp->clave)->where('existencia','>',0)->where('almacen_id',$request->get('almacen_id'))->orderBy('fecha_caducidad','ASC')->get();
            $existencia = 0;
            $existencia_unidosis = 0;

            foreach($stocks as $stock)
            {
                $existencia += $stock->existencia;
                $existencia_unidosis += $stock->existencia_unidosis;
            }               

            $objeto_response = array('almacen_id' => $request->get('almacen_id'),
                                            'clave' => $insumo_temp->clave,
                                            'existencia' => $existencia,
                                            'existencia_unidosis' => $existencia_unidosis);
            $insumo_temp->stockExistencia = $objeto_response;
            
        }
        
        /// fin get stock en almacen actual
       
        return Response::json([ 'data' => $insumos],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $object = Insumo::conDescripciones()->find($id);
        $object->load('informacionAmpliada');

        if(!$object){
            return Response::json(['error' => "No se encuentra el insumo que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        
        return Response::json([ 'data' => $object ], HttpResponse::HTTP_OK);
    }
}