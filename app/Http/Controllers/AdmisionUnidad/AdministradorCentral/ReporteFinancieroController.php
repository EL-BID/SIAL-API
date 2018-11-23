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

class ReporteFinancieroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lista()
    {
        $parametros = Input::only('clues','proveedores','jurisdicciones','tipos_unidad','status_pedido','agrupado_por','mes_inicio','anio_inicio','mes_fin','anio_fin','page','per_page');

        $items = self::getItemsQuery($parametros);
       
        return Response::json([ 'data' => $items],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function excel()
    {
        $parametros = Input::only('clues','proveedores','jurisdicciones','tipos_unidad','status_pedido','agrupado_por','mes_inicio','anio_inicio','mes_fin','anio_fin','page','per_page');

        $items = self::getItemsQuery($parametros);
       

         Excel::create("Reporte financiero al ".date('Y-m-d'), function($excel) use($items, $parametros) {

            $excel->sheet('Reporte financiero', function($sheet) use($items, $parametros) {
                $sheet->setAutoSize(true);
            
                if($parametros['agrupado_por'] == "UM"){
                    
                    $sheet->mergeCells('A1:H2');                    
                    
                    $sheet->mergeCells('I1:N1');
                    $sheet->mergeCells('I2:K2');
                    $sheet->mergeCells('L2:N2');

                    $sheet->getCell('I1')->setValue('Causes y Material de Curación');
                    $sheet->getCell('I2')->setValue('Monto');
                    $sheet->getCell('L2')->setValue('Insumos');

                    
                    $sheet->mergeCells('O1:T1');
                    $sheet->mergeCells('O2:Q2');
                    $sheet->mergeCells('R2:T2');

                    $sheet->getCell('O1')->setValue('No Causes');
                    $sheet->getCell('O2')->setValue('Monto');
                    $sheet->getCell('R2')->setValue('Insumos');
                    
                   
                    $sheet->row(3, array(
                        'Num', 'Clues','Tipo','Nombre','Modificado','Comprometido','Devengado', 'Disponible', 'Solicitado', 'Surtido', '%', 'Solicitado', 'Surtido','%', 'Solicitado', 'Surtido', '%', 'Solicitado', 'Surtido', '%'
                    ));
                } else {
                    $sheet->mergeCells('A1:G2');                    
                    
                    $sheet->mergeCells('H1:M1');
                    $sheet->mergeCells('H2:J2');
                    $sheet->mergeCells('K2:M2');

                    $sheet->getCell('H1')->setValue('Causes y Material de Curación');
                    $sheet->getCell('H2')->setValue('Monto');
                    $sheet->getCell('K2')->setValue('Insumos');

                    
                    $sheet->mergeCells('N1:S1');
                    $sheet->mergeCells('N2:P2');
                    $sheet->mergeCells('Q2:S2');

                    $sheet->getCell('N1')->setValue('No Causes');
                    $sheet->getCell('N2')->setValue('Monto');
                    $sheet->getCell('Q2')->setValue('Insumos');
                }
                if($parametros['agrupado_por'] == "P"){
                    $sheet->row(3, array(
                        'Num', 'Proveedor','Cant. Clues','Modificado','Comprometido','Devengado', 'Disponible', 'Solicitado', 'Surtido', '%', 'Solicitado', 'Surtido','%', 'Solicitado', 'Surtido', '%', 'Solicitado', 'Surtido', '%'
                    ));
                }
                if($parametros['agrupado_por'] == "NA"){
                    $sheet->row(3, array(
                        'Num', 'Nivel de atención','Cant. Clues','Modificado','Comprometido','Devengado', 'Disponible', 'Solicitado', 'Surtido', '%', 'Solicitado', 'Surtido','%', 'Solicitado', 'Surtido', '%', 'Solicitado', 'Surtido', '%'
                    ));
                }
                
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });
                $sheet->row(2, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });
                $sheet->row(3, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });

                $contador_filas = 3;
                $total_modificado = 0;
                $total_comprometido = 0;
                $total_devengado = 0;
                $total_disponible = 0;
                $total_monto_solicitado_causes_mat_cur = 0;
                $total_monto_recibido_causes_mat_cur = 0;
                $total_cantidad_solicitada_causes_mat_cur = 0;
                $total_cantidad_recibida_causes_mat_cur = 0;
                $total_monto_solicitado_no_causes = 0;
                $total_monto_recibido_no_causes = 0;
                $total_cantidad_solicitada_no_causes = 0;
                $total_cantidad_recibida_no_causes = 0;

                foreach($items as $item){
                    $contador_filas++;
                    
                    $total_modificado += $item->modificado;
                    $total_comprometido += $item->comprometido;
                    $total_devengado += $item->devengado;
                    $total_disponible += $item->disponible;
                    
                    $total_monto_solicitado_causes_mat_cur += $item->causes_mat_cur_monto_solicitado;
                    $total_monto_recibido_causes_mat_cur += $item->causes_mat_cur_monto_recibido;
                    $total_cantidad_solicitada_causes_mat_cur += $item->causes_mat_cur_cantidad_solicitada;
                    $total_cantidad_recibida_causes_mat_cur += $item->causes_mat_cur_cantidad_recibida;

                    $total_monto_solicitado_no_causes += $item->no_causes_monto_solicitado;
                    $total_monto_recibido_no_causes += $item->no_causes_monto_recibido;
                    $total_cantidad_solicitada_no_causes += $item->no_causes_cantidad_solicitada;
                    $total_cantidad_recibida_no_causes += $item->no_causes_cantidad_recibida;

                    if($parametros['agrupado_por'] == "UM"){
                        $sheet->appendRow(array(
                            $contador_filas - 3,
                            $item->clues,
                            $item->tipo,
                            $item->nombre,
                            $item->modificado,
                            $item->comprometido,
                            $item->devengado,
                            $item->disponible,
                            $item->causes_mat_cur_monto_solicitado,
                            $item->causes_mat_cur_monto_recibido,
                            $item->causes_mat_cur_porcentaje_monto,
                            $item->causes_mat_cur_cantidad_solicitada,
                            $item->causes_mat_cur_cantidad_recibida,
                            $item->causes_mat_cur_porcentaje_cantidad,
                            $item->no_causes_monto_solicitado,
                            $item->no_causes_monto_recibido,
                            $item->no_causes_porcentaje_monto,
                            $item->no_causes_cantidad_solicitada,
                            $item->no_causes_cantidad_recibida,
                            $item->no_causes_porcentaje_cantidad
                        ));                         
                    }

                    if($parametros['agrupado_por'] == "P"){
                        $sheet->appendRow(array(
                            $contador_filas - 3,
                            $item->nombre,
                            $item->cantidad_clues,
                            $item->modificado,
                            $item->comprometido,
                            $item->devengado,
                            $item->disponible,
                            $item->causes_mat_cur_monto_solicitado,
                            $item->causes_mat_cur_monto_recibido,
                            $item->causes_mat_cur_porcentaje_monto,
                            $item->causes_mat_cur_cantidad_solicitada,
                            $item->causes_mat_cur_cantidad_recibida,
                            $item->causes_mat_cur_porcentaje_cantidad,
                            $item->no_causes_monto_solicitado,
                            $item->no_causes_monto_recibido,
                            $item->no_causes_porcentaje_monto,
                            $item->no_causes_cantidad_solicitada,
                            $item->no_causes_cantidad_recibida,
                            $item->no_causes_porcentaje_cantidad
                        ));                         
                    }
                    if($parametros['agrupado_por'] == "NA"){
                        $sheet->appendRow(array(
                            $contador_filas - 3,
                            $item->nivel_atencion,
                            $item->cantidad_clues,
                            $item->modificado,
                            $item->comprometido,
                            $item->devengado,
                            $item->disponible,
                            $item->causes_mat_cur_monto_solicitado,
                            $item->causes_mat_cur_monto_recibido,
                            $item->causes_mat_cur_porcentaje_monto,
                            $item->causes_mat_cur_cantidad_solicitada,
                            $item->causes_mat_cur_cantidad_recibida,
                            $item->causes_mat_cur_porcentaje_cantidad,
                            $item->no_causes_monto_solicitado,
                            $item->no_causes_monto_recibido,
                            $item->no_causes_porcentaje_monto,
                            $item->no_causes_cantidad_solicitada,
                            $item->no_causes_cantidad_recibida,
                            $item->no_causes_porcentaje_cantidad
                        ));                         
                    }
                }
                if($parametros['agrupado_por'] == "UM"){
                    $sheet->cells("A1:T3", function($cells) {
                        $cells->setAlignment('center');
                    });

                    $sheet->appendRow(array(
                        "",
                        "",
                        "",
                        "TOTAL",
                        $total_modificado,
                        $total_comprometido,
                        $total_devengado,
                        $total_disponible,
                        
                        $total_monto_solicitado_causes_mat_cur,
                        $total_monto_recibido_causes_mat_cur,
                        ($total_monto_recibido_causes_mat_cur * 100 / $total_monto_solicitado_causes_mat_cur),
                        $total_cantidad_solicitada_causes_mat_cur,
                        $total_cantidad_recibida_causes_mat_cur,
                        ($total_cantidad_recibida_causes_mat_cur * 100 / $total_cantidad_solicitada_causes_mat_cur),

                        $total_monto_solicitado_no_causes,
                        $total_monto_recibido_no_causes,
                        ($total_monto_recibido_no_causes * 100 / $total_monto_solicitado_no_causes),
                        $total_cantidad_solicitada_no_causes,
                        $total_cantidad_recibida_no_causes,
                        ($total_cantidad_recibida_no_causes * 100 / $total_cantidad_solicitada_no_causes)
                    )); 

                    $sheet->setColumnFormat(array(
                        "E2:E".($contador_filas+1) => '"$" #,##0.00_-',
                        "F2:F".($contador_filas+1) => '"$" #,##0.00_-',
                        "G2:G".($contador_filas+1) => '"$" #,##0.00_-',
                        "H2:H".($contador_filas+1) => '"$" #,##0.00_-',
                        
                        "I2:I".($contador_filas+1) => '"$" #,##0.00_-',
                        "J2:J".($contador_filas+1) => '"$" #,##0.00_-',
                        "K2:K".($contador_filas+1) => '#,##0.00_- "%"',
                        "L2:L".($contador_filas+1) => '"$" #,##0.00_-',
                        "M2:M".($contador_filas+1) => '"$" #,##0.00_-',
                        "N2:N".($contador_filas+1) => '#,##0.00_- "%"',

                        "O2:O".($contador_filas+1) => '"$" #,##0.00_-',
                        "P2:P".($contador_filas+1) => '"$" #,##0.00_-',
                        "Q2:Q".($contador_filas+1) => '#,##0.00_- "%"',
                        "R2:R".($contador_filas+1) => '"$" #,##0.00_-',
                        "S2:S".($contador_filas+1) => '"$" #,##0.00_-',
                        "T2:T".($contador_filas+1) => '#,##0.00_- "%"'
                    ));

                    $sheet->setBorder("A1:T$contador_filas", 'thin');
                    $sheet->setBorder("D".($contador_filas+1).":T".($contador_filas+1), 'thin');
                    
                    $sheet->row(($contador_filas+1), function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    
                }
                if($parametros['agrupado_por'] != "UM"){
                                      
                    $sheet->cells("A1:S3", function($cells) {
                        $cells->setAlignment('center');
                    });
                    $sheet->appendRow(array(
                        "",
                        "",
                        "TOTAL",
                        $total_modificado,
                        $total_comprometido,
                        $total_devengado,
                        $total_disponible,

                        $total_monto_solicitado_causes_mat_cur,
                        $total_monto_recibido_causes_mat_cur,
                        ($total_monto_recibido_causes_mat_cur * 100 / $total_monto_solicitado_causes_mat_cur),
                        $total_cantidad_solicitada_causes_mat_cur,
                        $total_cantidad_recibida_causes_mat_cur,
                        ($total_cantidad_recibida_causes_mat_cur * 100 / $total_cantidad_solicitada_causes_mat_cur),

                        $total_monto_solicitado_no_causes,
                        $total_monto_recibido_no_causes,
                        ($total_monto_recibido_no_causes * 100 / $total_monto_solicitado_no_causes),
                        $total_cantidad_solicitada_no_causes,
                        $total_cantidad_recibida_no_causes,
                        ($total_cantidad_recibida_no_causes * 100 / $total_cantidad_solicitada_no_causes)
                    )); 

                    $sheet->setColumnFormat(array(
                        "D2:D".($contador_filas+1) => '"$" #,##0.00_-',
                        "E2:E".($contador_filas+1) => '"$" #,##0.00_-',
                        "F2:F".($contador_filas+1) => '"$" #,##0.00_-',
                        "G2:G".($contador_filas+1) => '"$" #,##0.00_-',
                        "H2:H".($contador_filas+1) => '"$" #,##0.00_-',
                        "I2:I".($contador_filas+1) => '"$" #,##0.00_-',
                        "J2:J".($contador_filas+1) => '#,##0.00_- "%" ',
                        "K2:K".($contador_filas+1) => '"$" #,##0.00_-',
                        "L2:L".($contador_filas+1) => '"$" #,##0.00_-',
                        "M2:M".($contador_filas+1) => '#,##0.00_- "%" ',
                        
                        "N2:N".($contador_filas+1) => '"$" #,##0.00_-',
                        "O2:O".($contador_filas+1) => '"$" #,##0.00_-',
                        "P2:P".($contador_filas+1) => '#,##0.00_- "%"',
                        "Q2:Q".($contador_filas+1) => '"$" #,##0.00_-',
                        "R2:R".($contador_filas+1) => '"$" #,##0.00_-',
                        "S2:S".($contador_filas+1) => '#,##0.00_- "%"'
                    ));

                    $sheet->setBorder("A1:S$contador_filas", 'thin'); 
                    $sheet->setBorder("C".($contador_filas+1).":S".($contador_filas+1), 'thin');  
                    $sheet->row(($contador_filas+1), function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                        $row->setFontSize(14);
                    });
                    
                }

                $sheet->freezePane('A4');
            });
         })->export('xls');
    }

    private function getItemsQuery($parametros){     
        if (!isset($parametros['agrupado_por']) ||  ($parametros['agrupado_por'] != "UM" && $parametros['agrupado_por'] != "P" && $parametros['agrupado_por'] != "NA")) {         
            return [];
        }  

        $variables = array();

        $query = "SELECT ";
        if($parametros['agrupado_por'] == "UM"){
            $query .= "unidad_medica_presupuesto.clues,  unidades_medicas.tipo,  unidades_medicas.nombre, ";
        }

        if($parametros['agrupado_por'] == "P"){
            $query .= "proveedores.nombre, COUNT(DISTINCT unidad_medica_presupuesto.clues) as cantidad_clues, ";
        }

        if($parametros['agrupado_por'] == "NA"){
            $query .= "nivel_atencion.nivel_atencion, COUNT(DISTINCT unidad_medica_presupuesto.clues) as cantidad_clues, ";
        }

        $subquery_pedidos_fechas = "";

        if( isset($parametros['mes_inicio']) && isset($parametros['anio_inicio']) &&
        isset($parametros['mes_fin']) && isset($parametros['anio_fin'])){
            $subquery_pedidos_fechas =" AND CAST(DATE_FORMAT(pedidos.fecha ,'%Y-%m-01') as DATE) BETWEEN STR_TO_DATE(CONCAT(:pedidos_anio_inicio,'-',LPAD(:pedidos_mes_inicio,2,'00'),'-01'),'%Y-%m-%d') AND STR_TO_DATE(CONCAT(:pedidos_anio_fin,'-',LPAD(:pedidos_mes_fin,2,'00'),'-01'),'%Y-%m-%d')";
            $variables['pedidos_mes_inicio']  = $parametros['mes_inicio'];  
            $variables['pedidos_anio_inicio']  = $parametros['anio_inicio']; 
            $variables['pedidos_mes_fin']  = $parametros['mes_fin'];  
            $variables['pedidos_anio_fin']  = $parametros['anio_fin'];  
        }else if(isset($parametros['mes_inicio']) && isset($parametros['anio_inicio']) && (!isset($parametros['mes_fin']) || !isset($parametros['anio_fin']))){
            $subquery_pedidos_fechas = " AND CAST(DATE_FORMAT(pedidos.fecha ,'%Y-%m-01') as DATE) >= STR_TO_DATE(CONCAT(:pedidos_anio_inicio,'-',LPAD(:pedidos_mes_inicio,2,'00'),'-01'),'%Y-%m-%d') ";
            $variables['pedidos_mes_inicio']  = $parametros['mes_inicio'];  
            $variables['pedidos_anio_inicio']  = $parametros['anio_inicio']; 
        } else if((!isset($parametros['mes_inicio']) || !isset($parametros['anio_inicio'])) && isset($parametros['mes_fin']) && isset($parametros['anio_fin'])){
            $subquery_pedidos_fechas = " AND CAST(DATE_FORMAT(pedidos.fecha ,'%Y-%m-01') as DATE) <= STR_TO_DATE(CONCAT(:pedidos_anio_fin,'-',LPAD(:pedidos_mes_fin,2,'00'),'-01'),'%Y-%m-%d') ";
            $variables['pedidos_mes_fin']  = $parametros['mes_fin'];  
            $variables['pedidos_anio_fin']  = $parametros['anio_fin'];  
        }

        $query .= " 
		SUM(causes_modificado) as causes_modificado,
		SUM(no_causes_modificado) as no_causes_modificado,
		SUM(material_curacion_modificado) as material_curacion_modificado,
		SUM(causes_modificado + no_causes_modificado + material_curacion_modificado) as modificado,
		
		SUM(causes_comprometido) as causes_comprometido,
		SUM(no_causes_comprometido) as no_causes_comprometido,
		SUM(material_curacion_comprometido) as material_curacion_comprometido,
		SUM(causes_comprometido + no_causes_comprometido + material_curacion_comprometido) as comprometido,
		
		SUM(causes_devengado) as causes_devengado,
		SUM(no_causes_devengado) as no_causes_devengado,
		SUM(material_curacion_devengado) as material_curacion_devengado,
		SUM(causes_devengado + no_causes_devengado + material_curacion_devengado) as devengado,
		
		SUM(causes_disponible) as causes_disponible,
		SUM(no_causes_disponible) as no_causes_disponible,
		SUM(material_curacion_disponible) as material_curacion_disponible,
        SUM(causes_disponible + no_causes_disponible + material_curacion_disponible) as disponible,
        
        IFNULL((pedidos_causes.monto_recibido + pedidos_mat_cur.monto_recibido ),0) as causes_mat_cur_monto_recibido,
        IFNULL((pedidos_causes.monto_solicitado + pedidos_mat_cur.monto_solicitado),0) as causes_mat_cur_monto_solicitado,
        IFNULL(((pedidos_causes.monto_recibido + pedidos_mat_cur.monto_recibido ) * 100 / (pedidos_causes.monto_solicitado + pedidos_mat_cur.monto_solicitado)),0) as causes_mat_cur_porcentaje_monto,
        
        IFNULL((pedidos_causes.cantidad_recibida + pedidos_mat_cur.cantidad_recibida),0) as causes_mat_cur_cantidad_recibida,
        IFNULL((pedidos_causes.cantidad_solicitada + pedidos_mat_cur.cantidad_solicitada),0) as causes_mat_cur_cantidad_solicitada,
        IFNULL(((pedidos_causes.cantidad_recibida + pedidos_mat_cur.cantidad_recibida ) * 100 / (pedidos_causes.cantidad_solicitada + pedidos_mat_cur.cantidad_solicitada)),0) as causes_mat_cur_porcentaje_cantidad,


        IFNULL(pedidos_no_causes.monto_recibido,0)  as no_causes_monto_recibido,
        IFNULL(pedidos_no_causes.monto_solicitado,0) as no_causes_monto_solicitado,
        IFNULL(pedidos_no_causes.porcentaje_monto,0) as no_causes_porcentaje_monto,        
        
        IFNULL(pedidos_no_causes.cantidad_recibida,0) as no_causes_cantidad_recibida,
        IFNULL(pedidos_no_causes.cantidad_solicitada,0) as no_causes_cantidad_solicitada,
        IFNULL(pedidos_no_causes.porcentaje_cantidad,0) as no_causes_porcentaje_cantidad
	
        FROM unidad_medica_presupuesto  
        
        LEFT JOIN unidades_medicas ON unidades_medicas.clues = unidad_medica_presupuesto.clues 
        LEFT JOIN proveedores ON proveedores.id = unidad_medica_presupuesto.proveedor_id
        LEFT JOIN (
            SELECT tipo, if(tipo = 'HO' OR tipo = 'HBC', '2','1') as nivel_atencion from unidades_medicas WHERE activa = 1 group by tipo
        ) AS nivel_atencion ON nivel_atencion.tipo = unidades_medicas.tipo
        
        LEFT JOIN (
            SELECT
            pedidos.clues,
            SUM(IFNULL(pedidos_insumos.monto_solicitado,0)) as monto_solicitado,
            SUM(IFNULL(pedidos_insumos.monto_recibido,0)) as monto_recibido,            
            SUM(IFNULL(pedidos_insumos.cantidad_solicitada,0)) as cantidad_solicitada,
            SUM(IFNULL(pedidos_insumos.cantidad_recibida,0)) as cantidad_recibida,
            CAST(DATE_FORMAT(pedidos.fecha ,'%Y-%m-01') as DATE) as fecha
            FROM pedidos_insumos
            LEFT JOIN pedidos ON pedidos.id = pedidos_insumos.pedido_id
            LEFT JOIN insumos_medicos ON pedidos_insumos.insumo_medico_clave = insumos_medicos.clave 
            
            WHERE insumos_medicos.tipo = 'ME' AND insumos_medicos.es_causes = 1 
            AND pedidos.status != 'BR'
            
            ".$subquery_pedidos_fechas."


            GROUP BY pedidos.clues
        ) AS pedidos_causes ON unidades_medicas.clues =  pedidos_causes.clues 


        LEFT JOIN (
            SELECT
            pedidos.clues,
            SUM(IFNULL(pedidos_insumos.monto_solicitado,0)) as monto_solicitado,
            SUM(IFNULL(pedidos_insumos.monto_recibido,0)) as monto_recibido,
            (SUM(IFNULL(pedidos_insumos.monto_recibido,0)) * 100 / SUM(IFNULL(pedidos_insumos.monto_solicitado,0))) as porcentaje_monto,
            SUM(IFNULL(pedidos_insumos.cantidad_solicitada,0)) as cantidad_solicitada,
            SUM(IFNULL(pedidos_insumos.cantidad_recibida,0)) as cantidad_recibida,
            (SUM( IFNULL(pedidos_insumos.cantidad_recibida,0)) * 100 / SUM(IFNULL(pedidos_insumos.cantidad_solicitada,0))) as porcentaje_cantidad,
            CAST(DATE_FORMAT(pedidos.fecha ,'%Y-%m-01') as DATE) as fecha
            FROM pedidos_insumos
            LEFT JOIN pedidos ON pedidos.id = pedidos_insumos.pedido_id
            LEFT JOIN insumos_medicos ON pedidos_insumos.insumo_medico_clave = insumos_medicos.clave 
            
            WHERE insumos_medicos.tipo = 'ME' AND insumos_medicos.es_causes = 0 
            AND pedidos.status != 'BR'
            
            ".$subquery_pedidos_fechas."


            GROUP BY pedidos.clues
        ) AS pedidos_no_causes ON unidades_medicas.clues =  pedidos_no_causes.clues 
        
        
        
        LEFT JOIN (
            SELECT
            pedidos.clues,
            SUM(IFNULL(pedidos_insumos.monto_solicitado,0)) * 1.16 as monto_solicitado,
            SUM(IFNULL(pedidos_insumos.monto_recibido,0)) * 1.16 as monto_recibido,            
            SUM(IFNULL(pedidos_insumos.cantidad_solicitada,0)) as cantidad_solicitada,
            SUM(IFNULL(pedidos_insumos.cantidad_recibida,0)) as cantidad_recibida,
            CAST(DATE_FORMAT(pedidos.fecha ,'%Y-%m-01') as DATE) as fecha
            FROM pedidos_insumos
            LEFT JOIN pedidos ON pedidos.id = pedidos_insumos.pedido_id
            LEFT JOIN insumos_medicos ON pedidos_insumos.insumo_medico_clave = insumos_medicos.clave 
            
            WHERE insumos_medicos.tipo = 'MC' 
            AND pedidos.status != 'BR'
            
            ".$subquery_pedidos_fechas."


            GROUP BY pedidos.clues
        ) AS pedidos_mat_cur ON unidades_medicas.clues =  pedidos_mat_cur.clues 

        WHERE unidades_medicas.activa = 1
        ";

       

        $where_insertado = true;
        if (isset($parametros['clues']) &&  $parametros['clues'] != "") {            
            $query .= " AND unidades_medicas.clues LIKE :clues";
            $where_insertado = true;
            $variables['clues']  = '%'.$parametros['clues'].'%';
        }

        if(isset($parametros['proveedores']) && $parametros['proveedores'] != ""){
            $proveedores = explode(',',$parametros['proveedores']);            
            if(count($proveedores)>0){
                if(!$where_insertado) {
                    $query .= " WHERE ";
                    $where_insertado = true;
                } else {
                    $query .= " AND ";
                }

                $contador = 0;
                $vars_items = "";
                foreach( $proveedores as $item){
                    if($contador>0){
                        $vars_items .= " , ";
                    }
                    $vars_items .= " :proveedor_".$item;
                    $variables[":proveedor_".$item] = $item;
                    $contador++;
                }

                $query .= " unidad_medica_presupuesto.proveedor_id IN ( ".$vars_items." )";              
            }              
        }

        if(isset($parametros['jurisdicciones']) && $parametros['jurisdicciones'] != ""){
            $jurisdicciones = explode(',',$parametros['jurisdicciones']);            
            if(count($jurisdicciones)>0){
                if(!$where_insertado) {
                    $query .= " WHERE ";
                    $where_insertado = true;
                } else {
                    $query .= " AND ";
                }

                $contador = 0;
                $vars_js = "";
                foreach( $jurisdicciones as $js){
                    if($contador>0){
                        $vars_js .= " , ";
                    }
                    $vars_js .= " :js_".$js;
                    $variables[":js_".$js] = $js;
                    $contador++;
                }
                $query .= " unidades_medicas.jurisdiccion_id IN ( ".$vars_js." )";            
            }              
        }

        if(isset($parametros['tipos_unidad']) && $parametros['tipos_unidad'] != ""){
            $tipos_unidad = explode(',',$parametros['tipos_unidad']);            
            if(count($tipos_unidad)>0){
                if(!$where_insertado) {
                    $query .= " WHERE ";
                    $where_insertado = true;
                } else {
                    $query .= " AND ";
                }


                $contador = 0;
                $vars_items = "";
                foreach( $tipos_unidad as $item){
                    if($contador>0){
                        $vars_items .= " , ";
                    }
                    $vars_items .= " :tipo_unidad_".$item;
                    $variables[":tipo_unidad_".$item] = $item;
                    $contador++;
                }

                $query .= " unidades_medicas.tipo IN ( ".$vars_items." )";           
            }              
        }
        if( isset($parametros['mes_inicio']) && isset($parametros['anio_inicio']) &&
            isset($parametros['mes_fin']) && isset($parametros['anio_fin'])){
            
            if(!$where_insertado) {
                $query .= " WHERE ";
                $where_insertado = true;
            } else {
                $query .= " AND ";
            }

            $query .= " STR_TO_DATE(CONCAT(unidad_medica_presupuesto.anio,'-',LPAD(unidad_medica_presupuesto.mes,2,'00'),'-01'),'%Y-%m-%d') BETWEEN STR_TO_DATE(CONCAT(:anio_inicio,'-',LPAD(:mes_inicio,2,'00'),'-01'),'%Y-%m-%d') AND STR_TO_DATE(CONCAT(:anio_fin,'-',LPAD(:mes_fin,2,'00'),'-01'),'%Y-%m-%d')";
           // $query .= " AND STR_TO_DATE(CONCAT(anio,'-',LPAD(mes,2,'00'),'-01'),'%Y-%m-%d') =  pedidos.fecha ";
            $variables['mes_inicio']  = $parametros['mes_inicio'];  
            $variables['anio_inicio']  = $parametros['anio_inicio']; 
            $variables['mes_fin']  = $parametros['mes_fin'];  
            $variables['anio_fin']  = $parametros['anio_fin'];  
        } else if(isset($parametros['mes_inicio']) && isset($parametros['anio_inicio']) && (!isset($parametros['mes_fin']) || !isset($parametros['anio_fin']))){
            if(!$where_insertado) {
                $query .= " WHERE ";
                $where_insertado = true;
            } else {
                $query .= " AND ";
            }

            $query .= " STR_TO_DATE(CONCAT(unidad_medica_presupuesto.anio,'-',LPAD(unidad_medica_presupuesto.mes,2,'00'),'-01'),'%Y-%m-%d') >= STR_TO_DATE(CONCAT(:anio_inicio,'-',LPAD(:mes_inicio,2,'00'),'-01'),'%Y-%m-%d') ";
           // $query .= " AND STR_TO_DATE(CONCAT(anio,'-',LPAD(mes,2,'00'),'-01'),'%Y-%m-%d') =  pedidos.fecha ";
            $variables['mes_inicio']  = $parametros['mes_inicio'];  
            $variables['anio_inicio']  = $parametros['anio_inicio']; 
        } else if((!isset($parametros['mes_inicio']) || !isset($parametros['anio_inicio'])) && isset($parametros['mes_fin']) && isset($parametros['anio_fin'])){
            if(!$where_insertado) {
                $query .= " WHERE ";
                $where_insertado = true;
            } else {
                $query .= " AND ";
            }

            $query .= " STR_TO_DATE(CONCAT(unidad_medica_presupuesto.anio,'-',LPAD(unidad_medica_presupuesto.mes,2,'00'),'-01'),'%Y-%m-%d') <= STR_TO_DATE(CONCAT(:anio_fin,'-',LPAD(:mes_fin,2,'00'),'-01'),'%Y-%m-%d') ";
            //$query .= " AND STR_TO_DATE(CONCAT(anio,'-',LPAD(mes,2,'00'),'-01'),'%Y-%m-%d') =  pedidos.fecha ";
            $variables['mes_fin']  = $parametros['mes_fin'];  
            $variables['anio_fin']  = $parametros['anio_fin']; 
        }
        
        if($parametros['agrupado_por'] == "UM" ){
            $query .= " GROUP BY unidad_medica_presupuesto.clues ORDER BY unidad_medica_presupuesto.clues";
        }

        if($parametros['agrupado_por'] == "NA"){
            $query .= " GROUP BY nivel_atencion  ORDER BY nivel_atencion";
        }
        if($parametros['agrupado_por'] == "P"){
            $query .= " GROUP BY proveedor_id ORDER BY proveedor_id";
        }
        
        //DB::enableQueryLog();
		$items = DB::select(DB::raw($query),$variables);
        //dd(DB::getQueryLog());
        //return [];
        return $items;
    }
    
}
