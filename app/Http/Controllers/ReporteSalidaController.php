<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

use App\Models\Turno,
    App\Models\Servicio,
    App\Models\UnidadMedica;

use \Excel;


class ReporteSalidaController extends Controller
{
    public function index(Request $request)
    {

       

        $parametros = Input::only('desde','hasta','clues', 'orden');
        $clues = "";
        $reporte_salida = DB::table("reporte_salidas")
                            ->join('insumos_medicos', 'insumos_medicos.clave', '=', 'reporte_salidas.clave')
                            ->join('unidades_medicas', 'unidades_medicas.clues', '=', 'reporte_salidas.clues')
                            ->where("anio", "=", "2018")
                            ->where("reporte_salidas.surtido", ">", 0)
                            ->where("reporte_salidas.negado", ">", 0)
                            ->whereBetween("mes", [$parametros['desde'],$parametros['hasta']])
                            ->groupBy("reporte_salidas.clave")
                            ->groupBy("reporte_salidas.clues")
                            ->limit(20);

        $reporte_salida_turno = DB::table("reporte_salidas")
                            ->join('insumos_medicos', 'insumos_medicos.clave', '=', 'reporte_salidas.clave')
                            ->join('unidades_medicas', 'unidades_medicas.clues', '=', 'reporte_salidas.clues')
                            ->where("anio", "=", "2018")
                            ->where("reporte_salidas.surtido", ">", 0)
                            ->where("reporte_salidas.negado", ">", 0)
                            ->whereBetween("mes", [$parametros['desde'],$parametros['hasta']])
                            ->groupBy("reporte_salidas.clave")
                            ->groupBy("reporte_salidas.clues")
                            ->limit(20);

        $reporte_salida_servicio = DB::table("reporte_salidas")
                            ->join('insumos_medicos', 'insumos_medicos.clave', '=', 'reporte_salidas.clave')
                            ->join('unidades_medicas', 'unidades_medicas.clues', '=', 'reporte_salidas.clues')
                            ->where("anio", "=", "2018")
                            ->where("reporte_salidas.surtido", ">", 0)
                            ->where("reporte_salidas.negado", ">", 0)
                            ->whereBetween("mes", [$parametros['desde'],$parametros['hasta']])
                            ->groupBy("reporte_salidas.clave")
                            ->groupBy("reporte_salidas.clues")
                            ->limit(20);
        
        if($parametros['clues']!='')
        {
            $reporte_salida = $reporte_salida->where("reporte_salidas.clues", $parametros['clues']);
            $reporte_salida_turno = $reporte_salida_turno->where("reporte_salidas.clues", $parametros['clues']);
            $reporte_salida_servicio = $reporte_salida_servicio->where("reporte_salidas.clues", $parametros['clues']);
            $clues = DB::table("unidades_medicas")->where("clues", "=", $parametros['clues'])->first();
        }                    


        if($parametros['orden'] == 1)
        {
            $reporte_salida = $reporte_salida->select("reporte_salidas.clave",
                                                    "insumos_medicos.descripcion",
                                                    "unidades_medicas.nombre",
                                                    DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                                    DB::RAW("sum(reporte_salidas.negado) as negado"),
                                                    DB::RAW("sum(reporte_salidas.surtido) as cantidad"))
                                            ->orderBy("surtido", "desc");

            $reporte_salida_turno = $reporte_salida_turno->select("reporte_salidas.clave",
                                            "insumos_medicos.descripcion",
                                            "reporte_salidas.turno_id",
                                            "unidades_medicas.nombre",
                                            DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                            DB::RAW("sum(reporte_salidas.negado) as negado"),
                                            DB::RAW("sum(reporte_salidas.surtido) as cantidad"))
                                            ->groupBy("reporte_salidas.turno_id")
                                            ->orderBy("surtido", "desc");
            
            $reporte_salida_servicio = $reporte_salida_servicio->select("reporte_salidas.clave",
                                            "insumos_medicos.descripcion",
                                            "unidades_medicas.nombre",
                                            "reporte_salidas.servicio_id",
                                            DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                            DB::RAW("sum(reporte_salidas.negado) as negado"),
                                            DB::RAW("sum(reporte_salidas.surtido) as cantidad"))
                                            ->groupBy("reporte_salidas.servicio_id")
                                            ->orderBy("surtido", "desc");
        }else{
            $reporte_salida = $reporte_salida->select("reporte_salidas.clave",
                                                    "insumos_medicos.descripcion",
                                                    "unidades_medicas.nombre",
                                                    DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                                    DB::RAW("sum(reporte_salidas.negado) as negado"),
                                                    DB::RAW("sum(reporte_salidas.negado) as cantidad"))
                                            ->orderBy("negado", "desc");

            $reporte_salida_turno = $reporte_salida_turno->select("reporte_salidas.clave",
                                            "insumos_medicos.descripcion",
                                            "reporte_salidas.turno_id",
                                            "unidades_medicas.nombre",
                                            DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                            DB::RAW("sum(reporte_salidas.negado) as negado"),
                                            DB::RAW("sum(reporte_salidas.negado) as cantidad"))
                                            ->groupBy("reporte_salidas.turno_id")
                                            ->orderBy("negado", "desc");
            
            $reporte_salida_servicio = $reporte_salida_servicio->select("reporte_salidas.clave",
                                            "insumos_medicos.descripcion",
                                            "reporte_salidas.servicio_id",
                                            "unidades_medicas.nombre",
                                            DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                            DB::RAW("sum(reporte_salidas.negado) as negado"),
                                            DB::RAW("sum(reporte_salidas.negado) as cantidad"))
                                            ->groupBy("reporte_salidas.servicio_id")
                                            ->orderBy("negado", "desc");
        }
        $reporte_salida = $reporte_salida->get();
        $reporte_salida_turno = $reporte_salida_turno->get();
        $reporte_salida_servicio = $reporte_salida_servicio->get();


        return Response::json(array("data" => array("salidas"=>$reporte_salida, "turnos"=>$reporte_salida_turno, "servicios"=>$reporte_salida_servicio, "clues"=>$clues) ), 200);                                    
                                            
    }

