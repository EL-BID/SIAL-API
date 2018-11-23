<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class AbastoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lista()
    {
        $parametros = Input::only('clues','proveedores','jurisdicciones','page','per_page','ordenar_causes','ordenar_no_causes','ordenar_material_curacion');

        $items = self::getItemsQuery($parametros);
        
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }
       
        return Response::json([ 'data' => $items],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function excel()
    {
        $parametros = Input::only('clues','proveedores','jurisdicciones','page','per_page','ordenar_causes','ordenar_no_causes','ordenar_material_curacion');

        $items = self::getItemsQuery($parametros);
        $items = $items->get();

         Excel::create("Abasto al ".date('Y-m-d'), function($excel) use($items) {

            $excel->sheet('Reporte de abasto', function($sheet) use($items) {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'Proveedor', 'Clues','Nombre','% Causes','% No causes','% Material de curación'
                ));
                $sheet->cells("A1:F1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });

                $contador_filas = 1;
                foreach($items as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->proveedor,
                        $item->clues,
                        $item->unidad_medica,
                        $item->porcentaje_causes,
                        $item->porcentaje_no_causes,
                        $item->porcentaje_material_curacion
                    )); 
                }
                $sheet->setBorder("A1:F$contador_filas", 'thin');
            });
         })->export('xls');
    }

    private function getItemsQuery($parametros){       

        $items = DB::table(DB::raw('

            (SELECT DATOS.proveedor, DATOS.proveedor_id, DATOS.clues, DATOS.jurisdiccion_id, DATOS.unidad_medica,
                    
                    if(CAUSES.total_claves is null, 0, CAUSES.total_claves) as total_claves_causes, 
                    DATOS.cantidad_causes as abasto_configurado_causes,
                    ROUND(if(CAUSES.total_claves  is null, 0, CAUSES.total_claves) * 100 / DATOS.cantidad_causes,2) as porcentaje_causes,
                    
                    if(NO_CAUSES.total_claves is null, 0, NO_CAUSES.total_claves) as total_claves_no_causes, 
                    DATOS.cantidad_no_causes  as abasto_configurado_no_causes,
                    ROUND(if(NO_CAUSES.total_claves  is null, 0, NO_CAUSES.total_claves) * 100 / DATOS.cantidad_causes,2) as porcentaje_no_causes,
                    
                    if(MATERIAL_CURACION.total_claves is null, 0, MATERIAL_CURACION.total_claves) as total_claves_material_curacion, 
                    DATOS.cantidad_material_curacion  as abasto_configurado_material_curacion,
                    ROUND(if(MATERIAL_CURACION.total_claves  is null, 0, MATERIAL_CURACION.total_claves) * 100 / DATOS.cantidad_causes,2) as porcentaje_material_curacion
            FROM
            (SELECT 
                
                proveedores.nombre as proveedor, 
                proveedores.id as proveedor_id, 
                unidades_medicas.clues, 
                unidades_medicas.jurisdiccion_id, 
                unidades_medicas.nombre as unidad_medica,
                unidad_medica_abasto_configuracion.cantidad_causes,
                unidad_medica_abasto_configuracion.cantidad_no_causes,
                unidad_medica_abasto_configuracion.cantidad_material_curacion
            FROM almacenes 
            LEFT JOIN proveedores on proveedores.id = almacenes.proveedor_id
            LEFT JOIN unidades_medicas on unidades_medicas.clues = almacenes.clues
            LEFT JOIN unidad_medica_abasto_configuracion on unidades_medicas.clues = unidad_medica_abasto_configuracion.clues

            WHERE unidades_medicas.activa = 1
            group by unidades_medicas.clues) AS DATOS

            LEFT JOIN 

            (SELECT 
                unidades_medicas.clues, 
                count(stock.clave_insumo_medico) as total_claves
            FROM stock
            LEFT JOIN almacenes on almacenes.id = stock.almacen_id
            LEFT JOIN insumos_medicos on stock.clave_insumo_medico = insumos_medicos.clave
            LEFT JOIN proveedores on proveedores.id = almacenes.proveedor_id
            LEFT JOIN unidades_medicas on unidades_medicas.clues = almacenes.clues

            WHERE insumos_medicos.tipo = "ME" and insumos_medicos.es_causes = 1
            group by unidades_medicas.clues) AS CAUSES

            ON DATOS.clues = CAUSES.clues

            LEFT JOIN 

            (SELECT 
                unidades_medicas.clues, 
                count(stock.clave_insumo_medico) as total_claves
            FROM stock
            LEFT JOIN almacenes on almacenes.id = stock.almacen_id
            LEFT JOIN insumos_medicos on stock.clave_insumo_medico = insumos_medicos.clave
            LEFT JOIN proveedores on proveedores.id = almacenes.proveedor_id
            LEFT JOIN unidades_medicas on unidades_medicas.clues = almacenes.clues

            WHERE insumos_medicos.tipo = "ME" and insumos_medicos.es_causes = 0
            group by unidades_medicas.clues) AS NO_CAUSES

            ON DATOS.clues = NO_CAUSES.clues

            LEFT JOIN 

            (SELECT 
                unidades_medicas.clues, 
                count(stock.clave_insumo_medico) as total_claves
                
            FROM stock
            LEFT JOIN almacenes on almacenes.id = stock.almacen_id
            LEFT JOIN insumos_medicos on stock.clave_insumo_medico = insumos_medicos.clave
            LEFT JOIN proveedores on proveedores.id = almacenes.proveedor_id
            LEFT JOIN unidades_medicas on unidades_medicas.clues = almacenes.clues

            WHERE insumos_medicos.tipo = "MC"
            group by unidades_medicas.clues) AS MATERIAL_CURACION

            ON DATOS.clues = MATERIAL_CURACION.clues) as abasto
        '));
            
        
        if (isset($parametros['clues']) &&  $parametros['clues'] != "") {
            $items = $items->where(function($query) use ($parametros){
                $query->where('unidad_medica','LIKE',"%".$parametros['clues']."%")->orWhere('clues','LIKE',"%".$parametros['clues']."%");
            });
        } 
        

        if(isset($parametros['proveedores']) && $parametros['proveedores'] != ""){
            $proveedores = explode(',',$parametros['proveedores']);            
            if(count($proveedores)>0){
                $items = $items->whereIn('proveedor_id',$proveedores);
            }              
        }

        if(isset($parametros['jurisdicciones']) && $parametros['jurisdicciones'] != ""){
            $jurisdicciones = explode(',',$parametros['jurisdicciones']);            
            if(count($jurisdicciones)>0){
                $items = $items->whereIn('jurisdiccion_id',$jurisdicciones);
            }              
        }

        if(isset($parametros['ordenar_causes'])){
            if($parametros['ordenar_causes'] == 'ASC'){
                $items = $items->orderBy('porcentaje_causes','ASC');
            }
            if($parametros['ordenar_causes'] == 'DESC'){
                $items = $items->orderBy('porcentaje_causes','DESC');
            }
        }

        if(isset($parametros['ordenar_no_causes'])){
            if($parametros['ordenar_no_causes'] == 'ASC'){
                $items = $items->orderBy('porcentaje_no_causes','ASC');
            }
            if($parametros['ordenar_no_causes'] == 'DESC'){
                $items = $items->orderBy('porcentaje_no_causes','DESC');
            }
        }
        if(isset($parametros['ordenar_material_curacion'])){
            if($parametros['ordenar_material_curacion'] == 'ASC'){
                $items = $items->orderBy('porcentaje_material_curacion','ASC');
            }
            if($parametros['ordenar_material_curacion'] == 'DESC'){
                $items = $items->orderBy('porcentaje_material_curacion','DESC');
            }
        }
        return $items;
    }
    
}
