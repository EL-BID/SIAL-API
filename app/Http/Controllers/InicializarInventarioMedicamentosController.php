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


/** 
* Controlador
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-03-22
*
* Controlador `AjusteMasInventario`: Controlador  para 
*
*/
class InicializarInventarioMedicamentosController extends Controller
{
     
    public function index(Request $request)
    {
///*******************************************************************************************************************************
        $parametros = Input::only('q','page','per_page','clues','almacen');
        $parametros['almacen'] = $request->get('almacen_id');

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }  
        $almacen     = Almacen::find($parametros['almacen']);
        $almacen_id  = $almacen->id;
    
        $inis   = InicializacionInventario::getModel();
        $inis   = $inis->with("inicializacionInventarioDetalle")->where('almacen_id',$almacen_id);

        if ($parametros['q'])
        {
            $inis =  $inis->where(function($query) use ($parametros) {
                 $query->where('id','LIKE',"%".$parametros['q']."%")
                       ->orWhere('observaciones','LIKE',"%".$parametros['q']."%");
             });
        }
 //////*********************************************************************************************************
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $inis = $inis->paginate($resultadosPorPagina);
        } else {
                    $inis = $inis->get();
               }
 //////*********************************************************************************************************

        if(count($inis) <= 0){

            return Response::json(array("status" => 404,"messages" => "No se han realizado inicializaciones de inventario en este almacén","data" => $inis), 200);
        } 
        else{
                return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $inis, "total" => count($inis)), 200);
            }

 
///****************************************************************************************************************************************      
///*******************************************************************************************************************************************
 
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
        
        $input_data = (object)Input::json()->all();
        $almacen = Almacen::find($parametros['almacen']);
        $almacen_id  = $almacen->id;
        $clues       = $request->get('clues');
        $servidor_id = property_exists($input_data, "servidor_id") ? $input_data->servidor_id : env('SERVIDOR_ID');

        $errors     = array();
        $nuevo      = 0;

        $cantidad_programas = 0;
        $cantidad_claves    = 0;
        $cantidad_insumos   = 0;
        $cantidad_lotes     = 0;
        $monto_total        = 0;
///*****************************************************************************************************************************************
///*****************************************************************************************************************************************
if($input_data->estatus=="INICIALIZADO")
{
        if(property_exists($input_data, "programas"))
        {
            $programas_x = $input_data->programas;
            if(count($programas_x) > 0 )
            {
                foreach ($programas_x as $key => $programa_validar)
                {
                    $validacion_programa = $this->validarPrograma($programa_validar);
                    $programa_validar    = (object) $programa_validar;

                    if($validacion_programa != "")
                    {   array_push($errors, $validacion_programa);  }

                    ///**********************************************************************************************************************
                            if(count($programa_validar->insumos) > 0 )
                            {
                                foreach ($programa_validar->insumos as $key => $insumo_validar)
                                {
                                    $validacion_insumo = $this->validarInsumo($insumo_validar);
                                    $insumo_validar    = (object) $insumo_validar;
                                    
                                    if($validacion_insumo != "")
                                    {   array_push($errors, $validacion_insumo);  }

                                    ///**********************************************************************************************************************
                                            if(count($insumo_validar->lotes) > 0 )
                                            {
                                                foreach ($insumo_validar->lotes as $key => $lote_validar)
                                                {
                                                    $validacion_lote = $this->validarLote($lote_validar);
                                                    $lote_validar    = (object) $lote_validar;

                                                    if($validacion_lote != "")
                                                    {   array_push($errors, $validacion_lote);  }
                                                }
                                            }else{ array_push($errors, array(array('Lotes' => array('no_items_lotes')))); }
                                    ///**********************************************************************************************************************
                                }
                            }else{ array_push($errors, array(array('Insumos' => array('no_items_insumos')))); }
                    ///**********************************************************************************************************************

                }
            }else{  array_push($errors, array(array('Programas' => array('no_items_programas'))));  }
                    
         }else{ array_push($errors, array(array('Programas' => array('no_existe_programas')))); }

}/// fin if INICIALIZADO
    

