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
use Carbon\Carbon;
use DateTime;


use App\Models\Movimiento;
use App\Models\Stock;
use App\Models\MovimientoInsumos;
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
use App\Models\CluesTurno;
use App\Models\CluesServicio;
use App\Models\Turno;
use App\Models\Servicio;
use App\Models\Programa;
use App\Models\PersonalClues;
use App\Models\UnidadMedica;



/** 
* Controlador Movimientos
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-03-22
*
* Controlador `Movimientos`: Controlador  para el manejo de entradas y salidas
*
*/
class SalidaAlmacenStandardController extends Controller
{
     

    
    public function index(Request $request)
    {
        $parametros = Input::only('q','page','per_page','almacen','tipo','fecha_desde','fecha_hasta','recibe','turno','servicio');
        $parametros['almacen'] = $request->get('almacen_id');

        

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 409,"messages" => "Debe especificar un almacen."), 200);
        }  
        
        $almacen = Almacen::find($parametros['almacen']);
        $movimientos = NULL;
        $data = NULL;

        $movimientos = DB::table("movimientos AS mov")
                             ->leftJoin('movimiento_metadatos AS mm', 'mm.movimiento_id', '=', 'mov.id')
                             ->leftJoin('usuarios AS users', 'users.id', '=', 'mov.usuario_id')
                             ->select('mov.*','mm.servicio_id','mm.turno_id','users.nombre')
                             ->where('mov.almacen_id',$parametros['almacen'])
                             ->where('mov.tipo_movimiento_id',18)
                             ->where('mov.deleted_at',NULL)
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

        if ($parametros['turno'] != "")
        {
            $movimientos = $movimientos->where('mm.turno_id','=',$parametros['turno']);
        }
        if ($parametros['servicio'] != "")
        {
            $movimientos = $movimientos->where('mm.servicio_id','=',$parametros['servicio']);
        }
        if ($parametros['recibe'] != "")
        {
            $movimientos = $movimientos->where(function($query) use ($parametros) {
                                                $query->where('mm.persona_recibe','LIKE',"%".$parametros['recibe']."%");
                                                });
        }

        $movimientos = $movimientos->get();

        $data = array();
        foreach($movimientos as $mov)
        {
            $movimiento_response = Movimiento::with('movimientoMetadato','movimientoUsuario','movimientoReceta')
                                             ->where('id',$mov->id)->first();

            $cantidad_claves  = MovimientoInsumos::where('movimiento_id',$movimiento_response->id)->groupBy('clave_insumo_medico')->count();
            $cantidad_insumos = DB::table('movimiento_insumos')
                                    ->where('movimiento_id', '=', $movimiento_response->id)
                                    ->where('movimiento_insumos.deleted_at',NULL)->sum('cantidad');

            if($cantidad_claves  == NULL){ $cantidad_claves  = 0 ; }
            if($cantidad_insumos == NULL){ $cantidad_insumos = 0 ; }

            $movimiento_response->numero_claves  = $cantidad_claves;
            $movimiento_response->numero_insumos = $cantidad_insumos;

            $movimiento_response->estatus = $movimiento_response->status;

            

            

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
        }else{
               $indice_adds = count($data);
             } 


        
        if(count($data) <= 0)
        { 
            ///***************************************************************************************************************************************
                $movimientos_all = Movimiento::with('movimientoMetadato','movimientoUsuario')
                                            ->where('tipo_movimiento_id',$parametros['tipo'])
                                            ->where('almacen_id',$parametros['almacen'])
                                            ->orderBy('updated_at','DESC')->get();
                $array_turnos     = array();
                $array_servicios  = array();

                foreach($movimientos_all as $mov)
                {
                    if(!in_array($mov->movimientoMetadato['turno'],$array_turnos))
                    {
                        array_push($array_turnos,$mov->movimientoMetadato['turno']);
                    }
                    if(!in_array($mov->movimientoMetadato['servicio'],$array_servicios))
                    {
                        array_push($array_servicios,$mov->movimientoMetadato['servicio']);
                    }
                }
                $array_turnos    = array_filter($array_turnos, function($v){return $v !== NULL;});
                $array_servicios = array_filter($array_servicios, function($v){return $v !== NULL;});

                $total = count($data);

                ////**************************************************************************************************************************************



            $data[0] = array ("turnos_disponibles" => $array_turnos, "servicios_disponibles" => $array_servicios);
            return Response::json(array("status" => 404,"messages" => "No hay resultados","data" => $data), 200);

          //return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data2, "total" => $total), 200);
        } 
        else{
                ///***************************************************************************************************************************************
                $movimientos_all = Movimiento::with('movimientoMetadato','movimientoUsuario')
                                            ->where('tipo_movimiento_id',$parametros['tipo'])
                                            ->where('almacen_id',$parametros['almacen'])
                                            ->orderBy('updated_at','DESC')->get();
                $array_turnos     = array();
                $array_servicios  = array();

                foreach($movimientos_all as $mov)
                {
                    if(!in_array($mov->movimientoMetadato['turno'],$array_turnos))
                    {
                        array_push($array_turnos,$mov->movimientoMetadato['turno']);
                    }
                    if(!in_array($mov->movimientoMetadato['servicio'],$array_servicios))
                    {
                        array_push($array_servicios,$mov->movimientoMetadato['servicio']);
                    }
                }
                $array_turnos    = array_filter($array_turnos, function($v){return $v !== NULL;});
                $array_servicios = array_filter($array_servicios, function($v){return $v !== NULL;});

                $total = count($data);

                ////**************************************************************************************************************************************
                if(isset($parametros['page']))
                {
                    $data2[$indice_adds] = array ("turnos_disponibles" => $array_turnos, "servicios_disponibles" => $array_servicios);
                return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data2, "total" => $total), 200);
                }else{
                        $data[$indice_adds] = array ("turnos_disponibles" => $array_turnos, "servicios_disponibles" => $array_servicios);
                        return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data, "total" => $total), 200);
                     }
                
            
        }
    }

   



  
    public function store(Request $request)
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

  
   