    public function catalogos(Request $request)
    {
        $catalogo_turno = Turno::all();
        $catalogo_servicio = Servicio::all();
        $catalogo_clues = UnidadMedica::all();
        return Response::json(array("data" => array( "catalogo_turno"=>$catalogo_turno, "catalogo_servicio"=>$catalogo_servicio, "catalogo_clues"=>$catalogo_clues) ), 200); 
    }

    public function reporteExcel()
    {
    $parametros;
    $parametros = Input::only('desde','hasta','clues', 'orden');

    $reporte_salida;
    $unidades = "";
    $reporte_salida = DB::table("reporte_salidas")
                        ->join('insumos_medicos', 'insumos_medicos.clave', '=', 'reporte_salidas.clave')
                        ->join('unidades_medicas', 'unidades_medicas.clues', '=', 'reporte_salidas.clues')
                        ->where("anio", "=", "2018")
                        ->whereBetween("mes", [$parametros['desde'],$parametros['hasta']])
                        ->groupBy("reporte_salidas.clave")
                        ->groupBy("reporte_salidas.clues")
                        ->select("reporte_salidas.clave",
                                "insumos_medicos.descripcion",
                                "unidades_medicas.nombre",
                                DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                DB::RAW("sum(reporte_salidas.negado) as negado"),
                                DB::RAW("sum(reporte_salidas.surtido) as cantidad"))
                        ->orderBy("surtido", "desc");

    $reporte_salida_turno = DB::table("reporte_salidas")
                        ->join('insumos_medicos', 'insumos_medicos.clave', '=', 'reporte_salidas.clave')
                        ->join('unidades_medicas', 'unidades_medicas.clues', '=', 'reporte_salidas.clues')
                        ->leftjoin('turnos', 'turnos.id', '=', 'reporte_salidas.turno_id')
                        ->where("anio", "=", "2018")
                        ->whereBetween("mes", [$parametros['desde'],$parametros['hasta']])
                        ->groupBy("reporte_salidas.clave")
                        ->groupBy("reporte_salidas.clues")
                        ->select("reporte_salidas.clave",
                                            "insumos_medicos.descripcion",
                                            "reporte_salidas.turno_id",
                                            "unidades_medicas.nombre",
                                            DB::RAW("turnos.nombre as turno_nombre"),
                                            DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                            DB::RAW("sum(reporte_salidas.negado) as negado"),
                                            DB::RAW("sum(reporte_salidas.surtido) as cantidad"))
                                            ->groupBy("reporte_salidas.turno_id")
                                            ->orderBy("surtido", "desc");      
                                            
    $reporte_salida_servicio = "";
    $reporte_salida_servicio = DB::table("reporte_salidas")
                        ->join('insumos_medicos', 'insumos_medicos.clave', '=', 'reporte_salidas.clave')
                        ->join('unidades_medicas', 'unidades_medicas.clues', '=', 'reporte_salidas.clues')
                        ->leftjoin('servicios', 'servicios.id', '=', 'reporte_salidas.servicio_id')
                        ->where("anio", "=", "2018")
                        ->whereBetween("mes", [$parametros['desde'],$parametros['hasta']])
                        ->groupBy("reporte_salidas.clave")
                        ->groupBy("reporte_salidas.clues")
                        ->select("reporte_salidas.clave",
                                            "insumos_medicos.descripcion",
                                            "reporte_salidas.turno_id",
                                            "unidades_medicas.nombre",
                                            DB::RAW("servicios.nombre as servicio_nombre"),
                                            DB::RAW("sum(reporte_salidas.surtido) as surtido"),
                                            DB::RAW("sum(reporte_salidas.negado) as negado"),
                                            DB::RAW("sum(reporte_salidas.surtido) as cantidad"))
                                            ->groupBy("reporte_salidas.servicio_id")
                                            ->orderBy("surtido", "desc");  

    if($parametros['clues']!='')
    {
        $unidades = UnidadMedica::where("clues", "=", $parametros['clues'])->first(); 
        if($unidades)
        {
            $reporte_salida = $reporte_salida->where("reporte_salidas.clues", "=", $parametros['clues']);  
            $reporte_salida_turno = $reporte_salida_turno->where("reporte_salidas.clues", "=", $parametros['clues']);  
            $reporte_salida_servicio = $reporte_salida_servicio->where("reporte_salidas.clues", "=", $parametros['clues']);  
        }
    }
    
    $reporte_salida = $reporte_salida->get();
    $reporte_salida_turno = $reporte_salida_turno->get();
    $reporte_salida_servicio = $reporte_salida_servicio->get();

    //return Response::json(array("data" => array("salidas"=>$reporte_salida) ), 200);    
    Excel::create("Reporte Salida Medicamentos y Mat. de Curacion ", function($excel) use($reporte_salida, $reporte_salida_turno, $reporte_salida_servicio, $parametros, $unidades) {

        $excel->sheet('Salida de Medicamentos', function($sheet) use($reporte_salida, $parametros, $unidades) {
            $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $periodo = "";
            if($parametros['desde'] == $parametros['hasta'])
                $periodo = $meses[$parametros['desde']-1];
            else    
                $periodo = " de ".$meses[$parametros['desde']-1]." a ".$meses[$parametros['hasta']-1]." del ".date("Y");

            if($unidades)
                $unidad_medica = $parametros['clues']." - ".$unidades->nombre;  
            else
                $unidad_medica =  "Todos";  
            $sheet->setAutoSize(true);
            
            $sheet->mergeCells('A1:E1');
            $sheet->mergeCells('B2:E2');
            $sheet->mergeCells('B3:E3');

            $sheet->row(1, array(
                'Reporte de Salida de Medicamentos y Material de Curación'
            ));
            $sheet->row(2, array(
                'Periodo de: ',$periodo
            ));
            $sheet->row(3, array(
                'Unidad Medica', $unidad_medica
            ));
            $sheet->row(4, array(
                'CLAVE', 'INSUMO', 'UNIDAD', 'SURTIDO', 'NEGADO'
            ));
            
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
            $sheet->row(4, function($row) {
                $row->setBackground('#DDDDDD');
                $row->setFontWeight('bold');
                $row->setFontSize(14);
            });
            
            $contador_filas = 5;
            foreach($reporte_salida as $item){
                $contador_filas++;					

                $sheet->appendRow(array(
                    $item->clave,
                    $item->descripcion,
                    $item->nombre,
                    $item->surtido,
                    $item->negado                    
                )); 
            }
            
            $sheet->appendRow(array(
                '',
                '',
                'TOTAL',
                "=SUM(D5:D".($contador_filas-1).")",
                "=SUM(E5:E".($contador_filas-1).")"
                
            ));

            $sheet->setBorder("A1:E$contador_filas", 'thin');


        });
        $excel->sheet('Turnos', function($sheet) use($reporte_salida_turno, $parametros, $unidades) {
            $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $periodo = "";
            if($parametros['desde'] == $parametros['hasta'])
                $periodo = $meses[$parametros['desde']-1];
            else    
                $periodo = " de ".$meses[$parametros['desde']-1]." a ".$meses[$parametros['hasta']-1]." del ".date("Y");

            if($unidades)
                $unidad_medica = $parametros['clues']." - ".$unidades->nombre;  
            else
                $unidad_medica =  "Todos";  
            $sheet->setAutoSize(true);
            
            $sheet->mergeCells('A1:F1');
            $sheet->mergeCells('B2:F2');
            $sheet->mergeCells('B3:F3');

            $sheet->row(1, array(
                'Reporte de Salida de Medicamentos y Material de Curación por Turnos'
            ));
            $sheet->row(2, array(
                'Periodo de: ',$periodo
            ));
            $sheet->row(3, array(
                'Unidad Medica', $unidad_medica
            ));
            $sheet->row(4, array(
                'CLAVE', 'INSUMO', 'UNIDAD', 'TURNO', 'SURTIDO', 'NEGADO'
            ));
            
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
            $sheet->row(4, function($row) {
                $row->setBackground('#DDDDDD');
                $row->setFontWeight('bold');
                $row->setFontSize(14);
            });
            
            $contador_filas = 5;
            foreach($reporte_salida_turno as $item){
                $contador_filas++;					

                $sheet->appendRow(array(
                    $item->clave,
                    $item->descripcion,
                    $item->nombre,
                    $item->turno_nombre,
                    $item->surtido,
                    $item->negado                    
                )); 
            }
            
            $sheet->appendRow(array(
                '',
                '',
                '',
                'TOTAL',
                "=SUM(E5:E".($contador_filas-1).")",
                "=SUM(F5:F".($contador_filas-1).")"
                
            ));

            $sheet->setBorder("A1:F$contador_filas", 'thin');


        });
        $excel->sheet('Servicios', function($sheet) use($reporte_salida_servicio, $parametros, $unidades) {
            $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $periodo = "";
            if($parametros['desde'] == $parametros['hasta'])
                $periodo = $meses[$parametros['desde']-1];
            else    
                $periodo = " de ".$meses[$parametros['desde']-1]." a ".$meses[$parametros['hasta']-1]." del ".date("Y");

            if($unidades)
                $unidad_medica = $parametros['clues']." - ".$unidades->nombre;  
            else
                $unidad_medica =  "Todos";  
            $sheet->setAutoSize(true);
            
            $sheet->mergeCells('A1:F1');
            $sheet->mergeCells('B2:F2');
            $sheet->mergeCells('B3:F3');

            $sheet->row(1, array(
                'Reporte de Salida de Medicamentos y Material de Curación por Servicios'
            ));
            $sheet->row(2, array(
                'Periodo de: ',$periodo
            ));
            $sheet->row(3, array(
                'Unidad Medica', $unidad_medica
            ));
            $sheet->row(4, array(
                'CLAVE', 'INSUMO', 'UNIDAD', 'SERVICIO', 'SURTIDO', 'NEGADO'
            ));
            
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
            $sheet->row(4, function($row) {
                $row->setBackground('#DDDDDD');
                $row->setFontWeight('bold');
                $row->setFontSize(14);
            });
            
            $contador_filas = 5;
            foreach($reporte_salida_servicio as $item){
                $contador_filas++;					

                $sheet->appendRow(array(
                    $item->clave,
                    $item->descripcion,
                    $item->nombre,
                    $item->servicio_nombre,
                    $item->surtido,
                    $item->negado                    
                )); 
            }
            
            $sheet->appendRow(array(
                '',
                '',
                '',
                'TOTAL',
                "=SUM(E5:E".($contador_filas-1).")",
                "=SUM(F5:F".($contador_filas-1).")"
                
            ));

            $sheet->setBorder("A1:F$contador_filas", 'thin');


        });
        })->export('xls');
    }
}