///*****************************************************************************************************************************************
///*****************************************************************************************************************************************
        if( count($errors) > 0 )
        {
            return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
        } 
////*****************************************************************************************************************************************
///*****************************************************************************************************************************************
    
        $success = false;
        DB::beginTransaction();
        try{

////****************************************************************************************************************************************
        $fecha_fin = NULL;
        if($input_data->estatus=="INICIALIZADO")
        {
          $fecha_fin =  date("Y-m-d");  
        }

        //$inicializacion = InicializacionInventario::find($input_data->id);

        $inicializacion = new InicializacionInventario;
        $inicializacion->servidor_id      = $servidor_id;
        $inicializacion->clues            = $clues;
        $inicializacion->almacen_id       = $almacen_id;
        $inicializacion->estatus          = $input_data->estatus;
        $inicializacion->fecha_inicio     = date("Y-m-d");
        $inicializacion->fecha_fin        = $fecha_fin;
        $inicializacion->observaciones    = $input_data->observaciones;
        $inicializacion->save();

        $nuevo = 1; 
            
         InicializacionInventarioDetalle::where("inicializacion_inventario_id", $inicializacion->id)->delete();

         foreach ($input_data->programas as $key => $programa)
        {
            $programa = (object) $programa;
                foreach ($programa->insumos as $key => $insumo)
                {
                     $insumo = (object) $insumo;
                     $insumo_x  = Insumo::datosUnidosis()->where('clave',$insumo->clave_insumo_medico)->first();
                     $cantidad_x_envase   = $insumo_x->cantidad_x_envase;

                     $iva_porcentaje = 0;
                     if($insumo_x->tipo == "ME")
                     { $iva_porcentaje = 0; }else{ $iva_porcentaje = 0.16; } 

                    foreach ($insumo->lotes as $key => $lote)
                    {
                        $lote = (object) $lote;
                    
                        DB::table('inicializacion_inventario_detalles')
                            ->where('inicializacion_inventario_id',$inicializacion->id)
                            ->where('almacen_id', $almacen_id)
                            ->where('programa_id',$programa->id)
                            ->where('clave_insumo_medico',$insumo->clave_insumo_medico)
                            ->where('lote',$lote->lote)
                            ->update(['deleted_at' => NULL]);

                         $iid = InicializacionInventarioDetalle::where('inicializacion_inventario_id',$inicializacion->id)
                                                               ->where('almacen_id',$almacen_id)
                                                               ->where('programa_id',$programa->id)
                                                               ->where('clave_insumo_medico',$insumo->clave_insumo_medico)
                                                               ->where('lote',$lote->lote)
                                                               ->where('fecha_caducidad',$lote->fecha_caducidad)
                                                               ->first();
                        if($lote->lote=="" && $lote->fecha_caducidad == "" && $lote->existencia == "")
                        {}else{  
                                if(!$iid){
                                            $iid = new InicializacionInventarioDetalle;
                                         } 
                            
                                    $iid->inicializacion_inventario_id = $inicializacion->id;
                                    $iid->almacen_id                   = $almacen_id;
                                    $iid->programa_id                  = $programa->id;
                                    $iid->clave_insumo_medico          = $insumo->clave_insumo_medico;
                                    $iid->lote                         = $lote->lote;
                                    $iid->exclusivo                    = $lote->exclusivo;
                                    $iid->fecha_caducidad              = $lote->fecha_caducidad;
                                    $iid->codigo_barras                = $lote->codigo_barras;
                                    $iid->existencia                   = $lote->existencia;
                                    $iid->existencia_unidosis          = ($lote->existencia * $cantidad_x_envase);
                                    $iid->precio_unitario              = $lote->precio_unitario;
                                    $iid->iva                          = ( $lote->precio_unitario * $iva_porcentaje );
                                    $iid->importe                      = ( $lote->existencia * $lote->precio_unitario );
                                    $iid->iva_importe                  = ( $lote->precio_unitario * $iva_porcentaje ) * $lote->existencia ;
                                    $iid->importe_con_iva              = ( $lote->precio_unitario + ( $lote->precio_unitario * $iva_porcentaje )) * $lote->existencia;
                                    $iid->save();
                            
                                }  

                    }
                }
        }

         $cantidad_programas  = DB::table('inicializacion_inventario_detalles')
                                ->where('inicializacion_inventario_id',$inicializacion->id)
                                ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                ->count(DB::raw('DISTINCT programa_id'));

         $cantidad_claves     = DB::table('inicializacion_inventario_detalles')
                                ->where('inicializacion_inventario_id',$inicializacion->id)
                                ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                ->count(DB::raw('DISTINCT clave_insumo_medico'));

         $cantidad_insumos    = DB::table('inicializacion_inventario_detalles')
                                    ->where('inicializacion_inventario_id',$inicializacion->id)
                                    ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                    ->sum('existencia');

         $cantidad_lotes      = DB::table('inicializacion_inventario_detalles')
                                ->where('inicializacion_inventario_id',$inicializacion->id)
                                ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                ->count('lote');
        
         $monto_total         = DB::table('inicializacion_inventario_detalles')
                                    ->where('inicializacion_inventario_id',$inicializacion->id)
                                    ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                    ->sum('importe_con_iva');

        $inicializacion->cantidad_programas = $cantidad_programas;
        $inicializacion->cantidad_claves    = $cantidad_claves;
        $inicializacion->cantidad_insumos   = $cantidad_insumos;
        $inicializacion->cantidad_lotes     = $cantidad_lotes;
        $inicializacion->monto_total        = $monto_total;
        $inicializacion->observaciones      = $input_data->observaciones;
        $inicializacion->save();

        //dd($inicializacion->id);

        if($inicializacion->estatus == "INICIALIZADO")
        {
            Stock::where("almacen_id", $almacen_id)->delete();
            
            $inis = InicializacionInventarioDetalle::where('almacen_id',$almacen_id)
                                                   ->where('inicializacion_inventario_id',$inicializacion->id)
                                                   ->get();
            $movimiento = new Movimiento;
            $movimiento->tipo_movimiento_id  = 19;
            $movimiento->almacen_id          = $almacen_id;
            $movimiento->fecha_movimiento    = date('Y-m-d');
            $movimiento->status              = "FI";
            $movimiento->observaciones       = $input_data->observaciones;
            $movimiento->save();
            
            foreach ($inis as $key => $ini)
            {           
                $new_stock = new Stock;
                $new_stock->almacen_id           = $almacen_id;
                $new_stock->clave_insumo_medico  = $ini->clave_insumo_medico;
                $new_stock->programa_id          = $ini->programa_id;
                $new_stock->marca_id             = $ini->marca_id;
                $new_stock->lote                 = $ini->lote;
                $new_stock->exclusivo            = $ini->exclusivo;
                $new_stock->fecha_caducidad      = $ini->fecha_caducidad;
                $new_stock->codigo_barras        = $ini->codigo_barras;
                $new_stock->existencia           = $ini->existencia;
                $new_stock->existencia_unidosis  = $ini->existencia_unidosis;
                $new_stock->unidosis_sueltas     = $ini->unidosis_sueltas;
                $new_stock->envases_parciales    = $ini->envases_parciales;
                $new_stock->save();

                $mi = new MovimientoInsumoS;
                $mi->movimiento_id       = $movimiento->id;
                $mi->stock_id            = $new_stock->id;
                $mi->clave_insumo_medico = $new_stock->clave_insumo_medico;
                $mi->modo_salida         = "N";
                $mi->cantidad            = $new_stock->existencia;
                $mi->cantidad_unidosis   = $new_stock->existencia_unidosis;
                $mi->precio_unitario     = $ini->precio_unitario;
                $mi->iva                 = $ini->iva;
                $mi->precio_total        = $ini->importe_con_iva;
                $mi->save();

            }

        }

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
            return Response::json(array("status" => 201,"messages" => "Creado correctamente","data" => $inicializacion), 201);        
        }else{
                DB::rollback();
                return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
             }
