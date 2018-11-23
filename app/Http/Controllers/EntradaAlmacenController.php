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
use App\Models\StockBorrador;
use App\Models\MovimientoInsumos;
use App\Models\MovimientoInsumosBorrador;
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
use App\Models\PersonalClues;



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

class EntradaAlmacenController extends Controller
{
     


    /**
	 * @api {index} /entrada-almacen/ Listar las entradas realizadas en un almacén.
	 * @apiVersion 1.0.0
	 * @apiName ListarEntradas
	 * @apiGroup Entrada Almacen de Medicamentos
	 *
	 * @apiParam {String} X-Almacen-Id En headers es el id del almacén del cual se requieren las entradas.
	 * @apiParam {Number} per_page Mediante url es la cantidad de elementos a listar en caso de desear paginado.
     *
	 * @apiSuccess {Number} status  Codigo http de respuesta a la petición realizada.
	 * @apiSuccess {String} messages Mensaje personalizado según el codigo de respuesta.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": "Operación realizada con exito",
     *       "data" : [
     *                  {
     *                       "id": "00012312",
     *                       "servidor_id": "0001",
     *                       "incremento": "2312",
     *                       "almacen_id": "0001165",
     *                       "tipo_movimiento_id": "1",
     *                       "status": "BR",
     *                       "fecha_movimiento": "2017-12-21",
     *                       "programa_id": "3",
     *                       "observaciones": "",
     *                       "cancelado": "0",
     *                       "observaciones_cancelacion": "",
     *                       "usuario_id": "root",
     *                       "created_at": "2017-12-21 12:21:02",
     *                       "updated_at": "2017-12-21 12:21:02",
     *                       "deleted_at": null,
     *                       "numero_claves": 0,
     *                       "numero_insumos": 0,
     *                       "movimiento_metadato": {
     *                           "id": "0001378",
     *                           "incremento": "378",
     *                           "servidor_id": "0001",
     *                           "movimiento_id": "00012312",
     *                           "folio_colectivo": null,
     *                           "servicio_id": null,
     *                           "turno_id": null,
     *                           "persona_recibe": "",
     *                           "usuario_id": "root",
     *                           "created_at": "2017-12-21 12:21:02",
     *                           "updated_at": "2017-12-21 12:21:02",
     *                           "deleted_at": null,
     *                           "turno": null,
     *                           "servicio": null
     *                       },
     *                       "movimiento_usuario": {
     *                           "id": "root",
     *                           "servidor_id": "0001",
     *                           "password": "$2y$10$g/HhW189eZmGo1RjvoclZ.uLNp7CMoe7WscGXmmSsn.iHOrPksyHe",
     *                           "nombre": "Super",
     *                           "apellidos": "Usuario",
     *                           "avatar": "avatar-circled-root",
     *                           "modulo_inicio": null,
     *                           "proveedor_id": null,
     *                           "su": "1",
     *                           "created_at": null,
     *                           "updated_at": null,
     *                           "deleted_at": null
     *                       },
     *                       "movimiento_receta": null
     *                       }
     *                ]
	 *     }
	 *
     * @apiError 403 El usuario no tiene permisos para realizar la consulta.
	 * @apiError 404 No se encontraron reultados de movimientos con los criterios de busqueda.
     * @apiError 409 Ocurrió un problema logico al consultar los datos.
     * @apiError 500 Ocurrió un problema con el servidor.
	 *
	 * @apiErrorExample Error-Response:
	 *     HTTP/1.1 404 Not Found
	 *     {
     *       "status": 404,
	 *       "messages": "No hay resultados"
	 *     }
	 */ 
    public function index(Request $request)
    {
        $parametros = Input::only('q','page','per_page','almacen','tipo','fecha_desde','fecha_hasta','recibe','turno','servicio');
        $parametros['almacen'] = $request->get('almacen_id');

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }  
        
        $almacen = Almacen::find($parametros['almacen']);
        $movimientos = NULL;
        $data = NULL;

        $movimientos = DB::table("movimientos AS mov")
                             ->leftJoin('movimiento_metadatos AS mm', 'mm.movimiento_id', '=', 'mov.id')
                             ->leftJoin('usuarios AS users', 'users.id', '=', 'mov.usuario_id')
                             ->select('mov.*','mm.servicio_id','mm.turno_id','users.nombre')
                             ->where('mov.almacen_id',$parametros['almacen'])
                             ->where('mov.tipo_movimiento_id',1)
                             ->where('mov.deleted_at',null)
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
        //dd(json_encode($movimientos)); die();
        $data = array();

        foreach($movimientos as $mov)
        {
            $movimiento_response = Movimiento::with('movimientoMetadato','movimientoUsuario','movimientoReceta')
                                             ->where('id',$mov->id)->first();

            $cantidad_claves  = MovimientoInsumos::where('movimiento_id',$movimiento_response->id)->distinct('clave_insumo_medico')->count();
            $cantidad_insumos = DB::table('movimiento_insumos')
                                    ->where('movimiento_id', '=', $movimiento_response->id)
                                    ->where('movimiento_insumos.deleted_at',null)->sum('cantidad');

            if($cantidad_claves  == NULL){ $cantidad_claves  = 0 ; }
            if($cantidad_insumos == NULL){ $cantidad_insumos = 0 ; }

            $movimiento_response->numero_claves  = $cantidad_claves;
            $movimiento_response->numero_insumos = $cantidad_insumos;

            $movimiento_response->estatus = $movimiento_response->status;

            

            array_push($data,$movimiento_response);
        }


        $indice_adds = 0;
        $data2       = null;

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




            ///***************************************************************************************************************************************
                $movimientos_all = Movimiento::with('movimientoMetadato','movimientoUsuario')
                                            ->where('tipo_movimiento_id',1)
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
        
        if(count($data) <= 0)
        { 

            $data[0] = array ("turnos_disponibles" => $array_turnos, "servicios_disponibles" => $array_servicios);
            return Response::json(array("status" => 404,"messages" => "No hay resultados","data" => $data), 200);

        }else{
            
               if(isset($parametros['page']))
                {
                    $data2[$indice_adds] = array ("turnos_disponibles" => $array_turnos, "servicios_disponibles" => $array_servicios);

                    return Response::json(array("status" => 200,"messages" => "Operación realizada con exito ...", "data" => $data2, "total" => $total), 200);

                }else{
                        $data[$indice_adds] = array ("turnos_disponibles" => $array_turnos, "servicios_disponibles" => $array_servicios);
                        return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data, "total" => $total), 200);
                     }
                
            
             }
    }

   




