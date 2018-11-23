<?php
namespace App\Http\Controllers\AlmacenGeneral;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Input;
use Request;
use Response;
use DB; 
use App\Models\AlmacenGeneral\InventarioArticulo;
use App\Models\AlmacenGeneral\InventarioMetadato;
use App\Models\AlmacenGeneral\InventarioArticuloMetadato;
use App\Models\ArticulosMetadatos;
use App\Models\AlmacenGeneral\MovimientoArticulos;
use \Excel;

/**
* Controlador InventarioArticulo
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Controlador `InventarioArticulo`: Manejo de usuarios del sistema
*
*/
class InventarioArticuloController extends Controller {
	/**
	 * Muestra una lista de los recurso según los parametros a procesar en la petición.
	 *
	 * <h3>Lista de parametros Request:</h3>
	 * <Ul>Paginación
	 * <Li> <code>$pagina</code> numero del puntero(offset) para la sentencia limit </ li>
	 * <Li> <code>$limite</code> numero de filas a mostrar por página</ li>	 
	 * </Ul>
	 * <Ul>Busqueda
	 * <Li> <code>$valor</code> string con el valor para hacer la busqueda</ li>
	 * <Li> <code>$order</code> campo de la base de datos por la que se debe ordenar la información. Por Defaul es ASC, pero si se antepone el signo - es de manera DESC</ li>	 
	 * </Ul>
	 *
	 * Ejemplo ordenamiento con respecto a id:
	 * <code>
	 * http://url?pagina=1&limite=5&order=id ASC 
	 * </code>
	 * <code>
	 * http://url?pagina=1&limite=5&order=-id DESC
	 * </code>
	 *
	 * Todo Los parametros son opcionales, pero si existe pagina debe de existir tambien limite
	 * @return Response 
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 404, "messages": "No hay resultados"),status) </code>
	 */
	public function index(){
		$datos = Request::all();
		
		// Si existe el paarametro pagina en la url devolver las filas según sea el caso
		// si no existe parametros en la url devolver todos las filas de la tabla correspondiente
		// esta opción es para devolver todos los datos cuando la tabla es de tipo catálogo
		if(array_key_exists("pagina", $datos)){
			$pagina = $datos["pagina"];
			if(isset($datos["order"])){
				$order = $datos["order"];
				if(strpos(" ".$order,"-"))
					$orden = "desc";
				else
					$orden = "asc";
				$order=str_replace("-", "", $order); 
			}
			else{
				$order = "id"; $orden = "asc";
			}
			
			if($pagina == 0){
				$pagina = 1;
			}
			if($pagina == 1)
				$datos["limite"] = $datos["limite"] - 1;
			// si existe buscar se realiza esta linea para devolver las filas que en el campo que coincidan con el valor que el usuario escribio
			// si no existe buscar devolver las filas con el limite y la pagina correspondiente a la paginación
			if(array_key_exists("buscar", $datos)){
				$columna = $datos["columna"];
				$valor   = $datos["valor"];
				$data = InventarioArticulo::with("InventarioArticuloMetadato", "Almacen", "Articulo")->orderBy($order, $orden);
				
				$search = trim($valor);
				$keyword = $search;
				$data = $data->whereNested(function($query) use ($keyword){	
						$query->Where("nombre", "LIKE", '%'.$keyword.'%'); 
				});
				
				$total = $data->get();
				$data = $data->skip($pagina-1)->take($datos["limite"])->get();
			}
			else{
				$data = InventarioArticulo::with("InventarioArticuloMetadato", "Almacen", "Articulo")->skip($pagina-1)->take($datos["limite"])->orderBy($order, $orden)->get();
				$total =  InventarioArticulo::all();
			}
			
		}
		else{
			$data = InventarioArticulo::with("InventarioArticuloMetadato", "Almacen", "Articulo")->get();
			$total = $data;
		}

		if(!$data){
			return Response::json(array("status" => 204, "messages" => "No hay resultados"),204);
		} 
		else {			
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data, "total" => count($total)), 200);			
		}
	}

	/**
	 * Crear un nuevo registro en la base de datos con los datos enviados
	 *
	 * <h4>Request</h4>
	 * Recibe un input request tipo json de los datos a almacenar en la tabla correspondiente
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 201, "messages": "Creado", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 500, "messages": "Error interno del servidor"),status) </code>
	 */
	public function store(){
		$this->ValidarParametros(Input::json()->all());			
		$datos = (object) Input::json()->all();	
		$success = false;

        DB::beginTransaction();
        try{
            $data = new InventarioArticulo;
            $success = $this->campos($datos, $data);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
        } 
        if ($success){
            DB::commit();
            return Response::json(array("status" => 201,"messages" => "Creado","data" => $data), 201);
        } 
        else{
            DB::rollback();
            return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
        }
		
	}

	/**
	 * 
	 * EXCEL
	 */
	public function excel(Request $request)
    {
        $parametros = Input::only('q','page','per_page','clues','clave_insumo','almacen','tipo','es_causes','buscar_en','seleccionar');

        //return $this->getItemsInventarioDetalles($parametros);
        Excel::create('Inventario_Almacén_General_'.$parametros['clues'].'_'.$parametros['almacen'].'_'.date('d-m-Y H-i-s'), function($excel)use($parametros)
        {
             //$excel->sheet('Reporte de almacenIventari', function($sheet) use($items)
            
            $excel->sheet('Articulos', function($sheet)use($parametros)
            {
                //$sheet->setAutoSize(true);
                //$items = $this->getItemsInventario($parametros);

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

                

                $data = InventarioArticulo::with("Articulo")->where('almacen_id',$parametros['almacen'])->get();

                foreach ($data as $y => $inventario)
				{
					$articulos_metadatos = ArticulosMetadatos::where('articulo_id',$inventario->articulo_id)->get();
					foreach ($articulos_metadatos as $z => $articulo_metadato)
					{
						if($articulo_metadato->requerido_inventario == 1)
						{
							$inv_metadato = InventarioMetadato::where('inventario_id',$inventario->id)
															  ->where('metadatos_id',$articulo_metadato->id)
															  ->first();
							$articulos_metadatos[$z]->valor = $inv_metadato->valor;
						}	
					}
					$data[$y]->inventario_metadato = $articulos_metadatos;
                    ///************************************************************************************************************************
                    $movimiento_articulo = MovimientoArticulos::where('inventario_id',$inventario->id)->first();
                    $data[$y]->movimiento_articulo = $movimiento_articulo;
                    ///************************************************************************************************************************
				}

                 //var_dump(json_encode($data));
               
                $sheet->mergeCells('A2:K2');
                $sheet->setCellValue('A2','INVENTARIO ARTICULOS PARA ALMACÉN '.$parametros['almacen'].' EN CLUES '.$parametros['clues'].' AL '.date('d-m-Y H:i:s'));
                //$sheet->row(2, array('','INVENTARIO ARTICULOS PARA ALMACÉN '.$parametros['almacen'].' EN CLUES '.$parametros['clues'].' AL '.date('d-m-Y H:i:s'),'','','','','','','','','',''));
                $sheet->row(2, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(14);
                                                    $row->setAlignment('center');
                                              });
                $sheet->setSize('A2', 220, 22);

                $sheet->row(4, array('','CRITERIOS DE BUSQUEDA : SELECCIONAR -> -- | CATEGORIA -> --'));

                $sheet->row(4, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(12);
                                              });

                $sheet->row(6, array('#','Clave','Nombre','Descripción', 'Metadatos','Lote','Fecha Cad.','Existencia','Precio Unitario','IVA','Valor Total'));
                $sheet->row(6, function($row) {
                                                    $row->setBackground('#DDDDDD');
                                                    $row->setFontWeight('bold');
                                                    $row->setFontSize(12);
                                              });
                


                $sheet->cells("A6:J6", function($cells) {
                                                            $cells->setAlignment('center');
                                                        });

                 $sheet->setSize('A6', 5, 18);
                 $sheet->setSize('B6', 15, 18);
                 $sheet->setSize('C6', 30, 18);
                 $sheet->setSize('D6', 50, 18);
                 $sheet->setSize('E6', 50, 18);

                 $sheet->setSize('F6', 10, 18);
                 $sheet->setSize('G6', 15, 18);
                 $sheet->setSize('H6', 10, 18);
                 $sheet->setSize('I6', 15, 18);
                 $sheet->setSize('J6', 15, 18);
                 $sheet->setSize('K6', 20, 18);
                 

                $num_row = 7;
                foreach($data as $item)
                {
                    //$sheet->setColumnFormat(array('J' => '0.00', 'K' => '0.00'));

                    $item = (object) $item;

                    $sheet->getStyle('I'.$num_row)->getNumberFormat()->setFormatCode("[$$-80A]#,##0.00;[RED]-[$$-80A]#,##0.00"); 
                    $sheet->getStyle('J'.$num_row)->getNumberFormat()->setFormatCode("[$$-80A]#,##0.00;[RED]-[$$-80A]#,##0.00"); 
                    $sheet->getStyle('K'.$num_row)->getNumberFormat()->setFormatCode("[$$-80A]#,##0.00;[RED]-[$$-80A]#,##0.00");
                    $metadatos_imprimir = "";
                    foreach ($item->inventario_metadato as $x => $metadato)
                    {
                        $metadatos_imprimir .= $metadato->campo." ".$metadato->valor.", ";  
                    } 

                    $sheet->appendRow(array(
                        $num_row-6,
                        'xxx',
                        $item->articulo['nombre'],
                        $item->articulo['descripcion'],
                        $metadatos_imprimir,
                        $item->lote,
                        $item->fecha_caducidad,
                        $item->existencia,
                        $item->movimiento_articulo['precio_unitario'],
                        $item->movimiento_articulo['iva'],
                        ($item->existencia) * ( $item->movimiento_articulo['precio_unitario'] + $item->movimiento_articulo['iva'] )
                        
                    ));

                    $num_row++;


                } // FIN FOREACH ITEMS
 
            });
          
        })->export('xls');
    }
	
	/**
	 * Actualizar el  registro especificado en el la base de datos
	 *
	 * <h4>Request</h4>
	 * Recibe un Input Request con el json de los datos
	 *
	 * @param  int  $id que corresponde al identificador del dato a actualizar 	 
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 304, "messages": "No modificado"),status) </code>
	 */
	public function update($id){
		$this->ValidarParametros(Input::json()->all());	

		$datos = (object) Input::json()->all();		
		$success = false;
        
        DB::beginTransaction();
        try{
        	$data = InventarioArticulo::find($id);

            if(!$data){
                return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
            }
            
            $success = $this->campos($datos, $data);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 500);
        } 
        if($success){
			DB::commit();
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		} 
		else {
			DB::rollback();
			return Response::json(array("status" => 304, "messages" => "No modificado"),200);
		}
	}

	public function campos($datos, $data){
		$success = false;
		$almacen_id = Request::header("X-Almacen-Id");
		$servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');

		$data->almacen_id	= $almacen_id;	
        $data->articulo_id	= property_exists($datos, "articulo_id") 		? $datos->articulo_id 		: $data->articulo_id;
        $data->numero_inventario	= property_exists($datos, "numero_inventario") 		? $datos->numero_inventario 		: $data->numero_inventario;
        $data->existencia	= property_exists($datos, "existencia") 		? $datos->existencia 		: $data->existencia;
        $data->observaciones	= property_exists($datos, "observaciones") 		? $datos->observaciones 		: $data->observaciones;
        $data->baja	= property_exists($datos, "baja") 		? $datos->baja 		: $data->baja;	
        
        if ($data->save()) { 

        	//verificar si existe contacto, en caso de que exista proceder a guardarlo
            if(property_exists($datos, "inventario_metadato")){
                
                //limpiar el arreglo de posibles nullos
                $inventario_metadato = array_filter($datos->inventario_metadato, function($v){return $v !== null;});

                //borrar los datos previos de articulo para no duplicar información
                InventarioArticuloMetadato::where("servidor_id", $servidor_id)->where("inventario_id", $data->id)->delete();

                //recorrer cada elemento del arreglo
                foreach ($inventario_metadato as $key => $value) {
                    //validar que el valor no sea null
                    if($value != null){
                        //comprobar si el value es un array, si es convertirlo a object mas facil para manejar.
                        if(is_array($value))
                            $value = (object) $value;

                        if($value->valor != ""){
	                        //comprobar que el dato que se envio no exista o este borrado, si existe y esta borrado poner en activo nuevamente
	                        DB::update("update inventario_metadatos set deleted_at = null where servidor_id = '$servidor_id' and inventario_id = ".$data->id." and metadatos_id = '".$value->metadatos_id."'");
	                        
	                        //si existe el elemento actualizar
	                        $item = InventarioArticuloMetadato::where("servidor_id", $servidor_id)->where("inventario_id", $data->id)->where("metadatos_id", $value->metadatos_id)->first();
	                        //si no existe crear
	                        if(!$item)
	                            $item = new InventarioArticuloMetadato;

	                        //llenar el modelo con los datos
	                        
	                        $item->inventario_id   		= $data->id; 
	                        $item->metadatos_id    		= $value->metadatos_id;
	                        $item->campo          		= $value->campo; 
	                        $item->valor    			= $value->valor; 
	                        
	                        $item->save(); 
                        }        
                    }
                }
            }       	
			$success = true;
		}  
		return $success;     						
	}
	/**
	 * Devuelve la información del registro especificado.
	 *
	 * @param  int  $id que corresponde al identificador del recurso a mostrar
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 404, "messages": "No hay resultados"),status) </code>
	 */
	public function show($id){
		$data = InventarioArticulo::with("InventarioArticuloMetadato", "Almacen", "Articulo")->find($id);			
		
		if(!$data){
			return Response::json(array("status"=> 404,"messages" => "No hay resultados"),404);
		} 
		else {				
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		}
	}
	
	/**
	 * Elimine el registro especificado del la base de datos (softdelete).
	 *
	 * @param  int  $id que corresponde al identificador del dato a eliminar
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 500, "messages": "Error interno del servidor"),status) </code>
	 */
	public function destroy($id){
		$success = false;
        DB::beginTransaction();
        try {
			$data = InventarioArticulo::find($id);
			$data->delete();
			
			$success=true;
		} 
		catch (\Exception $e) {
			return Response::json($e->getMessage(), 500);
        }
        if ($success){
			DB::commit();
			return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data), 200);
		} 
		else {
			DB::rollback();
			return Response::json(array("status" => 404, "messages" => "No se encontro el registro"), 404);
		}
	}	

	/**
	 * Validad los parametros recibidos, Esto no tiene ruta de acceso es un metodo privado del controlador.
	 *
	 * @param  Request  $request que corresponde a los parametros enviados por el cliente
	 *
	 * @return Response
	 * <code> Respuesta Error json con los errores encontrados </code>
	 */
	private function ValidarParametros($request){
		$rules = [
			"nombre" => "required|min:3"
		];
		$v = \Validator::make(Request::json()->all(), $rules );

		if ($v->fails()){
			return Response::json($v->errors());
		}
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
 ///********************************************************************************************************************************************
 ///********************************************************************************************************************************************   


}