////*****************************************************************************************************************************************



    }  /// FIN METODO     S   T   O   R   E 


///*************************************************************************************************************************************
///*************************************************************************************************************************************







/////                             S    H    O    W 
///*************************************************************************************************************************************
///*************************************************************************************************************************************
    public function show(Request $request,$id)
    {   
///****************************************************************************************************************************************
///****************************************************************************************************************************************

        $parametros = Input::only('q','page','per_page','almacen','tipo','fecha_desde','fecha_hasta','usuario','turno','servicio');
        $parametros['almacen'] = $request->get('almacen_id');

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }  
        
        $almacen = Almacen::find($parametros['almacen']);
        $movimientos = NULL;
        $data = NULL;

        $inicializacion = InicializacionInventario::find($id);

        $programas = array();

        if($inicializacion)
        {
            $iids_programas = InicializacionInventarioDetalle::where('inicializacion_inventario_id',$inicializacion->id)
                                                             ->groupBy('programa_id')
                                                             ->orderBy('id','asc')
                                                             ->get();
            foreach ($iids_programas as $key => $iid_programa)
            {  
                $programa_temp   = Programa::find($iid_programa->programa_id);

                $claves = array();
                $iids_claves = InicializacionInventarioDetalle::where('inicializacion_inventario_id',$inicializacion->id)
                               ->where('programa_id',$programa_temp->id)
                               ->groupBy('clave_insumo_medico')
                               ->orderBy('id','asc')->get();
                foreach ($iids_claves as $key => $iid_clave)
                {
                    $clave_temp = Insumo::conDescripciones()->find($iid_clave->clave_insumo_medico);
                    $clave_temp->load('informacionAmpliada');
                    
                    $clave_temp->clave_insumo_medico = $clave_temp->clave;

                ///****************************************************************************************************************************
                    $data_precios =  DB::table("precios_base_detalles AS pbd")->select(DB::raw("pbd.precio as precio_unitario"),"pb.anio")
                                    ->leftJoin('precios_base AS pb', function($join){
                                                $join->on('pb.activo','=',DB::raw("1"));
                                                $join->on('pbd.precio_base_id', '=', 'pb.id');
                                              })
                                    ->where('pbd.insumo_medico_clave',$clave_temp->clave)
                                    ->where('pbd.deleted_at',NULL)->first();

                    if($data_precios)
                    {
                        $clave_temp->anio             = $data_precios->anio;
                        $clave_temp->precio_unitario  = $data_precios->precio_unitario;
                    }else{
                            $clave_temp->anio             = NULL;
                            $clave_temp->precio_unitario  = NULL;
                         }
                 ///****************************************************************************************************************************
                    $lotes = array();
                    $iids_lotes = InicializacionInventarioDetalle::where('inicializacion_inventario_id',$inicializacion->id)
                                                                  ->where('programa_id',$programa_temp->id)
                                                                  ->where('clave_insumo_medico',$clave_temp->clave_insumo_medico)
                                                                  ->orderBy('id','asc')
                                                                  ->get();

                    foreach ($iids_lotes as $key => $iid_lote)
                    {
                        array_push($lotes,$iid_lote);
                    }

                    $clave_temp->lotes = $lotes;
                    array_push($claves,$clave_temp);
                }

                $programa_temp->insumos = $claves;
                array_push($programas,$programa_temp);
            }

            $inicializacion->programas = $programas;

            return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $inicializacion), 200);

        }else{
                return Response::json(array("status" => 404,"messages" => "No se encuentra la inicialización de Inventario"), 404);
             }


