<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;


use App\Models\CluesTurno;
use App\Models\UnidadMedica;
use App\Models\Insumo;
use App\Models\Pedido;
use App\Models\PedidoInsumo;
use App\Models\Usuario;
use App\Models\Almacen;
use App\Models\Contrato;
use App\Models\ContratoPrecio;
use App\Models\Proveedor;
use App\Models\Movimiento;
use App\Models\MovimientoInsumos;
use App\Models\MovimientoMetadato;
use App\Models\MovimientoDetalle;
use App\Models\MovimientoPedido;
use App\Models\NegacionInsumo;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\Stock;

use App\Models\SincronizacionProveedor;
use App\Models\SincronizacionMovimiento;
use App\Models\PedidoMetadatoSincronizacion;


/** 
* Controlador 
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017
*
* Controlador 
*
*/
class SincronizacionProveedorController extends Controller
{
     
     
///***************************************************************************************************************************
///***************************************************************************************************************************
   
    public function index(Request $request)
    {
        //var_dump(json_encode($request)); die();

    }

 ///***************************************************************************************************************************
///***************************************************************************************************************************
 
 public function store(Request $request)
    {
        $clues = $request->get('clues');
        if(!$clues){
            return Response::json(array("status" => 404,"messages" => "Debe especificar una Unidad Médica."), 404);
        } 


        

        
    }

 ///***************************************************************************************************************************
///***************************************************************************************************************************
 
    public function show($id)
    {

        
    }

///***************************************************************************************************************************
///***************************************************************************************************************************
 

    public function update(Request $request, $id){
 
    }
///***************************************************************************************************************************
///***************************************************************************************************************************
      
    public function destroy($id)
    {
        
    }

///***************************************************************************************************************************
///***************************************************************************************************************************
 
public function listarPedidos(Request $request)
{
    $parametros = Input::only('q','page','per_page','almacen','tipo','fecha_desde','fecha_hasta','clues','folio');
    $proveedor_id = $request->get('proveedor_id');

    $pedidos = Pedido::with('metadatosSincronizaciones','proveedor','unidadMedica')->where('proveedor_id',$proveedor_id);

    if( ($parametros['fecha_desde']!="") && ($parametros['fecha_hasta']!="") )
        {
            $pedidos = $pedidos->where('fecha','>=',$parametros['fecha_desde'])
                                       ->where('fecha','<=',$parametros['fecha_hasta']);
            
        }else{
                if( $parametros['fecha_desde'] != "" )
                {

                    $pedidos = $pedidos->where('fecha','>=',$parametros['fecha_desde']);
                }
                if( $parametros['fecha_hasta'] != "" )
                {
                    $pedidos = $pedidos->where('fecha','<=',$parametros['fecha_hasta']);
                }

             } 
    if($parametros['clues'] != "")
    {
        $pedidos = $pedidos->where('clues','LIKE','%'.trim( $parametros['clues']).'%' );
    }
    if($parametros['folio'] != "")
    {
        $pedidos = $pedidos->where('folio','LIKE','%'.$parametros['folio'].'%');
    }
     
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $pedidos = $pedidos->paginate($resultadosPorPagina);
        } else {
            $pedidos = $pedidos->get();
        }
       
        
    return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $pedidos, "total" => count($pedidos)), 200);

}

///***************************************************************************************************************************
///***************************************************************************************************************************

 
public function listarSincronizacionesPedido(Request $request)
{
    $parametros = Input::only('q','page','per_page','almacen','tipo','fecha_desde','fecha_hasta','clues','pedido_id');
    $proveedor_id = $request->get('proveedor_id');

    $pedido_id = $parametros['pedido_id'];

    $sincronizaciones = SincronizacionProveedor::where('pedido_id',$pedido_id);
     
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $sincronizaciones = $sincronizaciones->paginate($resultadosPorPagina);
        } else {
            $sincronizaciones = $sincronizaciones->get();
        }
       
        
    return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $sincronizaciones, "total" => count($sincronizaciones)), 200);

}

///***************************************************************************************************************************
///***************************************************************************************************************************
 
