<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Permiso;
use App\Models\UnidadMedica;
use App\Models\Articulos;
use App\Models\ArticulosMetadatos;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response;
use DB;



class AutoCompleteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function grupo_permiso()
    {
        $parametros = Input::only('term');
        
		$data =  Permiso::where(function($query) use ($parametros) {
		 	$query->where('grupo','LIKE',"%".$parametros['term']."%");
		});
        
        $variable = $data->distinct()->select(DB::raw("grupo as nombre"))->get();    
        $data = [];    
       	foreach ($variable as $key => $value) {
       		$data[] = $value->nombre;
       	}
        return Response::json([ 'data' => $data],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function clues()
    {  
        $parametros = Input::only('term');
        
		$data =  UnidadMedica::with('jurisdiccion')->where(function($query) use ($parametros) {
		 	$query->where('clues','LIKE',"%".$parametros['term']."%")
		 	->orWhere('nombre','LIKE',"%".$parametros['term']."%");
		});
        
        $data = $data->get();

        return Response::json([ 'data' => $data],200);
    }

    public function cluesPedidosCC()
    {  
        $parametros = Input::only('term');
        
		$data =  UnidadMedica::with('jurisdiccion')->where(function($query) use ($parametros){
		 	                            $query->where('clues','LIKE',"%".$parametros['term']."%")
		 	                                  ->orWhere('nombre','LIKE',"%".$parametros['term']."%");
		                    })->whereIn('tipo',['HO','AJ']);
        
        $data = $data->get();

        return Response::json([ 'data' => $data],200);
    }


     public function articulos()
    {
        $parametros = Input::only('term', 'clues', 'almacen');
        
        $data =  Articulos::with("Categoria", "Padre", "Hijos", "ArticulosMetadatos", "Inventarios")        
                       
        ->where(function($query) use ($parametros) {
            $query->where('articulos.descripcion','LIKE',"%".$parametros['term']."%")
            ->orWhere('articulos.nombre','LIKE',"%".$parametros['term']."%");
        });
        
        $data = $data->get();

        return Response::json([ 'data' => $data],200);
    }

    public function articulos_inventarios() {
        $parametros = Input::only('term', 'clues', 'almacen');
        // dd($parametros);  die;
        $data =  Articulos::with("Categoria", "ArticulosMetadatos")        
                       
        ->where(function($query) use ($parametros) {
            $query->where('articulos.descripcion','LIKE',"%".$parametros['term']."%")
            ->orWhere('articulos.nombre','LIKE',"%".$parametros['term']."%");
        });

        $data = $data->leftJoin('inventario', function($join) {
            $join->on('articulos.id', '=', 'inventario.articulo_id');
          })
        ->where('inventario.baja', 0)
        ->where('inventario.almacen_id', $parametros['almacen'])
        ->where('inventario.existencia', '>', 0)->take(1);
        
        $data = $data->get();

        return Response::json([ 'data' => $data],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function insumos(Request $request)
    {
        $parametros = Input::only('term', 'clues', 'almacen','stock');

        // $CLAVES = CluesClaves::where('clues',$clues)->get("clave");
///////// agregar abajo de los where para limitar a las claves de la unidad
        // ->whereIn('im.clave',$CLAVES)


        //Akira: tuve que agregar este if porque no se que pedo con su union mafufo
        // si hubiera algun problema corrijan en el "else" porfa
        if(isset($parametros['stock'])){
            $um = UnidadMedica::find($parametros['clues']);
            $almacenes = $um->almacenes;

            $ids_almacenes = [];
            foreach($almacenes as $item){
                $ids_almacenes[] = $item->id;
            }

            $data =  DB::table("insumos_medicos AS im")->distinct()
            ->select(
                "im.clave", "im.tipo", "g.nombre",DB::raw("um.nombre AS unidad_medida"), "m.cantidad_x_envase", "im.es_causes", "im.es_unidosis", "im.descripcion", 
                DB::raw("'' AS codigo_barras"),"pm.nombre AS presentacion_nombre",
                DB::raw("SUM(s.existencia) as existencia"))        
            ->leftJoin('stock AS s', 's.clave_insumo_medico', '=', 'im.clave')
            ->leftJoin('genericos AS g', 'g.id', '=', 'im.generico_id')
            ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'im.clave')
            ->leftJoin('unidades_medida AS um', 'um.id', '=', 'm.unidad_medida_id')
            ->leftJoin('presentaciones_medicamentos AS pm', 'pm.id', '=', 'm.presentacion_id')
            ->whereIn('almacen_id', $ids_almacenes)
            ->where('im.deleted_at',NULL)
            ->where(function($query1) use ($parametros) {
                $query1->where('im.tipo','ME')
                ->orWhere('im.tipo','MC');
            })->where(function($query) use ($parametros) {
                $query->where('im.clave','LIKE',"%".$parametros['term']."%")
                ->orWhere('g.nombre','LIKE',"%".$parametros['term']."%")
                ->orWhere('im.descripcion','LIKE',"%".$parametros['term']."%")
                ->orWhere('s.codigo_barras','LIKE',"%".$parametros['term']."%");
            })->orderBy('im.descripcion', 'asc');
            $data = $data->groupBy("clave")->get();
            
            return Response::json([ 'data' => $data],200);

        } else {
            $data1 =  DB::table("insumos_medicos AS im")
            ->select(DB::raw("pbd.precio as precio_unitario_base"),DB::raw("pbd.precio as precio_unitario"),"im.clave", "im.tipo", "g.nombre",DB::raw("um.nombre AS unidad_medida"), "m.cantidad_x_envase", "im.es_causes", "im.es_unidosis", "im.descripcion", DB::raw("'' AS codigo_barras"),"pm.nombre AS presentacion_nombre")        
            ->leftJoin('stock AS s', 's.clave_insumo_medico', '=', 'im.clave')
            ->leftJoin('genericos AS g', 'g.id', '=', 'im.generico_id')
            ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'im.clave')
            ->leftJoin('unidades_medida AS um', 'um.id', '=', 'm.unidad_medida_id')
            ->leftJoin('presentaciones_medicamentos AS pm', 'pm.id', '=', 'm.presentacion_id')

            ->leftJoin('precios_base AS pb', 'pb.activo','=',DB::raw("1"))
            ->leftJoin('precios_base_detalles AS pbd', function($join){
                    $join->on('im.clave', '=', 'pbd.insumo_medico_clave');
                    $join->on('pbd.precio_base_id', '=', 'pb.id');
                })


            ->where('almacen_id', $parametros['almacen'])
            ->where('im.deleted_at',NULL)
            ->where(function($query1) use ($parametros) {
                $query1->where('im.tipo','ME')
                ->orWhere('im.tipo','MC');
            })->where(function($query) use ($parametros) {
                $query->where('im.clave','LIKE',"%".$parametros['term']."%")
                ->orWhere('g.nombre','LIKE',"%".$parametros['term']."%")
                ->orWhere('im.descripcion','LIKE',"%".$parametros['term']."%")
                ->orWhere('s.codigo_barras','LIKE',"%".$parametros['term']."%");
            })->orderBy('im.descripcion', 'asc');
            
    
            //$parametros = Input::only('term', 'clues', 'almacen');
            
            $data2 =  DB::table("insumos_medicos AS im")->select(DB::raw("pbd.precio as precio_unitario_base"),DB::raw("pbd.precio as precio_unitario"),"im.clave", "im.tipo", "g.nombre",DB::raw("um.nombre AS unidad_medida"), "m.cantidad_x_envase", "im.es_causes", "im.es_unidosis", "im.descripcion", DB::raw("'' AS codigo_barras"),"pm.nombre AS presentacion_nombre")
            ->leftJoin('genericos AS g', 'g.id', '=', 'im.generico_id')
            ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'im.clave')
            ->leftJoin('unidades_medida AS um', 'um.id', '=', 'm.unidad_medida_id')
            ->leftJoin('presentaciones_medicamentos AS pm', 'pm.id', '=', 'm.presentacion_id')

            ->leftJoin('precios_base AS pb', 'pb.activo','=',DB::raw("1"))
            ->leftJoin('precios_base_detalles AS pbd', function($join){
                    $join->on('im.clave', '=', 'pbd.insumo_medico_clave');
                    $join->on('pbd.precio_base_id', '=', 'pb.id');
                })

            ->where('im.deleted_at',NULL)
            ->where(function($query2) use ($parametros) {
                $query2->where('im.tipo','ME')
                ->orWhere('im.tipo','MC');
            })->where(function($query) use ($parametros) {
                $query->where('im.clave','LIKE',"%".$parametros['term']."%")
                ->orWhere('g.nombre','LIKE',"%".$parametros['term']."%")
                ->orWhere('im.descripcion','LIKE',"%".$parametros['term']."%");
            })->orderBy('im.descripcion', 'asc');
            
    
            
            $data = $data1->union($data2);
            $data = $data->groupBy("clave")->get();
    
            return Response::json([ 'data' => $data],200);
        }


        
        
    }    

    public function insumosLaboratorioClinico()
    {
        $parametros = Input::only('term', 'clues', 'almacen');

        // $CLAVES = CluesClaves::where('clues',$clues)->get("clave");
///////// agregar abajo de los where para limitar a las claves de la unidad
        // ->whereIn('im.clave',$CLAVES)
        
        $data1 =  DB::table("insumos_medicos AS im")->distinct()->select("im.clave", "im.tipo","g.nombre",DB::raw("um.nombre AS unidad_medida"), "sl.cantidad_x_envase", "im.es_causes", "im.es_unidosis", "im.descripcion", DB::raw("'' AS codigo_barras"),"ps.nombre AS presentacion_nombre")
        ->leftJoin('stock AS s', 's.clave_insumo_medico', '=', 'im.clave')
        ->leftJoin('genericos AS g', 'g.id', '=', 'im.generico_id')
        ->leftJoin('sustancias_laboratorio AS sl', 'sl.insumo_medico_clave', '=', 'im.clave')
        ->leftJoin('unidades_medida AS um', 'um.id', '=', 'sl.unidad_medida_id')
        ->leftJoin('presentaciones_sustancias AS ps', 'ps.id', '=', 'sl.presentacion_id')
        ->where('almacen_id', $parametros['almacen'])
        ->where('im.tipo', 'LC')
        ->where('im.deleted_at',NULL)
        ->where(function($query) use ($parametros) {
            $query->where('im.clave','LIKE',"%".$parametros['term']."%")
            ->orWhere('g.nombre','LIKE',"%".$parametros['term']."%")
            ->orWhere('im.descripcion','LIKE',"%".$parametros['term']."%")
            ->orWhere('s.codigo_barras','LIKE',"%".$parametros['term']."%");
        })->orderBy('im.descripcion', 'asc');
        

        $parametros = Input::only('term', 'clues', 'almacen');
        
        $data2 =  DB::table("insumos_medicos AS im")->distinct()->select("im.clave", "im.tipo", "g.nombre",DB::raw("um.nombre AS unidad_medida"), "sl.cantidad_x_envase", "im.es_causes", "im.es_unidosis", "im.descripcion", DB::raw("'' AS codigo_barras"),"ps.nombre AS presentacion_nombre")
        ->leftJoin('genericos AS g', 'g.id', '=', 'im.generico_id')
        ->leftJoin('sustancias_laboratorio AS sl', 'sl.insumo_medico_clave', '=', 'im.clave')
        ->leftJoin('unidades_medida AS um', 'um.id', '=', 'sl.unidad_medida_id')
        ->leftJoin('presentaciones_sustancias AS ps', 'ps.id', '=', 'sl.presentacion_id')
        ->where('im.tipo', 'LC')
        ->where('im.deleted_at',NULL)
        ->where(function($query) use ($parametros) {
            $query->where('im.clave','LIKE',"%".$parametros['term']."%")
            ->orWhere('g.nombre','LIKE',"%".$parametros['term']."%")
            ->orWhere('im.descripcion','LIKE',"%".$parametros['term']."%");
        })->orderBy('im.descripcion', 'asc');
        
        
        $data = $data1->union($data2);
        $data = $data->groupBy("clave")->get();

        return Response::json([ 'data' => $data],200);
    }    
}