///*************************************************************************************************************************************
///*************************************************************************************************************************************
    
                if(property_exists($datos, "insumos"))
                {
                    if(count($datos->insumos) > 0 )
                    {
                        $detalle = array_filter($datos->insumos, function($v){return $v !== null;});
                        foreach ($detalle as $key => $value)
                            {
                                $validacion_insumos = $this->ValidarInsumos($key, NULL, $value, "S");
                                if($validacion_insumos != "")
                                    {
                                        array_push($errors, $validacion_insumos);
                                    }
                            }
                    }else{
                            array_push($errors, array(array('insumos' => array('no_items_insumos'))));
                         }
                }else{
                        array_push($errors, array(array('insumos' => array('no_exist_insumos'))));
                }

                if( count($errors) > 0 )
                {
                    return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
                } 

                DB::beginTransaction();
                try{
                        $movimiento_salida = new Movimiento;
                        $success = $this->validarTransaccionSalida($datos, $movimiento_salida,$almacen_id);
                } catch (\Exception $e){
                    DB::rollback();
                    return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                } 
                if ($success){
                    DB::commit();
                    $ms = Movimiento::with('movimientoMetadato')->find($movimiento_salida->id);
                    return Response::json(array("status" => 201,"messages" => "Creado","data" => $ms), 201);
                } 
                else{
                    DB::rollback();
                    return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
                }
                
       
///*************************************************************************************************************************************
///*************************************************************************************************************************************

    }


