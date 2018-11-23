<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;



use App\Models\InicializacionInventario;
use App\Models\InicializacionInventarioDetalle;
use App\Models\Movimiento;
use App\Models\Stock;
use App\Models\MovimientoInsumos;
use App\Models\MovimientoAjuste;
use App\Models\TiposMovimientos;
use App\Models\Insumo;
use App\Models\MovimientoMetadato;
use App\Models\MovimientoDetalle;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\RecetaMovimiento;
use App\Models\ContratoPrecio;
use App\Models\NegacionInsumo;
use App\Models\Almacen;
use App\Models\Programa;
use App\Models\Pedido;
use App\Models\PedidoInsumo;
use App\Models\PedidoMetadatoCC;
use App\Models\PedidoCcClues;



/** 
* Controlador
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-03-22
*
* Controlador ``: Controlador 
*
*/
class PedidoCompraConsolidadaDafController extends Controller
{
     
    public function index(Request $request)
    {
        $parametros = Input::only('q','page','per_page','clues','almacen');
     
        $pedidos  = Pedido::with("metadatoCompraConsolidada")
                            ->where('tipo_pedido_id','PCC')
                            ->where('pedido_padre',NULL)
                            ->where( function($q){
                                $q->where('status','CONCENTRADO')->orWhere('status','AJUSTADO');
                            });

        if ($parametros['q'])
        {
            $pedidos =  $pedidos->where(function($query) use ($parametros) {
                 $query->where('id','LIKE',"%".$parametros['q']."%")->orWhere('descripcion','LIKE',"%".$parametros['q']."%")->orWhere('folio','LIKE',"%".$parametros['q']."%");
             });
        }
        $pedidos = $pedidos->orderBy('updated_at','DESC');
 //////*********************************************************************************************************
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $pedidos = $pedidos->paginate($resultadosPorPagina);
        } else {
                    $pedidos = $pedidos->get();
               }
 //////*********************************************************************************************************
 foreach ($pedidos as $key => $pedido){
                 $pedido->estatus = $pedido->status;
 }
 //////*********************************************************************************************************


        if(count($pedidos) <= 0){

            return Response::json(array("status" => 404,"messages" => "No se han aperturado Pedidos de Compra Consolidada","data" => $pedidos), 200);
        } 
        else{
                return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $pedidos, "total" => count($pedidos)), 200);
            }

 
///****************************************************************************************************************************************      
///*******************************************************************************************************************************************      
   }





//////                   S   T   O   R   E
///********************************************************************************************************************************************
///*****************************************************************************************************************************************

public function store(Request $request)
{
    

 }  // fin store method



///*************************************************************************************************************************************
/////                             S    H    O    W 
///*************************************************************************************************************************************


    public function show($id)
    {
        $pedido = Pedido::with('metadatoCompraConsolidada','insumos','unidadesMedicas')->find($id);
        if(!$pedido){
			return Response::json(array("status" => 404,"messages" => "No se encuentra el pedido solicitado"), 200);
		}

        foreach($pedido->unidadesMedicas as $key => $um)
        {
            $pedido_hijo = Pedido::where('clues',$um->clues)->where('pedido_padre',$pedido->id)->first();
            $pedido->unidadesMedicas[$key]->pedido = $pedido_hijo;
        } 

    $pedido->estatus = $pedido->status;
     return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $pedido), 200);



    }



///***************************************************************************************************************************
///***************************************************************************************************************************
///***************************************************************************************************************************
///***************************************************************************************************************************