///*******************************************************************************************************************************************              
///*******************************************************************************************************************************************

    }




/////                             U  P   D   A   T  E

///***************************************************************************************************************************
///***************************************************************************************************************************

    public function update(Request $request, $id)
    {
        $parametros = Input::only('q','page','per_page','almacen');
        $parametros['almacen'] = $request->get('almacen_id');
        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }  
        
        $input_data = (object)Input::json()->all();
        $almacen = Almacen::find($parametros['almacen']);
        $almacen_id  = $almacen->id;
        $clues       = $request->get('clues');
        $servidor_id = property_exists($input_data, "servidor_id") ? $input_data->servidor_id : env('SERVIDOR_ID');

        $inicializacion = InicializacionInventario::find($input_data->id);

        if( ! $inicializacion)
        {
            return Response::json(array("status" => 404,"messages" => "No se encuentra la inicialización de Inventario"), 404);
        }

        if($inicializacion->estatus == "INICIALIZADO")
        {
            return Response::json(array("status" => 409,"messages" => "Esta inicialización ya esta cerrada !"), 409);
        }


        $errors     = array();
        $nuevo      = 0;

        $cantidad_programas = 0;
        $cantidad_claves    = 0;
        $cantidad_insumos   = 0;
        $cantidad_lotes     = 0;
        $monto_total        = 0;