///*************************************************************************************************************************************
///*************************************************************************************************************************************
/////                             S    H    O    W 
///*************************************************************************************************************************************
///*************************************************************************************************************************************

   


    public function show($id)
    {
        $movimiento =  Movimiento::with('almacen','movimientoMetadato','programa')->find($id);

        if(!$movimiento)
        {
			return Response::json(array("status" => 404,"messages" => "No se encuentra el movimiento solicitado"), 200);
		} 
        $movimiento = (object) $movimiento;

        $monto_total    = 0;
        $monto_subtotal = 0;
        $monto_iva      = 0;

////**************************************************************************************************************************************
////**************************************************************************************************************************************
        
            $insumos = DB::table('movimiento_insumos')
                    ->join('stock', 'movimiento_insumos.stock_id', '=', 'stock.id')
                    ->where('movimiento_insumos.movimiento_id', '=', $id)
                    ->where('movimiento_insumos.modo_salida', '=', "N")
                    ->where('movimiento_insumos.deleted_at',NULL)
                    ->groupby('stock.clave_insumo_medico')
                    ->select(DB::raw('SUM(movimiento_insumos.cantidad) as total_insumo'), 'stock.clave_insumo_medico','modo_salida')
                    ->get();

            $array_insumos = array();   

        ///*****************************************************************************************
            foreach($insumos as $insumo)
            {
                    $objeto_insumo = new \stdClass();
                    $array_lotes = array();
                    $subtotal_con_iva = 0;

                    $insumos2 = DB::table('movimiento_insumos')
                                    ->where('movimiento_id',$id)
                                    ->where('modo_salida',"N")
                                    ->where('deleted_at',NULL)
                                    ->get();

                    foreach($insumos2 as $insumo2)
                    {
                        $lote = DB::table('stock')->find($insumo2->stock_id);
                        
                        if($insumo->clave_insumo_medico == $lote->clave_insumo_medico)
                        {
                            $objeto_lote = new \stdClass();
                            $objeto_lote->id                  = $lote->id;
                            $objeto_lote->clave_insumo_medico = $lote->clave_insumo_medico;
                            $objeto_lote->marca_id            = $lote->marca_id;
                            $objeto_lote->lote                = $lote->lote;
                            $objeto_lote->codigo_barras       = $lote->codigo_barras;
                            $objeto_lote->fecha_caducidad     = $lote->fecha_caducidad;

                            $programa_temp = Programa::find($lote->programa_id);
                            if($programa_temp){
                                $objeto_lote->programa_nombre = $programa_temp->nombre;
                            }else{
                                $objeto_lote->programa_nombre = NULL;
                            }

                            $objeto_lote->modo_salida         = $insumo2->modo_salida;
                            $objeto_lote->cantidad            = $insumo2->cantidad;

                            /// precio_total -> es la multiplicacion de cantidad * precion_unitario sin iva

                            $objeto_lote->importe             = $insumo2->precio_total;
                            $objeto_lote->importe_con_iva     = $insumo2->precio_total + $insumo2->iva ;
                            $objeto_lote->precio_unitario     = $insumo2->precio_unitario;
                            $objeto_lote->iva                 = ( $insumo2->iva / $insumo2->cantidad ) ;

                            $monto_iva      += $insumo2->iva;
                            $monto_subtotal += ( $insumo2->precio_unitario * $insumo2->cantidad );
                            $monto_total    += ( $insumo2->precio_total + $insumo2->iva );

                            $subtotal_con_iva += ($insumo2->precio_total + $insumo2->iva);

                            array_push($array_lotes,$objeto_lote);
                        }
                    }

                    $insumo_detalles = Insumo::conDescripciones()->find($insumo->clave_insumo_medico);
                    $insumo_detalles->load('informacionAmpliada');

                    $insumo_detalles_temp = Insumo::conDescripciones()->find($insumo->clave_insumo_medico);

                    $movimiento_detalle = MovimientoDetalle::where('movimiento_id',$movimiento->id)
                                          ->where('clave_insumo_medico',$insumo->clave_insumo_medico)
                                          ->first();
                    

                    $objeto_insumo              = $insumo_detalles_temp;
                    $objeto_insumo->nombre      = $insumo_detalles_temp->generico_nombre;

                    $objeto_insumo->modo_salida         = $insumo->modo_salida;

                    $objeto_insumo->clave               = $insumo->clave_insumo_medico;
                    $objeto_insumo->cantidad            = $insumo->total_insumo;

                    $objeto_insumo->cantidad_solicitada = $movimiento_detalle ? $movimiento_detalle->cantidad_solicitada : 0;

                    $objeto_insumo->detalles            = property_exists($objeto_insumo, "detalles") ? $objeto_insumo->detalles : $insumo_detalles;
                    $objeto_insumo->lotes               = $array_lotes;

                    $objeto_insumo->subtotal_con_iva    = $subtotal_con_iva;

              array_push($array_insumos,$objeto_insumo);
            }
        ///*****************************************************************************************
        $insumos = DB::table('movimiento_insumos')
                    ->join('stock', 'movimiento_insumos.stock_id', '=', 'stock.id')
                    ->where('movimiento_insumos.movimiento_id', '=', $id)
                    ->where('movimiento_insumos.modo_salida', '=', "U")
                    ->where('movimiento_insumos.deleted_at',NULL)
                    ->groupby('stock.clave_insumo_medico')
                    ->select(DB::raw('SUM(movimiento_insumos.cantidad_unidosis) as total_insumo'), 'stock.clave_insumo_medico','modo_salida')
                    ->get();
        ///*****************************************************************************************
            foreach($insumos as $insumo)
            {
                    $objeto_insumo = new \stdClass();
                    $subtotal_con_iva = 0;
                    $array_lotes = array();

                    $insumos2 = DB::table('movimiento_insumos')
                                ->where('movimiento_id',$id)
                                ->where('modo_salida',"U")
                                ->where('deleted_at',NULL)
                                ->get();
                    foreach($insumos2 as $insumo2)
                    {
                        $lote = DB::table('stock')->find($insumo2->stock_id);
                        
                        if($insumo->clave_insumo_medico == $lote->clave_insumo_medico)
                        {
                            $objeto_lote = new \stdClass();
                            $objeto_lote->id                  = $lote->id;
                            $objeto_lote->clave_insumo_medico = $lote->clave_insumo_medico;
                            $objeto_lote->marca_id            = $lote->marca_id;
                            $objeto_lote->lote                = $lote->lote;
                            $objeto_lote->codigo_barras       = $lote->codigo_barras;
                            $objeto_lote->fecha_caducidad     = $lote->fecha_caducidad;

                            $programa_temp = Programa::find($lote->programa_id);
                            if($programa_temp){
                                $objeto_lote->programa_nombre = $programa_temp->nombre;
                            }else{
                                $objeto_lote->programa_nombre = NULL;
                            }

                            $objeto_lote->modo_salida         = $insumo2->modo_salida;
                            $objeto_lote->cantidad            = $insumo2->cantidad_unidosis;

                            ////****************************************************************************************************
                            /// precio_total es la multiplicacion de cantidad * precion_unitario sin iva

                            $objeto_lote->importe             = $insumo2->precio_total;
                            $objeto_lote->importe_con_iva     = $insumo2->precio_total + $insumo2->iva ;
                            $objeto_lote->precio_unitario     = $insumo2->precio_unitario;
                            $objeto_lote->iva                 = ( $insumo2->iva / $insumo2->cantidad_unidosis ) ;

                            $monto_iva      += $insumo2->iva;
                            $monto_subtotal += ( $insumo2->precio_unitario * $insumo2->cantidad_unidosis );
                            $monto_total    += ( $insumo2->precio_total + $insumo2->iva );

                            $subtotal_con_iva += ($insumo2->precio_total + $insumo2->iva);
                            ////****************************************************************************************************
                            

                            array_push($array_lotes,$objeto_lote);
                        }
                    }

                    $insumo_detalles = Insumo::conDescripciones()->find($insumo->clave_insumo_medico);
                    $insumo_detalles->load('informacionAmpliada');

                    $insumo_detalles_temp = Insumo::conDescripciones()->find($insumo->clave_insumo_medico);

                    $movimiento_detalle = MovimientoDetalle::where('movimiento_id',$movimiento->id)
                                          ->where('clave_insumo_medico',$insumo->clave_insumo_medico)
                                          ->first();
                    

                    $objeto_insumo              = $insumo_detalles_temp;
                    $objeto_insumo->nombre      = $insumo_detalles_temp->generico_nombre;

                    $objeto_insumo->clave               = $insumo->clave_insumo_medico;
                    $objeto_insumo->cantidad            = $insumo->total_insumo;

                    $objeto_insumo->modo_salida         = $insumo->modo_salida;

                    $objeto_insumo->cantidad_solicitada = $movimiento_detalle ? $movimiento_detalle->cantidad_solicitada : 0;

                    $objeto_insumo->detalles            = property_exists($objeto_insumo, "detalles") ? $objeto_insumo->detalles : $insumo_detalles;
                    $objeto_insumo->lotes               = $array_lotes;

                    $objeto_insumo->subtotal_con_iva    = $subtotal_con_iva;

              array_push($array_insumos,$objeto_insumo);
            }


        ///*******************************************************************************************************************************
        ///*******************************************************************************************************************************
 
                     
                $movimiento_detalle_negados = MovimientoDetalle::where('movimiento_id',$movimiento->id)
                                          ->where('cantidad_surtida','<=',0)
                                          ->get();
                $array_insumos_negados = array();

                $objeto_negado = new \stdClass();

                foreach($movimiento_detalle_negados as $negado)
                {
                    $insumo_detalles = Insumo::conDescripciones()->find($negado->clave_insumo_medico);
                    $insumo_detalles->load('informacionAmpliada');

                    $objeto_negado  = $insumo_detalles;

                    $objeto_negado->clave_insumo_medico          = $negado->clave_insumo_medico;
                    $objeto_negado->nombre                       = $insumo_detalles->generico_nombre;
                    $objeto_negado->modo_salida                  = $negado->modo_salida;

                    $objeto_negado->cantidad_solicitada          = $negado->cantidad_solicitada;
                    $objeto_negado->cantidad_solicitada_unidosis = $negado->cantidad_solicitada_unidosis;

                    $objeto_negado->cantidad_surtida          = $negado->cantidad_surtida;
                    $objeto_negado->cantidad_surtida_unidosis = $negado->cantidad_surtida_unidosis;

                    $objeto_negado->cantidad_negada              = $negado->cantidad_negada;
                    $objeto_negado->cantidad_negada_unidosis     = $negado->cantidad_negada_unidosis;

                    array_push($array_insumos_negados,$objeto_negado);

                }
                                          

                //var_dump($movimiento_detalle_negados); die();

            
        ///******************************************************************************************************************************
        ///*******************************************************************************************************************************
                $movimiento->insumos = $array_insumos;
                $movimiento->estatus = $movimiento->status;

                $movimiento->total       = $monto_total;
                $movimiento->subtotal    = $monto_subtotal;
                $movimiento->iva         = $monto_iva;

                $clues = $movimiento->movimientoMetadato['unidad_medica_destino'];
                $unidad_medica = UnidadMedica::find($clues);
                $movimiento->movimientoMetadato['unidad_medica'] = $unidad_medica;

                $movimiento->insumos_negados = $array_insumos_negados;


        

////**************************************************************************************************************************************
///****************************************************************************************************************************************

			return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $movimiento), 200);
        
    }


