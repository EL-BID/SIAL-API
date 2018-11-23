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


/** 
* Controlador Ajuste Más Inventario
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-03-22
*
* Controlador `AjusteMasInventario`: Controlador  para los ajustes más de inventario de insumos medicos
*
*/
class AjusteMasInventarioController extends Controller
{
     
    public function index(Request $request)
    {
 ///****************************************************************************************************************************************
        $parametros = Input::only('q','page','per_page','almacen','tipo','fecha_desde','fecha_hasta','usuario','turno','servicio');
        $parametros['almacen'] = $request->get('almacen_id');

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }  
        
        $almacen = Almacen::find($parametros['almacen']);
        $movimientos = null;
        $data = null;

        $movimientos = DB::table("movimientos AS mov")
                             ->leftJoin('usuarios AS users', 'users.id', '=', 'mov.usuario_id')
                             ->select('mov.*','users.nombre')
                             ->where('mov.almacen_id',$parametros['almacen'])
                             ->where('mov.tipo_movimiento_id',6)
                             ->orderBy('mov.updated_at','DESC');

        if( ($parametros['fecha_desde']!="") && ($parametros['fecha_hasta']!="") )
        {
            $movimientos = $movimientos->where('mov.fecha_movimiento','>=',$parametros['fecha_desde'])
                                       ->where('mov.fecha_movimiento','<=',$parametros['fecha_hasta']);
        }else{
                if( $parametros['fecha_desde'] != "" )
                {
                    $movimientos = $movimientos->where('mov.fecha_movimiento','>=',$parametros['fecha_desde']);
                }
                if( $parametros['fecha_hasta'] != "" )
                {
                    $movimientos = $movimientos->where('mov.fecha_movimiento','<=',$parametros['fecha_hasta']);
                }
             }   

        
        if ($parametros['usuario'] != "")
        {
            $movimientos = $movimientos->where(function($query) use ($parametros) {
                                                $query->where('users.nombre','LIKE',"%".$parametros['usuario']."%");
                                                });
        }

        $movimientos = $movimientos->get();

        $data = array();
        foreach($movimientos as $mov)
        {
            $movimiento_response = Movimiento::with('movimientoUsuario')->where('id',$mov->id)->first();

            $cantidad_claves  = MovimientoAjuste::where('movimiento_id',$movimiento_response->id)->distinct('clave_insumo_medico')->count();

            if($cantidad_claves  == NULL){ $cantidad_claves  = 0 ; }
            $movimiento_response->numero_claves  = $cantidad_claves;

            array_push($data,$movimiento_response);
        }


        $indice_adds = 0;

        if(isset($parametros['page']))
        {
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////            
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $itemCollection = new Collection($data);
            $perPage = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

            $indice_adds = count($currentPageItems);

            ///************************************************************************
                    $dataz;
                    if($currentPage > 1)
                    {
                        $tempdata = $currentPageItems;
                        foreach ($tempdata as $key => $value)
                        { $dataz[] = $value; }
                    }
                    else
                    {   $dataz = $currentPageItems; }
            ///************************************************************************
            $data2= new LengthAwarePaginator($dataz , count($itemCollection), $perPage);
    
            $data2->setPath($request->url());
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } 


        
        if(count($data) <= 0)
        { 
             return Response::json(array("status" => 404,"messages" => "No hay resultados","data" => $data), 200);
        } 
        else{
                ///***************************************************************************************************************************************
                $total = count($data);
                ////**************************************************************************************************************************************
            return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data2, "total" => $total), 200);
            
        }
 ///****************************************************************************************************************************************       
        
    }

  ////                  S  T  O  R  E
 ////********************************************************************************************************************************************

    public function store(Request $request)
    {
        $parametros = Input::only('q','page','per_page','almacen');
        $parametros['almacen'] = $request->get('almacen_id');
        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }  
        
        $almacen = Almacen::find($parametros['almacen']);
        $almacen_id = $almacen->id;

        $input_data = (object)Input::json()->all();
        $errors     = array();