/**
	 * @api {store} /entrada-almacen/ Insertar una entrada nueva.
	 * @apiVersion 1.0.0
	 * @apiName NuevaEntrada
	 * @apiGroup Entrada Almacen de Medicamentos
	 *
	 * @apiParam {String} X-Almacen-Id En headers es el id del almacén del cual se requieren las entradas.
     *
	 *
     *
     * @apiExample {js} Envio de Petición store:
     *
	 *     {
     *           "id": "",
     *           "actualizar": false,
     *           "tipo_movimiento_id": 1,
     *           "status": "FI",
     *           "fecha_movimiento": "2017-12-26T06:00:00.000Z",
     *           "observaciones": "TODO MUY OK OK",
     *           "programa_id": "3",
     *           "cancelado": "",
     *           "observaciones_cancelacion": "",
     *           "movimiento_metadato": {
     *               "persona_recibe": "PEDRITO DEMO",
     *               "servicio_id": null,
     *               "turno_id": null
     *           },
     *           "insumos": [
     *               {
     *               "clave": "010.000.4255.00",
     *               "nombre": "Genericos",
     *               "descripcion": "CIPROFLOXACINO Cápsula ó tableta 250 mg 8 cápsulas ó tabletas",
     *               "es_causes": "1",
     *               "es_unidosis": "1",
     *               "lote": "202020",
     *               "id": null,
     *               "codigo_barras": "",
     *               "fecha_caducidad": "2020-05-05",
     *               "cantidad": 700,
     *               "cantidad_x_envase": 8,
     *               "cantidad_surtida": 1,
     *               "movimiento_insumo_id": null,
     *               "stock_id": null
     *               },
     *               {
     *               "clave": "010.000.2144.00",
     *               "nombre": "Genericos",
     *               "descripcion": "LORATADINA Tableta o gragea 10 mg 20 tabletas o grageas",
     *               "es_causes": "1",
     *               "es_unidosis": "1",
     *               "lote": "101010",
     *               "id": null,
     *               "codigo_barras": "",
     *               "fecha_caducidad": "2020-10-10",
     *               "cantidad": 500,
     *               "cantidad_x_envase": 20,
     *               "cantidad_surtida": 1,
     *               "movimiento_insumo_id": null,
     *               "stock_id": null
     *               }
     *           ]
     *           }
     *
     *
     * @apiSuccess {Number} status  Codigo http de respuesta a la petición realizada.
	 * @apiSuccess {String} messages Mensaje personalizado según el codigo de respuesta.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 201 OK
	 *     {
     *           "status": 201,
     *           "messages": "Creado",
     *           "refrescar": true,
     *           "data": {
     *               "almacen_id": "0001165",
     *               "tipo_movimiento_id": 1,
     *               "status": "FI",
     *               "fecha_movimiento": "2017-12-26T06:00:00.000Z",
     *               "programa_id": "3",
     *               "observaciones": "TODO MUY OK OK",
     *               "cancelado": "",
     *               "observaciones_cancelacion": "",
     *               "servidor_id": "0001",
     *               "incremento": 2314,
     *               "id": "00012314",
     *               "usuario_id": "root",
     *               "updated_at": "2017-12-26 13:29:23",
     *               "created_at": "2017-12-26 13:29:23"
     *           }
     *       }
     *
	 *
     * @apiError 409 Ocurrió un problema logico al realizar el guardado.
     * @apiError 500 Ocurrió un problema con el servidor.
	 *
	 * @apiErrorExample Error-Response:
	 *     HTTP/1.1 404 Not Found
	 *     {
     *       "status": 500,
	 *       "messages": "Internal server error"
	 *     }
	 */

    public function store(Request $request)
    {
        $errors = array(); 

        $almacen_id=$request->get('almacen_id');       

        
        $datos = (object) Input::json()->all();	
        $success = false;

        $id_tipo_movimiento = $datos->tipo_movimiento_id;
        $tipo_movimiento = TiposMovimientos::Find($datos->tipo_movimiento_id);

        $tipo = NULL;
        if($tipo_movimiento)
            $tipo = $tipo_movimiento->tipo;

///*************************************************************************************************************************************

            if($datos->estatus == "BR")
            {
                $success = false;

                DB::beginTransaction();
                try{

                    $movimiento_entrada_br = new Movimiento;
                
            
                    $servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');

                //agregar al modelo los datos
                $movimiento_entrada_br->almacen_id                   =  $almacen_id;
                $movimiento_entrada_br->tipo_movimiento_id           =  $datos->tipo_movimiento_id;
                $movimiento_entrada_br->status                       =  $datos->estatus; 
                $movimiento_entrada_br->fecha_movimiento             =  property_exists($datos, "fecha_movimiento")               ? $datos->fecha_movimiento          : '';
                $movimiento_entrada_br->programa_id                  =  $datos->programa_id;
                $movimiento_entrada_br->observaciones                =  property_exists($datos, "observaciones")                  ? $datos->observaciones             : '';
                $movimiento_entrada_br->cancelado                    =  property_exists($datos, "cancelado")                      ? $datos->cancelado                 : '';
                $movimiento_entrada_br->observaciones_cancelacion    =  property_exists($datos, "observaciones_cancelacion")      ? $datos->observaciones_cancelacion : '';

                $movimiento_entrada_br->save(); 

                if(property_exists($datos,"movimiento_metadato"))
                {
                    $metadatos = new MovimientoMetadato;
                    $metadatos->movimiento_id  = $movimiento_entrada_br->id;
                    //$metadatos->servicio_id    = $datos->movimiento_metadato['servicio_id'];
                    $metadatos->persona_recibe = $datos->movimiento_metadato['persona_recibe'];

                    //dd($metadatos); die();


                    $metadatos->save();   
                }

                /////****************************************************************************************************************************
                    //MovimientoInsumos::where("movimiento_id", $movimiento_entrada_br->id)->delete(); 
                    
                    $movimientos_insumos_grabados = array();

                    if(property_exists($datos, "insumos"))
                    {
                        if(count($datos->insumos) > 0 )
                        {
                            $detalle = array_filter($datos->insumos, function($v){return $v !== null;});
                            foreach ($detalle as $key => $insumo)
                                {
                                    //$validacion_insumos = $this->ValidarInsumos($key, NULL, $insumo, $tipo);
                                    $insumo = (object) $insumo;                                    
                                    //if($validacion_insumos == "")
                                       // {
                                            //insertar avance stock y mov_insumos
                                            $insumo_info         = Insumo::datosUnidosis()->where('clave',$insumo->clave)->first();
                                            $cantidad_x_envase   = $insumo_info->cantidad_x_envase;
                                            $precio_insumo       = $this->conseguirPrecio($insumo->clave);

                                            $stock_borrador = new StockBorrador;
                                            $stock_borrador->almacen_id             = $almacen_id;
                                            $stock_borrador->clave_insumo_medico    = $insumo->clave;
                                            $stock_borrador->marca_id               = NULL;
                                            $stock_borrador->lote                   = $insumo->lote;
                                            $stock_borrador->fecha_caducidad        = $insumo->fecha_caducidad;
                                            $stock_borrador->codigo_barras          = $insumo->codigo_barras;
                                            $stock_borrador->existencia             = 0;
                                            $stock_borrador->existencia_unidosis    = 0;
                                            $stock_borrador->unidosis_sueltas       = 0;
                                            $stock_borrador->envases_parciales      = 0;
                                            $stock_borrador->save();

                                            $movimiento_insumo_br = new MovimientoInsumosBorrador;
                                            $movimiento_insumo_br->movimiento_id           = $movimiento_entrada_br->id; 
                                            $movimiento_insumo_br->stock_id       = $stock_borrador->id;
                                            $movimiento_insumo_br->clave_insumo_medico     = $insumo->clave;
                                            $movimiento_insumo_br->modo_salida             = "N";
                                            $movimiento_insumo_br->cantidad                = $insumo->cantidad;
                                            $movimiento_insumo_br->cantidad_unidosis       = $insumo->cantidad * $insumo->cantidad_x_envase;
                                            $movimiento_insumo_br->precio_unitario         = $precio_insumo['precio_unitario'];
                                            $movimiento_insumo_br->iva                     = $precio_insumo['iva']; 
                                            $movimiento_insumo_br->precio_total            = ( $precio_insumo['precio_unitario'] + $precio_insumo['iva'] ) * $insumo->cantidad;
                                            $movimiento_insumo_br->save();

                                            array_push($movimientos_insumos_grabados,$movimiento_insumo_br);

                                       // }
                                }/// fin foreach de insumos 
                        }
                    }// if existe propiedad insumos 
                 
                ////*****************************************************************************************************************************
                $success = true;
                } catch (\Exception $e){
                    DB::rollback();
                    return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                } 
                if ($success){
                                DB::commit();
                                return Response::json(array("status" => 201,"messages" => "Borrador creado","data" => $movimiento_entrada_br), 201);
                             }else{
                                    DB::rollback();
                                    return Response::json(array("status" => 409,"messages" => "Conflicto al guardar borrador"), 409);
                                  }

            }  // fin if status = BR


            $validacion = $this->ValidarMovimiento("", NULL, Input::json()->all(),$almacen_id);
            if(is_array($validacion))
            {
                return Response::json(['error' => $validacion], HttpResponse::HTTP_CONFLICT);
            }


                if(property_exists($datos, "insumos"))
                {
                    if(count($datos->insumos) > 0 )
                    {
                        $detalle = array_filter($datos->insumos, function($v){return $v !== null;});
                        foreach ($detalle as $key => $value)
                            {
                                $validacion_insumos = $this->ValidarInsumos($key, NULL, $value, $tipo, $datos->fecha_movimiento);
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

                DB::beginTransaction();
                try{
                        $movimiento_entrada = new Movimiento;
                        $success = $this->validarTransaccionEntrada($datos, $movimiento_entrada,$almacen_id);
                } catch (\Exception $e) {
                    DB::rollback();
                    return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                } 
                if ($success){
                    DB::commit();
                    return Response::json(array("status" => 201,"messages" => "Creado","refrescar"=>true,"data" => $movimiento_entrada), 201);
                } 
                else{
                    DB::rollback();
                    return Response::json(array("status" => 409,"messages" => "Conflicto"), 409);
                }
        
///*************************************************************************************************************************************

    }

///*************************************************************************************************************************************
///*************************************************************************************************************************************
/////                             S    H    O    W 
///*************************************************************************************************************************************
///*************************************************************************************************************************************



/**
	 * @api {show} /movimientos/id Ver una Entrada.
	 * @apiVersion 1.0.0
	 * @apiName VerEntrada
	 * @apiGroup Entrada Almacen de Medicamentos
	 *
	 * @apiParam {Number} id El id de la entrada a solicitar.
     *
	 * @apiSuccess {Number} status  Codigo http de respuesta a la petición realizada.
	 * @apiSuccess {String} messages Mensaje personalizado según el codigo de respuesta.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
	 *       "status": 200,
	 *       "messages": "Operación realizada con exito",
     *       "data" : {
     *                  {
     *                       "status": 200,
     *                      "messages": "Operación realizada con exito",
     *                       "data": {
     *                          "id": "00012305",
     *                           "servidor_id": "0001",
     *                           "incremento": "2305",
     *                           "almacen_id": "0001165",
     *                           "tipo_movimiento_id": "2",
     *                           "status": "FI",
     *                           "fecha_movimiento": "2017-12-05",
     *                           "programa_id": null,
     *                           "observaciones": "",
     *                           "cancelado": "0",
     *                           "observaciones_cancelacion": "",
     *                           "usuario_id": "root",
     *                           "created_at": "2017-12-05 12:17:08",
     *                           "updated_at": "2017-12-05 12:17:08",
     *                           "deleted_at": null,
     *                           "insumos": [
     *                           {
     *                               "clave": "010.000.5940.01",
     *                               "tipo": "ME",
     *                               "generico_id": "1689",
     *                               "es_causes": "0",
     *                               "es_unidosis": "1",
     *                               "tiene_fecha_caducidad": "1",
     *                               "descontinuado": "0",
     *                               "descripcion": "IBUPROFENO TABLETA O CÁPSULA 200 MG ENVASE CON 12 TABLETAS",
     *                               "created_at": null,
     *                               "updated_at": null,
     *                               "deleted_at": null,
     *                               "generico_nombre": "Genericos",
     *                               "es_cuadro_basico": "0",
     *                               "nombre": "Genericos",
     *                               "modo_salida": "N",
     *                               "cantidad": "2.00",
     *                               "cantidad_solicitada": "2.00",
     *                               "detalles": {
     *                               "clave": "010.000.5940.01",
     *                               "tipo": "ME",
     *                               "generico_id": "1689",
     *                               "es_causes": "0",
     *                               "es_unidosis": "1",
     *                               "tiene_fecha_caducidad": "1",
     *                               "descontinuado": "0",
     *                               "descripcion": "IBUPROFENO TABLETA O CÁPSULA 200 MG ENVASE CON 12 TABLETAS",
     *                               "created_at": null,
     *                               "updated_at": null,
     *                               "deleted_at": null,
     *                               "generico_nombre": "Genericos",
     *                               "es_cuadro_basico": "0",
     *                               "informacion_ampliada": {...}
     *                               },
     *                               "lotes": [
     *                               {
     *                                   "id": "00023",
     *                                   "clave_insumo_medico": "010.000.5940.01",
     *                                   "marca_id": null,
     *                                   "lote": "LOTE101030",
     *                                   "codigo_barras": "",
     *                                   "fecha_caducidad": "2018-08-10",
     *                                   "modo_salida": "N",
     *                                   "cantidad": "2.00"
     *                               }
     *                               ]
     *                           }
     *                           ],
     *                          "insumos_negados": [],
     *                           "almacen": {...},
     *                           "movimiento_metadato": {
     *                           "id": "0001374",
     *                           "incremento": "374",
     *                           "servidor_id": "0001",
     *                           "movimiento_id": "00012305",
     *                           "folio_colectivo": null,
     *                           "servicio_id": "122",
     *                           "turno_id": "2",
     *                           "persona_recibe": "Maria Victoria Castellanos",
     *                           "usuario_id": "root",
     *                           "created_at": "2017-12-05 12:17:08",
     *                           "updated_at": "2017-12-05 12:17:08",
     *                           "deleted_at": null,
     *                           "turno": {...},
     *                           "servicio": {...}
     *                           }
     *                       }
     *                       } 
     *                }
	 *     }
	 *
     * @apiError 403 El usuario no tiene permisos para realizar la consulta.
	 * @apiError 404 No se encontraron reultados de movimientos con los criterios de busqueda.
     * @apiError 409 Ocurrió un problema logico al consultar los datos.
     * @apiError 500 Ocurrió un problema con el servidor.
	 *
	 * @apiErrorExample Error-Response:
	 *     HTTP/1.1 404 Not Found
	 *     {
     *       "status": 404,
	 *       "messages": "No hay resultados"
	 *     }
	 */

    public function show($id)
    {
        $movimiento =  Movimiento::with('almacen','movimientoMetadato')->find($id);

        if(!$movimiento){
			return Response::json(array("status" => 404,"messages" => "No se encuentra el movimiento solicitado"), 200);
		} 
        $movimiento = (object) $movimiento;

        $estatus = $movimiento->status;
///**************************************************************************************************************************************
///**************************************************************************************************************************************
            $insumos = DB::table('movimiento_insumos')
                        ->join('stock', 'movimiento_insumos.stock_id', '=', 'stock.id')
                        ->where('movimiento_insumos.movimiento_id', '=', $id)
                        ->where('movimiento_insumos.deleted_at',null)
                        ->groupby('stock.clave_insumo_medico')
                        ->select(DB::raw('SUM(movimiento_insumos.cantidad) as total_insumo'), 'stock.clave_insumo_medico','modo_salida')
                        ->get();

            if($estatus == "BR")
                {
                    $insumos = DB::table('movimiento_insumos_borrador')
                        ->join('stock_borrador', 'movimiento_insumos_borrador.stock_id', '=', 'stock_borrador.id')
                        ->where('movimiento_insumos_borrador.movimiento_id', '=', $id)
                        ->where('movimiento_insumos_borrador.deleted_at',null)
                        ->groupby('stock_borrador.clave_insumo_medico')
                        ->select(DB::raw('SUM(movimiento_insumos_borrador.cantidad) as total_insumo'), 'stock_borrador.clave_insumo_medico','modo_salida')
                        ->get();
                }



            
            $array_insumos = array();   

        ///*****************************************************************************************
            foreach($insumos as $insumo)
            {
                    $objeto_insumo = new \stdClass();
                    $array_lotes = array();

                    $insumos2 = DB::table('movimiento_insumos')
                                ->where('movimiento_id',$id)
                                ->where('deleted_at',null)
                                ->get();

                    if($estatus == "BR")
                        {
                            $insumos2 = DB::table('movimiento_insumos_borrador')
                                ->where('movimiento_id',$id)
                                ->where('deleted_at',null)
                                ->get();
                        }

                    foreach($insumos2 as $insumo2)
                    {
                        $lote = DB::table('stock')->find($insumo2->stock_id);
                        if($estatus == "BR")
                            {
                                $lote = DB::table('stock_borrador')->find($insumo2->stock_id);
                            }
                        
                        if($insumo->clave_insumo_medico == $lote->clave_insumo_medico)
                        {
                            $objeto_lote = new \stdClass();
                            $objeto_lote->id                  = $lote->id;
                            $objeto_lote->clave_insumo_medico = $lote->clave_insumo_medico;
                            $objeto_lote->marca_id            = $lote->marca_id;
                            $objeto_lote->lote                = $lote->lote;
                            $objeto_lote->codigo_barras       = $lote->codigo_barras;
                            $objeto_lote->fecha_caducidad     = $lote->fecha_caducidad;

                            $objeto_lote->movimiento_insumo_id= $insumo2->id;
                            $objeto_lote->stock_id            = $insumo2->stock_id;

                            $objeto_lote->modo_salida         = $insumo2->modo_salida;
                            $objeto_lote->cantidad            = $insumo2->cantidad;

                            array_push($array_lotes,$objeto_lote);
                        }
                    }

                    $insumo_detalles      = Insumo::conDescripciones()->find($insumo->clave_insumo_medico);
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

              array_push($array_insumos,$objeto_insumo);
            }
        ///*****************************************************************************************
         
                $movimiento->insumos = $array_insumos;
                $movimiento->estatus = $movimiento->status;
       

////**************************************************************************************************************************************
////**************************************************************************************************************************************
 
		return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $movimiento), 200);        
    }   




///*********************    F U N  C  T I O N        U  P  D  A  T  E      ************************************************************
///***************************************************************************************************************************
///***************************************************************************************************************************
    public function update(Request $request, $id)
    {
////************************************************************************************************************************************        
        $errors = array(); 

        $almacen_id=$request->get('almacen_id');       

        $datos = (object) Input::json()->all();	
        $success = false;
        $id_tipo_movimiento = $datos->tipo_movimiento_id;
        $tipo_movimiento = TiposMovimientos::find($datos->tipo_movimiento_id);
        $tipo = NULL;
        if($tipo_movimiento)
            $tipo = $tipo_movimiento->tipo;

            if($datos->estatus == "BR")
            {
                $success = false;

                DB::beginTransaction();
                try{

                $movimiento_entrada_br = Movimiento::find($id);
                                
                $servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');

                //agregar al modelo los datos
                $movimiento_entrada_br->almacen_id                   =  $almacen_id;
                $movimiento_entrada_br->tipo_movimiento_id           =  $datos->tipo_movimiento_id;
                $movimiento_entrada_br->status                       =  $datos->estatus; 
                $movimiento_entrada_br->fecha_movimiento             =  property_exists($datos, "fecha_movimiento")               ? $datos->fecha_movimiento          : '';
                $movimiento_entrada_br->programa_id                  =  $datos->programa_id;
                $movimiento_entrada_br->observaciones                =  property_exists($datos, "observaciones")                  ? $datos->observaciones             : '';
                $movimiento_entrada_br->cancelado                    =  property_exists($datos, "cancelado")                      ? $datos->cancelado                 : '';
                $movimiento_entrada_br->observaciones_cancelacion    =  property_exists($datos, "observaciones_cancelacion")      ? $datos->observaciones_cancelacion : '';

                $movimiento_entrada_br->save(); 

                 
                $metadatos = MovimientoMetadato::where("movimiento_id",$movimiento_entrada_br->id)->first();

                $metadatos->servicio_id    = $datos->movimiento_metadato['servicio_id'];
                $metadatos->persona_recibe = $datos->movimiento_metadato['persona_recibe']; 

                    //dd(json_encode($metadatos)); die(); 

                $metadatos->save();   
                 

                /////****************************************************************************************************************************
                    $movimientos_insumos_grabados = array();

                    if(property_exists($datos, "insumos"))
                    {
                        if(count($datos->insumos) > 0 )
                        {
                            $detalle = array_filter($datos->insumos, function($v){return $v !== null;});
                            foreach ($detalle as $key => $insumo)
                                {
                                    //$validacion_insumos = $this->ValidarInsumos($key, NULL, $insumo, $tipo);
                                    $insumo = (object) $insumo;
                                    //if($validacion_insumos == "")
                                       // {
                                            //insertar avance stock y mov_insumos
                                            $insumo_info         = Insumo::datosUnidosis()->where('clave',$insumo->clave)->first();
                                            $cantidad_x_envase   = $insumo_info->cantidad_x_envase;
                                            $precio_insumo       = $this->conseguirPrecio($insumo->clave);

                                            ///****************************************************************************************************
                                            ///****************************************************************************************************
                                            ///****************************************************************************************************

                                            // si trae id de stock
                                            if($insumo->stock_id != NULL)
                                            {
                                                $stock_borrador = StockBorrador::find($insumo->stock_id);

                                                $stock_borrador->almacen_id             = $almacen_id;
                                                $stock_borrador->clave_insumo_medico    = $insumo->clave;
                                                $stock_borrador->marca_id               = NULL;
                                                $stock_borrador->lote                   = $insumo->lote;
                                                $stock_borrador->fecha_caducidad        = $insumo->fecha_caducidad;
                                                $stock_borrador->codigo_barras          = $insumo->codigo_barras;
                                                $stock_borrador->existencia             = 0;
                                                $stock_borrador->existencia_unidosis    = 0;
                                                $stock_borrador->unidosis_sueltas       = 0;
                                                $stock_borrador->envases_parciales      = 0;
                                                $stock_borrador->save();

                                                $movimiento_insumo_br = MovimientoInsumosBorrador::find($insumo->movimiento_insumo_id);

                                                $movimiento_insumo_br->movimiento_id           = $movimiento_entrada_br->id; 
                                                $movimiento_insumo_br->stock_id                = $stock_borrador->id;
                                                $movimiento_insumo_br->clave_insumo_medico     = $insumo->clave;
                                                $movimiento_insumo_br->modo_salida             = "N";
                                                $movimiento_insumo_br->cantidad                = $insumo->cantidad;
                                                $movimiento_insumo_br->cantidad_unidosis       = $insumo->cantidad * $insumo->cantidad_x_envase;
                                                $movimiento_insumo_br->precio_unitario         = $precio_insumo['precio_unitario'];
                                                $movimiento_insumo_br->iva                     = $precio_insumo['iva']; 
                                                $movimiento_insumo_br->precio_total            = ( $precio_insumo['precio_unitario'] + $precio_insumo['iva'] ) * $insumo->cantidad;
                                                $movimiento_insumo_br->save();

                                                array_push($movimientos_insumos_grabados,$movimiento_insumo_br);


                                            }else{
                                                    

                                                        $stock_borrador = new StockBorrador;
                                                        $stock_borrador->almacen_id             = $almacen_id;
                                                        $stock_borrador->clave_insumo_medico    = $insumo->clave;
                                                        $stock_borrador->marca_id               = NULL;
                                                        $stock_borrador->lote                   = $insumo->lote;
                                                        $stock_borrador->fecha_caducidad        = $insumo->fecha_caducidad;
                                                        $stock_borrador->codigo_barras          = $insumo->codigo_barras;
                                                        $stock_borrador->existencia             = 0;
                                                        $stock_borrador->existencia_unidosis    = 0;
                                                        $stock_borrador->unidosis_sueltas       = 0;
                                                        $stock_borrador->envases_parciales      = 0;
                                                        $stock_borrador->save();

                                                        $movimiento_insumo_br = new MovimientoInsumosBorrador;
                                                        $movimiento_insumo_br->movimiento_id           = $movimiento_entrada_br->id; 
                                                        $movimiento_insumo_br->stock_id                = $stock_borrador->id;
                                                        $movimiento_insumo_br->clave_insumo_medico     = $insumo->clave;
                                                        $movimiento_insumo_br->modo_salida             = "N";
                                                        $movimiento_insumo_br->cantidad                = $insumo->cantidad;
                                                        $movimiento_insumo_br->cantidad_unidosis       = $insumo->cantidad * $insumo->cantidad_x_envase;
                                                        $movimiento_insumo_br->precio_unitario         = $precio_insumo['precio_unitario'];
                                                        $movimiento_insumo_br->iva                     = $precio_insumo['iva']; 
                                                        $movimiento_insumo_br->precio_total            = ( $precio_insumo['precio_unitario'] + $precio_insumo['iva'] ) * $insumo->cantidad;
                                                        $movimiento_insumo_br->save();

                                                    array_push($movimientos_insumos_grabados,$movimiento_insumo_br);

                                                 }/// si trae   id de stock borrador

                                            ///****************************************************************************************************
                                            ///****************************************************************************************************
                                       // }
                                }
                        }
                    }  // si existe la propiedad insumos 


                    /////    VALIDAR DUPLICADOS
                    $movs_insumos = MovimientoInsumosBorrador::where('movimiento_id',$movimiento_entrada_br->id)->get();
                    
                    if($movs_insumos)
                    {
                        foreach($movs_insumos as $mi_db)
                        {   
                            $mi_db  = (object) $mi_db;
                            $borrar = true;
                            
                            foreach($movimientos_insumos_grabados as $mi_gra)
                            {
                                $mi_gra = (object) $mi_gra;

                                if($mi_db->id == $mi_gra->id) /// si el movimiento insumo de la base existe en los grabados. no se borra
                                {
                                    $borrar = false;
                                }
                            }

                            if($borrar == true)
                            {
                                $mi_delete = MovimientoInsumosBorrador::find($mi_db->id);
                                $sb_delete = StockBorrador::find($mi_db->stock_id);
                                $sb_delete->delete();
                                $mi_delete->delete();
                            }

                        }
                    }else{
                            /// borrar las 2 tablas insumos borrador y stock borrador del movimiento borrador
                         }




                ////*****************************************************************************************************************************
                $success = true;
                } catch (\Exception $e){
                    DB::rollback();
                    return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
                } 
                if ($success){
                                DB::commit();
                                $movimiento_entrada_br->actualizar = true;
                                return Response::json(array("status" => 201,"messages" => "Borrador creado","data" => $movimiento_entrada_br), 201);
                             }else{
                                    DB::rollback();
                                    return Response::json(array("status" => 409,"messages" => "Conflicto al guardar borrador"), 409);
                                  }

            }  // fin if status = BR






            $validacion = $this->ValidarMovimiento("", NULL, Input::json()->all(),$almacen_id);
            if(is_array($validacion))
            {
                return Response::json(['error' => $validacion], HttpResponse::HTTP_CONFLICT);
            }


            if(property_exists($datos, "insumos"))
            {
                    if(count($datos->insumos) > 0 )
                    {
                        $detalle = array_filter($datos->insumos, function($v){return $v !== null;});
                        foreach ($detalle as $key => $value)
                            {
                                $validacion_insumos = $this->ValidarInsumos($key, NULL, $value, $tipo, $datos->fecha_movimiento);
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

                DB::beginTransaction();
                try{
                        $movimiento_entrada = new Movimiento;
                        $movimiento_entrada = Movimiento::find($id);
                        $success = $this->validarTransaccionEntradaUpdate($datos, $movimiento_entrada,$almacen_id);

                    } catch (\Exception $e) {
                                                DB::rollback();
                                                return Response::json(["status" => 500, 'error' => "ERROR AL EJECUTAR TRANSACCIÓN. ".$e->getMessage()], 500);
                                            } 
                if($success)
                {
                    $movs_insumos_borrador = MovimientoInsumosBorrador::where('movimiento_id',$movimiento_entrada->id)->get();
                    foreach($movs_insumos_borrador as $mi_borrador)
                    {
                        $mi_borrador = (object) $mi_borrador;
                        $si_borrador = StockBorrador::find($mi_borrador->stock_id);
                        if($si_borrador)
                        {
                            $si_borrador->delete();
                            $mi_borrador->delete();
                        }
                    }

                    DB::commit();
                    return Response::json(array("status" => 201,"messages" => "Creado","data" => $movimiento_entrada), 201);
                }else{
                        DB::rollback();
                        return Response::json(array("status" => 409,"messages" => "Conflicto"), 409);
                     }
        
///*************************************************************************************************************************************


 
    }///  FIN UPDATE FUNCTION
     



///***************************************************************************************************************************
///***************************************************************************************************************************
 



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
                    'tipo_movimiento_id' => 'required|integer|in:1,2,3,4,5,6,7,8,9,10,11,12',
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

        }
        
        if($request['tipo_movimiento_id'] == 5 )
            {
                $request_temp = (object)$request;
                if(property_exists($request_temp,'receta'))
                {
                        
                    $reglas = [
                                'tipo_movimiento_id'    => 'required|integer|in:5',
                                'receta.folio'          => 'required|string',
                                'receta.tipo_receta_id'    => 'required|integer',
                                'receta.fecha_receta'   => 'required',
                                'receta.doctor'         => 'required|string',
                                'receta.paciente'       => 'required|string',
                                'receta.diagnostico'    => 'required|string'
                            ];

                            $receta = $request['receta'];
                            $receta_buscar = Receta::where("folio",$receta['folio'])->first();

                            if($receta_buscar)
                            { 
                                array_push($errors_validar_movimiento, array(array('receta' => array('Folio duplicado'))));
                                return $errors_validar_movimiento;
                            }  
                }
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

///**************************************************************************************************************************
///**************************************************************************************************************************  
    private function ValidarInsumos($key, $id, $request,$tipo,$fecha_validacion)
    { 
        $mensajes = [
                        'required'      => "Debe ingresar este campo.",
                        'email'         => "formato de email invalido",
                        'unique'        => "unique",
                        'integer'       => "Solo cantidades enteras.",
                        'min'           => "La cantidad debe ser mayor de cero.",
                        'after'         => "La fecha de caducidad debe ser mayor a la fecha del movimiento."
                    ];
         
        $reglas = [
                        'clave'                 => 'required',
                        'cantidad'              => 'required|integer|min:0',
                        'cantidad_x_envase'     => 'required|integer',
                        'lote'                  => 'required',
                        'fecha_caducidad'       => 'required|date|after:'.$fecha_validacion,      
                  ];
                         
        $v = \Validator::make($request, $reglas, $mensajes );
        $mensages_validacion = array();
 
        if ($v->fails())
        {
            foreach ($v->errors()->messages() as $indice => $item)  // todos los mensajes de todos los campos
            {
                $msg_validacion = array();
                    foreach ($item as $msg)
                    {
                         array_push($mensages_validacion, $msg);
                    }
			}  
			return $mensages_validacion;
        }else{
                return ;
             }
	}
///***************************************************************************************************************************
///                  M O V I M I E N T O         E  N  T  R  A  D  A
///***************************************************************************************************************************


   

    private function validarTransaccionEntrada($datos, $movimiento_entrada,$almacen_id){
		$success = false;
        //comprobar que el servidor id no me lo envian por parametro, si no poner el servidor por default de la configuracion local, si no seleccionar el servidor del parametro
        $servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');

        //agregar al modelo los datos
        $movimiento_entrada->almacen_id                   =  $almacen_id;
        $movimiento_entrada->tipo_movimiento_id           =  $datos->tipo_movimiento_id;
        $movimiento_entrada->status                       =  $datos->estatus; 
        $movimiento_entrada->fecha_movimiento             =  property_exists($datos, "fecha_movimiento")          ? $datos->fecha_movimiento          : '';
        $movimiento_entrada->programa_id                  =  $datos->programa_id;
        $movimiento_entrada->observaciones                =  property_exists($datos, "observaciones")             ? $datos->observaciones             : '';
        $movimiento_entrada->cancelado                    =  property_exists($datos, "cancelado")                 ? $datos->cancelado                 : '';
        $movimiento_entrada->observaciones_cancelacion    =  property_exists($datos, "observaciones_cancelacion") ? $datos->observaciones_cancelacion : '';

        // si se guarda el maestro tratar de guardar el detalle  
        if( $movimiento_entrada->save() )
        {
            $success = true;

            if(property_exists($datos,"movimiento_metadato"))
            {
                $metadatos = new MovimientoMetadato;
                $metadatos->movimiento_id  = $movimiento_entrada->id;
                $metadatos->servicio_id    = $datos->movimiento_metadato['servicio_id'];
                $metadatos->persona_recibe = $datos->movimiento_metadato['persona_recibe'];


                $metadatos->save();   
            }

            //verificar si existe contacto, en caso de que exista proceder a guardarlo
            if(property_exists($datos, "insumos")){
                 $detalle = array_filter($datos->insumos, function($v){return $v !== NULL;});


                 foreach ($detalle as $key => $value)
                {
                     if($value != NULL)
                     {
                         if(is_array($value))
                            $value = (object) $value;

                        $precio_insumo = $this->conseguirPrecio($value->clave);

                        //*************************************************************************************
                        //Verificar si esta en la lista de negados
                        $negacion = NegacionInsumo::where('almacen_id',$almacen_id)
                                                ->where('clave_insumo_medico',$value->clave)
                                                ->first();
                        if($negacion)
                        {
                            $negacion->delete();
                        }
                        //*************************************************************************************

                        $item_stock = new Stock;

                        $item_stock->almacen_id             = $almacen_id;
                        $item_stock->clave_insumo_medico    = $value->clave;
                        $item_stock->marca_id               = NULL;
                        $item_stock->lote                   = $value->lote;
                        $item_stock->fecha_caducidad        = $value->fecha_caducidad;
                        $item_stock->codigo_barras          = $value->codigo_barras;
                        $item_stock->existencia             = $value->cantidad;
                        $item_stock->existencia_unidosis    = ( $value->cantidad_x_envase * $value->cantidad );

                        $item_stock_check = Stock::where('clave_insumo_medico',$value->clave)
                                                 ->where('lote',$value->lote)
                                                 ->where('fecha_caducidad',$value->fecha_caducidad)
                                                 ->where('codigo_barras',$value->codigo_barras)
                                                 ->where('almacen_id',$almacen_id)->first();
                        if($item_stock_check)
                        {
                            $item_stock_check->existencia           = $item_stock_check->existencia + $value->cantidad;
                            $item_stock_check->existencia_unidosis  = $item_stock_check->existencia_unidosis + ( $value->cantidad_x_envase * $value->cantidad );
                            
                            if( $item_stock_check->save() )
                            {
                                    $item_detalles = new MovimientoInsumos;

                                    $item_detalles->movimiento_id           = $movimiento_entrada->id; 
                                    $item_detalles->stock_id                = $item_stock_check->id;
                                    $item_detalles->clave_insumo_medico     = $value->clave;

                                    $item_detalles->modo_salida             = "N";
                                    $item_detalles->cantidad                = $value->cantidad;
                                    $item_detalles->cantidad_unidosis       = $value->cantidad * $value->cantidad_x_envase;

                                    $item_detalles->precio_unitario         = $precio_insumo['precio_unitario'];
                                    $item_detalles->iva                     = $precio_insumo['iva']; 
                                    $item_detalles->precio_total            = ( $precio_insumo['precio_unitario'] + $precio_insumo['iva'] ) * $value->cantidad;

                                    $item_detalles->save(); 
                            }else{   
                                    return Response::json(['error' => $validacion_insumos], HttpResponse::HTTP_CONFLICT);
                                }

                        }else{
                                    if($item_stock->save())
                                    {
                                        $item_detalles = new MovimientoInsumos;

                                        $item_detalles->movimiento_id           = $movimiento_entrada->id; 
                                        $item_detalles->stock_id                = $item_stock->id;
                                        $item_detalles->clave_insumo_medico     = $value->clave;

                                        $item_detalles->modo_salida             = "N";
                                        $item_detalles->cantidad                = $item_stock->existencia;
                                        $item_detalles->cantidad_unidosis       = $item_stock->existencia_unidosis;

                                        $item_detalles->precio_unitario         = $precio_insumo['precio_unitario'];
                                        $item_detalles->iva                     = $precio_insumo['iva']; 
                                        $item_detalles->precio_total            = ( $precio_insumo['precio_unitario'] + $precio_insumo['iva'] ) * $item_stock->existencia;

                                        $item_detalles->save();

                                    }else{
                                            return Response::json(['error' => $validacion_insumos], HttpResponse::HTTP_CONFLICT);
                                         }
                             }

                            
                    }
                }
            }               
        }
        
        return $success;
    }


///***************************************************************************************************************************
///     M O V I M I E N T O         E  N  T  R  A  D  A      U  P  D  A  T  E 
///***************************************************************************************************************************


    private function validarTransaccionEntradaUpdate($datos, $movimiento_entrada,$almacen_id){
		$success = false;
        //comprobar que el servidor id no me lo envian por parametro, si no poner el servidor por default de la configuracion local, si no seleccionar el servidor del parametro
        $servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');

        //agregar al modelo los datos
        $movimiento_entrada->almacen_id                   =  $almacen_id;
        $movimiento_entrada->tipo_movimiento_id           =  $datos->tipo_movimiento_id;
        $movimiento_entrada->status                       =  $datos->estatus; 
        $movimiento_entrada->programa_id                  =  $datos->programa_id;
        $movimiento_entrada->fecha_movimiento             =  property_exists($datos, "fecha_movimiento")          ? $datos->fecha_movimiento          : '';
        $movimiento_entrada->observaciones                =  property_exists($datos, "observaciones")             ? $datos->observaciones             : '';
        $movimiento_entrada->cancelado                    =  property_exists($datos, "cancelado")                 ? $datos->cancelado                 : '';
        $movimiento_entrada->observaciones_cancelacion    =  property_exists($datos, "observaciones_cancelacion") ? $datos->observaciones_cancelacion : '';

        // si se guarda el maestro tratar de guardar el detalle  
        if( $movimiento_entrada->save() )
        {
            $success = true;

            if(property_exists($datos,"movimiento_metadato"))
            { 
                $metadatos = MovimientoMetadato::where('movimiento_id',$movimiento_entrada->id)->first();
                $metadatos->movimiento_id  = $movimiento_entrada->id;
                $metadatos->servicio_id    = $datos->movimiento_metadato['servicio_id'];
                $metadatos->persona_recibe = $datos->movimiento_metadato['persona_recibe'];


                $metadatos->save();   
            }

            //verificar si existe contacto, en caso de que exista proceder a guardarlo
            if(property_exists($datos, "insumos")){
                 $detalle = array_filter($datos->insumos, function($v){return $v !== NULL;});


                 foreach ($detalle as $key => $value)
                {
                     if($value != NULL)
                     {
                         if(is_array($value))
                            $value = (object) $value;

                        $precio_insumo = $this->conseguirPrecio($value->clave);

                        //*************************************************************************************
                        //Verificar si esta en la lista de negados
                        $negacion = NegacionInsumo::where('almacen_id',$almacen_id)
                                                ->where('clave_insumo_medico',$value->clave)
                                                ->first();
                        if($negacion)
                        {
                            $negacion->delete();
                        }
                        //*************************************************************************************
                        //*************************************************************************************

                        $objeto_insumo = $value;
                        //  si trae id : buscarlo y actualizarlo
                        if($objeto_insumo->stock_id != NULL)
                        {

                            $item_stock_ok = new Stock;
                            $item_stock_ok->almacen_id             = $almacen_id;
                            $item_stock_ok->clave_insumo_medico    = $value->clave;
                            $item_stock_ok->marca_id               = NULL;
                            $item_stock_ok->lote                   = $value->lote;
                            $item_stock_ok->fecha_caducidad        = $value->fecha_caducidad;
                            $item_stock_ok->codigo_barras          = $value->codigo_barras;
                            $item_stock_ok->existencia             = $value->cantidad;
                            $item_stock_ok->existencia_unidosis    = ( $value->cantidad_x_envase * $value->cantidad );
                            $item_stock_ok->save();

 
                            $item_detalles = new MovimientoInsumos;

                            $item_detalles->movimiento_id           = $movimiento_entrada->id; 
                            $item_detalles->stock_id                = $item_stock_ok->id;
                            $item_detalles->clave_insumo_medico     = $value->clave;
                            $item_detalles->modo_salida             = "N";
                            $item_detalles->cantidad                = $value->cantidad;
                            $item_detalles->cantidad_unidosis       = $value->cantidad * $value->cantidad_x_envase;
                            $item_detalles->precio_unitario         = $precio_insumo['precio_unitario'];
                            $item_detalles->iva                     = $precio_insumo['iva']; 
                            $item_detalles->precio_total            = ( $precio_insumo['precio_unitario'] + $precio_insumo['iva'] ) * $value->cantidad;

                            $item_detalles->save(); 

                        }else {
                                    $item_stock_check = Stock::where('clave_insumo_medico',$value->clave)
                                                    ->where('lote',$value->lote)
                                                    ->where('fecha_caducidad',$value->fecha_caducidad)
                                                    ->where('codigo_barras',$value->codigo_barras)
                                                    ->where('almacen_id',$almacen_id)->first();

                                    if($item_stock_check)
                                    {
                                        $item_stock_check->existencia           = $item_stock_check->existencia + $value->cantidad;
                                        $item_stock_check->existencia_unidosis  = $item_stock_check->existencia_unidosis + ( $value->cantidad_x_envase * $value->cantidad );
                                        $item_stock_check->save();
                                        
                                        $item_detalles = new MovimientoInsumos;

                                        $item_detalles->movimiento_id           = $movimiento_entrada->id; 
                                        $item_detalles->stock_id                = $item_stock_check->id;
                                        $item_detalles->clave_insumo_medico     = $value->clave;
                                        $item_detalles->modo_salida             = "N";
                                        $item_detalles->cantidad                = $value->cantidad;
                                        $item_detalles->cantidad_unidosis       = $value->cantidad * $value->cantidad_x_envase;
                                        $item_detalles->precio_unitario         = $precio_insumo['precio_unitario'];
                                        $item_detalles->iva                     = $precio_insumo['iva']; 
                                        $item_detalles->precio_total            = ( $precio_insumo['precio_unitario'] + $precio_insumo['iva'] ) * $value->cantidad;
                                        $item_detalles->save(); 
                                        
                                    }else{
                                            $item_stock = new Stock;

                                            $item_stock->almacen_id             = $almacen_id;
                                            $item_stock->clave_insumo_medico    = $value->clave;
                                            $item_stock->marca_id               = NULL;
                                            $item_stock->lote                   = $value->lote;
                                            $item_stock->fecha_caducidad        = $value->fecha_caducidad;
                                            $item_stock->codigo_barras          = $value->codigo_barras;
                                            $item_stock->existencia             = $value->cantidad;
                                            $item_stock->existencia_unidosis    = ( $value->cantidad_x_envase * $value->cantidad );
                                            $item_stock->save();
                            
                                            $item_detalles = new MovimientoInsumos;

                                            $item_detalles->movimiento_id           = $movimiento_entrada->id; 
                                            $item_detalles->stock_id                = $item_stock->id;
                                            $item_detalles->clave_insumo_medico     = $value->clave;
                                            $item_detalles->modo_salida             = "N";
                                            $item_detalles->cantidad                = $value->cantidad;
                                            $item_detalles->cantidad_unidosis       = $value->cantidad * $value->cantidad_x_envase;
                                            $item_detalles->precio_unitario         = $precio_insumo['precio_unitario'];
                                            $item_detalles->iva                     = $precio_insumo['iva']; 
                                            $item_detalles->precio_total            = ( $precio_insumo['precio_unitario'] + $precio_insumo['iva'] ) * $value->cantidad;

                                            $item_detalles->save(); 

                                         }

                              }///  fin si no trae id
                           
                    }  /// diferente de NULL EL VALUE
                }
            }               
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