///*****************************************************************************************************************************************
///*****************************************************************************************************************************************
if($input_data->estatus=="INICIALIZADO")
{
        if(property_exists($input_data, "programas"))
        {
            $programas_x = $input_data->programas;
            if(count($programas_x) > 0 )
            {
                foreach ($programas_x as $key => $programa_validar)
                {
                    $validacion_programa = $this->validarPrograma($programa_validar);
                    $programa_validar    = (object) $programa_validar;

                    if($validacion_programa != "")
                    {   array_push($errors, $validacion_programa);  }

                    ///**********************************************************************************************************************
                            if(count($programa_validar->insumos) > 0 )
                            {
                                foreach ($programa_validar->insumos as $key => $insumo_validar)
                                {
                                    $validacion_insumo = $this->validarInsumo($insumo_validar);
                                    $insumo_validar    = (object) $insumo_validar;
                                    
                                    if($validacion_insumo != "")
                                    {   array_push($errors, $validacion_insumo);  }

                                    ///**********************************************************************************************************************
                                            if(count($insumo_validar->lotes) > 0 )
                                            {
                                                foreach ($insumo_validar->lotes as $key => $lote_validar)
                                                {
                                                    $validacion_lote = $this->validarLote($lote_validar);
                                                    $lote_validar    = (object) $lote_validar;

                                                    if($validacion_lote != "")
                                                    {   array_push($errors, $validacion_lote);  }
                                                }
                                            }else{ array_push($errors, array(array('Lotes' => array('no_items_lotes')))); }
                                    ///**********************************************************************************************************************
                                }
                            }else{ array_push($errors, array(array('Insumos' => array('no_items_insumos')))); }
                    ///**********************************************************************************************************************

                }
            }else{  array_push($errors, array(array('Programas' => array('no_items_programas'))));  }
                    
         }else{ array_push($errors, array(array('Programas' => array('no_existe_programas')))); }

}/// fin if INICIALIZADO
    

///*****************************************************************************************************************************************
///*****************************************************************************************************************************************
        if( count($errors) > 0 )
        {
            return Response::json(['error' => $errors], HttpResponse::HTTP_CONFLICT);
        } 