public function analizarJson(Request $request)
{
    $proveedor_id = $request->get('proveedor_id');

    $input_data = (object)Input::json()->all();

    $pedido_id      = $input_data->pedido;
    $json_proveedor = $input_data->json;

    if(is_array($json_proveedor))
    { $json_proveedor = (object)$json_proveedor; }

    $errors                  = array();

    $total_recetas           = 0;
    $recetas_validas         = 0;
    $recetas_invalidas       = 0;

    $total_colectivos        = 0;
    $colectivos_validos      = 0;
    $colectivos_invalidos    = 0;

    $clues_json   = $json_proveedor->clues;
    $pedido       = Pedido::find($pedido_id);
    $clues_pedido = $pedido->clues;

    if($clues_pedido != $clues_json)
    {
        array_push($errors, array(array('recetas' => array('Clues incorrecta'))));
        return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
    }


    if(property_exists($json_proveedor, "recetas"))
    {
        foreach($json_proveedor->recetas as $receta)
        {   $total_recetas++;
            $receta = (object) $receta;

            $folio_buscar = $receta->folio;

            $receta_buscar = Receta::where("folio",$folio_buscar)->first();
            if($receta_buscar)
            { $recetas_invalidas++; }else{ $recetas_validas++; }

            if(property_exists($receta, "insumos"))
            {
                
            }else{
                    array_push($errors, array(array('recetas' => array('no_existe_insumos'))));
                 }

        }

    }else{
            array_push($errors, array(array('recetas' => array('no_existe_recetas'))));
         }

    if(property_exists($json_proveedor, "colectivos"))
    {
        foreach($json_proveedor->colectivos as $colectivo)
        {   $total_colectivos++;
            $colectivo = (object) $colectivo;

            $folio_buscar = $colectivo->folio;

            $colectivo_buscar = MovimientoMetadato::where('folio_colectivo',$folio_buscar)->first();
            if($colectivo_buscar)
            { $colectivos_invalidos++; }else{ $colectivos_validos++; }
        }

    }else{
            array_push($errors, array(array('colectivos' => array('no_exite_colectivos'))));
         }

    if( count($errors) > 0 )
    {
        return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
    }else{

            $data = array(
                        "total_recetas" => $total_recetas,
                        "recetas_validas" => $recetas_validas,
                        "recetas_invalidas" => $recetas_invalidas,
                        "total_colectivos" => $total_colectivos,
                        "colectivos_validos" => $colectivos_validos,
                        "colectivos_invalidos" => $colectivos_invalidos);

        return Response::json(array("data" => array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data)), 200);

    }
        
            
        
         


            
}