public function update(Request $request, $id)
{
    $parametros = Input::only('q','page','per_page');      
    $input_data = (object)Input::json()->all();
    $servidor_id = property_exists($input_data, "servidor_id") ? $input_data->servidor_id : env('SERVIDOR_ID');

    $errors     = array();
    $nuevo      = 0;

    $presupuesto_compra               = 0;
    $presupuesto_causes               = 0;
    $presupuesto_no_causes            = 0;
    $presupuesto_causes_asignado      = 0;
    $presupuesto_causes_disponible    = 0;
    $presupuesto_no_causes_asignado   = 0;
    $presupuesto_no_causes_disponible = 0;
///*****************************************************************************************************************************************
$pedido = Pedido::with('metadatoCompraConsolidada','unidadMedica')->find($id);
    if(!$pedido){
			return Response::json(array("status" => 404,"messages" => "No se encuentra el pedido solicitado"), 200);
		} 


///*****************************************************************************************************************************************
if($input_data->estatus=="AJUSTADO")
{

}/// fin if INICIALIZADO
    
///*****************************************************************************************************************************************
if( count($errors) > 0 )
{
    return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
} 
///*****************************************************************************************************************************************
    
$success = false;
DB::beginTransaction();
try{
////****************************************************************************************************************************************
        
        $pedido->clues          = $input_data->clues;
        $pedido->tipo_pedido_id = "PCC";
        $pedido->folio          = "";
        $pedido->fecha          = $input_data->fecha;
        $pedido->status         = $input_data->estatus;
        $pedido->save();

        $insumos = $input_data->insumos;
        PedidoInsumo::where('pedido_id',$pedido->id)->delete();

        foreach ($insumos as $key => $insumo)
        {
            $insumo = (object) $insumo;

            $precio_base = DB::table('precios_base')
                                ->select('precios_base.anio', 'precios_base_detalles.*')
                                ->leftJoin('precios_base_detalles', function ($join){
                                    $join->on('precios_base.id', '=', 'precios_base_detalles.precio_base_id');
                                })->where('precios_base.activo', 1)
                                  ->where('precios_base_detalles.insumo_medico_clave', $insumo->insumo_medico_clave)
                                  ->first();
            if($precio_base)
            {

                $insumo_db = PedidoInsumo::withTrashed()
                                        ->where('pedido_id',$pedido->id)
                                        ->where('insumo_medico_clave',$insumo->insumo_medico_clave)
                                        ->first();
                if($insumo_db)
                {   $insumo_db->restore(); }else{ $insumo_db = new PedidoInsumo; }

                $insumo_db->pedido_id             = $pedido->id;
                $insumo_db->insumo_medico_clave   = $insumo->insumo_medico_clave;
                //$insumo_db->cantidad_enviada      = 0;
                $insumo_db->cantidad_solicitada   = $insumo->cantidad_solicitada;
                $insumo_db->cantidad_recibida     = 0;
                $insumo_db->precio_unitario       = $insumo->precio_unitario;
                //$insumo_db->monto_enviado         = ( $insumo->precio_unitario * $insumo->cantidad_solicitada );
                $insumo_db->monto_solicitado      = ( $insumo->precio_unitario * $insumo->cantidad_solicitada );
                $insumo_db->monto_recibido        = 0;
                $insumo_db->save();
            }

            if($precio_base->es_causes == 1)
                {
                    $presupuesto_causes_asignado += ( $insumo->precio_unitario * $insumo->cantidad_solicitada );
                }else{
                        $presupuesto_no_causes_asignado += ( $insumo->precio_unitario * $insumo->cantidad_solicitada );
                     }
        }

/*
        $pedido_padre_dam = Pedido::with('unidadesMedicas')->find($pedido->pedido_padre);
        if ($pedido_padre_dam)
        {
            $pedido_padre_dam = (object) $pedido_padre_dam;
            
            foreach ($pedido_padre_dam->unidadesMedicas as $key => $um)
            {
                if($um->clues == $pedido->clues)
                {
                    $pedido_cc_clues = PedidoCcClues::find($um->id);
                    $pedido_cc_clues->estatus                          = $pedido->status;
                    $pedido_cc_clues->presupuesto_causes_asignado      = $presupuesto_causes_asignado;
                    $pedido_cc_clues->presupuesto_no_causes_asignado   = $presupuesto_no_causes_asignado;
                    $pedido_cc_clues->presupuesto_causes_disponible    = $pedido_cc_clues->presupuesto_causes - $presupuesto_causes_asignado;
                    $pedido_cc_clues->presupuesto_no_causes_disponible = $pedido_cc_clues->presupuesto_no_causes - $presupuesto_no_causes_asignado;
                    $pedido_cc_clues->save();
                }
            }
        }
*/

////*****************************************************************************************************************************************

        $success = true;
        } catch (\Exception $e) {   
                                    $success = false;
                                    DB::rollback();
                                    return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                                } 
        if ($success)
        {
            DB::commit();
            $pedido->estatus         = $pedido->status;
            return Response::json(array("status" => 201,"messages" => "Pedido para Compra Consolidadda creado correctamente","data" => $pedido), 201);
        }else{
                DB::rollback();
                return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
             }
////*****************************************************************************************************************************************
    }