////****************************************************************************************************************************************

        if(property_exists($input_data, "insumos"))
        {
                    if(count($input_data->insumos) > 0 )
                    {
                        $insumos = array_filter($input_data->insumos, function($v){return $v !== null;});
                        foreach ($insumos as $key => $insumo)
                            {
                                $validacion_insumos = $this->ValidarInsumos($key, NULL, $insumo);
                                if($validacion_insumos != "")
                                    {
                                        array_push($errors, $validacion_insumos);
                                    }
                            }
                    }else{
                            array_push($errors, array(array('insumos' => array('no_items_insumos'))));
                         }
        }else{
                        array_push($errors, array(array('insumos' => array('no_existe_insumos'))));
             }

        if( count($errors) > 0 )
        {
            return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
        } 

////*****************************************************************************************************************************************
            $success = false;
            DB::beginTransaction();
            try{
                        $movimiento = new Movimiento;
                        $this->ejecutarAjusteMas($input_data, $movimiento,$almacen_id);
                        $success = true;
                } catch (\Exception $e) {   $success = false;
                                            DB::rollback();
                                            return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                                       } 
                if ($success)
                {
                    DB::commit();
                    $ma = Movimiento::with('movimientoAjuste')->find($movimiento->id);
                    return Response::json(array("status" => 201,"messages" => "Creado","data" => $ma), 201);
                } 
                else{
                        DB::rollback();
                        return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
                    }
////*****************************************************************************************************************************************

    }


///*************************************************************************************************************************************
///*************************************************************************************************************************************

/////                             S    H    O    W 
///*************************************************************************************************************************************
///*************************************************************************************************************************************

    public function show($id)
    {

        $movimiento = Movimiento::with('movimientoUsuario')->find($id);
        if(!$movimiento){
			return Response::json(array("status" => 404,"messages" => "No se encuentra el ajuste más solicitado"), 200);
		} 

        $movimiento = (object) $movimiento;
        $insumos    = MovimientoAjuste::where('movimiento_id',$movimiento->id)->groupBy('clave_insumo_medico')->get();

        $array_insumos = array();

        foreach ($insumos as $key => $insumo)
        {   
            $insumo = (object) $insumo;

            $array_lotes = array();
            $objeto_insumo = Insumo::conDescripciones()->find($insumo->clave_insumo_medico);
            $objeto_insumo->load('informacionAmpliada');

            $lotes_ajustados = MovimientoAjuste::where('movimiento_id',$movimiento->id)->where('clave_insumo_medico',$insumo->clave_insumo_medico)->get();
            foreach ($lotes_ajustados as $key => $lote_ajustado)
            {
                $lote_ajustado = (object) $lote_ajustado;

                $objeto_lote = Stock::find($lote_ajustado->stock_id);
                $objeto_lote->nuevo               = $lote_ajustado->lote_nuevo;
                $objeto_lote->existencia_anterior = $lote_ajustado->existencia_anterior;
                $objeto_lote->nueva_existencia    = $lote_ajustado->nueva_existencia;
                            
                array_push($array_lotes,$objeto_lote);
            }
            $objeto_insumo->lotes = $array_lotes;
            array_push($array_insumos,$objeto_insumo);
            
         }

         $movimiento->insumos = $array_insumos;



        

     return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $movimiento), 200);



    }

///***************************************************************************************************************************
///***************************************************************************************************************************

    public function update(Request $request, $id)
    {
 
    }
     
    public function destroy($id)
    {
        
    }


