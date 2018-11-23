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
use \Excel;


/** 
* Controlador Inventario
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-03-22
*
* Controlador `Inventario`: Controlador  para la consulta de inventarios
*
*/
class InventarioInsumosController extends Controller
{
     
     /**
	 * @api {index} /entrada-almacen/ Listar las existencias de los insumos en un almacén.
	 * @apiVersion 1.0.0
	 * @apiName ListarExistencias
	 * @apiGroup Existencia Insumos Medicos
	 *
	 * @apiParam {String} X-Almacen-Id En headers es el id del almacén del cual se requieren las entradas.
     * @apiParam {Number} page Mediante url es la pagina solicitada.
	 * @apiParam {Number} per_page Mediante url es la cantidad de elementos a listar en caso de desear paginado.
     * @apiParam {Number} buscar_en Mediante url permite los valores MIS_CLAVES Ó TODAS_LAS_CLAVES.
     * @apiParam {Number} seleccionar Mediante url permite los valores TODO, EXISTENTE y NO_EXISTENTE.
     * @apiParam {Number} tipo Mediante url es es el tipo de insumo a buscar (TODO, CAUSES, NO_CAUSES, MC y CONTROLADO).
     * @apiParam {Number} clave_insumo Mediante url permite un valor alfanumerico para buscar una clave en especifico.
     *
	 * @apiSuccess {Number} status  Codigo http de respuesta a la petición realizada.
	 * @apiSuccess {String} messages Mensaje personalizado según el codigo de respuesta.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     {
     *           "status": 200,
     *           "messages": "Operación realizada con exito",
     *           "data": {
     *               "total": 2049,
     *               "per_page": "20",
     *               "current_page": 1,
     *               "last_page": 103,
     *               "next_page_url": "http://sialapiv2.yoursoft.com.mx/public/index.php/inventario-insumos?page=2",
     *               "prev_page_url": null,
     *               "from": 1,
     *               "to": 20,
     *               "data": [
     *                { "clave_insumo_medico": "010.000.0071.00","descripcion": "BENZATINA BENCILPENICILINA DE 600 000 UI, SUSPENSIÓN INYECTABLE, ENVASE CON UN FRASCO ÁMPULA Y 5 ML DE DILUYENTE.","tipo": "ME","es_causes": "0","es_unidosis": "0","existencia": 0,"existencia_unidosis": 0,"updated_at": null},
     *               { "clave_insumo_medico": "010.000.0105.00","descripcion": "PARACETAMOL Supositorio 300 mg 3 supositorios","tipo": "ME","es_causes": "1","es_unidosis": "1","existencia": 50,"existencia_unidosis": 150,"updated_at": null},
     *               { "clave_insumo_medico": "010.000.0106.00","descripcion": "PARACETAMOL Solución oral 100 mg/ml Envase con gotero 15 ml","tipo": "ME","es_causes": "1","es_unidosis": "0","existencia": 0,"existencia_unidosis": 0,"updated_at": null}
     *               ]
     *           },
     *           "total": 20
     *       }
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
        //buscar_en:  MC , TC,       seleccionar :  NO_EXISTENTE, EXISTENTE
        $parametros = Input::only('q','page','per_page','clues','clave_insumo','almacen','tipo','es_causes','buscar_en','seleccionar');
        

        $parametros['almacen'] = $request->get('almacen_id');

        if(!$request->get('almacen_id')){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }     

        $data = array();
        $claves = NULL;

        $almacen_id = $request->get('almacen_id');

        $almacen = Almacen::find($almacen_id);
        $clues   = $almacen->clues;


            if($parametros['buscar_en'] == "MIS_CLAVES")
            {
                $claves = DB::table("clues_claves AS cc")->leftJoin('insumos_medicos AS im', 'im.clave', '=', 'cc.clave_insumo_medico')
                              ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'cc.clave_insumo_medico')
                              ->select('cc.clave_insumo_medico','im.descripcion','im.tipo','im.es_causes','es_unidosis')
                              ->where('clues',$clues);

                if($parametros['clave_insumo'] != "")
                {
                    $claves = $claves->where(function($query)use($parametros){
                        $query->where('cc.clave_insumo_medico','LIKE',"%".$parametros['clave_insumo']."%")->orWhere('im.descripcion','LIKE',"%".$parametros['clave_insumo']."%");
                    });
                }
            }
            if($parametros['buscar_en'] == "TODAS_LAS_CLAVES")
            {
                $claves = DB::table('insumos_medicos AS im')
                              ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'im.clave')
                              ->select('im.clave AS clave_insumo_medico','im.descripcion','im.tipo','im.es_causes','im.es_unidosis');
                if($parametros['clave_insumo'] != "")
                {
                    $claves = $claves->where(function($query)use($parametros){
                        $query->where('im.clave','LIKE',"%".$parametros['clave_insumo']."%")->orWhere('im.descripcion','LIKE',"%".$parametros['clave_insumo']."%");
                    });
                }
            }


            if($parametros['tipo'] == "TODO")
            {
            }else{
                if($parametros['tipo'] == "CAUSES")
                    {
                        $claves = $claves->where('im.tipo','ME')->where('es_causes',1);
                    }
                if($parametros['tipo'] == "NO_CAUSES")
                    {
                        $claves = $claves->where('im.tipo','ME')->where('es_causes',0);
                    }
                if($parametros['tipo'] == "MC")
                    {
                        $claves = $claves->where('im.tipo','MC');
                    }
                if($parametros['tipo'] == "CONTROLADO")
                    {
                        $claves = $claves->where('im.tipo','ME')->where('m.es_controlado',1);
                    }
            }


            

            $claves = $claves->get();

            foreach($claves as $clave)
            {
                $existencia = 0; $existencia_unidosis = 0;
                $updated_at = NULL;
                $stocks = Stock::with('movimientoInsumo')->where('almacen_id',$almacen_id)->where('clave_insumo_medico',$clave->clave_insumo_medico)->get();
                ////*****************************************************************************************
                        $insumo_x  = Insumo::datosUnidosis()->where('clave',$clave->clave_insumo_medico)->first();
                        $cantidad_x_envase   = $insumo_x['cantidad_x_envase'];

                        $iva_porcentaje = 0;
                        if($insumo_x['tipo'] == "ME")
                        { $iva_porcentaje = 0; }else{ $iva_porcentaje = 0.16; }
                    ////*****************************************************************************************
                $importe_temp    = 0;

                if($stocks)
                {
                    foreach ($stocks as $key => $stock) 
                    {
                        $existencia          += $stock->existencia;
                        $existencia_unidosis += $stock->existencia_unidosis;

                        $precio_unitario_con_iva = $stock->movimientoInsumo['precio_unitario'] + $stock->movimientoInsumo['iva'];
                        $existencia_real         = ( $stock->existencia_unidosis / $cantidad_x_envase );
                        $importe_temp            += ( $precio_unitario_con_iva * $existencia_real );
                    }
                }
                
                $clave->existencia          = property_exists($clave, "existencia") ? $clave->existencia : $existencia;
                $clave->existencia_unidosis = property_exists($clave, "existencia_unidosis") ? $clave->existencia_unidosis : $existencia_unidosis;

                $clave->importe_con_iva     = $importe_temp;

                $clave->updated_at          = property_exists($clave, "updated_at") ? $clave->updated_at : $updated_at;
                array_push($data,$clave);
            }

            $data_existente    = array();
            $data_no_existente = array();

            foreach ($data as $key => $clave) 
            {
                $clave = (object) ($clave);

                    if($clave->existencia > 0)
                    {
                        array_push($data_existente,$clave);
                    }else{
                            array_push($data_no_existente,$clave);
                         }
            }

            if($parametros['seleccionar'] == "EXISTENTE")
            {
                $data = $data_existente;
            }
            if($parametros['seleccionar'] == "NO_EXISTENTE")
            {
                $data = $data_no_existente;
            }


            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $itemCollection = new Collection($data);
            $perPage = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

    ///************************************************************************
                    $dataz;
                    if($currentPage > 1)
                    {
                        $tempdata = $currentPageItems;
                        foreach ($tempdata as $key => $value)
                        {
                            $dataz[] = $value;
                        }
                    }
                    else
                    {   $dataz = $currentPageItems; }
    ///************************************************************************
            $data2= new LengthAwarePaginator($dataz , count($itemCollection), $perPage);
            //$data2= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);
            $data2->setPath($request->url());

    //////*********************************************************************************************************
         if(isset($parametros['page']))
         {

            ///$resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            ///$data2 = $this->paginadorMaster($data,$resultadosPorPagina);
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $itemCollection = new Collection($data);
            $perPage = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

    ///************************************************************************
                    $dataz;
                    if($currentPage > 1)
                    {
                        $tempdata = $currentPageItems;
                        foreach ($tempdata as $key => $value)
                        {
                            $dataz[] = $value;
                        }
                    }
                    else
                    {   $dataz = $currentPageItems; }
    ///************************************************************************
            $data2= new LengthAwarePaginator($dataz , count($itemCollection), $perPage);
            //$data2= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);
            $data2->setPath($request->url());

         } else {
                    $data2 = $data;
                }
 //////*********************************************************************************************************
 //////*********************************************************************************************************

        if(count($data2) <= 0){

            return Response::json(array("status" => 404,"messages" => "No hay resultados","data" => $data2), 200);
        } 
        else{
                return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data2, "total" => count($data2)), 200);
            }

 ///****************************************************************************************************************************************
 ///****************************************************************************************************************************************
 ///****************************************************************************************************************************************       
        
    }

    public function store(Request $request)
    {       
    }

///*************************************************************************************************************************************
///*************************************************************************************************************************************

/////                             S    H    O    W 
///*************************************************************************************************************************************
///*************************************************************************************************************************************

    public function show($id)
    {   
    }
///***************************************************************************************************************************
///***************************************************************************************************************************

    public function update(Request $request, $id)
    {
    }
     
    public function destroy($id)
    {   
    }

    public function paginadorMaster($items,$perPage)
    {
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage; 

        // Get only the items you need using array_slice 
        $itemsForCurrentPage = array_slice($items, $offSet, $perPage, true);

        

        return new LengthAwarePaginator($itemsForCurrentPage, count($items), $perPage,Paginator::resolveCurrentPage(), array('path' => Paginator::resolveCurrentPath()));
    }

////*******************************************************************************************************************************************************************
public function excel(Request $request)
    {
        $parametros = Input::only('q','page','per_page','clues','clave_insumo','almacen','tipo','es_causes','buscar_en','seleccionar');

        
        //return $this->getItemsInventarioDetalles($parametros);
        Excel::create('Inventario_'.$parametros['clues'].'_'.$parametros['almacen'].'_'.date('d-m-Y H-i-s'), function($excel)use($parametros){
            

             //$excel->sheet('Reporte de almacenIventari', function($sheet) use($items)
            
            $excel->sheet('Insumos Medicos', function($sheet)use($parametros)
            {
                //$sheet->setAutoSize(true);
                $items = $this->getItemsInventario($parametros);

                $claves       = "";
                $seleccionar  = "";
                $tipo_insumos = "";
                $clave        = "";

                if($parametros['buscar_en'] == "TODAS_LAS_CLAVES")
                {
                    $claves = "TODAS LAS CLAVES";
                }else{
                        $claves = "MIS CLAVES";
                     }


                if($parametros['seleccionar'] == "TODO")
                {
                    $seleccionar = "TODOS INSUMOS";
                }  
                if($parametros['seleccionar'] == "EXISTENTE")
                {
                    $seleccionar = "INSUMOS EXISTENTES";
                } 
                if($parametros['seleccionar'] == "NO_EXISTENTE")
                {
                    $seleccionar = "INSUMOS AGOTADOS";
                }  

                if($parametros['tipo'] == "TODO")
                {
                    $tipo_insumos = "TODOS";
                }
                if($parametros['tipo'] == "CAUSES")
                {
                    $tipo_insumos = "MED. CAUSES";
                }
                if($parametros['tipo'] == "NO_CAUSES")
                {
                    $tipo_insumos = "MED. NO CAUSES";
                } 
                if($parametros['tipo'] == "CONTROLADO")
                {
                    $tipo_insumos = "MED. CONTROLADO";
                } 

                 
                

                
               
                $sheet->row(2, array('','INVENTARIO DE ALMACÉN '.$parametros['almacen'].' EN CLUES '.$parametros['clues'].' AL '.date('d-m-Y H:i:s'),'','','','','','','','','',''));
                $sheet->row(2, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(14);
                                              });

                $sheet->row(4, array('','BUSQUEDA EN : '.$claves.' | SELECCIONAR : '.$seleccionar.' | TIPO INSUMOS : '.$tipo_insumos.' | CLAVE : '.$parametros['clave_insumo']));

                $sheet->row(4, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(12);
                                              });

                $sheet->row(6, array('Clave','Descripción', 'C.P.D','C.P.S','C.P.M','Existencia','Existencia Unidosis','Valor'));
                $sheet->row(6, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(12);
                                              });
                


                $sheet->cells("A6:M6", function($cells) {
                                                            $cells->setAlignment('center');
                                                        });

                 $sheet->setSize('A2', 25, 18);
                 $sheet->setSize('B2', 70, 18);
                 $sheet->setSize('F2', 20, 18);
                 $sheet->setSize('G2', 30, 18);

                 $sheet->setSize('A4', 25, 18);
                 $sheet->setSize('B4', 70, 18);
                 $sheet->setSize('F4', 20, 18);
                 $sheet->setSize('G4', 30, 18);
                 
                 $sheet->setSize('A6', 25, 18);
                 $sheet->setSize('B6', 70, 18);
                 $sheet->setSize('F6', 20, 18);
                 $sheet->setSize('G6', 30, 18);
                 $sheet->setSize('H6', 30, 18);

                foreach($items as $item)
                {
                    //$sheet->setColumnFormat(array('J' => '0.00', 'K' => '0.00'));

                    $sheet->appendRow(array(
                        
                        $item->clave_insumo_medico,
                        $item->descripcion,
                        "--",
                        "--",
                        "--",
                        $item->existencia,
                        $item->existencia_unidosis,
                        $item->importe_con_iva
                    )); 


                } // FIN FOREACH ITEMS
 
            });
            $excel->sheet('Insumos Medicos Detalles', function($sheet)use($parametros)
            {
                //$sheet->setAutoSize(true);
                $items = $this->getItemsInventarioDetalles($parametros);

                $claves       = "";
                $seleccionar  = "";
                $tipo_insumos = "";
                $clave        = "";

                if($parametros['buscar_en'] == "TODAS_LAS_CLAVES")
                {
                    $claves = "TODAS LAS CLAVES";
                }else{
                        $claves = "MIS CLAVES";
                     }


                if($parametros['seleccionar'] == "TODO")
                {
                    $seleccionar = "TODOS INSUMOS";
                }  
                if($parametros['seleccionar'] == "EXISTENTE")
                {
                    $seleccionar = "INSUMOS EXISTENTES";
                } 
                if($parametros['seleccionar'] == "NO_EXISTENTE")
                {
                    $seleccionar = "INSUMOS AGOTADOS";
                }  

                if($parametros['tipo'] == "TODO")
                {
                    $tipo_insumos = "TODOS";
                }
                if($parametros['tipo'] == "CAUSES")
                {
                    $tipo_insumos = "MED. CAUSES";
                }
                if($parametros['tipo'] == "NO_CAUSES")
                {
                    $tipo_insumos = "MED. NO CAUSES";
                } 
                if($parametros['tipo'] == "CONTROLADO")
                {
                    $tipo_insumos = "MED. CONTROLADO";
                } 

                 
                

                
               
                $sheet->row(2, array('','INVENTARIO DE ALMACÉN '.$parametros['almacen'].' EN CLUES '.$parametros['clues'].' AL '.date('d-m-Y H:i:s'),'','','','','','','','','',''));
                $sheet->row(2, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(14);
                                              });

                $sheet->row(4, array('','BUSQUEDA EN : '.$claves.' | SELECCIONAR : '.$seleccionar.' | TIPO INSUMOS : '.$tipo_insumos.' | CLAVE : '.$parametros['clave_insumo']));

                $sheet->row(4, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(12);
                                              });

                $sheet->row(6, array('Clave','Descripción', 'C.P.D','C.P.S','C.P.M', 'Lote', 'Caducidad','Existencia','Existencia Unidosis', 'P. Unitario C/IVA', 'Precio Total'));
                $sheet->row(6, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(12);
                                              });
                $sheet->cells("A6:M6", function($cells) {
                                                            $cells->setAlignment('center');
                                                        });
                 $sheet->setColumnFormat(array('J' => '0.00','K' => '0.00'));

                 $sheet->setSize('A2', 25, 18);
                 $sheet->setSize('B2', 70, 18);
                 $sheet->setSize('F2', 20, 18);
                 $sheet->setSize('G2', 30, 18);

                 $sheet->setSize('A4', 25, 18);
                 $sheet->setSize('B4', 70, 18);
                 $sheet->setSize('F4', 20, 18);
                 $sheet->setSize('G4', 30, 18);
                 
                 $sheet->setSize('A6', 25, 18);
                 $sheet->setSize('B6', 70, 18);
                 $sheet->setSize('F6', 10, 18);
                 $sheet->setSize('G6', 15, 18);
                 $sheet->setSize('H6', 20, 18);
                 $sheet->setSize('I6', 25, 18);
                 $sheet->setSize('J6', 30, 18);
                 $sheet->setSize('K6', 20, 18);

                foreach($items as $item)
                {
                    $sheet->appendRow(array(
                        
                        $item->clave_insumo_medico,
                        $item->descripcion,
                        "--",
                        "--",
                        "--",                        
                        $item->lote,
                        $item->fecha_caducidad,
                        $item->existencia,
                        $item->existencia_unidosis,
                        $item->precio_unitario,
                        $item->precio_total
                    )); 
                } // FIN FOREACH ITEMS
 
            });
          
        })->export('xls');
    }
////*******************************************************************************************************************************************************************
////***************************************************************************************************************************************************
public function getItemsInventario($parametros)
    {
        

        if(!$parametros['almacen']){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }     

        $data = array();
        $claves = NULL;

        $almacen_id = $parametros['almacen'];

        $almacen = Almacen::find($almacen_id);
        $clues   = $almacen->clues;

            if($parametros['buscar_en'] == "MIS_CLAVES")
            {
                $claves = DB::table("clues_claves AS cc")->leftJoin('insumos_medicos AS im', 'im.clave', '=', 'cc.clave_insumo_medico')
                              ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'cc.clave_insumo_medico')
                              ->select('cc.clave_insumo_medico','im.clave','im.descripcion','im.tipo','im.es_causes','im.es_unidosis')
                              ->where('clues',$clues);

                if($parametros['clave_insumo'] != "")
                {
                   // $claves = $claves->where('cc.clave_insumo_medico',$parametros['clave_insumo']);
                   $claves = $claves->where('im.descripcion','LIKE',"%".$parametros['clave_insumo']."%");
                                                
                }
                
            }
            if($parametros['buscar_en'] == "TODAS_LAS_CLAVES")
            {
                $claves = DB::table('insumos_medicos AS im')
                              ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'im.clave')
                              ->select('im.clave AS clave_insumo_medico','im.descripcion','im.tipo','im.es_causes','im.es_unidosis');

                if($parametros['clave_insumo'] != "")
                {
                   // $claves = $claves->where('im.clave',$parametros['clave_insumo']);
                   $claves = $claves->where('im.descripcion','LIKE',"%".$parametros['clave_insumo']."%");
                }

            }


            if($parametros['tipo'] == "TODO")
            {
            }else{
                    if($parametros['tipo'] == "CAUSES")
                        {
                            $claves = $claves->where('im.tipo','ME')->where('es_causes',1);
                        }
                    if($parametros['tipo'] == "NO_CAUSES")
                        {
                            $claves = $claves->where('im.tipo','ME')->where('es_causes',0);
                        }
                    if($parametros['tipo'] == "MC")
                        {
                            $claves = $claves->where('im.tipo','MC');
                        }
                    if($parametros['tipo'] == "CONTROLADO")
                        {
                            $claves = $claves->where('im.tipo','ME')->where('m.es_controlado',1);
                        }
                  }


            if($parametros['clave_insumo'] != "")
            {
                /*
                $claves = $claves->where(function($query) use ($parametros) {
                                                $query->where('im.descripcion','LIKE',"%".$parametros['clave_insumo']."%")
                                                ->orWhere('im.clave','LIKE',"%".$parametros['clave_insumo']."%");
                                                });
                                                */
            }

            $claves = $claves->get();
            foreach($claves as $clave)
            {
                $existencia = 0; $existencia_unidosis = 0;
                $updated_at = NULL;
                $stocks = Stock::where('almacen_id',$almacen_id)->where('clave_insumo_medico',$clave->clave_insumo_medico)->get();
                ////*****************************************************************************************
                        $insumo_x  = Insumo::datosUnidosis()->where('clave',$clave->clave_insumo_medico)->first();
                        $cantidad_x_envase   = $insumo_x['cantidad_x_envase'];

                        $iva_porcentaje = 0;
                        if($insumo_x['tipo'] == "ME")
                        { $iva_porcentaje = 0; }else{ $iva_porcentaje = 0.16; }
                ////*****************************************************************************************
                 $importe_temp    = 0;

                if($stocks)
                {
                    foreach ($stocks as $key => $stock) 
                    {
                        $existencia          += $stock->existencia;
                        $existencia_unidosis += $stock->existencia_unidosis;

                        $precio_unitario_con_iva = $stock->movimientoInsumo['precio_unitario'] + $stock->movimientoInsumo['iva'];
                        $existencia_real         = ( $stock->existencia_unidosis / $cantidad_x_envase );
                        $importe_temp            += ( $precio_unitario_con_iva * $existencia_real );
                    }
                }
                
                $clave->existencia          = property_exists($clave, "existencia") ? $clave->existencia : $existencia;
                $clave->existencia_unidosis = property_exists($clave, "existencia_unidosis") ? $clave->existencia_unidosis : $existencia_unidosis;
                $clave->importe_con_iva     = $importe_temp;
                $clave->updated_at          = property_exists($clave, "updated_at") ? $clave->updated_at : $updated_at;
                array_push($data,$clave);
            }

            //return $data;
            $data_existente    = array();
            $data_no_existente = array();

            foreach ($data as $key => $clave) 
            {
                $clave = (object) ($clave);

                    if($clave->existencia > 0)
                    {
                        array_push($data_existente,$clave);
                    }else{
                            array_push($data_no_existente,$clave);
                         }
            }

            if($parametros['seleccionar'] == "EXISTENTE")
            {
                $data = $data_existente;
            }
            if($parametros['seleccionar'] == "NO_EXISTENTE")
            {
                $data = $data_no_existente;
            }

            return $data;
    }

    public function getItemsInventarioDetalles($parametros)
    {
        

        if(!$parametros['almacen']){
            return Response::json(array("status" => 404,"messages" => "Debe especificar un almacen."), 200);
        }     

        $data = array();
        $claves = NULL;

        $almacen_id = $parametros['almacen'];

        $almacen = Almacen::find($almacen_id);
        $clues   = $almacen->clues;

            if($parametros['buscar_en'] == "MIS_CLAVES")
            {
                $claves = DB::table("clues_claves AS cc")->leftJoin('insumos_medicos AS im', 'im.clave', '=', 'cc.clave_insumo_medico')
                              ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'cc.clave_insumo_medico')
                              ->select('cc.clave_insumo_medico','im.clave','im.descripcion','im.tipo','im.es_causes','im.es_unidosis')
                              ->where('clues',$clues);

                if($parametros['clave_insumo'] != "")
                {
                   // $claves = $claves->where('cc.clave_insumo_medico',$parametros['clave_insumo']);
                   $claves = $claves->where('im.descripcion','LIKE',"%".$parametros['clave_insumo']."%");
                                                
                }
                
            }
            if($parametros['buscar_en'] == "TODAS_LAS_CLAVES")
            {
                $claves = DB::table('insumos_medicos AS im')
                              ->leftJoin('medicamentos AS m', 'm.insumo_medico_clave', '=', 'im.clave')
                              ->select('im.clave AS clave_insumo_medico','im.descripcion','im.tipo','im.es_causes','im.es_unidosis');

                if($parametros['clave_insumo'] != "")
                {
                   // $claves = $claves->where('im.clave',$parametros['clave_insumo']);
                   $claves = $claves->where('im.descripcion','LIKE',"%".$parametros['clave_insumo']."%");
                }

            }


            if($parametros['tipo'] == "TODO")
            {
            }else{
                    if($parametros['tipo'] == "CAUSES")
                        {
                            $claves = $claves->where('im.tipo','ME')->where('es_causes',1);
                        }
                    if($parametros['tipo'] == "NO_CAUSES")
                        {
                            $claves = $claves->where('im.tipo','ME')->where('es_causes',0);
                        }
                    if($parametros['tipo'] == "MC")
                        {
                            $claves = $claves->where('im.tipo','MC');
                        }
                    if($parametros['tipo'] == "CONTROLADO")
                        {
                            $claves = $claves->where('im.tipo','ME')->where('m.es_controlado',1);
                        }
                  }


            if($parametros['clave_insumo'] != "")
            {
                /*
                $claves = $claves->where(function($query) use ($parametros) {
                                                $query->where('im.descripcion','LIKE',"%".$parametros['clave_insumo']."%")
                                                ->orWhere('im.clave','LIKE',"%".$parametros['clave_insumo']."%");
                                                });
                                                */
            }

            $claves = $claves->get();
            $data_completo = array();
            
            foreach($claves as $clave)
            {
                $existencia = 0; $existencia_unidosis = 0;
                $updated_at = NULL;
                $stocks = Stock::with("movimientoInsumo")->where('almacen_id',$almacen_id)->where('clave_insumo_medico',$clave->clave_insumo_medico)->get();

                $contador = 0;
                if($stocks)
                {
                    foreach ($stocks as $key => $stock) 
                    {
                        //$data_flag = array();    
                        //echo $stock->existencia."--";

                        $count = count($data_completo);
                        $data_completo[$count]['clave_insumo_medico'] = $clave->clave_insumo_medico;
                        $data_completo[$count]['descripcion'] = $clave->descripcion;
                        $data_completo[$count]['es_causes'] = $clave->es_causes;
                        $data_completo[$count]['es_unidosis'] = $clave->es_unidosis;
                        $data_completo[$count]['tipo'] = $clave->tipo;
                        $data_completo[$count]['existencia'] = $stock->existencia;
                        $data_completo[$count]['existencia_unidosis'] = $stock->existencia_unidosis;
                        $data_completo[$count]['lote'] = $stock->lote;
                        $data_completo[$count]['fecha_caducidad'] = $stock->fecha_caducidad;
                        if($stock->existencia == "")
                            $stock->existencia = 0;
                        if($stock->existencia_unidosis == "")
                            $stock->existencia_unidosis = 0;

                        
                        if(isset($stock->movimientoinsumo))
                        {
                            $precio_unitario_medicamento = ($stock->movimientoinsumo->precio_unitario + $stock->movimientoinsumo->iva);
                            $data_completo[$count]['precio_unitario'] = $precio_unitario_medicamento;
                            $data_completo[$count]['precio_total'] = ($stock->existencia * $precio_unitario_medicamento);
                        }else{
                            $precio_unitario_medicamento = 0;
                            $data_completo[$count]['precio_unitario'] = $precio_unitario_medicamento;
                            $data_completo[$count]['precio_total'] = 0;
                        }

                       
                        $contador ++;
                        
                    }
                }
                if($contador == 0)
                {
                    $count = count($data_completo);
                       
                    $data_completo[$count]['clave_insumo_medico'] = $clave->clave_insumo_medico;
                    $data_completo[$count]['descripcion'] = $clave->descripcion;
                    $data_completo[$count]['es_causes'] = $clave->es_causes;
                    $data_completo[$count]['es_unidosis'] = $clave->es_unidosis;
                    $data_completo[$count]['tipo'] = $clave->tipo;
                    $data_completo[$count]['existencia'] = 0;
                    $data_completo[$count]['existencia_unidosis'] = 0;
                    $data_completo[$count]['lote'] = "";
                    $data_completo[$count]['fecha_caducidad'] = "";
                    $data_completo[$count]['precio_unitario'] = 0;
                    $data_completo[$count]['precio_total'] = 0;
                    
                }
            }

            //return $data;
            $data_existente    = array();
            $data_no_existente = array();
            $data_sin_filtro = array();

            foreach ($data_completo as $key => $clave) 
            {
                $clave = (object) ($clave);

                    if($clave->existencia > 0)
                    {
                        array_push($data_existente,$clave);
                    }else{
                            array_push($data_no_existente,$clave);
                         }
                    array_push($data_sin_filtro,$clave);
            }

            if($parametros['seleccionar'] == "EXISTENTE")
            {
                $data_completo = $data_existente;
            }else if($parametros['seleccionar'] == "NO_EXISTENTE")
            {
                $data_completo = $data_no_existente;
            }else
            {
                $data_completo = $data_sin_filtro;
            }

            return $data_completo;
    }
        
 ///****************************************************************************************************************************************
 ///****************************************************************************************************************************************       

 
}