////*****************************************************************************************************************************************
///*****************************************************************************************************************************************
    
        $success = false;
        DB::beginTransaction();
        try{

////****************************************************************************************************************************************
        $fecha_fin = null;
        if($input_data->estatus=="INICIALIZADO")
        {
          $fecha_fin =  date("Y-m-d");  
        }


        $inicializacion->servidor_id      = $servidor_id;
        $inicializacion->clues            = $clues;
        $inicializacion->almacen_id       = $almacen_id;
        $inicializacion->estatus          = $input_data->estatus;
        //$inicializacion->fecha_inicio     = date("Y-m-d");
        $inicializacion->fecha_fin        = $fecha_fin;
        $inicializacion->observaciones    = $input_data->observaciones;
        $inicializacion->save();

        $nuevo = 1; 
            
         InicializacionInventarioDetalle::where("inicializacion_inventario_id", $inicializacion->id)->delete();

         foreach ($input_data->programas as $key => $programa)
        {
            $programa = (object) $programa;
                foreach ($programa->insumos as $key => $insumo)
                {
                     $insumo = (object) $insumo;
                     $insumo_x  = Insumo::datosUnidosis()->where('clave',$insumo->clave_insumo_medico)->first();
                     $cantidad_x_envase   = $insumo_x->cantidad_x_envase;

                     $iva_porcentaje = 0;
                     if($insumo_x->tipo == "ME")
                     { $iva_porcentaje = 0; }else{ $iva_porcentaje = 0.16; } 

                    InicializacionInventarioDetalle::where('inicializacion_inventario_id',$inicializacion->id)
                                                    ->where('almacen_id', $almacen_id)
                                                    ->where('programa_id',$programa->id)
                                                    ->where('clave_insumo_medico',$insumo->clave_insumo_medico)
                                                    ->delete();

                    foreach ($insumo->lotes as $key => $lote)
                    {
                        $lote = (object) $lote;

                        //dd(json_encode($lote));

                        if($lote->lote=="" && $lote->fecha_caducidad == "" && $lote->existencia == "")
                        {}else{
                    
                        DB::table('inicializacion_inventario_detalles')
                            ->where('inicializacion_inventario_id',$inicializacion->id)
                            ->where('almacen_id', $almacen_id)
                            ->where('programa_id',$programa->id)
                            ->where('clave_insumo_medico',$insumo->clave_insumo_medico)
                            ->where('clave_insumo_medico',$insumo->clave_insumo_medico)
                            ->where('lote',$lote->lote)
                            ->where('fecha_caducidad',$lote->fecha_caducidad)
                            ->update(['deleted_at' => NULL]);
                    
                         $iid = InicializacionInventarioDetalle::where('inicializacion_inventario_id',$inicializacion->id)
                                                               ->where('almacen_id',$almacen_id)
                                                               ->where('programa_id',$programa->id)
                                                               ->where('clave_insumo_medico',$insumo->clave_insumo_medico)
                                                               ->where('lote',$lote->lote)
                                                               ->where('fecha_caducidad',$lote->fecha_caducidad)
                                                               ->first();
                        if(!$iid){
                                    $iid = new InicializacionInventarioDetalle;
                                 }

                                    $iid->inicializacion_inventario_id = $inicializacion->id;
                                    $iid->almacen_id                   = $almacen_id;
                                    $iid->programa_id                  = $programa->id;
                                    $iid->clave_insumo_medico          = $insumo->clave_insumo_medico;
                                    $iid->lote                         = $lote->lote;
                                    $iid->exclusivo                    = $lote->exclusivo;
                                    $iid->fecha_caducidad              = $lote->fecha_caducidad;
                                    $iid->codigo_barras                = $lote->codigo_barras;
                                    $iid->existencia                   = $lote->existencia;
                                    $iid->existencia_unidosis          = ($lote->existencia * $cantidad_x_envase);
                                    $iid->precio_unitario              = $lote->precio_unitario;
                                    $iid->iva                          = ( $lote->precio_unitario * $iva_porcentaje );
                                    $iid->importe                      = ( $lote->existencia * $lote->precio_unitario );
                                    $iid->iva_importe                  = ( $lote->precio_unitario * $iva_porcentaje ) * $lote->existencia ;
                                    $iid->importe_con_iva              = ( $lote->precio_unitario + ( $lote->precio_unitario * $iva_porcentaje )) * $lote->existencia;
                                    $iid->save();

                        }

                    }

                }
        }

         $cantidad_programas  = DB::table('inicializacion_inventario_detalles')
                                ->where('inicializacion_inventario_id',$inicializacion->id)
                                ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                ->count(DB::raw('DISTINCT programa_id'));

         $cantidad_claves     = DB::table('inicializacion_inventario_detalles')
                                ->where('inicializacion_inventario_id',$inicializacion->id)
                                ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                ->count(DB::raw('DISTINCT clave_insumo_medico'));

         $cantidad_insumos    = DB::table('inicializacion_inventario_detalles')
                                    ->where('inicializacion_inventario_id',$inicializacion->id)
                                    ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                    ->sum('existencia');

         $cantidad_lotes      = DB::table('inicializacion_inventario_detalles')
                                ->where('inicializacion_inventario_id',$inicializacion->id)
                                ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                ->count('lote');
        
         $monto_total         = DB::table('inicializacion_inventario_detalles')
                                    ->where('inicializacion_inventario_id',$inicializacion->id)
                                    ->where('inicializacion_inventario_detalles.deleted_at',NULL)
                                    ->sum('importe_con_iva');

        $inicializacion->cantidad_programas = $cantidad_programas;
        $inicializacion->cantidad_claves    = $cantidad_claves;
        $inicializacion->cantidad_insumos   = $cantidad_insumos;
        $inicializacion->cantidad_lotes     = $cantidad_lotes;
        $inicializacion->monto_total        = $monto_total;
        $inicializacion->observaciones      = $input_data->observaciones;
        $inicializacion->save();

        //dd($inicializacion->id);

        if($inicializacion->estatus == "INICIALIZADO")
        {
            Stock::where("almacen_id", $almacen_id)->delete();
            
            $inis = InicializacionInventarioDetalle::where('almacen_id',$almacen_id)
                                                   ->where('inicializacion_inventario_id',$inicializacion->id)
                                                   ->get();
            $movimiento = new Movimiento;
            $movimiento->tipo_movimiento_id  = 19;
            $movimiento->almacen_id          = $almacen_id;
            $movimiento->fecha_movimiento    = date('Y-m-d');
            $movimiento->status              = "FI";
            $movimiento->observaciones       = $input_data->observaciones;
            $movimiento->save();
            
            foreach ($inis as $key => $ini)
            {           
                $new_stock = new Stock;
                $new_stock->almacen_id           = $almacen_id;
                $new_stock->clave_insumo_medico  = $ini->clave_insumo_medico;
                $new_stock->programa_id          = $ini->programa_id;
                $new_stock->marca_id             = $ini->marca_id;
                $new_stock->lote                 = $ini->lote;
                $new_stock->exclusivo            = $ini->exclusivo;
                $new_stock->fecha_caducidad      = $ini->fecha_caducidad;
                $new_stock->codigo_barras        = $ini->codigo_barras;
                $new_stock->existencia           = $ini->existencia;
                $new_stock->existencia_unidosis  = $ini->existencia_unidosis;
                $new_stock->unidosis_sueltas     = $ini->unidosis_sueltas;
                $new_stock->envases_parciales    = $ini->envases_parciales;
                $new_stock->save();

                $mi = new MovimientoInsumoS;
                $mi->movimiento_id       = $movimiento->id;
                $mi->stock_id            = $new_stock->id;
                $mi->clave_insumo_medico = $new_stock->clave_insumo_medico;
                $mi->modo_salida         = "N";
                $mi->cantidad            = $new_stock->existencia;
                $mi->cantidad_unidosis   = $new_stock->existencia_unidosis;
                $mi->precio_unitario     = $ini->precio_unitario;
                $mi->iva                 = $ini->iva;
                $mi->precio_total        = $ini->importe_con_iva;
                $mi->save();

            }

        }

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
            return Response::json(array("status" => 200,"messages" => "Guardado correctamente","data" => $inicializacion), 200);
        }else{
                DB::rollback();
                return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
             }