/*
    public function update(Request $request, $id)
    {

    $parametros = Input::only('q','page','per_page');
          
    $input_data = (object)Input::json()->all();
    $servidor_id = property_exists($input_data, "servidor_id") ? $input_data->servidor_id : env('SERVIDOR_ID');

    $errors     = array();
    $nuevo      = 0;

    $pedido = Pedido::with('metadatoCompraConsolidada','unidadesMedicas')->find($id);
        if(!$pedido){
			return Response::json(array("status" => 404,"messages" => "No se encuentra el pedido solicitado"), 200);
		} 



if($input_data->estatus=="AJUSTADO")
{
    foreach ($input_data->unidades_medicas as $key => $um)
    {
       $validacion_unidad = $this->validarUnidadMedica($um);
       if($validacion_unidad != "")
        {
            array_push($errors, $validacion_unidad);
        }
    }
    $validacion_metadatos = $this->validarMetadatos($input_data->metadato_compra_consolidada);
    if($validacion_metadatos != "")
        {
            array_push($errors, $validacion_metadatos);
        }

    $validacion_pedido = $this->validarPedidoDam((array)$input_data);
    if($validacion_pedido != "")
        {
            array_push($errors, $validacion_pedido);
        }
 }/// fin if INICIALIZADO
    

if( count($errors) > 0 )
{
    return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
} 

    
$success = false;
DB::beginTransaction();
try{

        
        $pedido->clues          = "";
        $pedido->tipo_pedido_id = "PCC";
        $pedido->folio          = "";
        $pedido->fecha          = date('Y-m-d');
        $pedido->status         = $input_data->estatus;
        $pedido->save();

        $pedido_mcc = PedidoMetadatoCC::where('pedido_id',$pedido->id)->first();
        $pedido_mcc->pedido_id                        = $pedido->id;
        $pedido_mcc->programa_id                      = $input_data->metadato_compra_consolidada['programa_id'];
        $pedido_mcc->fecha_limite_captura             = $input_data->metadato_compra_consolidada['fecha_limite_captura'];
        $pedido_mcc->lugar_entrega                    = $input_data->metadato_compra_consolidada['lugar_entrega'];
        $pedido_mcc->presupuesto_compra               = $input_data->metadato_compra_consolidada['presupuesto_compra'];
        $pedido_mcc->presupuesto_causes               = $input_data->metadato_compra_consolidada['presupuesto_causes'];
        $pedido_mcc->presupuesto_causes_asignado      = $input_data->metadato_compra_consolidada['presupuesto_causes_asignado'];
        $pedido_mcc->presupuesto_causes_disponible    = $input_data->metadato_compra_consolidada['presupuesto_causes_disponible'];
        $pedido_mcc->presupuesto_no_causes            = $input_data->metadato_compra_consolidada['presupuesto_no_causes'];
        $pedido_mcc->presupuesto_no_causes_asignado   = $input_data->metadato_compra_consolidada['presupuesto_no_causes_asignado'];
        $pedido_mcc->presupuesto_no_causes_disponible = $input_data->metadato_compra_consolidada['presupuesto_no_causes_disponible'];
        $pedido_mcc->save();

        $unidades_medicas = $input_data->unidades_medicas;
        PedidoCcClues::where('pedido_id',$pedido->id)->delete();

        foreach ($unidades_medicas as $key => $um)
        {
            $um = (object) $um;
            if($um->clues != "")
            {    
                $pedido_cc_clues = PedidoCcClues::withTrashed()->where('clues',$um->clues)->where('pedido_id',$pedido->id)->first();
                if($pedido_cc_clues)
                {   $pedido_cc_clues->restore(); }else{  $pedido_cc_clues = new PedidoCcClues;   }

                $pedido_cc_clues->pedido_id                      =  $pedido->id;
                $pedido_cc_clues->clues                          =  $um->clues;
                $pedido_cc_clues->estatus                        =  $um->estatus;
                $pedido_cc_clues->presupuesto_clues              =  $um->presupuesto_clues;
                $pedido_cc_clues->presupuesto_causes             =  $um->presupuesto_causes;
                $pedido_cc_clues->presupuesto_no_causes          =  $um->presupuesto_no_causes;
                $pedido_cc_clues->save();

                if($input_data->estatus =="INICIALIZADO")
                {

                        $pedido_um = new Pedido;
                        $pedido_um->pedido_padre   = $pedido->id;
                        $pedido_um->clues          = $um->clues;
                        $pedido_um->tipo_pedido_id = "PCC";
                        $pedido_um->folio          = "";
                        $pedido_um->fecha          = date("Y-m-d");
                        $pedido_um->status         = "BR";
                        $pedido_um->save();

                        $pedido_um_mcc = new PedidoMetadatoCC;
                        $pedido_um_mcc->pedido_id                         = $pedido_um->id;
                        $pedido_um_mcc->programa_id                       = $input_data->metadato_compra_consolidada['programa_id'];
                        $pedido_um_mcc->fecha_limite_captura              = $input_data->metadato_compra_consolidada['fecha_limite_captura'];
                        $pedido_um_mcc->presupuesto_compra                = $um->presupuesto_clues;
                        $pedido_um_mcc->presupuesto_causes                = $um->presupuesto_causes;
                        $pedido_um_mcc->presupuesto_causes_asignado       = 0;
                        $pedido_um_mcc->presupuesto_causes_disponible     = $um->presupuesto_causes;
                        $pedido_um_mcc->presupuesto_no_causes             = $um->presupuesto_no_causes;
                        $pedido_um_mcc->presupuesto_no_causes_asignado    = 0;
                        $pedido_um_mcc->presupuesto_no_causes_disponible  = $um->presupuesto_no_causes;
                        $pedido_um_mcc->save();
                }
                
            }
        }



            $success = true;
        } catch (\Exception $e) {   
                                    $success = false;
                                    DB::rollback();
                                    return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                                } 
        if ($success)
        {
            DB::commit();
            $pedido->estatus         = $pedido->status;
            return Response::json(array("status" => 201,"messages" => "Pedido para Compra Consolidadda creado correctamente","data" => $pedido), 201);
        }else{
                DB::rollback();
                return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
             }

    }
     

     */



    public function destroy($id)
    {
        
    }
 