////////////////////////   P R O C E S A R   J S O N     ---    P R O C E S A R   J S O N  
///***************************************************************************************************************************
///***************************************************************************************************************************

 public function procesarJson(Request $request)
{
    $proveedor_id = $request->get('proveedor_id');

    $input_data = Input::json()->all();

    $pedido_id      = $input_data['pedido'];
    $json_proveedor = $input_data['json'];

    $json_proveedor_string = json_encode($json_proveedor);

    //var_dump($json_proveedor_string); die();

    $json_proveedor = (object) $input_data['json'];

    $almacen_id     = $json_proveedor->almacen_id;

    $pedido         = Pedido::find($pedido_id);

    if(is_array($json_proveedor))
    { $json_proveedor = (object)$json_proveedor; }

    $errors                  = array();

    $total_recetas           = 0;
    $recetas_validas         = 0;
    $recetas_invalidas       = 0;

    $total_colectivos        = 0;
    $colectivos_validos      = 0;
    $colectivos_invalidos    = 0;

    $clues_json   = $json_proveedor->clues;
    $pedido       = Pedido::find($pedido_id);
    $clues_pedido = $pedido->clues;

    // INSERTAR REGISTRO DE SINCRONIZACION AQUI

    $sincronizacion_proveedor = new SincronizacionProveedor();
    $sincronizacion_proveedor->clues                 = $clues_json;
    $sincronizacion_proveedor->almacen_id            = $almacen_id;
    $sincronizacion_proveedor->proveedor_id          = $proveedor_id;
    $sincronizacion_proveedor->pedido_id             = $pedido_id;
    $sincronizacion_proveedor->fecha_surtimiento     = $json_proveedor->fecha;
    $sincronizacion_proveedor->recetas_validas       = 0;
    $sincronizacion_proveedor->colectivos_validos    = 0;
    $sincronizacion_proveedor->recetas_duplicadas    = 0;
    $sincronizacion_proveedor->colectivos_duplicados = 0;
    $sincronizacion_proveedor->json                  = $json_proveedor_string;
    $sincronizacion_proveedor->save();


    if(property_exists($json_proveedor, "recetas"))
    {
        // recorrido de recetas (por cada receta se genera un movimiento )
        foreach($json_proveedor->recetas as $receta)
        {   $total_recetas++;
            $receta = (object) $receta;

            $folio_buscar = $receta->folio;

            $receta_buscar = Receta::where("folio",$folio_buscar);
            if($receta_buscar)
            { 
                $recetas_invalidas++; 
            }else{ 
                    $recetas_validas++;

                    $movimiento = new Movimiento();
                    $movimiento->almacen_id                     = $json_proveedor->almacen_id;
                    $movimiento->tipo_movimiento_id             = 15;
                    $movimiento->status                         = "FI";
                    $movimiento->fecha_movimiento               = $json_proveedor->fecha;

                    $movimiento->save();

                    //GUARDAR REGISTRO DE SINCRONIZACION Y MOVIMIENTO AQUI

                    $sincronizacion_movimiento = new SincronizacionMovimiento();
                    $sincronizacion_movimiento->sincronizacion_proveedor_id = $sincronizacion_proveedor->id;
                    $sincronizacion_movimiento->movimiento_id               = $movimiento->id;
                    $sincronizacion_movimiento->save();

            
                    $receta_insertar = new Receta();
                    $receta_insertar->movimiento_id  = $movimiento->id;
                    $receta_insertar->folio          = $receta->folio;
                    $receta_insertar->folio_receta   = $receta->folio_receta;
                    $receta_insertar->fecha_receta   = $receta->fecha_receta;
                    $receta_insertar->fecha_surtido  = $receta->fecha_surtido;
                    $receta_insertar->tipo_receta_id = $receta->tipo_receta_id;
                    $receta_insertar->doctor         = $receta->doctor;
                    $receta_insertar->paciente       = $receta->paciente;
                    $receta_insertar->diagnostico    = $receta->diagnostico;

                    $receta_insertar->save();
                
                    $monto_recibido_receta = 0;

                foreach ($receta->insumos as $key => $receta_insumo)
                    { $receta_insumo = (object) $receta_insumo;

                        $receta_detalles = new RecetaDetalle();
                        $receta_detalles->receta_id              = $receta_insertar->id;
                        $receta_detalles->clave_insumo_medico    = $receta_insumo->clave_insumo_medico;
                        $receta_detalles->cantidad_recetada      = $receta_insumo->cantidad_recetada;
                        $receta_detalles->cantidad               = $receta_insumo->cantidad_surtida; // 2L
                        $receta_detalles->dosis                  = $receta_insumo->dosis;
                        $receta_detalles->frecuencia             = $receta_insumo->frecuencia;
                        $receta_detalles->duracion               = $receta_insumo->duracion;

                        $receta_detalles->save();
                        
                            // CONSEGUIR PRECIO Y DETALLES DEL iNSUMO 
                            ///*************************************************************************************************************
                            
                        $movimiento_pedido = MovimientoPedido::where('pedido_id',$pedido_id)->first();
                        $movimiento_insumo = (object)MovimientoInsumos::where('movimiento_id',$movimiento_pedido->movimiento_id)
                                                ->where('clave_insumo_medico',$receta_insumo->clave_insumo_medico)->first();

                            ///stock de donde se sacará lo indicado en la receta                    
                        $stock = Stock::find($movimiento_insumo->stock_id);
                        
                        $diferencia_faltante = 0;
                        if($stock->existencia < $receta_insumo->cantidad_surtida)
                        {
                            $diferencia_faltante = $receta_insumo->cantidad_surtir - $stock->existencia;
                        }

                        $clave_insumo_medico = $receta_insumo->clave_insumo_medico;
                        $insumo = Insumo::datosUnidosis()->find($clave_insumo_medico);
                        $cantidad_x_envase = $insumo->cantidad_x_envase;
                          
                             
                        $precios = $this->conseguirPrecio($clave_insumo_medico);
                        ///*************************************************************************************************************

                        $movimiento_insumo = new MovimientoInsumos();
                        $movimiento_insumo->movimiento_id        = $movimiento->id; 
                        if($stock)
                            {
                                $movimiento_insumo->stock_id     = $stock->id;
                            }
                        $movimiento_insumo->clave_insumo_medico  = $receta_insumo->clave_insumo_medico;
                        $movimiento_insumo->modo_salida          = "N";
                        $movimiento_insumo->cantidad             = $receta_insumo->cantidad_surtida;
                        $movimiento_insumo->cantidad_unidosis    = $cantidad_x_envase * $receta_insumo->cantidad_surtida;
                        $movimiento_insumo->precio_unitario      = $precios->precio_unitario;
                        $movimiento_insumo->iva                  = $precios->iva;
                        $movimiento_insumo->precio_total         = $receta_insumo->cantidad_surtida * ( $precio_unitario + $precios->iva);

                        $movimiento_insumo->save();


                        $movimiento_detalles = new MovimientoDetalle();
                        $movimiento_detalles->movimiento_id                 = $movimiento->id;
                        $movimiento_detalles->clave_insumo_medico           = $clave_insumo_medico;
                        $movimiento_detalles->modo_salida                   = "N";
                        $movimiento_detalles->cantidad_solicitada           = $receta_insumo->cantidad_solicitada;
                        $movimiento_detalles->cantidad_solicitada_unidosis  = ($receta_insumo->cantidad_solicitada * $cantidad_x_envase);
                        
                        //$movimiento_detalles->cantidad_existente            = $stock->existencia;
                        //$movimiento_detalles->cantidad_existente_unidosis   = $stock->existencia_unidosis;
                        if($stock)
                            {
                                $movimiento_detalles->cantidad_existente            = $stock->existencia;
                                $movimiento_detalles->cantidad_existente_unidosis   = $stock->existencia_unidosis;
                            }else{
                                    $movimiento_detalles->cantidad_existente            = 0;
                                    $movimiento_detalles->cantidad_existente_unidosis   = 0;
                                 }

                        $movimiento_detalles->cantidad_surtida              = $receta_insumo->cantidad_surtida;
                        $movimiento_detalles->cantidad_surtida_unidosis    = ($receta_insumo->cantidad_surtida * $cantidad_x_envase);
                        $movimiento_detalles->cantidad_negada               = ($receta_insumo->cantidad_solicitada - $receta_insumo->cantidad_surtida);
                        $movimiento_detalles->cantidad_negada_unidosis      = ($receta_insumo->cantidad_solicitada - $receta_insumo->cantidad_surtida) * $cantidad_x_envase;
                        $movimiento_detalles->save();
                            
                        if($stock)
                            {
                                $stock->existencia          = $stock->existencia - $receta_insumo->cantidad_surtida;
                                $stock->existencia_unidosis = $stock->existencia_unidosis - ($cantidad_x_envase * $receta_insumo->cantidad_surtida);
                                $stock->save();
                            }    
                        

                        $monto_recibido_receta += $movimiento_insumo->precio_total;
                        ///*****
                        $total_cantidad_recibida  += $movimiento_insumo->cantidad_surtida;

                        //***********************************************************************************************************************************************************************
                        if($receta_insumo->cantidad_surtida < $receta_insumo->cantidad_solicitada)
                        {   
                            $cantidad_negada = $receta_insumo->cantidad_solicitada - $receta_insumo->cantidad_surtida ;           
                            $this->guardarEstadisticaNegacion($clave_insumo_medico,$almacen_id,$cantidad_negada);
                        }
                        //***********************************************************************************************************************************************************************
                        $pedido_insumo = PedidoInsumo::where('insumo_medico_clave',$clave_insumo_medico)->where('pedido_id',$pedido_id)->first();
                        if($pedido_insumo)
                            {
                                $pedido_insumo->cantidad_recibida = $pedido_insumo->cantidad_recibida + $receta_insumo->cantidad_surtida;
                                $pedido_insumo->monto_recibido    = $pedido_insumo->monto_recibido    + $movimiento_insumo->precio_total;
                                $pedido_insumo->save();
                            }
                        

                        ///*****

                    }// fin foreach insumos receta

                    $pedido->total_monto_recibido = ( $pedido->total_monto_recibido + $monto_recibido_receta );
                    $pedido->save();

                    ///****
                    $total_claves = PedidoInsumo::where('pedido_id',$pedido_id)
                                                ->where(function($query) use ($parametros) {
                                                            $query->where('cantidad_recibida','<>',NULL)
                                                                  ->orWhere('cantidad_recibida','>',0);
                                                        })->count();

                    $pedido->total_monto_recibido     = ( $pedido->total_monto_recibido + $monto_recibido_receta );
                    $pedido->total_claves_recibidas   = $total_claves;
                    $pedido->total_cantidad_recibida  = ( $pedido->total_cantidad_recibida + $receta_insumo->cantidad_surtida );
                    $pedido->save();
                    ///****
                    
                 }/// fin else ( receta no repetida para procesar)

        }/// fin foreach recetas

    }else{
            array_push($errors, array(array('recetas' => array('no_exite_recetas'))));
         }




//////   P R O C E S A R      C O L E C T I V O S   
//////*********************************************************************************************************************************************
//////*********************************************************************************************************************************************

    if(property_exists($json_proveedor, "colectivos"))
    {
        foreach($json_proveedor->colectivos as $colectivo)
        {   
            $total_colectivos++;
            $colectivo = (object) $colectivo;

            $folio_buscar = $colectivo->folio;

            $colectivo_buscar = MovimientoMetadato::where("folio_colectivo",$folio_buscar)->first();

            if($colectivo_buscar)
            { /// se ignoran los colectivos ya existentes.
                $colectivos_invalidos++; 
            }else{  // solo se procesan colectivos validos
                    $colectivos_validos++; 

                    $movimiento_colectivo = new Movimiento();
                    $movimiento_colectivo->almacen_id                     = $json_proveedor->almacen_id;
                    $movimiento_colectivo->tipo_movimiento_id             = 16;
                    $movimiento_colectivo->status                         = "FI";
                    $movimiento_colectivo->fecha_movimiento               = $json_proveedor->fecha;
                    $movimiento_colectivo->save();

                    // REGISTRAR SINCRONIZACION MOVIMIENTO 
                    $sincronizacion_movimiento = new SincronizacionMovimiento();
                    $sincronizacion_movimiento->sincronizacion_proveedor_id = $sincronizacion_proveedor->id;
                    $sincronizacion_movimiento->movimiento_id               = $movimiento_colectivo->id;
                    $sincronizacion_movimiento->save();


                    $monto_recibido_colectivo = 0;
                    $total_cantidad_recibida  = 0;

                    foreach ($colectivo->insumos as $key => $colectivo_insumo)
                    { $colectivo_insumo = (object)$colectivo_insumo;

                        $movimiento_pedido   = MovimientoPedido::where('pedido_id',$pedido_id)->first();

                           // var_dump($movimiento_pedido); die();

                        $movimiento_insumo_x = MovimientoInsumos::where('movimiento_id',$movimiento_pedido->movimiento_id)
                                             ->where('clave_insumo_medico',$colectivo_insumo->clave)->first();
                        
                        //stock de donde se sacará lo indicado en la receta                    
                        $stock_x = Stock::where('id',$movimiento_insumo_x['stock_id'])->first();

                        if(!$stock_x)
                        {
                            //var_dump(json_encode($stock_x)); die();
                        }
                        $clave_insumo_medico = $colectivo_insumo->clave;
                        $insumo = Insumo::datosUnidosis()->find($clave_insumo_medico);
                        $cantidad_x_envase = $insumo->cantidad_x_envase;
                               
                        $precios = $this->conseguirPrecio($clave_insumo_medico);

                        $movimiento_insumo = new MovimientoInsumos();
                        $movimiento_insumo->movimiento_id        = $movimiento_colectivo->id;
                        $movimiento_insumo->tipo_insumo_id       = $precios->tipo_insumo_id;
                        if($stock_x)
                            {
                                $movimiento_insumo->stock_id     = $stock_x->id;
                            }
                        $movimiento_insumo->clave_insumo_medico  = $colectivo_insumo->clave;
                        $movimiento_insumo->modo_salida          = "N";
                        $movimiento_insumo->cantidad             = $colectivo_insumo->cantidad_surtida;
                        $movimiento_insumo->cantidad_unidosis    = ( $colectivo_insumo->cantidad_surtida * $cantidad_x_envase );
                        $movimiento_insumo->precio_unitario      = $precios->precio_unitario;
                        $movimiento_insumo->iva                  = $precios->iva;
                        $movimiento_insumo->precio_total         = ($colectivo_insumo->cantidad_surtida) * ($precios->precio_unitario + $precios->iva);

                        $movimiento_insumo->save();

                        $movimiento_detalles = new MovimientoDetalle();
                        $movimiento_detalles->movimiento_id                 = $movimiento_colectivo->id;
                        $movimiento_detalles->clave_insumo_medico           = $clave_insumo_medico;
                        $movimiento_detalles->modo_salida                   = "N";
                        $movimiento_detalles->cantidad_solicitada           = $colectivo_insumo->cantidad_solicitada;
                        $movimiento_detalles->cantidad_solicitada_unidosis  = ($colectivo_insumo->cantidad_solicitada * $cantidad_x_envase);

                        if($stock_x)
                            {
                                $movimiento_detalles->cantidad_existente            = $stock_x->existencia;
                                $movimiento_detalles->cantidad_existente_unidosis   = $stock_x->existencia_unidosis;
                            }else{
                                    $movimiento_detalles->cantidad_existente            = 0;
                                    $movimiento_detalles->cantidad_existente_unidosis   = 0;
                                 }
                        

                        $movimiento_detalles->cantidad_surtida              = $colectivo_insumo->cantidad_surtida;
                        $movimiento_detalles->cantidad_surtida_unidosis    = ($colectivo_insumo->cantidad_surtida * $cantidad_x_envase);
                        $movimiento_detalles->cantidad_negada               = ($colectivo_insumo->cantidad_solicitada - $colectivo_insumo->cantidad_surtida);
                        $movimiento_detalles->cantidad_negada_unidosis      = ($colectivo_insumo->cantidad_solicitada - $colectivo_insumo->cantidad_surtida) * $cantidad_x_envase;
                        $movimiento_detalles->save();

                        if($stock_x)
                            {
                                $stock_x->existencia          = $stock_x->existencia - $colectivo_insumo->cantidad_surtida;
                                $stock_x->existencia_unidosis = $stock_x->existencia_unidosis - ($cantidad_x_envase * $colectivo_insumo->cantidad_surtida);
                                $stock_x->save();
                            } 
                        

                        $monto_recibido_colectivo += $movimiento_insumo->precio_total;
                        $total_cantidad_recibida  += $movimiento_insumo->cantidad_surtida;

                        //***********************************************************************************************************************************************************************
                        if($colectivo_insumo->cantidad_surtida < $colectivo_insumo->cantidad_solicitada)
                        {   
                            $cantidad_negada = $colectivo_insumo->cantidad_solicitada - $colectivo_insumo->cantidad_surtida ;     
                            $this->guardarEstadisticaNegacion($clave_insumo_medico,$almacen_id,$cantidad_negada);
                        }
                        //***********************************************************************************************************************************************************************
                        $pedido_insumo = PedidoInsumo::where('insumo_medico_clave',$clave_insumo_medico)->where('pedido_id',$pedido_id)->first();

                        if($pedido_insumo)
                            {
                                $pedido_insumo->cantidad_recibida = $pedido_insumo->cantidad_recibida + $colectivo_insumo->cantidad_surtida;
                                $pedido_insumo->monto_recibido    = $pedido_insumo->monto_recibido    + $movimiento_insumo->precio_total;

                                $pedido_insumo->save();
                            }
                        
                         
                    } /// fin foreach insumos de cada colectivo

                    $parametros = 1;
                    $total_claves = PedidoInsumo::where('pedido_id',$pedido_id)
                                                ->where(function($query) use ($parametros) {
                                                            $query->where('cantidad_recibida','<>',NULL)
                                                                  ->orWhere('cantidad_recibida','>',0);
                                                        })->count();

                    $pedido->total_monto_recibido     = ( $pedido->total_monto_recibido + $monto_recibido_colectivo );
                    $pedido->total_claves_recibidas   = $total_claves;
                    $pedido->total_cantidad_recibida  = ( $pedido->total_cantidad_recibida + $colectivo_insumo->cantidad_surtida );
                    $pedido->save();

                 }/// fin else ( colectivo no repetida para procesar)
        }/// fin foreach colectvos

    }else{
            array_push($errors, array(array('colectivos' => array('no_exite_colectivos'))));
         }

    
    $pms = PedidoMetadatosincronizacion::where('pedido_id',$pedido_id)->first();

    if($pms)
    {
        $pms->total_recetas                = $pms->total_recetas + $recetas_validas;
        $pms->total_colectivos             = $pms->total_colectivos + $colectivos_validos;
        $pms->total_recetas_repetidas      = $pms->total_recetas_repetidas + $recetas_invalidas;
        $pms->total_colectivos_repetidos   = $pms->total_colectivos_repetidos + $colectivos_invalidos;
        $pms->numero_sincronizaciones      = $pms->numero_sincronizaciones + 1;
        $pms->save();

    }else{
            $pms = new PedidoMetadatoSincronizacion();
            $pms->pedido_id                    = $pedido_id;
            $pms->total_recetas                = $recetas_validas;
            $pms->total_recetas_repetidas      = $pms->total_recetas_repetidas + $recetas_invalidas;
            $pms->total_colectivos_repetidos   = $pms->total_colectivos_repetidos + $colectivos_invalidos;
            $pms->numero_sincronizaciones      = 1;
            $pms->save();
         }

    



    if( count($errors) > 0 )
    {
        return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
    }else{

        $data = array(
                     "total_recetas" => $total_recetas,
                     "recetas_validas" => $recetas_validas,
                     "recetas_invalidas" => $recetas_invalidas,
                     "total_colectivos" => $total_colectivos,
                     "colectivos_validos" => $colectivos_validos,
                     "colectivos_invalidos" => $colectivos_invalidos);

        return Response::json(array("data" => array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data)), 200);

    }
        
            
}