////****************************************************************************************************************************************
    }
     



    public function destroy($id)
    {
        
    }
 

///**************************************************************************************************************************

private function validarPrograma($request)
    { 
        $mensajes = [
                        'required'      => "Debe ingresar este campo.",
                        'integer'       => "Solo cantidades enteras.",
                    ];
        $reglas = [
                        'id'            => 'required'
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

    private function validarInsumo($request)
    { 
        $mensajes = [
                        'required'      => "Debe ingresar este campo."
                    ];
        $reglas = [
                        'clave_insumo_medico'  => 'required'
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
private function validarLote($request)
    { 
        $mensajes = [
                        'required'      => "Debe ingresar este campo.",
                        'numeric'       => "Debe ingresar una cantidad valida para precio unitario.",
                        'email'         => "formato de email invalido",
                        'unique'        => "unique",
                        'integer'       => "Solo cantidades enteras.",
                        'min'           => "La cantidad debe ser mayor de cero.",
                        'after'         => "La fecha de caducidad debe ser mayor a hoy."
                    ]; 
        $reglas   = [
                        'lote'                  => 'required',
                        'exclusivo'             => 'required|integer',
                        'fecha_caducidad'       => 'required|date|after:today', 
                        'existencia'            => 'required|integer',
                        'precio_unitario'       => 'required|numeric'
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


////**************************************************************************************************************************************************
///****************************************************************************************************************************************************
}
