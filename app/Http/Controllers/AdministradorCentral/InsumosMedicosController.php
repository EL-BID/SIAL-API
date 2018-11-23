<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\ClavesBasicas, App\Models\ClavesBasicasDetalle,App\Models\ClavesBasicasUnidadMedica,App\Models\UnidadMedica;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

use App\Models\Insumo, 
    App\Models\Medicamento, 
    App\Models\MaterialCuracion, 
    App\Models\PresentacionesMedicamentos, 
    App\Models\UnidadMedida, 
    App\Models\ViasAdministracion,
    App\Models\HistorialInsumo,
    App\Models\HistorialMedicamento,
    App\Models\HistorialMaterialCuracion;

class InsumosMedicosController extends Controller
{

    public function presentaciones(Request $request){
        return Response::json(['data' => PresentacionesMedicamentos::all()],200);
    }
    public function unidadesMedida(Request $request){
        return Response::json(['data' => UnidadMedida::all()],200);
    }

    public function viasAdministracion(Request $request){
        return Response::json(['data' => viasAdministracion::all()],200);
    }
     

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   $parametros = Input::only('q','causes','unidosis','tipo','no_disponible_pedidos','descontinuado','atencion_medica','salud_publica','page','per_page');
        
        $items =  Insumo::select('*');
        
        if ($parametros['tipo']) {
            if ($parametros['tipo'] == "ME") {
                $items = $items->where('tipo','ME');
            }

            if ($parametros['tipo'] == "MC") {
                $items = $items->where('tipo','MC');
            }
        }
       
       
           
        if ($parametros['causes'] == 1) {
            $items = $items->where('es_causes',1);
        } else if ($parametros['causes'] == 0) {
            
            $items = $items->where('es_causes',0);
        }

        if ($parametros['unidosis'] == 1) {
            $items = $items->where('es_unidosis',1);
        } else if ($parametros['unidosis'] == 0) {
            
            $items = $items->where('es_unidosis',0);
        }

        if ($parametros['descontinuado'] == 1) {
            $items = $items->where('descontinuado',1);
        } else if ($parametros['descontinuado'] == 0) {
            
            $items = $items->where('descontinuado',0);
        }


        if ($parametros['no_disponible_pedidos'] == 1) {
            $items = $items->where('no_disponible_pedidos',1);
        } else if ($parametros['no_disponible_pedidos'] == 0) {
            
            $items = $items->where('no_disponible_pedidos',0);
        }

        if ($parametros['atencion_medica'] == 1) {
            $items = $items->where('atencion_medica',1);
        } else if ($parametros['atencion_medica'] == 0) {            
            $items = $items->where('atencion_medica',0);
        }

        if ($parametros['salud_publica'] == 1) {
            $items = $items->where('salud_publica',1);
        } else if ($parametros['salud_publica'] == 0) {            
            $items = $items->where('salud_publica',0);
        }
        