///***************************************************************************************************************************
///***************************************************************************************************************************
 
	private function ValidarMovimiento($key, $id, $request)
    { 
        $mensajes = [
                        'required'      => "required",
                        'email'         => "email",
                        'unique'        => "unique",
                        'integer'       => "not_integer",
                        'in'            => 'no_valido',
                    ];

        $reglas = array();
        $reglas = [
                    'tipo_movimiento_id' => 'required|integer|in:1,2,3,4,5,6,7,8,9',
                  ];

        if($request['movimiento_metadato'] != NULL)
        {
            if($request['tipo_movimiento_id'] == 1 )
            {
                $reglas = [
                            'tipo_movimiento_id'                 => 'required|integer|in:1,2,3,4,5,6,7,8',
                            'movimiento_metadato.persona_recibe' => 'required|string',
                          ];
            }
            if($request['tipo_movimiento_id'] == 2 )
            {
                $reglas = [
                            'tipo_movimiento_id'                 => 'required|integer|in:1,2,3,4,5,6,7,8',
                            'movimiento_metadato.servicio_id'    => 'required|integer',
                            'movimiento_metadato.persona_recibe' => 'required|string',
                            'movimiento_metadato.turno_id'       => 'required|integer',
                          ];
            }
            

        }else if($request['receta'] != NULL){
                
            $reglas = [
                        'tipo_movimiento_id'    => 'required|integer|in:5',
                        'receta.folio'          => 'required|string',
                        'receta.tipo_receta'    => 'required|string',
                        'receta.fecha_receta'   => 'required',
                        'receta.doctor'         => 'required|string',
                        'receta.paciente'       => 'required|string',
                        'receta.diagnostico'    => 'required|string',
                        'receta.imagen_receta'  => 'required|string',
                      ];
        }

    $v = \Validator::make($request, $reglas, $mensajes );

    //*********************************************************************************************************
        if ($v->fails())
        {
			$mensages_validacion = array();
            foreach ($v->errors()->messages() as $indice => $item)  // todos los mensajes de todos los campos
            {
                $msg_validacion = array();
                    foreach ($item as $msg)
                    {
                        array_push($msg_validacion, $msg);
                    }
                    array_push($mensages_validacion, array($indice.''.$key => $msg_validacion));
			}
			return $mensages_validacion;
        }else{
                return true;
             }
	}


