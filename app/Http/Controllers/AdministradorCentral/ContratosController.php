<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Contrato, App\Models\ContratoPrecio, App\Models\Insumo, App\Models\TipoInsumo, App\Models\Proveedor;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class ContratosController extends Controller
{

    public function proveedores(Request $request){
        return Response::json(['data' => Proveedor::all()],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   $parametros = Input::only('q','page','per_page');
        
        $items =  Contrato::select('contratos.*', 'proveedores.nombre_corto as proveedor')
                    ->leftJoin('proveedores','contratos.proveedor_id','=','proveedores.id');
        
        
        

        if ($parametros['q']) {
            
            $items = $items->where('proveedores.nombre','LIKE',"%".$parametros['q']."%")->orWhere('contratos.id','LIKE',"%".$parametros['q']."%");
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $object = Contrato::find($id);

        if(!$object){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

       /* $precios = $object->precios;
        foreach($precios as $precio){
            $precio->tipo;
            $precio->insumo;
        }*/
        $object = $object->load('precios','precios.insumo','precios.tipo');
        //$object =  $object->load("detalles.insumoConDescripcion.informacion","detalles.insumoConDescripcion.generico.grupos");

        return Response::json([ 'data' => $object ], HttpResponse::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $mensajes = [            
            'required'      => "required",
            'numeric'      => "numeric",
            'date'      => "date",
        ];

        $reglas = [
            //'id'            => 'required|unique:usuarios,id,'.$id,
            'proveedor_id'        => 'required',
            'monto_minimo'         => 'required|numeric',
            'monto_maximo'         => 'required|numeric',
            'fecha_inicio'        => 'required|date',
            'fecha_fin'        => 'required|date',
        ];

        $inputs = Input::only('proveedor_id','monto_minimo',"monto_maximo","fecha_inicio","fecha_fin","activo");

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            $errors =  $v->errors();           
            return Response::json(['error' =>$errors], HttpResponse::HTTP_CONFLICT);
        }
        $errors = [];
        if($inputs['monto_minimo']>$inputs['monto_maximo']){
            $errors["monto_minimo"] =  ["smaller_than"];           
        }

        if (strtotime($inputs["fecha_inicio"]) > strtotime($inputs["fecha_fin"])) {
            $errors["fecha_inicio"] =  ["smaller_than"];           
        }

        if(count($errors)>0){
            return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
            

            if(!isset($inputs["activo"])){
                $inputs["activo"] = false;
            }
            if($inputs["activo"] != false){
                Contrato::where('proveedor_id',$inputs["proveedor_id"])->update(['activo' => false]);            
            }
            $insumo = Contrato::create($inputs);           
            DB::commit();
            return Response::json([ 'data' => $insumo ],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $mensajes = [            
            'required'      => "required",
            'numeric'      => "numeric",
            'date'      => "date",
        ];

        $reglas = [
            //'id'            => 'required|unique:usuarios,id,'.$id,
            'proveedor_id'        => 'required',
            'monto_minimo'         => 'required|numeric',
            'monto_maximo'         => 'required|numeric',
            'fecha_inicio'        => 'required|date',
            'fecha_fin'        => 'required|date',
        ];

        $inputs = Input::only('proveedor_id','monto_minimo',"monto_maximo","fecha_inicio","fecha_fin","activo",'precios');


        $contrato = Contrato::find($id);

        if(!$contrato){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }


        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            $errors =  $v->errors();           
            return Response::json(['error' =>$errors], HttpResponse::HTTP_CONFLICT);
        }
        $errors = [];
        if($inputs['monto_minimo']>$inputs['monto_maximo']){
            $errors["monto_minimo"] =  ["smaller_than"];           
        }

        if (strtotime($inputs["fecha_inicio"]) > strtotime($inputs["fecha_fin"])) {
            $errors["fecha_inicio"] =  ["smaller_than"];           
        }

        if(count($errors)>0){
            return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
            

            if(!isset($inputs["activo"])){
                $inputs["activo"] = false;
            }
            if($inputs["activo"] != false){
                Contrato::where('proveedor_id',$inputs["proveedor_id"])->update(['activo' => false]);            
            }
            
            
            $contrato->activo = $inputs["activo"];
            $contrato->proveedor_id = $inputs["proveedor_id"];
            $contrato->monto_minimo = $inputs["monto_minimo"];
            $contrato->monto_maximo = $inputs["monto_maximo"];
            $contrato->fecha_inicio = $inputs["fecha_inicio"];
            $contrato->fecha_fin = $inputs["fecha_fin"];
            $contrato->save();

            $precios = $contrato->precios()->get();

            foreach($precios as $item){
                ContratoPrecio::destroy($item->id);
            }
           

            $items = [];
            foreach($inputs['precios'] as $item){
                
                $items[] = new ContratoPrecio([
                    "contrato_id" => $id, 
                    "tipo_insumo_id" => $item["tipo_insumo_id"],
                    "proveedor_id" => $contrato->proveedor_id,
                    "insumo_medico_clave" => $item["insumo_medico_clave"],
                    "precio" => $item["precio"]
                ]);
            }
            


            $contrato->precios()->saveMany($items);

            

            //DB::rollback();
            DB::commit();
            return Response::json([ 'data' => $contrato ],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            //$object = ClavesBasicas::destroy($id);
            //return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activar(Request $request, $id)
    {
        $item = Contrato::find($id);

        if(!$item){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();
        try {
           
            Contrato::where('proveedor_id',$item->proveedor_id)->update(['activo' => false]);
            $item->activo = true;
            $item->save();
            DB::commit();
            //DB::rollback();
            return Response::json([ 'data' => $item ],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    
    public function descargarFormato(Request $request){

        $tiposInsumo = TipoInsumo::all();
        $insumos = Insumo::all();

        
        Excel::create("Formato de carga de lista de precios para contratos de proveedores SIAL", function($excel) use($tiposInsumo, $insumos) {


            $excel->sheet('Precios', function($sheet)  {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'Clave (CLAVE Pestaña: REF INSUMOS)',
                    'Tipo (CLAVE Pestaña: REF TIPOS)',
                    'Precio'
                ));
                $sheet->cells("A1:C1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                });

                $sheet->appendRow(array(
                    "010.000.000.00 (EJEMPLO)",
                    7,
                    25.00
                )); 

                $sheet->setAutoFilter('A1:C1');
            });
            

            $excel->sheet('REF TIPOS', function($sheet) use($tiposInsumo) {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'CLAVE', 'NOMBRE'
                ));
                $sheet->cells("A1:B1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                   
                });

                $contador_filas = 1;
                foreach($tiposInsumo as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->id,
                        $item->clave." - ".$item->nombre
                    )); 
                }
                $sheet->cells("A1:A".$contador_filas, function($cells) {
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });
                $sheet->setBorder("A1:B$contador_filas", 'thin');
                $sheet->setAutoFilter('A1:B1');

                $sheet->getProtection()->setSheet(true);
            });

            $excel->sheet('REF INSUMOS', function($sheet) use($insumos) {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'CLAVE', 'TIPO (ME = Medicamentos, MC= Material de curación)','DESCRIPCION', 'CAUSES', 'DESCONTINUADO'
                ));
                $sheet->cells("A1:E1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                   
                });

                $contador_filas = 1;
                foreach($insumos as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->clave,                        
                        $item->tipo,
                        $item->descripcion,
                        $item->es_causes ? "SI" : "NO",
                        $item->descontinuado ? "SI" : "NO"
                    )); 
                }
                $sheet->cells("A1:A".$contador_filas, function($cells) {
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });
                $sheet->setBorder("A1:E$contador_filas", 'thin');
                $sheet->setAutoFilter('A1:E1');

                $sheet->getProtection()->setSheet(true);
            });
           
           $excel->setActiveSheetIndex(0);

           


         })->export('xls');
    }

    public function cargarExcel(Request $request){
        ini_set('memory_limit', '-1');

        try{
            if ($request->hasFile('archivo')){
				$file = $request->file('archivo');

				if ($file->isValid()) {
                    $path = $file->getRealPath();

                    $precios = [];
                    $contrato_id = $request->input("contrato_id");
                    $contrato = Contrato::find($contrato_id);

                    Excel::load($file, function($reader) use (&$precios, $contrato) {
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
                                0 'Clave',
                                1 'Tipo',
                                2 'Precio'
                            */
                            $precio = new ContratoPrecio();
                            $precio->contrato_id = $contrato->id;
                            $precio->proveedor_id = $contrato->proveedor_id;
                            $precio->insumo_medico_clave = $data[0];
                            $precio->tipo_insumo_id = $data[1];
                            $precio->precio = $data[2];

                            $errores = [];
                            try{
                                $precio->save();
                                $precio->insumo;
                                $precio->tipo;

                            } catch(\Exception $e){
                                

                                $precio->error= $e->getMessage();
                               
                                if(strpos($precio->error, "Integrity constraint violation: 1452") != false){
                                    $precio->error_detectado = "Uno o más de los valores de las columnas no es correcto por favor corrija e intente de nuevo.";                                    
                                } else if(strpos($precio->error, "Integrity constraint violation: 1062") != false){
                                    $precio->error_detectado = "La clave está repetida o ya existe en la base de datos.";
                                } else {
                                    $precio->error_detectado = "No se pudo detectar el error, por favor revise que los valores sean correctos.";
                                }
                            }


                            $precios[] = $precio;
                            
                        }
                        
                       

                        DB::rollback();
                    });

					return Response::json([ 'data' => ["precios" => $precios]],200);

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


        $contrato = Contrato::find($id);

        if(!$contrato){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        $contrato->proveedor;


       /* $precios = $object->precios;
        foreach($precios as $precio){
            $precio->tipo;
            $precio->insumo;
        }*/
      //  $object = $object->load('precios','precios.insumo','precios.tipo');
        
        $tiposInsumo = TipoInsumo::all();
        $insumos = Insumo::all();

        
        Excel::create("Lista de precios del contrato de: ".$contrato->proveedor->nombre_corto, function($excel) use($contrato, $tiposInsumo, $insumos) {
            $excel->sheet('Datos generales', function($sheet)  use($contrato){
                $sheet->setAutoSize(true);

                $sheet->row(1, array('Proveedor', $contrato->proveedor->nombre));
                $sheet->row(2, array('RFC', $contrato->proveedor->rfc));
                $sheet->row(3, array('Monto mínimo:', $contrato->monto_minimo));
                $sheet->row(4, array('Monto máximo:', $contrato->monto_maximo));
                $sheet->row(5, array('Fecha inicio:', \PHPExcel_Shared_Date::PHPToExcel(strtotime( $contrato->fecha_inicio))));
                $sheet->row(6, array('Fecha fin:', \PHPExcel_Shared_Date::PHPToExcel(strtotime( $contrato->fecha_fin))));
                $sheet->row(7, array('Contrato activo:', $contrato->activo ? "SI":"NO"));

                $sheet->cells("A1:A7", function($cells) {
                    $cells->setBackground('#DDDDDD');
                    $cells->setFontWeight('bold');
                });

                $sheet->cells("B1:B7", function($cells) {
                    $cells->setAlignment('left');
                });               

                $sheet->cells("B1:B1", function($cells) {
                    $cells->setFontWeight('bold');
                });
                
                if($contrato->activo ){
                    $sheet->cells("B7:B7", function($cells) {
                        $cells->setFontWeight('bold');
                    });
                }

                $sheet->setColumnFormat(array(
                    "B3:B4" => '"$" #,##0.00_-',
                    "B5:B6" => 'dd/MM/yyyy'
                ));

                $sheet->setBorder("A1:B7", 'thin');
            });

            // Obtenemos los tipos de insumos para separar en pestañas

            $query_tipos = '
            SELECT  tipos_insumos.id as id, tipos_insumos.nombre as nombre, COUNT(contratos_precios.id) as total
            FROM tipos_insumos 
            JOIN contratos_precios ON tipos_insumos.id = contratos_precios.tipo_insumo_id
            WHERE contratos_precios.contrato_id = :contrato_id
            GROUP BY tipos_insumos.id';
            $variables = [
                'contrato_id' =>$contrato->id
            ];
            $tipos = DB::select(DB::raw($query_tipos),$variables);

            foreach($tipos as $tipo){
                $excel->sheet($tipo->nombre." (".$tipo->total.")", function($sheet) use ($contrato, $tipo)  {
                    $sheet->setAutoSize(true);
                    $items = ContratoPrecio::where("contrato_id", $contrato->id)->where('tipo_insumo_id',$tipo->id)->get();
                    $sheet->row(1, array('CLAVE', 'DESCRIPCION', 'PRECIO'));
                    $contador_tipos = 1;
                    foreach($items as $item){
                        $item->insumo;
                        $sheet->appendRow(array(
                            $item->insumo_medico_clave,
                            $item->insumo != null ? $item->insumo->descripcion: "",
                            $item->precio
                        )); 
                        $contador_tipos++;
                    }

                    $sheet->row(1, function($row) {
                        $row->setBackground('#DDDDDD');
                        $row->setFontWeight('bold');
                    });
                    $sheet->setColumnFormat(array(
                        "C2:C$contador_tipos" => '"$" #,##0.00_-',
                    ));

                    $sheet->setBorder("A1:C$contador_tipos", 'thin');
                    $sheet->setAutoFilter('A1:C1');
                });
            }


           
           $excel->setActiveSheetIndex(0);

           


         })->export('xls');
    }
}