///**************************************************************************************************************************

private function validarUnidadMedica($request)
    { 
        $mensajes = [
                        'required'      => "Debe ingresar este campo de la UM.",
                        'integer'       => "Solo cantidades enteras.",
                        'numeric'       => "Debe ingresar un numero valido.",
                        'email'         => "formato de email invalido",
                        'unique'        => "unique",
                        'min'           => "La cantidad debe ser mayor de cero.",
                    ];
        $reglas = [
                        'clues'                         => 'required',
                        'estatus'                       => 'required',
                        'presupuesto_causes'            => 'required',
                        'presupuesto_no_causes'         => 'required',
                  ];
                         
        $v = \Validator::make($request, $reglas, $mensajes );
        $mensages_validacion = array();
 
        if ($v->fails())
        {
            foreach ($v->errors()->messages() as $indice => $item) 
            {
                $msg_validacion = array();
                array_push($mensages_validacion, $item);
			}  
			return $mensages_validacion;
        }else{
                return ;
             }
	}

///***************************************************************************************************************************
private function validarMetadatos($request)
    { 

        $mensajes = [
                        'required'      => "Debe ingresar este campo de Metadatos.",
                        'integer'       => "Solo cantidades enteras.",
                        'numeric'       => "Debe ingresar un numero valido.",
                        'email'         => "formato de email invalido",
                        'unique'        => "unique",
                        'min'           => "La cantidad debe ser mayor de cero.",
                    ];
        $reglas = [
                        'fecha_limite_captura'          => 'required',
                        'lugar_entrega'                 => 'required',
                        'presupuesto_compra'            => 'required',
                        'presupuesto_causes'            => 'required',
                        'presupuesto_no_causes'         => 'required',
                        'presupuesto_causes_asignado'        => 'required',
                        'presupuesto_no_causes_asignado'     => 'required',
                        'presupuesto_causes_disponible'      => 'required',
                        'presupuesto_no_causes_disponible'   => 'required',
                  ];
                         
        $v = \Validator::make($request, $reglas, $mensajes );
        $mensages_validacion = array();
 
        if ($v->fails())
        {
            foreach ($v->errors()->messages() as $indice => $item) 
            {
                $msg_validacion = array();
                array_push($mensages_validacion, $item);
			}  
			return $mensages_validacion;
        }else{
                return ;
             }
	}