///***************************************************************************************************************************
///***************************************************************************************************************************
   
   public function validarArrayRecetasColectivos(Request $request)
    {
        $errors = array(); 
        $almacen_id=$request->get('almacen_id');       

        $validacion = $this->ValidarMovimiento("", NULL, Input::json()->all(),$almacen_id);
		if(is_array($validacion))
        {
			return Response::json(['error' => $validacion], HttpResponse::HTTP_CONFLICT);
		}
        $datos = (object) Input::json()->all();	
        $success = false;

        if(property_exists($datos, "recetas"))
        {
            if(count($datos->insumos) > 0 )
            {
                $detalle = array_filter($datos->insumos, function($v){return $v !== null;});
                foreach ($detalle as $key => $value)
                {
                    $validacion_insumos = $this->ValidarInsumos($key, NULL, $value, $tipo);
                    if($validacion_insumos != "")
                        {
                            array_push($errors, $validacion_insumos);
                        }
                }

            }else{
                    array_push($errors, array(array('recetas' => array('objeto_recetas_vacio'))));
                 }
        }else{
                array_push($errors, array(array('recetas' => array('no_existe_objeto_recetas'))));
             }

 

        return Response::json(array("status" => 200,"messages" => "Estatus Entrega Pedidos"), 200);
    }