///**************************************************************************************************************************
///**************************************************************************************************************************
   
    private function ValidarInsumos($key, $id, $insumo)
    { 
        $mensajes = [
                        'required'      => "required",
                        'email'         => "email",
                        'unique'        => "unique",
                        'integer'       => "integer",
                        'min'           => "min"
                    ];

         $reglas = [
                    'clave'   => 'required',
                   ];
                
                     
        $v = \Validator::make($insumo, $reglas, $mensajes );
        $mensages_validacion = array();
 

        foreach($insumo['lotes'] as $i => $lote)
        {
                $lote = (object) $lote;
                
                $v->after(function($v) use($lote,$insumo,$i)
                {

                    ///****************************************************************************************
                    if(property_exists($lote, 'nuevo'))
                    {
                        if($lote->nuevo == 0)
                        {
                            $lote_check =  Stock::where('clave_insumo_medico',$insumo['clave'])->find($lote->id);
                            if($lote_check)
                            {
                                if(property_exists($lote, 'nueva_existencia'))
                                    {
                                        if($lote_check->existencia > $lote->nueva_existencia)
                                        {
                                            $v->errors()->add('lote_'.$lote->id.'_', 'cantidad_invalida');
                                        }
                                    }else{
                                            $v->errors()->add('lote_'.$lote->id.'_', 'nueva_existencia_requerido'); 
                                         }
                                
                            }else{
                                    $v->errors()->add('lote_'.$lote->id.'_', 'no_existe_lote_enviado'); 
                                 }

                        }else{  /// cuando es un lote nuevo
                                if(property_exists($lote, 'nueva_existencia'))
                                    {
                                        if($lote->nueva_existencia < 0)
                                        {
                                            $v->errors()->add('lote_'.$lote->id.'_', 'nueva_existencia_invalida');
                                        }
                                    }else{
                                            $v->errors()->add('lote_'.$lote->id.'_', 'nueva_existencia_requerido'); 
                                         }
                             }

                    }else{
                                $v->errors()->add('lote_'.$lote->id.'_', 'campo_nuevo_requerido'); 
                         }
                     
                  ///****************************************************************************************    
                });
        }    

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


///***************************************************************************************************************************************************   
///***************************************************************************************************************************************************
   
    public function ejecutarAjusteMas($input_data,$movimiento, $almacen_id )
    {
        $movimiento->almacen_id          = $almacen_id; 
        $movimiento->tipo_movimiento_id  = 6;
        $movimiento->status              = "FI";
        $movimiento->fecha_movimiento    = date("Y-m-d");
        $movimiento->observaciones       = $input_data->observaciones;
        $movimiento->save();

         if(property_exists($input_data, "insumos"))
        {
            foreach($input_data->insumos as $insumo)
            {
                $insumo              = (object) $insumo;
                $insumo_info         = Insumo::datosUnidosis()->where('clave',$insumo->clave)->first();
                $cantidad_x_envase   = $insumo_info->cantidad_x_envase;

                foreach($insumo->lotes as $lote)
                {
                    $lote = (object) $lote;

                    $movimiento_ajuste = new MovimientoAjuste();
                    $movimiento_ajuste->movimiento_id    = $movimiento->id;

                    if($lote->ajuste == 1)
                    {
                       if($lote->nuevo == 1)
                        {
                            $lote_temp = Stock::where('lote',$lote->lote)
                                            ->where('fecha_caducidad',$lote->fecha_caducidad)
                                            ->where('codigo_barras',$lote->codigo_barras)
                                            ->where('clave_insumo_medico',$insumo->clave)
                                            ->where('almacen_id',$almacen_id)
                                            ->orderBy('created_at','DESC')->first();
                            if($lote_temp)
                            {
                                $movimiento_ajuste->lote_nuevo                   = 0;
                                $movimiento_ajuste->stock_id                     = $lote_temp->id;
                                $movimiento_ajuste->clave_insumo_medico          = $insumo->clave;
                                $movimiento_ajuste->existencia_anterior          = $lote_temp->existencia;
                                $movimiento_ajuste->existencia_unidosis_anterior = $lote_temp->existencia_unidosis;
                                $movimiento_ajuste->nueva_existencia             = $lote->nueva_existencia;
                                $movimiento_ajuste->nueva_existencia_unidosis    = ($lote_temp->unidosis_sueltas ) + ($lote->nueva_existencia * $cantidad_x_envase);
                                $movimiento_ajuste->observaciones                = "";
                                $movimiento_ajuste->save();

                                $lote_temp->existencia          = $lote_temp->existencia + $lote->nueva_existencia;
                                $lote_temp->existencia_unidosis = $lote_temp->existencia_unidosis + ($lote->nueva_existencia * $cantidad_x_envase);
                                $lote_temp->save();                                   
                            }else{
                                        $lote_nuevo = new Stock();
                                        
                                        $lote_nuevo->almacen_id          = $almacen_id;
                                        $lote_nuevo->clave_insumo_medico = $insumo->clave;
                                        $lote_nuevo->marca_id            = NULL;
                                        $lote_nuevo->lote                = $lote->lote;
                                        $lote_nuevo->fecha_caducidad     = $lote->fecha_caducidad;
                                        $lote_nuevo->codigo_barras       = $lote->codigo_barras;                     
                                        $lote_nuevo->existencia          = $lote->nueva_existencia;
                                        $lote_nuevo->existencia_unidosis = $lote->nueva_existencia * $cantidad_x_envase;
                                        $lote_nuevo->unidosis_sueltas    = 0;
                                        $lote_nuevo->envases_parciales   = 0;
                                        $lote_nuevo->save();

                                        $movimiento_ajuste->lote_nuevo                   = 1;
                                        $movimiento_ajuste->stock_id                     = $lote_nuevo->id;
                                        $movimiento_ajuste->clave_insumo_medico          = $insumo->clave;
                                        $movimiento_ajuste->existencia_anterior          = 0;
                                        $movimiento_ajuste->existencia_unidosis_anterior = 0;
                                        $movimiento_ajuste->nueva_existencia             = $lote->nueva_existencia;
                                        $movimiento_ajuste->nueva_existencia_unidosis    = $lote->nueva_existencia * $cantidad_x_envase;
                                        $movimiento_ajuste->observaciones                = "";
                                        $movimiento_ajuste->save(); 
                                    }
                        }else{
                                $lote_ajustar = Stock::find($lote->id);

                                $movimiento_ajuste->lote_nuevo                   = 0;
                                $movimiento_ajuste->stock_id                     = $lote_ajustar->id;
                                $movimiento_ajuste->clave_insumo_medico          = $insumo->clave;
                                $movimiento_ajuste->existencia_anterior          = $lote_ajustar->existencia;
                                $movimiento_ajuste->existencia_unidosis_anterior = $lote_ajustar->existencia_unidosis;
                                $movimiento_ajuste->nueva_existencia             = $lote->nueva_existencia;
                                $movimiento_ajuste->nueva_existencia_unidosis    = ($lote_ajustar->unidosis_sueltas ) + ($lote->nueva_existencia * $cantidad_x_envase);
                                $movimiento_ajuste->observaciones                = "";
                                $movimiento_ajuste->save();

                                $lote_ajustar->existencia          = $lote->nueva_existencia;
                                $lote_ajustar->existencia_unidosis = $lote->nueva_existencia * $cantidad_x_envase;
                                $lote_ajustar->save();
                            }///fin else ( cuando es un lote-Stock existtente )

                    }/// fin if se ajustará el lote ( nuevo ó existente )

                }/// fin foreach recorrido de lotes
            
            }/// fin foreach recorrido de insumos

        }else{

             }
    }

////**************************************************************************************************************************************************
////**************************************************************************************************************************************************
    public function paginadorMaster($items,$perPage)
    {
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage; 

        // Get only the items you need using array_slice 
        $itemsForCurrentPage = array_slice($items, $offSet, $perPage, true);

        

        return new LengthAwarePaginator($itemsForCurrentPage, count($items), $perPage,Paginator::resolveCurrentPage(), array('path' => Paginator::resolveCurrentPath()));
    }
///****************************************************************************************************************************************************
}