        if ($parametros['q']) {
            
            $items = $items->where('descripcion','LIKE',"%".$parametros['q']."%")->orWhere('clave','LIKE',"%".$parametros['q']."%");
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $mensajes = [            
            'required'      => "required",
            'required_if'      => "required",
            'unique'      => "unique",
        ];

        $reglas = [
            //'id'            => 'required|unique:usuarios,id,'.$id,
            'clave'        => 'required|unique:insumos_medicos',
            'tipo'         => 'required',
            'descripcion'        => 'required',
            'medicamento.presentacion_id'        => 'required_if:tipo,"ME"',
            'medicamento.unidad_medida_id'        => 'required_if:tipo,"ME"',
            'medicamento.cantidad_x_envase'        => 'required_if:tipo,"ME"',
            'medicamento.contenido'        => 'required_if:tipo,"ME"',
            'material_curacion.unidad_medida_id'        => 'required_if:tipo,"MC"',
            'material_curacion.cantidad_x_envase'        => 'required_if:tipo,"MC"',
        ];

        $inputs = Input::only('clave','tipo',"es_causes","es_unidosis","tiene_fecha_caducidad","no_disponible_pedidos","descontinuado","descripcion","medicamento","material_curacion", "atencion_medica","salud_publica");

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
            $inputs["generico_id"] = "1689";// Esto está así en la base de datos no se porque decidieron ponerle el mismo generico id a todo pero bueno :/

            if(!isset($inputs["tiene_fecha_caducidad"])){
                $inputs["tiene_fecha_caducidad"] = false;
            }
            if(!isset($inputs["descontinuado"])){
                $inputs["descontinuado"] = false;
            }
          
            if(!isset($inputs["no_disponible_pedidos"])){
                $inputs["no_disponible_pedidos"] = false;
            }
            
            $insumo = Insumo::create($inputs);
            
            if($inputs["tipo"]=="ME"){
                $medicamento = new Medicamento($inputs["medicamento"]);
                $insumo->medicamento()->save($medicamento);
            } else {
                $material_curacion = new MaterialCuracion($inputs["material_curacion"]);
                $insumo->materialCuracion()->save($material_curacion);
            }
            
            /*
            $items = [];
            foreach($inputs['items'] as $item){
                $items[] = new ClavesBasicasDetalle([
                    'insumo_medico_clave' => $item
                ]);
            }

            $clavesBasicas->detalles()->saveMany($items);
*/
            DB::commit();
            return Response::json([ 'data' => $insumo ],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $object = Insumo::find($id);

        if(!$object){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        if($object->tipo == "ME"){
            $object->medicamento;
        }
        if($object->tipo == "MC"){
            $object->materialCuracion;
        }
        //$object =  $object->load("detalles.insumoConDescripcion.informacion","detalles.insumoConDescripcion.generico.grupos");

        return Response::json([ 'data' => $object ], HttpResponse::HTTP_OK);
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
            'required_if'      => "required",
            'unique'      => "unique",
        ];

        $reglas = [
            'clave'        => 'required|unique:insumos_medicos,clave,'.$id.',clave',
            'tipo'         => 'required',
            'descripcion'        => 'required',
            'medicamento.presentacion_id'        => 'required_if:tipo,"ME"',
            'medicamento.unidad_medida_id'        => 'required_if:tipo,"ME"',
            'medicamento.cantidad_x_envase'        => 'required_if:tipo,"ME"',
            'medicamento.contenido'        => 'required_if:tipo,"ME"',
            'material_curacion.unidad_medida_id'        => 'required_if:tipo,"MC"',
            'material_curacion.cantidad_x_envase'        => 'required_if:tipo,"MC"',
        ];

        $inputs = Input::only('clave','tipo',"es_causes","es_unidosis","tiene_fecha_caducidad","no_disponible_pedidos","descontinuado","descripcion","medicamento","material_curacion", "atencion_medica","salud_publica");


        $insumo = Insumo::find($id);

        if(!$insumo){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        
        
        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {


            $historial_insumo = new HistorialInsumo();
            
            if($insumo->tipo == "ME"){
                $medicamento = $insumo->medicamento();
            } else {
 
                $material_curacion = $insumo->materialCuracion();
            }

            $historial_insumo->clave = $insumo->clave;
            $historial_insumo->tipo = $insumo->tipo;
            $historial_insumo->generico_id = $insumo->generico_id;
            $historial_insumo->es_causes = $insumo->es_causes;
            $historial_insumo->es_unidosis =  $insumo->es_unidosis;
            $historial_insumo->descontinuado = $insumo->descontinuado;
            //no_disponible_pedidos
            $historial_insumo->no_disponible_pedidos = $insumo->no_disponible_pedidos;
            $historial_insumo->descripcion = $insumo->descripcion;
            $historial_insumo->atencion_medica = $insumo->atencion_medica;
            $historial_insumo->salud_publica = $insumo->salud_publica;
            $historial_insumo->save();

            
            $insumo->clave = $inputs['clave'];
            $insumo->tipo = $inputs['tipo'];
            $insumo->es_causes = !isset($inputs['es_causes'])? false : $inputs['es_causes'] ;
            $insumo->es_unidosis = !isset($inputs['es_unidosis'])? false : $inputs['es_unidosis'];
            $insumo->descontinuado = !isset($inputs['descontinuado'])? false : $inputs['descontinuado'];
            $insumo->no_disponible_pedidos = !isset($inputs['no_disponible_pedidos'])? false : $inputs['no_disponible_pedidos'];
            $insumo->descripcion = $inputs['descripcion'];
            $insumo->atencion_medica = !isset($inputs['atencion_medica'])? false : $inputs['atencion_medica'] ;
            $insumo->salud_publica = !isset($inputs['salud_publica'])? false : $inputs['salud_publica'] ;
          

            if($inputs["tipo"]=="ME"){

                $historial_medicamento = new HistorialMedicamento();

                if($medicamento){                    
                    //$historial_medicamento->historial_id = $historial_insumo->id;

                    $alt = $medicamento->get();
                    $historial_medicamento->insumo_medico_clave = $alt[0]->insumo_medico_clave;
                    $historial_medicamento->forma_farmaceutica_id = $alt[0]->forma_farmaceutica_id;
                    $historial_medicamento->presentacion_id = $alt[0]->presentacion_id;
                    $historial_medicamento->es_controlado = $alt[0]->es_controlado;
                    $historial_medicamento->es_surfactante = $alt[0]->es_surfactante;
                    $historial_medicamento->concentracion = $alt[0]->concentracion;
                    $historial_medicamento->contenido = $alt[0]->contenido;
                    $historial_medicamento->cantidad_x_envase = $alt[0]->cantidad_x_envase;
                    $historial_medicamento->unidad_medida_id = $alt[0]->unidad_medida_id;
                    $historial_medicamento->indicaciones = $alt[0]->indicaciones;
                    $historial_medicamento->via_administracion_id = $alt[0]->via_administracion_id;
                    $historial_medicamento->dosis = $alt[0]->dosis;
                    
                    $historial_insumo->medicamento()->save($historial_medicamento);

                    // $insumo->medicamento->update($inputs["medicamento"]);

                   $medicamento->update($inputs["medicamento"]);
                } else {
                    // Por si era material de curacion pero ahora es medicamento guardamos
                    if($material_curacion){

                        $alt = $material_curacion->get(); 
                        $historial_material_curacion = new HistorialMaterialCuracion();
                        $historial_material_curacion->insumo_medico_clave = $alt[0]->insumo_medico_clave;
                        $historial_material_curacion->nombre_generico_especifico = $alt[0]->nombre_generico_especifico;
                        $historial_material_curacion->cantidad_x_envase = $alt[0]->cantidad_x_envase;
                        $historial_material_curacion->unidad_medida_id = $alt[0]->unidad_medida_id;
                        $historial_material_curacion->funcion = $alt[0]->funcion;
                        $historial_insumo->materialCuracion()->save($historial_material_curacion);

                    }

                    $medicamento = new Medicamento($inputs["medicamento"]);
                    $insumo->medicamento()->save($medicamento);
                }

                
                
            } else {
                $historial_material_curacion = new HistorialMaterialCuracion();

                if($material_curacion){

                    $alt = $material_curacion->get(); 
                    $historial_material_curacion->insumo_medico_clave = $alt[0]->insumo_medico_clave;
                    $historial_material_curacion->nombre_generico_especifico = $alt[0]->nombre_generico_especifico;
                    $historial_material_curacion->cantidad_x_envase = $alt[0]->cantidad_x_envase;
                    $historial_material_curacion->unidad_medida_id = $alt[0]->unidad_medida_id;
                    $historial_material_curacion->funcion = $alt[0]->funcion;
                    $historial_insumo->materialCuracion()->save($historial_material_curacion);
                    
                    $material_curacion->update($inputs["material_curacion"]);
                 } else {
                     // Por si antes era medicamento
                    if($medicamento){
                        $alt = $medicamento->get();
                        $historial_medicamento = new HistorialMedicamento();
                        $historial_medicamento->insumo_medico_clave = $alt[0]->insumo_medico_clave;
                        $historial_medicamento->forma_farmaceutica_id = $alt[0]->forma_farmaceutica_id;
                        $historial_medicamento->presentacion_id = $alt[0]->presentacion_id;
                        $historial_medicamento->es_controlado = $alt[0]->es_controlado;
                        $historial_medicamento->es_surfactante = $alt[0]->es_surfactante;
                        $historial_medicamento->concentracion = $alt[0]->concentracion;
                        $historial_medicamento->contenido = $alt[0]->contenido;
                        $historial_medicamento->cantidad_x_envase = $alt[0]->cantidad_x_envase;
                        $historial_medicamento->unidad_medida_id = $alt[0]->unidad_medida_id;
                        $historial_medicamento->indicaciones = $alt[0]->indicaciones;
                        $historial_medicamento->via_administracion_id = $alt[0]->via_administracion_id;
                        $historial_medicamento->dosis = $alt[0]->dosis;
                        $historial_insumo->medicamento()->save($historial_medicamento);
                    }
                    
                    $material_curacion = new MaterialCuracion($inputs["material_curacion"]);
                    $insumo->materialCuracion()->save($material_curacion);
                 }
            }
            $insumo->save();


            /*
            $detalles = $clavesBasicas->detalles()->get();

            foreach($detalles as $item){
                 ClavesBasicasDetalle::destroy($item->id);
            }
           


            $items = [];
            foreach($inputs['items'] as $item){
                $items[] = new ClavesBasicasDetalle([
                    'insumo_medico_clave' => $item
                ]);
            }

            $clavesBasicas->detalles()->saveMany($items);*/

            DB::commit();
            return Response::json([ 'data' => $insumo ],200);

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


    public function exportarExcel(Request $request){

        $medicamentos = Insumo::where("tipo","ME")->with("medicamento","medicamento.UnidadMedida","medicamento.PresentacionMedicamento","medicamento.ViaAdministracion")->get();
        $material_curacion = Insumo::where("tipo","MC")->with("materialCuracion","materialCuracion.UnidadMedida")->get();
        
        Excel::create("Insumos médicos SIAL - ".date('Y-m-d'), function($excel) use($medicamentos, $material_curacion) {


            $excel->sheet('Medicamentos', function($sheet) use($medicamentos) {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'Clave', 'Tipo','Causes','Unidosis','Tiene Fecha Caducidad','Controlado','Surfactante','Bloquear en Pedidos','Descontinuado','Descripción',
                    'Presentación','Concentración','Contenido','Cantidad X Envase','Unidad de medida', 'Vía de Administración','Dosis','Indicaciones'
                ));
                $sheet->cells("A1:R1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                });

                $contador_filas = 1;
                foreach($medicamentos as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->clave,
                        $item->tipo,
                        $item->es_causes?1:0,
                        $item->es_unidosis?1:0,
                        $item->tiene_fecha_caducidad?1:0,
                        $item->medicamento->es_controlado?1:0,
                        $item->medicamento->es_surfactante?1:0,                        
                        $item->no_disponible_pedidos?1:0,
                        $item->descontinuado?1:0,
                        $item->descripcion,
                        $item->medicamento->PresentacionMedicamento != null ? $item->medicamento->PresentacionMedicamento->nombre: "",                        
                        $item->medicamento->concentracion,
                        $item->medicamento->contenido,
                        $item->medicamento->cantidad_x_envase,
                        $item->medicamento->UnidadMedida != null ? $item->medicamento->unidadMedida->nombre ." (".$item->medicamento->unidadMedida->clave.")": "",
                        $item->medicamento->ViaAdministracion != null ? $item->medicamento->ViaAdministracion->nombre: "",
                        $item->medicamento->dosis,
                        $item->medicamento->indicaciones
                    )); 
                }

                $sheet->setAutoFilter('A1:R1');
                $sheet->setBorder("A1:R$contador_filas", 'thin');
            });

            $excel->sheet('Material de Curación', function($sheet) use($material_curacion) {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'Clave', 'Tipo','Causes','Unidosis','Tiene Fecha Caducidad','Bloquear en Pedidos','Descontinuado','Descripción',
                    'Cantidad X Envase','Unidad de medida'
                ));
                $sheet->cells("A1:J1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                });

                $contador_filas = 1;
                foreach($material_curacion as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->clave,
                        $item->tipo,
                        $item->es_causes?1:0,
                        $item->es_unidosis?1:0,
                        $item->tiene_fecha_caducidad?1:0,                        
                        $item->no_disponible_pedidos?1:0,
                        $item->descontinuado?1:0,
                        $item->descripcion,
                        $item->materialCuracion->cantidad_x_envase,
                        $item->materialCuracion->UnidadMedida != null ? $item->materialCuracion->unidadMedida->nombre ." (".$item->materialCuracion->unidadMedida->clave.")": "",
                    )); 
                }
                $sheet->setBorder("A1:J$contador_filas", 'thin');
                $sheet->setAutoFilter('A1:J1');
            });


            
           
           $excel->setActiveSheetIndex(0);

           


         })->export('xls');
    }
    
    public function descargarFormato(Request $request){

        $unidadesMedida = UnidadMedida::all();
        $presentaciones = PresentacionesMedicamentos::all();
        $viasAdministracion = ViasAdministracion::all();

        
        Excel::create("Formato de carga de Insumos médicos SIAL", function($excel) use($unidadesMedida, $presentaciones, $viasAdministracion) {


            $excel->sheet('Medicamentos', function($sheet)  {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'Clave',
                    'Causes (1 = SI, 0 = NO)',
                    'Unidosis (1 = SI, 0 = NO)',
                    'Tiene Fecha Caducidad (1 = SI, 0 = NO)',
                    'Controlado (1 = SI, 0 = NO)',
                    'Surfactante (1 = SI, 0 = NO)',
                    'Bloquear en Pedidos (1 = SI, 0 = NO)',
                    'Descontinuado (1 = SI, 0 = NO)',
                    'Atencion Médica (1 = SI, 0 = NO)',
                    'Salud Pública (1 = SI, 0 = NO)',
                    'Descripción',
                    'Presentación (CLAVE Pestaña: REF PRESENTACION)',
                    'Concentración',
                    'Contenido',
                    'Cantidad X Envase',
                    'Unidad de medida (CLAVE Pestaña: REF UNIDAD MEDIDA)',
                    'Vía de Administración(CLAVE Pestaña: REF VIA ADMON)',
                    'Dosis',
                    'Indicaciones'
                ));
                $sheet->cells("A1:S1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                });

                $sheet->appendRow(array(
                    "010.000.000.00 (EJEMPLO)",
                    1,
                    1,
                    1,
                    0,
                    0,
                    0,
                    0,
                    1,
                    1,
                    "Descripción completa (EJEMPLO): ÁCIDO ACETILSALICÍLICO Tableta soluble o efervescente 300 mg 20 tabletas solubles o efervescentes",
                    11,                    
                    "300 mg",
                    "Caja con 20 tabletas solubles o efervescentes",
                    20,
                    1,
                    39,
                    "La que el médico señale, etc.",
                    "Artritis reumatoide. Osteoartritis. Espondilitis anquilosante. Fiebre reumática aguda. Dolor o fiebre."
                )); 

                $sheet->setAutoFilter('A1:S1');
            });

            $excel->sheet('Material de Curación', function($sheet) {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'Clave', 
                    'Causes  (1 = SI, 0 = NO)',
                    'Unidosis  (1 = SI, 0 = NO)',
                    'Tiene Fecha Caducidad  (1 = SI, 0 = NO)',
                    'Bloquear en Pedidos (1 = SI, 0 = NO)',
                    'Descontinuado  (1 = SI, 0 = NO)',
                    'Atencion Médica (1 = SI, 0 = NO)',
                    'Salud Pública (1 = SI, 0 = NO)',
                    'Descripción',
                    'Cantidad X Envase',
                    'Unidad de medida (CLAVE Pestaña: REF UNIDAD MEDIDA)'
                ));
                $sheet->cells("A1:K1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                });

                $sheet->appendRow(array(
                    "010.000.000.00 (EJEMPLO)",
                    0,
                    0,
                    0,
                    0,
                    0,
                    1,
                    1,
                    "VENDA DE GASA DE ALGODÓN. LONGITUD 27 M. ANCHO 5 CM. PIEZA (EJEMPLO)",
                    1,
                    1,
                )); 
                
                $sheet->setAutoFilter('A1:K1');

                //$sheet->getComment('H2')->getText()->createTextRun('hola:');
               /* $sheet->getComment('H1')->setAuthor('Hugo Corzo');
                $objCommentRichText = $sheet->getComment('H1')->getText()->createTextRun('Clave de la unidad de medida:');
                $objCommentRichText->getFont()->setBold(true);
                $sheet->getComment('H1')->getText()->createTextRun("\r\n");
                $sheet->getComment('H1')->getText()->createTextRun('Debe escribir un valor tomando como referencia la columna: "CLAVE" de la pestaña "REF UNIDAD MEDIDA".');*/
            });


            $excel->sheet('REF PRESENTACION', function($sheet) use($presentaciones) {
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
                foreach($presentaciones as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->id,
                        $item->nombre
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

            $excel->sheet('REF UNIDAD MEDIDA', function($sheet) use($unidadesMedida) {
                $sheet->setAutoSize(true);
                $sheet->row(1, array(
                    'CLAVE', 'NOTACION','NOMBRE'
                ));
                $sheet->cells("A1:C1", function($cells) {
                    $cells->setAlignment('center');
                });
                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                   
                });

                $contador_filas = 1;
                foreach($unidadesMedida as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->id,
                        $item->clave,
                        $item->nombre
                    )); 
                }
                $sheet->cells("A1:A".$contador_filas, function($cells) {
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                });
                $sheet->setBorder("A1:C$contador_filas", 'thin');
                $sheet->setAutoFilter('A1:C1');

                $sheet->getProtection()->setSheet(true);
            });
            $excel->sheet('REF VIAS ADMON', function($sheet) use($viasAdministracion) {
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
                foreach($viasAdministracion as $item){
                    $contador_filas++;
                    $sheet->appendRow(array(
                        $item->id,
                        $item->nombre
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
            
           
           $excel->setActiveSheetIndex(0);

           


         })->export('xls');
    }

    public function confirmarCarga(Request $request){
        ini_set('memory_limit', '-1');
        DB::beginTransaction();
        try {

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

                    $medicamentos = [];
                    $materiales_curacion = [];
                
                    Excel::load($file, function($reader) use (&$medicamentos, &$materiales_curacion) {
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
                                1 'Causes (1 = SI, 0 = NO)',
                                2 'Unidosis (1 = SI, 0 = NO)',
                                3 'Tiene Fecha Caducidad (1 = SI, 0 = NO)',
                                4 'Controlado (1 = SI, 0 = NO)',
                                5 'Surfactante (1 = SI, 0 = NO)',
                                6 'No disponible / Pedidos (1 = SI, 0 = NO)',
                                7 'Descontinuado (1 = SI, 0 = NO)',
                                8 'Atencion Médica (1 = SI, 0 = NO)',
                                9 'Salud Pública (1 = SI, 0 = NO)',
                                10 'Descripción',
                                11 'Presentación (CLAVE Pestaña: REF PRESENTACION)',
                                12 'Concentración',
                                13 'Contenido',
                                14 'Cantidad X Envase',
                                15 'Unidad de medida (CLAVE Pestaña: REF UNIDAD MEDIDA)',
                                16 'Vía de Administración(CLAVE Pestaña: REF VIA ADMON)',
                                17 'Dosis',
                                18 'Indicaciones'
                            */
                            $insumo = new Insumo();
                            $insumo->clave = $data[0];
                            $insumo->tipo = "ME";
                            $insumo->generico_id=  "1689";// Esto está así en la base de datos no se porque decidieron ponerle el mismo generico id a todo pero bueno :/;
                            $insumo->atencion_medica = $data[8];
                            $insumo->salud_publica = $data[9];
                            $insumo->es_causes = $data[1];
                            $insumo->es_unidosis = $data[2];                           
                            $insumo->tiene_fecha_caducidad = $data[3];
                            $insumo->no_disponible_pedidos = $data[6];
                            $insumo->descontinuado = $data[7];
                            $insumo->descripcion = $data[10];

                            $medicamento = new Medicamento();
                            $medicamento->insumo_medico_clave = $data[0];
                            $medicamento->presentacion_id = $data[11];
                            $medicamento->es_controlado = $data[4];
                            $medicamento->es_surfactante = $data[5];
                            $medicamento->concentracion = $data[12];
                            $medicamento->contenido = $data[13];
                            $medicamento->cantidad_x_envase = $data[14];
                            $medicamento->unidad_medida_id = $data[15];
                            $medicamento->via_administracion_id = $data[16];
                            $medicamento->dosis = $data[17];
                            $medicamento->indicaciones = $data[18];

                            try{
                                $insumo->save();
                                $insumo->medicamento()->save($medicamento);
                                $insumo->save();
                                $med = $insumo->medicamento;
                                $med->PresentacionMedicamento;
                                $med->UnidadMedida;
                                $med->ViaAdministracion;

                            } catch(\Exception $e){
                                $insumo->medicamento = $medicamento;
                                $med = $insumo->medicamento;
                                $med->PresentacionMedicamento;
                                $med->UnidadMedida;
                                $med->ViaAdministracion;
                                $insumo->error = $e->getMessage();
                               
                                if(strpos($insumo->error, "Integrity constraint violation: 1452") != false){
                                    $insumo->error_detectado = "Uno o más de los valores de las columnas no es correcto por favor corrija e intente de nuevo.";                                    
                                } else if(strpos($insumo->error, "Integrity constraint violation: 1062") != false){
                                    $insumo->error_detectado = "La clave está repetida o ya existe en la base de datos.";
                                } else {
                                    $insumo->error_detectado = "No se pudo detectar el error, por favor revise que los valores sean correctos.";
                                }
                            }
                            $medicamentos[] = $insumo;
                        }
                        
                        $sheet = $objExcel->getSheet(1);
                        $highestRow = $sheet->getHighestRow();
                        $highestColumn = $sheet->getHighestColumn();
        
                        //  Loop through each row of the worksheet in turn
                        for ($row = 2; $row <= $highestRow; $row++)
                        {
                            //  Read a row of data into an array
                            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                                NULL, TRUE, FALSE);
                                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                                NULL, TRUE, FALSE);
                            $data =  $rowData[0];      
                            
                            /*                             
                            0 'Clave', 
                            1 'Causes  (1 = SI, 0 = NO)',
                            2 'Unidosis  (1 = SI, 0 = NO)',
                            3 'Tiene Fecha Caducidad  (1 = SI, 0 = NO)',
                            4 'No disponible / Pedidos (1 = SI, 0 = NO)',
                            5 'Descontinuado  (1 = SI, 0 = NO)',
                            6 'Atencion Médica (1 = SI, 0 = NO)',
                            7 'Salud Pública (1 = SI, 0 = NO)',
                            8 'Descripción',
                            9 'Cantidad X Envase',
                            10 'Unidad de medida (CLAVE Pestaña: REF UNIDAD MEDIDA)'
                            */
                           
                            $insumo = new Insumo();
                            $insumo->clave = $data[0];
                            $insumo->tipo = "MC";
                            $insumo->generico_id=  "1689";// Esto está así en la base de datos no se porque decidieron ponerle el mismo generico id a todo pero bueno :/;
                            $insumo->atencion_medica = $data[6];
                            $insumo->salud_publica = $data[7];
                            $insumo->es_causes = $data[1];
                            $insumo->es_unidosis = $data[2];
                            $insumo->tiene_fecha_caducidad = $data[3];
                            $insumo->no_disponible_pedidos = $data[4];
                            $insumo->descontinuado = $data[5];
                            $insumo->descripcion = $data[8];

                            $material_curacion = new MaterialCuracion();
                            $material_curacion->insumo_medico_clave = $data[0];
                            $material_curacion->cantidad_x_envase = $data[9];
                            $material_curacion->unidad_medida_id = $data[10];
                            try{
                                $insumo->save();
                                $insumo->materialCuracion()->save($material_curacion);
                                $insumo->save();
                                $mc = $insumo->materialCuracion;
                                $mc->UnidadMedida;
                            } catch(\Exception $e){
                                $insumo->material_curacion = $material_curacion;
                                $mc = $insumo->material_curacion;
                                $mc->UnidadMedida;
                                $insumo->error = $e->getMessage();
                                if(strpos($insumo->error, "Integrity constraint violation: 1452") != false){
                                    $insumo->error_detectado = "Uno o más de los valores de las columnas no es correcto por favor corrija e intente de nuevo.";
                                } else if(strpos($insumo->error, "Integrity constraint violation: 1062") != false){
                                    $insumo->error_detectado = "La clave está repetida o ya existe en la base de datos.";
                                } else {
                                    $insumo->error_detectado = "No se pudo detectar el error, por favor revise que los valores sean correctos.";
                                }
                                
                            }
                            $materiales_curacion[] = $insumo;
                        }

                        DB::rollback();
                    });

					return Response::json([ 'data' => ["medicamentos" => $medicamentos, "material_curacion"=>$materiales_curacion]],200);

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

    public function confirmarCargaMasiva(Request $request){
        ini_set('memory_limit', '-1');

        try{
            $medicamentos_procesados = [];
            $materiales_curacion_procesados  = [];

            $input = Input::only("medicamentos, material_curacion");
            $medicamentos = Input::get("medicamentos");
            $material_curacion = Input::get("material_curacion");
            
            $bandera_hay_errores = false;
            
            DB::beginTransaction();
            if(isset($medicamentos)){
                $medicamentos_a_cargar = [];
                $medicamentos_correctos = [];
                $medicamentos_por_validar = [];

                if(isset($medicamentos["correctos"])){
                    $medicamentos_correctos = $medicamentos["correctos"];
                } 

                if(isset($medicamentos["por_validar"])){
                    $medicamentos_por_validar = $medicamentos["por_validar"];
                } 

                $medicamentos_a_cargar = array_merge($medicamentos_correctos, $medicamentos_por_validar);
                
                
                $total = count($medicamentos_a_cargar);

               
                for ($i = 0; $i < $total; $i++)
                {
                    
                    $data =  $medicamentos_a_cargar[$i];
                    
                    $insumo = new Insumo();
                    $insumo->clave = $data['clave'];
                    $insumo->tipo = $data['tipo'];
                    $insumo->generico_id=  "1689";// Esto está así en la base de datos no se porque decidieron ponerle el mismo generico id a todo pero bueno :/;
                    $insumo->atencion_medica = $data['atencion_medica'];
                    $insumo->salud_publica = $data['salud_publica'];
                    $insumo->es_causes = $data['es_causes'];
                    $insumo->es_unidosis = $data['es_unidosis'];
                    $insumo->tiene_fecha_caducidad = $data['tiene_fecha_caducidad'];
                    $insumo->no_disponible_pedidos = $data['no_disponible_pedidos'];
                    $insumo->descontinuado = $data['descontinuado'];
                    $insumo->descripcion = $data['descripcion'];

                    $data_medicamento = $data["medicamento"];

                    $medicamento = new Medicamento();
                    $medicamento->insumo_medico_clave = $data['clave'];
                    $medicamento->presentacion_id = $data_medicamento['presentacion_id'];
                    $medicamento->es_controlado = $data_medicamento['es_controlado'];
                    $medicamento->es_surfactante = $data_medicamento['es_surfactante'];
                    $medicamento->concentracion = $data_medicamento['concentracion'];
                    $medicamento->contenido = $data_medicamento['contenido'];
                    $medicamento->cantidad_x_envase = $data_medicamento['cantidad_x_envase'];
                    $medicamento->unidad_medida_id = $data_medicamento['unidad_medida_id'];
                    $medicamento->via_administracion_id = $data_medicamento['via_administracion_id'];
                    $medicamento->dosis = $data_medicamento['dosis'];
                    $medicamento->indicaciones = $data_medicamento['indicaciones'];

                    try{
                        $insumo->save();
                        $insumo->medicamento()->save($medicamento);
                        $insumo->save();

                        $med = $insumo->medicamento;
                        $med->PresentacionMedicamento;
                        $med->UnidadMedida;
                        $med->ViaAdministracion;

                    } catch(\Exception $e){
                        $bandera_hay_errores = true;
                        $insumo->medicamento = $medicamento;
                        $med = $insumo->medicamento;
                        $med->PresentacionMedicamento;
                        $med->UnidadMedida;
                        $med->ViaAdministracion;
                        $insumo->error = $e->getMessage();
                        
                        if(strpos($insumo->error, "Integrity constraint violation: 1452") != false){
                            $insumo->error_detectado = "Uno o más de los valores de las columnas no es correcto por favor corrija e intente de nuevo.";                                    
                        } else if(strpos($insumo->error, "Integrity constraint violation: 1062") != false){
                            $insumo->error_detectado = "La clave está repetida o ya existe en la base de datos.";
                        } else {
                            $insumo->error_detectado = "No se pudo detectar el error, por favor revise que los valores sean correctos.";
                        }
                    }

                    $medicamentos_procesados[] = $insumo;
                }
                
            } 

            if(isset($material_curacion)){
                $material_curacion_a_cargar = [];
                $material_curacion_correctos = [];
                $material_curacion_por_validar = [];

                if(isset($material_curacion["correctos"])){
                    $material_curacion_correctos = $material_curacion["correctos"];
                } 

                if(isset($material_curacion["por_validar"])){
                    $material_curacion_por_validar = $material_curacion["por_validar"];
                } 

                $material_curacion_a_cargar = array_merge($material_curacion_correctos, $material_curacion_por_validar);
                
                
                $total = count($material_curacion_a_cargar);

               
                for ($i = 0; $i < $total; $i++)
                {
                    
                    $data =  $material_curacion_a_cargar[$i];
                    
                    $insumo = new Insumo();
                    $insumo->clave = $data['clave'];
                    $insumo->tipo = $data['tipo'];
                    $insumo->generico_id=  "1689";// Esto está así en la base de datos no se porque decidieron ponerle el mismo generico id a todo pero bueno :/;
                    $insumo->atencion_medica = $data['atencion_medica'];
                    $insumo->salud_publica = $data['salud_publica'];
                    $insumo->es_causes = $data['es_causes'];
                    $insumo->es_unidosis = $data['es_unidosis'];
                    $insumo->tiene_fecha_caducidad = $data['tiene_fecha_caducidad'];
                    $insumo->no_disponible_pedidos = $data['no_disponible_pedidos'];
                    $insumo->descontinuado = $data['descontinuado'];
                    $insumo->descripcion = $data['descripcion'];

                    $data_material_curacion = $data["material_curacion"];

                    $material_curacion = new MaterialCuracion();
                    $material_curacion->insumo_medico_clave =  $data['clave'];
                    $material_curacion->cantidad_x_envase = $data_material_curacion['cantidad_x_envase'];
                    $material_curacion->unidad_medida_id =  $data_material_curacion['unidad_medida_id'];


                    try{
                        $insumo->save();
                        $insumo->materialCuracion()->save($material_curacion);
                        $insumo->save();
                        $mc = $insumo->materialCuracion;
                        $mc->UnidadMedida;
                    } catch(\Exception $e){
                        $bandera_hay_errores = true;
                        $insumo->material_curacion = $material_curacion;
                        $mc = $insumo->material_curacion;
                        $mc->UnidadMedida;
                        $insumo->error = $e->getMessage();
                        if(strpos($insumo->error, "Integrity constraint violation: 1452") != false){
                            $insumo->error_detectado = "Uno o más de los valores de las columnas no es correcto por favor corrija e intente de nuevo.";
                        } else if(strpos($insumo->error, "Integrity constraint violation: 1062") != false){
                            $insumo->error_detectado = "La clave está repetida o ya existe en la base de datos.";
                        } else {
                            $insumo->error_detectado = "No se pudo detectar el error, por favor revise que los valores sean correctos.";
                        }
                        
                    }

                    $materiales_curacion_procesados[] = $insumo;
                }
                
            } 

            

          

            if($bandera_hay_errores){
                DB::rollback();
                return Response::json([ 'data' => ["error"=>true,"medicamentos" => $medicamentos_procesados, "material_curacion"=>$materiales_curacion_procesados]],200);
            } else{
                //DB::rollback();
                DB::commit();
                return Response::json([ 'data' => ["error"=>false,"medicamentos" => $medicamentos_procesados, "material_curacion"=>$materiales_curacion_procesados]],200);
            }
        } catch(\Exception $e){
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