///***************************************************************************************************************************
///***************************************************************************************************************************
   public function sincronizarRecetasColectivos(Request $request)
    {
        return Response::json(array("status" => 200,"messages" => "Estatus Entrega Pedidos"), 200);
    }

///***************************************************************************************************************************
///***************************************************************************************************************************
   
	 ///**************************************************************************************************************************
///**************************************************************************************************************************
   
    private function ValidarInsumos($key, $id, $request,$tipo){ 
        $mensajes = [
                        'required'      => "required",
                        'email'         => "email",
                        'unique'        => "unique",
                        'integer'       => "integer",
                        'min'           => "min"
                    ];

        if($tipo=='RECETA')
                {
                    $reglas = [
                                'clave_insumo_medico'     => 'required',
                                'cantidad_recetada'       => 'required|integer',
                                'cantidad_surtida'        => 'required|integer',
                                'dosis'                   => 'required|integer',
                                'frecuencia'              => 'required|integer',
                                'duracion'                => 'required|integer'
                              ];
                }else{ // ES COLECTIVO
                        $reglas = [
                                    'clave_insumo_medico'    => 'required',
                                    'cantidad_solicitada'    => 'required|numeric',
                                    'cantidad_surtida'       => 'required|integer',
                                  ];
                     }
                     
        $v = \Validator::make($request, $reglas, $mensajes );
        $mensages_validacion = array();

        if($tipo=='S')
        {
            foreach($request['lotes'] as $i => $lote)
            {
                $lote = (object) $lote;
                $lote_check =  Stock::where('clave_insumo_medico',$request['clave'])->find($lote->id);

                $v->after(function($v) use($lote,$lote_check,$i)
                {
                    ///****************************************************************************************
                    if($lote_check)
                    {
                        if($lote->cantidad <= 0)
                        {
                            $v->errors()->add('lote_'.$lote->id.'_', 'cantidad_invalida');
                        }

                        /// validar cantidad solicitada en req contra lo del find
                        if($lote->modo_salida=='N')
                        {
                            if($lote->cantidad <= $lote_check->existencia)
                            {
                            }else{
                                    $v->errors()->add('lote_'.$lote->id.'_', 'lote_insuficiente');
                                 }
                        }else {
                                if($lote->cantidad <= $lote_check->existencia_unidosis)
                                {
                                }else{
                                        $v->errors()->add('lote_'.$lote->id.'_', 'lote_insuficiente');
                                    }
                               }
                        

                    }else{
                            if($lote->cantidad <= 0)
                            {
                                $v->errors()->add('lote_'.$lote->id.'_', 'cantidad_invalida');
                            }

                            if(property_exists($lote, 'nuevo'))
                            {
                                // verificar si existe lote,codigo, barra y fecha cad
                            }else{
                                    $v->errors()->add('lote_'.$lote->id.'_', 'no_existe'); 
                                 }
                         }
                  ///****************************************************************************************    
                     
                  

                });
            }    
        }// FIN IF TIPO SALIDA

        if ($v->fails())
        {
            foreach ($v->errors()->messages() as $indice => $item)  // todos los mensajes de todos los campos
            {
                $msg_validacion = array();
                    foreach ($item as $msg)
                    {
                        array_push($msg_validacion, $msg);
                    }
                    array_push($mensages_validacion, array($indice.''.$key => $msg_validacion));
			}
           
			return $mensages_validacion;
        }else{
                return ;
             }
	}