///***************************************************************************************************************************
///***************************************************************************************************************************
 
    public function update(Request $request, $id)
    {
 
    }
     
    public function destroy($id)
    {
        
    }
///***************************************************************************************************************************
///***************************************************************************************************************************
 
	private function ValidarMovimiento($key, $id, $request)
    { 
        $errors_validar_movimiento = array();

        $mensajes = [
                        'required'      => "required",
                        'email'         => "email",
                        'unique'        => "unique",
                        'integer'       => "not_integer",
                        'in'            => 'no_valido',
                    ];

        $reglas = array();
                
        $reglas = [
                    'tipo_movimiento_id'                 => 'required|integer|in:18',
                    'movimiento_metadato.persona_recibe' => 'required|string',
                    'movimiento_metadato.turno_id'       => 'required|integer',
                  ];
        

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

        if($tipo=='E')
                {
                    $reglas = [
                                'clave'                 => 'required',
                                'cantidad'              => 'required|integer|min:0',
                                'cantidad_x_envase'     => 'required|integer',
                                'lote'                  => 'required',
                                'fecha_caducidad'       => 'required',
                                
                              ];
                }else{
                        $reglas = [
                                    'clave'                 => 'required',
                                    'cantidad'              => 'required|integer|min:0',
                                    'cantidad_solicitada'   => 'required|numeric|min:1',
                                    'cantidad_x_envase'     => 'required|integer',
                                  ];
                     }
                     
        $v = \Validator::make($request, $reglas, $mensajes );
        $mensages_validacion = array();
 
        //$lotes = array();
        //$request_object = (object) $request;
        //$lotes = $request_object->lotes;

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
                        
                        $fecha_caducidad = new DateTime($lote_check->fecha_caducidad);
                        $now = new DateTime("now");

                        if($lote_check->fecha_caducidad == "" || $lote_check->fecha_caducidad == NULL)
                        {
                        }else{
                                if($now >= $fecha_caducidad )
                                {
                                    $v->errors()->add('lote_'.$lote->id.'_', 'lote_caducado');
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

                            $fecha_caducidad = new DateTime($lote->fecha_caducidad);
                            $now = new DateTime("now");
                            
                            if($lote->fecha_caducidad == "" || $lote->fecha_caducidad == NULL)
                            {
                            }else{
                                    if($now >= $fecha_caducidad )
                                    {
                                        $v->errors()->add('lote_'.$lote->id.'_', 'lote_caducado');
                                    }
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
  
    private function ValidarInsumosReceta($key, $id, $request,$tipo){ 
        $mensajes = [
                        'required'      => "required",
                        'email'         => "email",
                        'unique'        => "unique",
                        'integer'       => "integer",
                        'min'           => "min"
                    ];

        $reglas = [
                        'clave'                 => 'required|string',
                        'cantidad'              => 'required|integer|min:0',
                        'cantidad_x_envase'     => 'required|integer',
                        'dosis'                 => 'required|numeric',
                        'frecuencia'            => 'required|numeric|min:0',
                        'duracion'              => 'required|numeric|min:1',
                        'cantidad_recetada'     => 'required|integer|min:1',
                  ];
                           
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
                    if($lote_check)
                    {
                        if($lote->cantidad <= 0)
                        {
                            $v->errors()->add('lote_'.$lote->id.'_', 'cantidad_invalida');
                        }
                        /// validar cantidad solicitada en req contra lo del find
                        if($lote->cantidad <= $lote_check->existencia)
                        {

                        }else{
                                $v->errors()->add('lote_'.$lote->id.'_', 'lote_insuficiente');
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




    
///**************************************************************************************************************************
///                  M O V I M I E N T O         S  A  L  I  D  A
///**************************************************************************************************************************
       

    private function validarTransaccionSalida($datos, $movimiento_salida,$almacen_id)
    {
		$success = false;

        //comprobar que el servidor id no me lo envian por parametro, si no poner el servidor por default de la configuracion local, si no seleccionar el servidor del parametro
        $servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');

        //agregar al modelo los datos
        $movimiento_salida->almacen_id                   =  $almacen_id;
        $movimiento_salida->tipo_movimiento_id           =  18;
        $movimiento_salida->programa_id                  =  $datos->programa_id; 
        $movimiento_salida->status                       =  $datos->estatus; 
        $movimiento_salida->fecha_movimiento             =  property_exists($datos, "fecha_movimiento")          ? $datos->fecha_movimiento          : '';
        $movimiento_salida->observaciones                =  property_exists($datos, "observaciones")             ? $datos->observaciones             : '';
        $movimiento_salida->cancelado                    =  property_exists($datos, "cancelado")                 ? $datos->cancelado                 : '';
        $movimiento_salida->observaciones_cancelacion    =  property_exists($datos, "observaciones_cancelacion") ? $datos->observaciones_cancelacion : '';

        $lotes_master = array();

        // si se guarda el movimiento tratar de guardar el detalle de insumos
        if( $movimiento_salida->save() )
        {
            $success = true;

            if(property_exists($datos,"movimiento_metadato"))
            {
                $metadatos = new MovimientoMetadato;
                $metadatos->movimiento_id          = $movimiento_salida->id;
                $metadatos->unidad_medica_destino  = $datos->movimiento_metadato['unidad_medica_destino'];
                $metadatos->servicio_id            = NULL;
                $metadatos->persona_recibe         = $datos->movimiento_metadato['persona_recibe'];
                $metadatos->turno_id               = $datos->movimiento_metadato['turno_id'];

                $metadatos->save();
                
            }


            if(property_exists($datos, "insumos"))
            {
                $insumos = array_filter($datos->insumos, function($v){return $v !== NULL;});

                $lotes_nuevos  = array();
                $lotes_ajustar = array();
 
        ///  PRIMER PASADA PARA IDENTIFICAR LOS LOTES NUEVOS A AJUSTAR / GENERAR ENTRADA 
                 foreach ($insumos as $key => $insumo)
                { 
                     if($insumo != NULL)
                     {
                         if(is_array($insumo)){ $insumo = (object) $insumo; }

                         $clave_insumo_medico = $insumo->clave;
                         
                         $precio = $this->conseguirPrecio($clave_insumo_medico);

                                foreach($insumo->lotes as $index => $lote)
                                {
                                     if(is_array($lote)){ $lote = (object) $lote; }


                                     ///REVISAR MODO NORMAL Ó MODO UNIDOSIS AQUI

                                    if($lote->nuevo == 1)
                                    {
                                         $lote_temp = Stock::where('lote',$lote->lote)
                                                            ->where('fecha_caducidad',$lote->fecha_caducidad)
                                                            ->where('codigo_barras',$lote->codigo_barras)
                                                            ->where('clave_insumo_medico',$clave_insumo_medico)
                                                            ->where('almacen_id',$almacen_id)
                                                            ->orderBy('created_at','DESC')->first();

                                        /// si ya existe un lote vacio con esos detalles : se agrega uno
                                        if($lote_temp)
                                          { 
                                                // Se actualiza el stock y sus detalles
                                                $lote_temp->existencia          = $lote_temp->existencia + $lote->existencia;
                                                $lote_temp->existencia_unidosis = $lote_temp->existencia_unidosis + ($lote->existencia * $insumo->cantidad_x_envase);
                                                
                                                //$lote_temp->unidosis_sueltas    = 0;
                                                //$lote_temp->existencia_unidosis = 0;
                                                //aqui se calcula la exstencia unidosis parcial

                                                /// CHECAR AQUI EÑ CASO DEL LOTE EXCLUSIVO Y EL CASO DE EXCLUSIVO AL SUMAR LOTE EXISTENTE

                                                $lote_temp->save();
                                                // adicion del campo cantidad al objeto lote/stock
                                                $lote_temp->cantidad    = property_exists($lote_temp, "cantidad") ? $lote_temp->cantidad : $lote->cantidad;
                                                $lote_temp->modo_salida = property_exists($lote_temp, "modo_salida") ? $lote_temp->modo_salida : $lote->modo_salida;

                                                array_push($lotes_nuevos,$lote_temp);
                                                array_push($lotes_master,$lote_temp);
                                          }else{
                                                    $lote_insertar = new Stock;

                                                    $lote_insertar->almacen_id             = $movimiento_salida->almacen_id;
                                                    $lote_insertar->clave_insumo_medico    = $clave_insumo_medico;
                                                    $lote_insertar->marca_id               = NULL;
                                                    $lote_insertar->exclusivo              = $lote->exclusivo;
                                                    $lote_insertar->lote                   = $lote->lote;
                                                    $lote_insertar->fecha_caducidad        = $lote->fecha_caducidad;
                                                    $lote_insertar->codigo_barras          = $lote->codigo_barras;
                                                    $lote_insertar->existencia             = $lote->existencia;
                                                    $lote_insertar->existencia_unidosis    = ( $insumo->cantidad_x_envase * $lote->cantidad );

                                                    $lote_insertar->unidosis_sueltas  = 0;
                                                    $lote_insertar->envases_parciales = 0;

                                                    $lote_insertar->save();
                                                    //      adicion del campo cantidad al objeto lote/stock
                                                    $lote_insertar->cantidad    = property_exists($lote_insertar, "cantidad") ? $lote_insertar->cantidad : $lote->cantidad;
                                                    $lote_insertar->modo_salida = property_exists($lote_insertar, "modo_salida") ? $lote_insertar->modo_salida : $lote->modo_salida;

                                                    array_push($lotes_nuevos,$lote_insertar);
                                                    array_push($lotes_master,$lote_insertar);
                                               }

                                    }else{
                                            // aqui ya trae el campo cantidad el objeto lote/stock
                                            array_push($lotes_master,$lote);
                                         }
                                /// *********************************************************************************************************************************************************************************************************
                                /// *********************************************************************************************************************************************************************************************************
                                                                    
                                } /// FIN PRIMER FOREACH QUE RECORRE TODOS LOS INSUMOS PARA SALIR

                        /// GUARDAR MOVIMIENTO_DETALLES DEL INSUMO RECORRIDO 
                       $stocks = Stock::where('clave_insumo_medico',$clave_insumo_medico)
                                      ->where('existencia','>',0)
                                      ->where('almacen_id',$almacen_id)
                                      ->orderBy('fecha_caducidad','ASC')->get();

                        $existencia = 0;
                        $existencia_unidosis = 0;
 
                        foreach($stocks as $stock)
                        {   
                            $existencia          += $stock->existencia; 
                            $existencia_unidosis += $stock->existencia_unidosis; 
                        }  

                        $movimiento_detalle = new MovimientoDetalle;
                        $movimiento_detalle->movimiento_id       = $movimiento_salida->id;
                        $movimiento_detalle->clave_insumo_medico = $clave_insumo_medico;
                        $movimiento_detalle->modo_salida         = $insumo->modo_salida;

                        if($insumo->modo_salida == 'N')
                        {
                            $movimiento_detalle->cantidad_solicitada          = $insumo->cantidad_solicitada;
                            $movimiento_detalle->cantidad_solicitada_unidosis = $insumo->cantidad_solicitada * $insumo->cantidad_x_envase;

                            $movimiento_detalle->cantidad_existente           = $existencia;
                            $movimiento_detalle->cantidad_existente_unidosis  = $existencia_unidosis;

                            $movimiento_detalle->cantidad_surtida             = $insumo->cantidad_surtida;
                            $movimiento_detalle->cantidad_surtida_unidosis    = $insumo->cantidad_surtida * $insumo->cantidad_x_envase;

                            $movimiento_detalle->cantidad_negada              = $insumo->cantidad_solicitada - $insumo->cantidad_surtida;
                            $movimiento_detalle->cantidad_negada_unidosis     = ( $insumo->cantidad_solicitada - $insumo->cantidad_surtida ) * ( $insumo->cantidad_x_envase ); 

                        }else{
                                $movimiento_detalle->cantidad_solicitada          = $insumo->cantidad_solicitada / $insumo->cantidad_x_envase;
                                $movimiento_detalle->cantidad_solicitada_unidosis = $insumo->cantidad_solicitada ;

                                $movimiento_detalle->cantidad_existente           = $existencia;
                                $movimiento_detalle->cantidad_existente_unidosis  = $existencia_unidosis;

                                $movimiento_detalle->cantidad_surtida             = $insumo->cantidad_surtida / $insumo->cantidad_x_envase;
                                $movimiento_detalle->cantidad_surtida_unidosis    = $insumo->cantidad_surtida ;

                                $movimiento_detalle->cantidad_negada              = ( $insumo->cantidad_solicitada - $insumo->cantidad_surtida ) / ( $insumo->cantidad_x_envase );
                                $movimiento_detalle->cantidad_negada_unidosis     = ( $insumo->cantidad_solicitada - $insumo->cantidad_surtida ) ; 

                             } 
                        
                        $movimiento_detalle->save();

                        // AQUI  LA TABLA DE ESTADISTICAS PARA NEGACIONES
                        if($movimiento_detalle->cantidad_negada > 0)
                        {
                            $negacion_resusitada = 0;
                            $tipo_insumo_id = 0;

                            $contrato_precio = ContratoPrecio::where('insumo_medico_clave',$clave_insumo_medico)->first();
                            if($contrato_precio)
                            {
                                $tipo_insumo_id = $contrato_precio->tipo_insumo_id;
                            }

                            // Si no existe registro para resusitar, se comprueba existencia de registro activo
                            $negacion = NegacionInsumo::where('almacen_id',$almacen_id)
                                                ->where('clave_insumo_medico',$clave_insumo_medico)
                                                ->first();
                            if(!$negacion)
                            {
                                    // Busqueda de registro de negación a resusitar para el insumo negado
                                    $negacion = DB::table('negaciones_insumos')
                                                    ->where('clave_insumo_medico',$clave_insumo_medico)
                                                    ->where('deleted_at','!=',NULL)
                                                    ->first();

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
                                            ->where('movimiento_insumos.deleted_at',NULL)
                                            ->orderBy('created_at','DESC')
                                            ->first();

                                //var_dump($ultima_entrada_insumo); die();


                                if($ultima_entrada_insumo)
                                {
                                    $ultima_entrada                = $ultima_entrada_insumo->created_at;
                                    $cantidad_entrada              = $ultima_entrada_insumo->cantidad;
                                    $cantidad_entrada_unidosis     = $ultima_entrada_insumo->cantidad_unidosis;
                                }
                             ///**************************************************************************************************************************
                           
                           $cantidad_negada          = $movimiento_detalle->cantidad_negada;
                           $cantidad_negada_unidosis = $movimiento_detalle->cantidad_negada_unidosis;        
                           
                            // Si existe registro de negación de insumo ( activo ó resusitado )
                            if($negacion)
                            { 
                                $negacion_insumo  = NegacionInsumo::find($negacion->id);

                                if($negacion_resusitada == 1)
                                {
                                    //$negacion_insumo                                = $negacion;

                                    $negacion_insumo->fecha_inicio                  = date("Y-m-d");
                                    $negacion_insumo->fecha_fin                     = date("Y-m-d");
                                    $negacion_insumo->cantidad_acumulada            = $cantidad_negada;
                                    $negacion_insumo->cantidad_acumulada_unidosis   = $cantidad_negada_unidosis;
                                    $negacion_insumo->ultima_entrada                = $ultima_entrada;
                                    $negacion_insumo->cantidad_entrada              = $cantidad_entrada;
                                    $negacion_insumo->cantidad_entrada_unidosis     = $cantidad_entrada_unidosis;

                                }else{
                                        //$negacion_insumo                                = $negacion;

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

                                   // var_dump(json_encode($negacion_insumo)); die();

                                    $negacion_insumo->clave_insumo_medico           = $clave_insumo_medico;
                                    $negacion_insumo->clues                         = $almacen->clues;
                                    $negacion_insumo->almacen_id                    = $almacen_id;
                                    $negacion_insumo->tipo_insumo                   = $tipo_insumo_id;
                                    $negacion_insumo->fecha_inicio                  = date("Y-m-d");
                                    $negacion_insumo->fecha_fin                     = date("Y-m-d");
                                    $negacion_insumo->cantidad_acumulada            = $cantidad_negada;
                                    $negacion_insumo->cantidad_acumulada_unidosis   = $cantidad_negada_unidosis;
                                    $negacion_insumo->ultima_entrada                = $ultima_entrada;
                                    $negacion_insumo->cantidad_entrada              = $cantidad_entrada;
                                    $negacion_insumo->cantidad_entrada_unidosis     = $cantidad_entrada_unidosis;

                                    //var_dump($negacion_insumo); die();

                                    $negacion_insumo->save();
                                 }
                        }else{
                                //*************************************************************************************
                                //Verificar si esta en la lista de negados. 
                                $negacion = NegacionInsumo::where('almacen_id',$almacen_id)
                                                        ->where('clave_insumo_medico',$clave_insumo_medico)
                                                        ->first();
                                if($negacion)
                                { /// Si esta dentro de los negados, se borra porque en ese momento se esta surtiendo y dejando de negar.
                                    $negacion->delete();
                                }
                                //*************************************************************************************

                             }
                 

                    }///FIN IF INSUMO != NULL
                }////   FIN FOREACH     I N S U M O S     -> PRIMERA PASADA
///********************************************************************************************************************************************
                                /// insertar el movimiento entrada de ajuste y ligar los detalles con su stock ya agregado en pasada anterior
                                if(count($lotes_nuevos) > 0)
                                {
                                    /// insertar movimiento de entrada por ajuste ( tipo_movimiento_id = 6 )
                                    $movimiento_ajuste = new Movimiento;
                                    $movimiento_ajuste->almacen_id                   =  $almacen_id;
                                    $movimiento_ajuste->tipo_movimiento_id           =  6;
                                    $movimiento_ajuste->status                       =  "FI";  
                                    $movimiento_ajuste->fecha_movimiento             =  date("Y-m-d");
                                    $movimiento_ajuste->observaciones                =  "SE REALIZA ENTRADA POR AJUSTE";
                                    $movimiento_ajuste->cancelado                    =  property_exists($datos, "cancelado")                 ? $datos->cancelado                 : '';
                                    $movimiento_ajuste->observaciones_cancelacion    =  property_exists($datos, "observaciones_cancelacion") ? $datos->observaciones_cancelacion : '';
                                
                                    $movimiento_ajuste->save();

                                    foreach($lotes_nuevos as $lote_link)
                                    {
                                        $item_detalles = new MovimientoInsumos;

                                        ///var_dump(json_encode($lote_link));

                                        $item_detalles->movimiento_id           = $movimiento_ajuste->id; 
                                        $item_detalles->stock_id                = $lote_link->id;
                                        $item_detalles->clave_insumo_medico     = $clave_insumo_medico; 


                                        $item_detalles->cantidad                = $lote_link->cantidad;

                                        ///AQUI METER LA CANTIDAD UNIDOSIS

                                        $item_detalles->precio_unitario         = $precio['precio_unitario'];
                                        $item_detalles->iva                     = $precio['iva']; 
                                        $item_detalles->precio_total            = ( $precio['precio_unitario'] + $precio['iva'] ) * $lote_link->cantidad;

                                        $item_detalles->save();
                                    }
                                } /// FIN IF EXISTEN LOTES NUEVOS 

////*************************************************************************************************************************
                            //FOREACH SEGUNDA PASADA A INSUMOS PARA ACTUALIZAR STOCK DE SALIDA
                                foreach($lotes_master as $index => $lote)
                                {
                                    $lote_stock          = Stock::find($lote->id);
                                    $insumo_temp         = Insumo::find($lote_stock->clave_insumo_medico);
                                    $precio              = $this->conseguirPrecio($lote_stock->clave_insumo_medico);
                                                                                                        
                                    $insumo              = Insumo::datosUnidosis()->where('clave',$lote_stock->clave_insumo_medico)->first();

                                    $cantidad_x_envase   = $insumo->cantidad_x_envase; 

                                    
                                    /// INICIA CALCULO DEL NUEVO STOCK SEGUN EL MODO ELEGIDO
                                    if($lote->modo_salida == "N")
                                    {
                                        $lote_stock->existencia          = ($lote_stock->existencia - $lote->cantidad );
                                        $lote_stock->existencia_unidosis = ($lote_stock->existencia_unidosis - ( $lote->cantidad * $cantidad_x_envase) );

                                    }else{ 
                                            /// la variable cantidad se interpreta para existencia_unidosis
                                            $para_salir = ($lote->cantidad / $cantidad_x_envase);
                                            $enteros_salir = 0;
                                            if($lote->cantidad <= $lote_stock->unidosis_sueltas)
                                            {
                                                $enteros_salir = 0;
                                            }else{
                                            //aqui el error
                                                    if($para_salir > 0 && $para_salir <= 1)
                                                    {
                                                        $enteros_salir = 1;
                                                    }else{                                        
                                                            if($para_salir != intval($para_salir))
                                                            { // es un decimal y tambien es mayor que uno
                                                                // valida si para surtir la unidosis solicitada es necesario abrir una caja nueva 
                                                                // ( aparte de la que ya esta abierta )
                                                                if( ( $lote_stock->unidosis_sueltas + (intval($para_salir)*$cantidad_x_envase)) >= $lote->cantidad )
                                                                {
                                                                    $enteros_salir = intval($para_salir);
                                                                }else{ $enteros_salir = intval($para_salir)+1; }

                                                            }else{ // es un numero entero
                                                                    $enteros_salir = intval($para_salir);
                                                                }
                                                         }
                                                 }
                                            
                                        
                                            $nueva_existencia            =  $lote_stock->existencia - $enteros_salir;
                                            $nueva_existencia_unidosis   =  $lote_stock->existencia_unidosis - $lote->cantidad;

                                            $unidosis_enteras  = ( $nueva_existencia * $cantidad_x_envase );
                                            $unidosis_sueltas  = $nueva_existencia_unidosis - $unidosis_enteras;

                                            $lote_stock->existencia          = $nueva_existencia;
                                            $lote_stock->existencia_unidosis = $nueva_existencia_unidosis;
                                            $lote_stock->unidosis_sueltas    = $unidosis_sueltas;

                                            if($unidosis_sueltas > 0)
                                            {
                                                $lote_stock->envases_parciales   = 1;
                                            }else{
                                                    $lote_stock->envases_parciales   = 0;
                                                 }
                                     
                                    }// fin else ( si es salida unidosis )

                                    $lote_stock->save();

                                    $item_detalles = new MovimientoInsumos;

                                    $item_detalles->movimiento_id           = $movimiento_salida->id; 
                                    $item_detalles->stock_id                = $lote_stock->id;
                                    $item_detalles->clave_insumo_medico     = $lote_stock->clave_insumo_medico; /// CORRECCION ORIGEN DE CLAVE

                                    // se agregan las cantidades correspondientes segun el modo de salida
                                    if($lote->modo_salida == "N")
                                    {
                                        $item_detalles->cantidad                = $lote->cantidad;
                                        $item_detalles->cantidad_unidosis       = $lote->cantidad * $insumo->cantidad_x_envase;
                                    }else{
                                            $item_detalles->cantidad               = $lote->cantidad/$insumo->cantidad_x_envase;
                                            $item_detalles->cantidad_unidosis      = $lote->cantidad;
                                         }
                                    
                                    $item_detalles->modo_salida             = $lote->modo_salida;

                                    $item_detalles->precio_unitario         = $precio['precio_unitario'];
                                    $item_detalles->iva                     = $precio['iva'];

                                    $item_detalles->precio_total            = ( $precio['precio_unitario'] + $precio['iva'] ) * $item_detalles->cantidad;

                                    $item_detalles->save();
                                
                                }/// FIN FOREACH SEGUNDA PASADA A INSUMOS

            } /// FIN IF EXISTE INSUMOS           
        }
        
        return $success;
    }



 

///**************************************************************************************************************************
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

    return $response;
 }



 ////***************        GUARDAR ESTADISTICA PARA NEGACION DE INSUMO    ***************************************************
///**************************************************************************************************************************

public function guardarEstadisticaNegacion($clave_insumo_medico,$almacen_id,$cantidad_negada)
    {

        $negacion_resusitada = 0;
        $precios             = (object) $this->conseguirPrecio($clave_insumo_medico);
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
                                            ->where('movimientos.deleted_at',NULL)
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