///***************************************************************************************************************************


    private function validarPedidoDam($request)
    { 
        $mensajes = [
                        'required'      => "Debe ingresar este campo del Pedido."
                    ];
        $reglas = [
                        'tipo_pedido_id'  => 'required',
                        'estatus'         => 'required',
                        'fecha'           => 'required'
                  ];
                         
        $v = \Validator::make($request, $reglas, $mensajes );
        $mensages_validacion = array();
 
        if ($v->fails())
        {
            foreach ($v->errors()->messages() as $indice => $item)  // todos los mensajes de todos los campos
            {
                $msg_validacion = array();
                array_push($mensages_validacion, $item);
			}  
			return $mensages_validacion;
        }else{
                return ;
             }
	}
///***************************************************************************************************************************
 
 

////**************************************************************************************************************************************************
///****************************************************************************************************************************************************
public function concentrarPedidoDam(Request $request)
{
 
$success = false;
DB::beginTransaction();
try{
////****************************************************************************************************************************************    
  $input_data = (object)Input::json()->all();

  $insumos =   DB::table('pedidos_insumos')
                 ->select('pedidos_insumos.*',DB::raw('SUM(cantidad_solicitada) as total_cantidad'))
                 ->leftJoin('pedidos', function ($join){
                            $join->on('pedidos.id', '=', 'pedidos_insumos.pedido_id');
                 })->where('pedidos.pedido_padre', $input_data->id)
                   ->where('pedidos_insumos.deleted_at', NULL)
                   ->groupBy('pedidos_insumos.insumo_medico_clave')
                   ->get();
    
                foreach ($insumos as $key => $insumo)
                {
                    $insumo_db = new PedidoInsumo;
                    $insumo_db->pedido_id             = $input_data->id;
                    $insumo_db->insumo_medico_clave   = $insumo->insumo_medico_clave;
                    $insumo_db->cantidad_enviada      = 0;
                    $insumo_db->cantidad_solicitada   = $insumo->total_cantidad;
                    $insumo_db->cantidad_recibida     = 0;
                    $insumo_db->precio_unitario       = $insumo->precio_unitario;
                    $insumo_db->monto_enviado         = ( $insumo->precio_unitario * $insumo->total_cantidad );
                    $insumo_db->monto_solicitado      = ( $insumo->precio_unitario * $insumo->total_cantidad );
                    $insumo_db->monto_recibido        = 0;
                    $insumo_db->save();
                }
    $pedido = Pedido::find( $input_data->id );
    $pedido->status = "CONCENTRADO";
    $pedido->save();

    ////*****************************************************************************************************************************************
          $success = true;
        } catch (\Exception $e) {   
                                    $success = false;
                                    DB::rollback();
                                    return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                                } 
        if ($success)
        {
            DB::commit();
            $pedido->estatus         = $pedido->status;
            return Response::json(array("status" => 201,"messages" => "Pedido Concentrado creado correctamente","data" => $pedido), 201);
        }else{
                DB::rollback();
                return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
             }

    
}

////**************************************************************************************************************************************************
///***************************************************************************************************************************************************
public function verPedidoConcentradoDam($id)
{
 
   $pedido = Pedido::with('metadatoCompraConsolidada','insumos')->find($id);
        if(!$pedido){
			return Response::json(array("status" => 404,"messages" => "No se encuentra el pedido solicitado"), 200);
		} 

    $pedido->estatus = $pedido->status;
     return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $pedido), 200);



  
    foreach ($insumos as $key => $insumo)
    {
    }

}
////**************************************************************************************************************************************************
///****************************************************************************************************************************************************

}