///***************************************************************************************************************************
///***************************************************************************************************************************
  ///                  F U N C I O N      C O N S E G U I R      P R E C I O    I N S U M O  
///**************************************************************************************************************************
 public function conseguirPrecio($clave_insumo_medico)
 {
    $precio_unitario = 0; 
    $iva             = 0;
    $tipo_insumo_id  = 0;
    $response = array();

    $contrato_precio = ContratoPrecio::where('insumo_medico_clave',$clave_insumo_medico)->first();
                            if($contrato_precio){
                                $tipo_insumo_id = $contrato_precio->tipo_insumo_id;
                                $precio_unitario = $contrato_precio->precio;
                                if($contrato_precio->tipo_insumo_id == 3){
                                    $iva = $precio_unitario - ($precio_unitario/1.16 );
                                }
                            }

    $response['tipo_insumo_id'] = $tipo_insumo_id;
    $response['precio_unitario'] = $precio_unitario;
    $response['iva']             = $iva;

    return (object)$response;
 }
////***************        GUARDAR ESTADISTICA PARA NEGACION DE INSUMO    ***************************************************
///**************************************************************************************************************************

public function guardarEstadisticaNegacion($clave_insumo_medico,$almacen_id,$cantidad_negada)
    {

        $negacion_resusitada = 0;
        $precios             = $this->conseguirPrecio($clave_insumo_medico);
        $insumo              = Insumo::datosUnidosis()->where('clave',$clave_insumo_medico)->first();
        $cantidad_x_envase   = $insumo->cantidad_x_envase;

                            // Si no existe registro para resusitar, se comprueba existencia de registro activo
                            $negacion = NegacionInsumo::where('almacen_id',$almacen_id)->where('clave_insumo_medico',$clave_insumo_medico)->first();
                            if(!$negacion)
                            {
                                // Busqueda de registro de negación a resusitar para el insumo negado
                                $negacion = DB::table('negaciones_insumos')->where('clave_insumo_medico',$clave_insumo_medico)->where('deleted_at','!=',NULL)->first();
                                // Si existe registro muerto se resusita
                                if($negacion)
                                {
                                    DB::update("update negaciones_insumos set deleted_at = null where id = '".$negacion->id."'");
                                    $negacion_resusitada = 1;
                                } 
                            }

                            $negacion_insumo = NULL;
                            $almacen = Almacen::find($almacen_id);
                            ///*************************************************************************************************
                            // Encontrar ultima entrada al almacen del insumo negado
                            $ultima_entrada                  = NULL;
                            $cantidad_entrada                = 0;
                            $cantidad_entrada_unidosis       = 0;

                            $ultima_entrada_insumo = DB::table('movimientos')
                                            ->join('movimiento_insumos', 'movimientos.id', '=', 'movimiento_insumos.movimiento_id')
                                            ->select('movimientos.*', 'movimiento_insumos.clave_insumo_medico', 'movimiento_insumos.cantidad', 'movimiento_insumos.cantidad_unidosis')
                                            ->where('movimientos.almacen_id',$almacen_id)
                                            ->where('movimiento_insumos.clave_insumo_medico',$clave_insumo_medico)
                                            ->where('movimientos.tipo_movimiento_id',1)
                                            ->orderBy('created_at','DESC')
                                            ->first();

                            if($ultima_entrada_insumo)
                            {
                                $ultima_entrada                = $ultima_entrada_insumo->created_at;
                                $cantidad_entrada              = $ultima_entrada_insumo->cantidad;
                                $cantidad_entrada_unidosis     = $ultima_entrada_insumo->cantidad_unidosis;
                            }
                            ///**************************************************************************************************************************
                            $cantidad_negada          = $cantidad_negada;
                            $cantidad_negada_unidosis = ($cantidad_negada * $cantidad_x_envase);
                                 
                            // Si existe registro de negación de insumo ( activo ó resusitado )
                            if($negacion)
                            { 
                                $negacion_insumo  = NegacionInsumo::find($negacion->id);

                                if($negacion_resusitada == 1)
                                {
                                    $negacion_insumo->fecha_inicio                  = date("Y-m-d");
                                    $negacion_insumo->fecha_fin                     = date("Y-m-d");
                                    $negacion_insumo->cantidad_acumulada            = $cantidad_negada;
                                    $negacion_insumo->cantidad_acumulada_unidosis   = $cantidad_negada_unidosis;
                                    $negacion_insumo->ultima_entrada                = $ultima_entrada;
                                    $negacion_insumo->cantidad_entrada              = $cantidad_entrada;
                                    $negacion_insumo->cantidad_entrada_unidosis     = $cantidad_entrada_unidosis;

                                }else{
                                        $negacion_insumo->cantidad_acumulada            = $negacion_insumo->cantidad_acumulada + $cantidad_negada;
                                        $negacion_insumo->cantidad_acumulada_unidosis   = $negacion_insumo->cantidad_acumulada_unidosis + $cantidad_negada_unidosis;
                                        $negacion_insumo->fecha_fin                     = date("Y-m-d");
                                        $negacion_insumo->ultima_entrada                = $ultima_entrada;
                                        $negacion_insumo->cantidad_entrada              = $cantidad_entrada;
                                        $negacion_insumo->cantidad_entrada_unidosis     = $cantidad_entrada_unidosis; 
                                     }

                                $negacion_insumo->save();     
                                
                            }else{
                                    $negacion_insumo = new NegacionInsumo;

                                    $negacion_insumo->clave_insumo_medico           = $clave_insumo_medico;
                                    $negacion_insumo->clues                         = $almacen->clues;
                                    $negacion_insumo->almacen_id                    = $almacen_id;
                                    $negacion_insumo->tipo_insumo                   = $precios->tipo_insumo_id;
                                    $negacion_insumo->fecha_inicio                  = date("Y-m-d");
                                    $negacion_insumo->fecha_fin                     = date("Y-m-d");
                                    $negacion_insumo->cantidad_acumulada            = $cantidad_negada;
                                    $negacion_insumo->cantidad_acumulada_unidosis   = $cantidad_negada_unidosis;
                                    $negacion_insumo->ultima_entrada                = $ultima_entrada;
                                    $negacion_insumo->cantidad_entrada              = $cantidad_entrada;
                                    $negacion_insumo->cantidad_entrada_unidosis     = $cantidad_entrada_unidosis;

                                    $negacion_insumo->save();
                                 }
                        




    }
////*************************************************************************************************************************
////*************************************************************************************************************************     

}
