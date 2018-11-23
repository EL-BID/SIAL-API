<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\PresupuestoEjercicio, App\Models\PresupuestoUnidadMedica,  App\Models\UnidadMedica, App\Models\Jurisdiccion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class PresupuestoController extends Controller
{
    public function ejercicios(){
        $items = PresupuestoEjercicio::orderBy('ejercicio','desc')->get();
        return Response::json([ 'data' => $items],200);
    }

    public function tiposUnidadMedica(){
        $items = UnidadMedica::select("tipo")->groupBy('tipo')->whereNotNull("tipo")->where("tipo","!=","")->orderBy('tipo', 'asc')->get();
        return Response::json([ 'data' => $items],200);
    }

    public function jurisdicciones(){
        $items = Jurisdiccion::all();
        return Response::json([ 'data' => $items],200);
    }


    public function presupuestoUnidadesMedicas(Request $request, $id){
        $items = PresupuestoUnidadMedica::where('presupuesto_id',$id)->with('unidadMedica')->orderBy('clues','asc')->get();
        return Response::json([ 'data' => $items],200);

    }

    
    public function ultimoPresupuesto(Request $request){
        $presupuesto = PresupuestoEjercicio::orderBy('ejercicio','desc')->first();
        if($presupuesto){
            $presupuesto_unidades_medicas = $presupuesto->presupuestoUnidadesMedicas;
            foreach($presupuesto_unidades_medicas as $item){
                $item->unidadMedica;
                $item->causes = $item->causes_modificado;
                $item->no_causes = $item->no_causes_modificado;
            }
        } else {
            $ums = UnidadMedica::where('activa',1)->get();
            $unidades_medicas = [];
            foreach($ums as $item){
                $unidades_medicas[] = [
                    "clues" => $item->clues,
                    "unidad_medica" => $item,
                    "causes" => null,
                    "no_causes" => null
                ];
            }
            $presupuesto["ejercicio"] = null;
            $presupuesto["causes"] = null;
            $presupuesto["no_causes"] = null;
            $presupuesto["factor_meses"] = null;
            $presupuesto["presupuesto_unidades_medicas"] = $unidades_medicas;
        }
        
        return Response::json([ 'data' => $presupuesto],200);
    }

    public function unidadesMedicas(Request $request){
        $input = Input::only("clues", "tipos_unidad_medica","jurisdicciones");

        $items = UnidadMedica::where(function($query) use ($input) {
            $query->where('clues','LIKE',"%".$input['clues']."%")->orWhere("nombre",'like','%'.$input['clues'].'%');
        });

        if($input['jurisdicciones'] != ""){
            $jurisdicciones = explode(',',$input['jurisdicciones']);  
        
            if(count($jurisdicciones)>0){
                $items = $items->whereIn('jurisdiccion_id',$jurisdicciones);
            }
        }
        

        if($input['tipos_unidad_medica'] != "" ){
            $tipos = explode(',',$input['tipos_unidad_medica']);  
            $items = $items->whereIn('tipo',$tipos);
        }
        
        $items = $items->with(["jurisdiccion"])->get();

        return Response::json([ 'data' => $items],200);

    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $mensajes = [            
            'required'      => "required",
            'numeric'       => "numeric",
            'integer'       => "integer",
            'unique'        => "unique",
            'min'           => "min"
        ];

        $reglas = [
            //'id'            => 'required|unique:usuarios,id,'.$id,
            'ejercicio'        => 'required|integer|unique:presupuesto_ejercicio,ejercicio',
            'causes'           => 'required|numeric|min:0',
            'no_causes'        => 'required|numeric|min:0',
            'factor_meses'     => 'required|integer',
            'presupuesto_unidades_medicas' => 'array',
            'presupuesto_unidades_medicas.*.causes' => 'required|numeric|min:0',
            'presupuesto_unidades_medicas.*.no_causes' => 'required|numeric|min:0'
        ];

        $inputs = Input::only('ejercicio','causes',"no_causes","factor_meses","presupuesto_unidades_medicas");

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            $errors =  $v->errors();           
            return Response::json(['error' =>$errors], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try{

            $presupuesto = PresupuestoEjercicio::create($inputs);
            $items = [];

            foreach($inputs['presupuesto_unidades_medicas'] as $item){
                
                $items[] = new PresupuestoUnidadMedica([
                    "presupuesto_id" => $presupuesto->id, 
                    "clues" =>$item["clues"],
                    "causes_autorizado" => $item["causes"],
                    "causes_modificado" => $item["causes"],
                    "causes_disponible" => $item["causes"],
                    "causes_comprometido" => 0,
                    "causes_devengado" => 0,
                    "no_causes_autorizado" => $item["no_causes"],
                    "no_causes_modificado" => $item["no_causes"],
                    "no_causes_disponible" => $item["no_causes"],
                    "no_causes_comprometido" => 0,
                    "no_causes_devengado" => 0,
                ]);
            }

            $presupuesto->presupuestoUnidadesMedicas()->saveMany($items);
            //DB::rollback();
            DB::commit();
            return Response::json([ 'data' => $presupuesto],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function cargarExcel(Request $request){
        ini_set('memory_limit', '-1');

        try{
            if ($request->hasFile('archivo')){
				$file = $request->file('archivo');

				if ($file->isValid()) {
                    $path = $file->getRealPath();

                    $unidades_medicas = [];
                    $total_causes = 0;
                    $total_no_causes = 0;
                    $con_errores = false;
                    Excel::load($file, function($reader) use (&$unidades_medicas, &$total_causes, &$total_no_causes, &$con_errores) {
                        $objExcel = $reader->getExcel();
                        $sheet = $objExcel->getSheet(0);
                        $highestRow = $sheet->getHighestRow();
                        $highestColumn = $sheet->getHighestColumn();
        
                        //  Loop through each row of the worksheet in turn
                        DB::beginTransaction();
                       
                        for ($row = 2; $row <= $highestRow; $row++)
                        {
                            //  Read a row of data into an array
                            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                                NULL, TRUE, FALSE);
                            $data =  $rowData[0];
                            


                            /*
                                0 'CLUES',
                                1 'TIPO',
                                2 'NOMBRE',
                                3 'JURISDICCION'
                                4 'CAUSES'
                                5 'NO CAUSES'
                            */

                            $unidad_medica = UnidadMedica::find($data[0]);

                            if($unidad_medica){
                                $causes = floatval($data[4]);
                                $total_causes += $causes;

                                $no_causes = floatval($data[5]);
                                $total_no_causes += $no_causes;

                                $unidades_medicas[] = array(
                                    'clues'=>$data[0],
                                    'unidad_medica' => $unidad_medica,
                                    'causes' => $causes,
                                    'no_causes' => $no_causes
                                );
                            } else {
                                $fake_unidad_medica =  new UnidadMedica();
                                $fake_unidad_medica->clues = $data[0];
                                $fake_unidad_medica->tipo = $data[1];
                                $fake_unidad_medica->nombre = $data[2];
                                $fake_unidad_medica->jurisdiccion = null;
                                $unidades_medicas[] = array(
                                    'clues'=>$data[0],
                                    'unidad_medica' => $fake_unidad_medica,
                                    'causes' => floatval($data[4]),
                                    'no_causes' => floatval($data[5]),
                                    'error' => true
                                );
                                $con_errores = true;
                            }
                            
                        }
                        
                       

                        DB::rollback();
                    });

                    $presupuesto = array(
                        'causes' => $total_causes,
                        'no_causes' => $total_no_causes,
                        'presupuesto_unidades_medicas' => $unidades_medicas,
                        'con_errores' => $con_errores

                    );

					return Response::json([ 'data' => $presupuesto],200);

				} else {
					throw new \Exception("Archivo inválido.");
				}
			} else {
				throw new \Exception("No hay archivo.");
			}
        } catch(\Exception $e){
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    } 

    public function exportarExcel(Request $request, $id){
        $presupuesto = PresupuestoEjercicio::find($id);
        
        if(!$presupuesto){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        $presupuesto_unidades_medicas = PresupuestoUnidadMedica::where('presupuesto_id',$id)->with('unidadMedica')->orderBy('clues','asc')->get();
        
        Excel::create("Presupuesto ".$presupuesto->ejercicio." - Generado el ".date('Y-m-d'), function($excel) use($presupuesto, $presupuesto_unidades_medicas) {

            $excel->sheet("Presupuesto ".$presupuesto->ejercicio, function($sheet) use($presupuesto, $presupuesto_unidades_medicas) {
                $sheet->setAutoSize(true);
                
                $sheet->mergeCells('A1:B4');
                $sheet->mergeCells('C1:F1');
                $sheet->mergeCells('G1:H3');
                $sheet->mergeCells('I1:L1');
                $sheet->row(1, array('PRESUPUESTO '.$presupuesto->ejercicio,'','CAUSES','','','','','','NO CAUSES'));
                
                $sheet->mergeCells('C2:D2');
                $sheet->mergeCells('E2:F2');
                $sheet->mergeCells('I2:J2');
                $sheet->mergeCells('K2:L2');
                $sheet->row(2, array('','','Autorizado/Modificado','','Disponible','','','','Autorizado/Modificado','','Disponible'));

                $sheet->mergeCells('C3:D3');
                $sheet->mergeCells('E3:F3');
                $sheet->mergeCells('I3:J3');
                $sheet->mergeCells('K3:L3');
                
                //$sheet->mergeCells('A4:A5');
                //$sheet->mergeCells('B4:B5');
                $sheet->mergeCells('C4:D4');
                $sheet->mergeCells('E4:F4');
                $sheet->mergeCells('G4:H4');
                $sheet->mergeCells('I4:J4');
                $sheet->mergeCells('K4:L4');

                $sheet->row(4, array('','','Autorizado','','Modificado','','Comprometido','','Devengado','','Disponible'));
                $sheet->row(5, array('Clues','Nombre'   ,'CAUSES','NO CAUSES','CAUSES','NO CAUSES','CAUSES','NO CAUSES','CAUSES','NO CAUSES','CAUSES','NO CAUSES'));

                $sheet->freezePane('A6');

                $sheet->cells("A1:L5", function($cells) {
                    $cells->setAlignment('center');
                    $cells->setBackground('#DDDDDD');
                    $cells->setFontWeight('bold');
                });

                $sheet->getStyle('A1:L5')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $sheet->cells("A1:B4", function($cells) {
                    $cells->setFontSize(16);
                });

                $sheet->cells("C3:L3", function($cells) {
                    $cells->setBackground('#FFFFFF');
                });

                $contador_filas = 5;

                foreach($presupuesto_unidades_medicas as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->clues,
                        $item->unidadMedica->nombre,
                        $item->causes_autorizado,
                        $item->no_causes_autorizado,
                        $item->causes_modificado,
                        $item->no_causes_modificado,
                        $item->causes_comprometido,
                        $item->no_causes_comprometido,
                        $item->causes_devengado,
                        $item->no_causes_devengado,
                        $item->causes_disponible,
                        $item->no_causes_disponible
                    ));
                }               

                $last_row = $contador_filas;
                $contador_filas++;


                $sheet->getRowDimension($contador_filas)->setRowHeight(0.1);

                $contador_filas++;
                $subtotal_row = $contador_filas;
                $sheet->mergeCells("A$subtotal_row:B".($subtotal_row + 1));                
                $sheet->row($subtotal_row, array('TOTAL','',"=SUM(C6:C$last_row)","=SUM(D6:D$last_row)","=SUM(E6:E$last_row)","=SUM(F6:F$last_row)","=SUM(G6:G$last_row)","=SUM(H6:H$last_row)","=SUM(I6:I$last_row)","=SUM(J6:J$last_row)","=SUM(K6:K$last_row)","=SUM(L6:L$last_row)"));
                
                $contador_filas++;                
                $sheet->mergeCells("C$contador_filas:D$contador_filas");
                $sheet->mergeCells("E$contador_filas:F$contador_filas");
                $sheet->mergeCells("G$contador_filas:H$contador_filas");
                $sheet->mergeCells("I$contador_filas:J$contador_filas");
                $sheet->mergeCells("K$contador_filas:L$contador_filas");
                $sheet->row($contador_filas, array('','',"=SUM(C$subtotal_row:D$subtotal_row)",'',"=SUM(E$subtotal_row:F$subtotal_row)",'',"=SUM(G$subtotal_row:H$subtotal_row)",'',"=SUM(I$subtotal_row:J$subtotal_row)",'',"=SUM(K$subtotal_row:L$subtotal_row)"));


                $sheet->row(3, array('','',$presupuesto->causes,'',"=I$subtotal_row",'','','',$presupuesto->no_causes,'',"=K$subtotal_row"));

                $sheet->cells("A$subtotal_row:L$contador_filas", function($cells) {
                    $cells->setAlignment('center');
                    $cells->setBackground('#DDDDDD');
                    $cells->setFontWeight('bold');
                });

                $sheet->cells("C6:L$subtotal_row", function($cells) {
                    $cells->setAlignment('right');
                });

                $sheet->setBorder("A1:L$contador_filas", 'thin');

                $sheet->setColumnFormat(array(
                    "C6:L$contador_filas" => '"$" #,##0.00_-',
                    "C3:L3" => '"$" #,##0.00_-',
                ));

                $sheet->setAutoFilter("A5:L5");
            });           
           
           $excel->setActiveSheetIndex(0);

         })->export('xls');
    }
    

    public function descargarFormato(Request $request){

        $unidades_medicas = UnidadMedica::where('activa',1)->orderBy('jurisdiccion_id','asc','clues','asc')->get();


        
        Excel::create("Formato de carga de presupuesto de unidades", function($excel) use($unidades_medicas) {


            $excel->sheet('Presupuesto', function($sheet) use($unidades_medicas)  {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'Clues',
                    'Tipo',
                    'Nombre',
                    'Jurisdicción',
                    '$ CAUSES',
                    '$ NO CAUSES'
                ));
                $sheet->cells("A1:F1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                });
                foreach($unidades_medicas as $item){
                    $item->jurisdiccion;
                    $sheet->appendRow(array(
                        $item->clues,
                        $item->tipo,
                        $item->nombre,
                        $item->jurisdiccion? $item->jurisdiccion->numero." - ".$item->jurisdiccion->nombre :  "",
                        0.00,
                        0.00
                    )); 
                }
                $c = count($unidades_medicas);
                if($c > 0){
                    $c++;
                    $sheet->setColumnFormat(array(
                        "E2:F$c" => '"$" #,##0.00_-',
                    ));
                }
                
               

                $sheet->setAutoFilter('A1:F1');
            });
            


           


         })->export('xls');
    }
}