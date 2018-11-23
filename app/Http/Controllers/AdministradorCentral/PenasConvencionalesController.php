<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;

use App\Models\Usuario, App\Models\Proveedor, App\Models\Presupuesto, App\Models\UnidadMedicaPresupuesto, App\Models\Pedido, App\Models\Insumo, App\Models\Almacen, App\Models\Repositorio, App\Models\LogPedidoBorrador, App\Models\PedidoPresupuestoApartado, App\Models\LogPedidoCancelado, App\Models\AjustePresupuestoPedidoRegresion, App\Models\Servidor, App\Models\AjustePedidoPresupuestoApartado;
use Carbon\Carbon;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class PenasConvencionalesController extends Controller {
	public function resumen(){
		$parametros = Input::only('clues', 'periodo','mes','proveedor','tipo_unidad');


		$porcentaje_penalizacion = 0.005;

		$proveedor_where = "";

		if($parametros['proveedor']!= "-1"){
			$proveedor_where = " AND proveedor_id = ".$parametros['proveedor'];
		}

		$anio_select = "'TODOS' as anio";
		$where = "";
		if($parametros['periodo'] != "-1"){
			$anio_select = "pedidos.anio as anio";			
			$where = " WHERE  pedidos.anio = ".$parametros['periodo'];
		}

		$mes_select = "'TODOS' as mes, 'TODOS' as mes_nombre";
		if($parametros['mes'] != "-1"){
			$mes_select = 'CASE pedidos.mes
			WHEN 1 THEN "ENERO" 
			WHEN 2 THEN "FEBRERO" 
			WHEN 3 THEN "MARZO" 
			WHEN 4 THEN "ABRIL" 
			WHEN 5 THEN "MAYO" 
			WHEN 6 THEN "JUNIO" 
			WHEN 7 THEN "JULIO" 
			WHEN 8 THEN "AGOSTO" 
			WHEN 9 THEN "SEPTIEMBRE" 
			WHEN 10 THEN "OCTUBRE" 
			WHEN 11 THEN "NOVIEMBRE" 
			ELSE "DICIEMBRE" END AS mes_nombre, pedidos.mes as mes';
			if($where != ""){
				$where .= " AND ";
			} else {
				$where .= " WHERE ";
			}
			$where .= " pedidos.mes = ".$parametros['mes'];
		}

		$tipo_unidad_select = "'TODAS' as tipo_unidad";
		$tipo_unidad_where = "";
		if($parametros['tipo_unidad'] != "-1"){
			$tipo_unidad_select = "'".$parametros['tipo_unidad']."' as tipo_unidad";
			$tipo_unidad_where = " AND unidades_medicas.tipo = '".$parametros['tipo_unidad']."'";
		}


		$clues_where = "";
		if($parametros['clues'] != ""){			
			$clues_where = " AND (unidades_medicas.clues LIKE '%".$parametros['clues']."%' OR unidades_medicas.nombre  LIKE '%".$parametros['clues']."%')";
		}


	


		$query = "
		
			SELECT
			pedidos.proveedor_id,
			proveedores.nombre_corto as proveedor,
			".$tipo_unidad_select.",
			".$mes_select.",
			".$anio_select.",
			count(pedidos.id) as pedidos,
			count(pedidos_incumplidos.id) as pedidos_incumplidos,
			SUM(IFNULL(pedidos_incumplidos.total_monto_solicitado,0.00)) as total_monto_solicitado,
			SUM(IFNULL(pedidos_incumplidos.total_monto_recibido,0.00)) as total_monto_recibido,
			(SUM(IFNULL(pedidos_incumplidos.total_monto_solicitado,0.00)) - SUM(IFNULL(pedidos_incumplidos.total_monto_recibido,0.00))) * 0.05 * 30 as monto_pena_convencional

			FROM
			(
			SELECT 
			id,
			proveedor_id,
			Month(fecha) as mes,
			Year(fecha) as anio,
			unidades_medicas.tipo

			FROM  pedidos, unidades_medicas
			WHERE pedidos.status != 'BR' AND pedidos.clues = unidades_medicas.clues ".$clues_where." ".$tipo_unidad_where." ".$proveedor_where." 
			) as pedidos

			LEFT JOIN
			(
			SELECT 
			id,
			proveedor_id,
			Month(fecha) as mes,
			Year(fecha) as anio,
			unidades_medicas.tipo,
			total_monto_solicitado,
			total_monto_recibido

			FROM  pedidos, unidades_medicas
			WHERE (status = 'EX' OR status = 'EX-CA') AND pedidos.clues = unidades_medicas.clues ".$clues_where." ".$tipo_unidad_where." ".$proveedor_where." 
			) as pedidos_incumplidos

			ON pedidos.id = pedidos_incumplidos.id

			LEFT JOIN( SELECT id, nombre_corto FROM proveedores) as proveedores ON pedidos.proveedor_id = proveedores.id 		
			
			".$where."
			GROUP BY pedidos.proveedor_id
		";

		DB::enableQueryLog();

		$data = DB::table(DB::raw("(".$query.") as chuchi"))->get();


		$query_executed = DB::getQueryLog();
		$query_executed = end($query_executed);
		return Response::json([ 'data' => $data, 'query' => $query_executed],200);
		
	}

	public function detalle(){
		$parametros = Input::only('clues', 'periodo','mes','proveedor','tipo_unidad','page','per_page');


		$porcentaje_penalizacion = 0.05;

		$proveedor_where = "";

		if($parametros['proveedor']!= "-1"){
			$proveedor_where = " AND proveedor_id = ".$parametros['proveedor'];
		}

		$anio_select = "pedidos.anio as anio";		
		$where = "";
		if($parametros['periodo'] != "-1"){
				
			$where = " AND  pedidos.anio = ".$parametros['periodo'];
		}

		$mes_select = 'CASE pedidos.mes
			WHEN 1 THEN "ENERO" 
			WHEN 2 THEN "FEBRERO" 
			WHEN 3 THEN "MARZO" 
			WHEN 4 THEN "ABRIL" 
			WHEN 5 THEN "MAYO" 
			WHEN 6 THEN "JUNIO" 
			WHEN 7 THEN "JULIO" 
			WHEN 8 THEN "AGOSTO" 
			WHEN 9 THEN "SEPTIEMBRE" 
			WHEN 10 THEN "OCTUBRE" 
			WHEN 11 THEN "NOVIEMBRE" 
			ELSE "DICIEMBRE" END AS mes_nombre, pedidos.mes as mes';

		if($parametros['mes'] != "-1"){
			
			
			$where .= " AND pedidos.mes = ".$parametros['mes'];
		}

		$tipo_unidad_select = "'TODAS' as tipo_unidad";
		$tipo_unidad_where = "";
		if($parametros['tipo_unidad'] != "-1"){
			$tipo_unidad_select = "'".$parametros['tipo_unidad']."' as tipo_unidad";
			$tipo_unidad_where = " AND unidades_medicas.tipo = '".$parametros['tipo_unidad']."'";
		}


		$clues_where = "";
		if($parametros['clues'] != ""){			
			$clues_where = " AND (unidades_medicas.clues LIKE '%".$parametros['clues']."%' OR unidades_medicas.nombre  LIKE '%".$parametros['clues']."%')";
		}

		$query = "
		
			SELECT
			pedidos.id,
			pedidos.folio,
			pedidos.clues,
			pedidos.unidad_medica,
			pedidos.proveedor_id,
			proveedores.nombre_corto as proveedor,
			".$tipo_unidad_select.",
			".$mes_select.",
			".$anio_select.",
			IFNULL(pedidos_incumplidos.total_monto_solicitado,0.00) as total_monto_solicitado,
			IFNULL(pedidos_incumplidos.total_monto_recibido,0.00) as total_monto_recibido,
			(IFNULL(pedidos_incumplidos.total_monto_solicitado,0.00) - IFNULL(pedidos_incumplidos.total_monto_recibido,0.00)) * 0.005 * 30 as monto_pena_convencional

			FROM
			(
			SELECT 
			id,
			folio,
			pedidos.clues,
			unidades_medicas.nombre as unidad_medica,
			proveedor_id,
			Month(fecha) as mes,
			Year(fecha) as anio,
			unidades_medicas.tipo

			FROM  pedidos, unidades_medicas
			WHERE pedidos.status != 'BR' AND pedidos.clues = unidades_medicas.clues ".$clues_where." ".$tipo_unidad_where." ".$proveedor_where." 
			) as pedidos

			LEFT JOIN
			(
			SELECT 
			id,
			proveedor_id,
			Month(fecha) as mes,
			Year(fecha) as anio,
			unidades_medicas.tipo,
			total_monto_solicitado,
			total_monto_recibido

			FROM  pedidos, unidades_medicas
			WHERE (status = 'EX' OR status = 'EX-CA') AND pedidos.clues = unidades_medicas.clues ".$clues_where." ".$tipo_unidad_where." ".$proveedor_where." 
			) as pedidos_incumplidos

			ON pedidos.id = pedidos_incumplidos.id

			LEFT JOIN( SELECT id, nombre_corto FROM proveedores) as proveedores ON pedidos.proveedor_id = proveedores.id 		
			WHERE total_monto_solicitado > 0 
			".$where." 
			
		";

		DB::enableQueryLog();

		$data = DB::table(DB::raw("(".$query.") as chuchi"));
		
		
		if(isset($parametros['page'])){
			$resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
			
            $data = $data->paginate($resultadosPorPagina);
        } else {
            $data = $data->get();
        }

		$query_executed = DB::getQueryLog();
		$query_executed = end($query_executed);
		return Response::json([ 'data' => $data, 'query' => $query_executed],200);
		
	}

	public function excel($id){

		$pedido = Pedido::find($id);
		if(!$pedido){
			echo "Pedido no existe";
			return;
		}
		$unidad_medica =  $pedido->unidadMedica;
		$proveedor =  $pedido->proveedor;
		
		/*
		((pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) * pedidos_insumos.precio_unitario  * IF(insumos_medicos.tipo = 'MC', 1.16, 1)) as monto,
			((pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) * pedidos_insumos.precio_unitario  * IF(insumos_medicos.tipo = 'MC', 1.16, 1)) * 0.005 * 30 as pena_convencional
		*/

		$query = "
		SELECT
			pedidos_insumos.insumo_medico_clave as clave,
			insumos_medicos.descripcion,
			tipos_insumos.nombre as tipo_insumo,
			insumos_medicos.tipo as tipo,
			((pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) * pedidos_insumos.precio_unitario) as monto,
			(((pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) * pedidos_insumos.precio_unitario) * IF(insumos_medicos.tipo = 'MC', 0.16, 0)) as iva,

			pedidos_insumos.precio_unitario,
			(pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) as cantidad_faltante		
			
			FROM pedidos_insumos, pedidos, insumos_medicos,tipos_insumos, unidades_medicas 

		WHERE	
			pedidos_insumos.pedido_id = pedidos.id 
			AND pedidos_insumos.insumo_medico_clave = insumos_medicos.clave
			AND tipos_insumos.id = pedidos_insumos.tipo_insumo_id 
			AND unidades_medicas.clues = pedidos.clues
			AND (pedidos.status = 'EX' OR pedidos.status = 'EX-CA')
			AND (pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) > 0
			AND pedidos.id = '".$id."'";

		$items = DB::table(DB::raw("(".$query.") as chuchi"))->get();

		Excel::create("Penas convencionales Pedido Folio ", function($excel) use($items,$pedido, $unidad_medica, $proveedor) {

			$excel->sheet('Reporte de penas convencionales', function($sheet) use($items,$pedido,  $unidad_medica, $proveedor) {
				$sheet->setAutoSize(true);
				
				$sheet->mergeCells('B1:K1');
				$sheet->mergeCells('B2:K2');
				$sheet->mergeCells('B3:K3');
				$sheet->mergeCells('B4:K4');

				//($item->folio)?$item->folio:'S/F'

				$sheet->row(1, array(
					'Pedido Folio',$pedido->folio?$pedido->folio: 'S/F'
				));
				$sheet->row(2, array(
					'Proveedor',$proveedor->nombre
				));
				$sheet->row(3, array(
					'Unidad Medica',$pedido->clues." ".$unidad_medica->nombre." Tipo unidad: ".$unidad_medica->tipo
				));
				$sheet->row(4, array(
					'Fecha',\PHPExcel_Shared_Date::PHPToExcel(strtotime($pedido->fecha))
				));
				
				$sheet->row(5, array(
					'Clave','Descripcion', 'Tipo', 'Cantidad','Precio unitario','Monto', 'IVA', 'Monto (con IVA)',	'% Pena Convencional', 'Días Pena Convencional','Pena convencional'
				));
				$sheet->setColumnFormat(array(
					"B4:G4" => 'dd/MM/yyyy'
				));
				$sheet->row(1, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				$sheet->row(2, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				$sheet->row(3, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				$sheet->row(4, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				$sheet->row(5, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
				});
				$contador_filas = 5;
				foreach($items as $item){
					$contador_filas++;					

					$sheet->appendRow(array(
						$item->clave,
						$item->descripcion,
						$item->tipo_insumo,
						$item->cantidad_faltante,
						$item->precio_unitario,
						$item->monto,
						$item->iva,
						'=SUM(F'.$contador_filas.':G'.$contador_filas.')',
						0.005,
						30,
						'=PRODUCT(H'.$contador_filas.':J'.$contador_filas.')'
						//$item->pena_convencional
					)); 
				}

				$sheet->appendRow(array(
					'',
					'',
					'',
					'',
					'TOTAL',
					"=SUM(F3:F$contador_filas)",
					'',
				   "=SUM(H3:H$contador_filas)",
				   '',
				   '',
				   "=SUM(K3:K$contador_filas)"
				));

				$sheet->setBorder("A1:K$contador_filas", 'thin');

				$contador_filas += 1;

				$sheet->setBorder("E$contador_filas:I$contador_filas", 'thin');
				$sheet->row($contador_filas, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				
				
				$sheet->setColumnFormat(array(
						"E6:F$contador_filas" => '"$" #,##0.00_-',
					));

				$sheet->setColumnFormat(array(
					"H6:H$contador_filas" => '"$" #,##0.00_-',
				));
				$sheet->setColumnFormat(array(
					"K6:K$contador_filas" => '"$" #,##0.00_-',
				));
			});
			})->export('xls');
	}

	public function excelResumen(){
		$parametros = Input::only('anio','mes','proveedor_id', 'tipo_unidad', 'clues');


		
		$proveedor = Proveedor::find($parametros['proveedor_id']);

		if(!$proveedor){
			echo "Proveedor no existe";
			return;
		}

		$where_clues = "";
		if($parametros["clues"] != ""){
			$where_clues = " AND (unidades_medicas.clues LIKE '%".$parametros["clues"]."%' OR unidades_medicas.nombre LIKE '%".$parametros["clues"]."%')";
		}

		$where_anio = "";
		if($parametros["anio"] != "TODOS"){
			$where_anio = " AND YEAR(pedidos.fecha) = ".$parametros["anio"];
		}

		$where_mes = "";
		if($parametros["mes"] != "TODOS"){
			$where_mes = " AND MONTH(pedidos.fecha) = ".$parametros["mes"];
		}

		$where_tipo = "";
		if($parametros["tipo_unidad"] != "TODAS"){
			$where_tipo = " AND unidades_medicas.tipo = '".$parametros["tipo_unidad"]."'";
		}
		
		
		$query = "
		SELECT
			pedidos_insumos.insumo_medico_clave as clave,
			insumos_medicos.descripcion,
			tipos_insumos.nombre as tipo_insumo,
			insumos_medicos.tipo as tipo,
			SUM(pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) as cantidad_faltante,			
			pedidos_insumos.precio_unitario,			
			SUM((pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) * pedidos_insumos.precio_unitario  * IF(insumos_medicos.tipo != 'MC', 1.16, 1)) as monto,
			SUM((pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) * pedidos_insumos.precio_unitario  * IF(insumos_medicos.tipo != 'MC', 1.16, 1)) * 0.05 * 30 as pena_convencional

		FROM pedidos_insumos, pedidos, insumos_medicos,tipos_insumos, unidades_medicas 

		WHERE	
			pedidos_insumos.pedido_id = pedidos.id 
			AND pedidos_insumos.insumo_medico_clave = insumos_medicos.clave
			AND tipos_insumos.id = pedidos_insumos.tipo_insumo_id 
			AND unidades_medicas.clues = pedidos.clues
			AND (pedidos.status = 'EX' OR pedidos.status = 'EX-CA')
			AND (pedidos_insumos.cantidad_solicitada - IFNULL(pedidos_insumos.cantidad_recibida,0)) > 0
			AND pedidos.proveedor_id = '".$parametros['proveedor_id']."'
			".$where_anio."
			".$where_mes." 
			".$where_tipo."
		GROUP BY pedidos_insumos.insumo_medico_clave 
		";

		$items = DB::table(DB::raw("(".$query.") as chuchi"))->get();

		Excel::create("Penas convencionales Pedido Folio ", function($excel) use($items,$proveedor, $parametros) {

			$excel->sheet('Reporte de penas convencionales', function($sheet) use($items,$proveedor, $parametros) {
				$sheet->setAutoSize(true);
				
				$sheet->mergeCells('B1:I1');
				$sheet->mergeCells('B2:I2');
				$sheet->mergeCells('B3:I3');
				$sheet->mergeCells('B4:I4');

				//($item->folio)?$item->folio:'S/F'

				$sheet->row(1, array(
					'Proveedor',$proveedor->nombre
				));
				
				$periodo = "";
				if($parametros["anio"] == "TODOS" && $parametros["mes"] == "TODOS"){
					$periodo = "TODOS";
				} else{
					$mes = "";
					switch($parametros["mes"]){
						case "1": $mes = "ENERO"; break;
						case "2": $mes = "FEBRERO"; break;
						case "3": $mes = "MARZO"; break;
						case "4": $mes = "ABRIL"; break;
						case "5": $mes = "MAYO"; break;
						case "6": $mes = "JUNIO"; break;
						case "7": $mes = "JULIO"; break;
						case "8": $mes = "AGOSTO"; break;
						case "9": $mes = "SEPTIEMBRE"; break;
						case "10": $mes = "OCTUBRE"; break;
						case "11": $mes = "NOVIEMBRE"; break;
						case "12": $mes = "DICIEMBRE"; break;
						default: $mes = "TODOS";

					}
					$periodo = $mes. " ".$parametros["anio"];
				}

				$sheet->row(2, array(
					'Periodo',$periodo
				));
				
				$sheet->row(3, array(
					'Tipo de Unidad Médica',$parametros['tipo_unidad']
				));
				
				$sheet->row(5, array(
					'Clave','Descripcion', 'Tipo', 'Cantidad','Precio unitario','Monto (iva incluido)', '% Penalizacion', 'Días Penalización','Pena convencional'
				));
				$sheet->setColumnFormat(array(
					"B4:G4" => 'dd/MM/yyyy'
				));
				$sheet->row(1, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				$sheet->row(2, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				$sheet->row(3, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				$sheet->row(4, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				$sheet->row(5, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
				});
				$contador_filas = 5;
				foreach($items as $item){
					$contador_filas++;					

					$sheet->appendRow(array(
						$item->clave,
						$item->descripcion,
						$item->tipo_insumo,
						$item->cantidad_faltante,
						$item->precio_unitario,
						$item->monto,
						0.005,
						30,
						//'=PRODUCT(F:'.$contador_filas.':H'.$contador_filas.')'
						'=PRODUCT(F'.$contador_filas.':H'.$contador_filas.')'
						//$item->pena_convencional
					)); 
				}

				$sheet->appendRow(array(
					'',
					'',
					'',
					'',
					'TOTAL',
					"=SUM(F3:F$contador_filas)",
					'',
					'',
			       "=SUM(I3:I$contador_filas)"
				));

				$sheet->setBorder("A1:I$contador_filas", 'thin');

				$contador_filas += 1;

				$sheet->setBorder("E$contador_filas:I$contador_filas", 'thin');
				$sheet->row($contador_filas, function($row) {
					$row->setBackground('#DDDDDD');
					$row->setFontWeight('bold');
					$row->setFontSize(14);
				});
				
				
				$sheet->setColumnFormat(array(
						"E6:F$contador_filas" => '"$" #,##0.00_-',
					));
				$sheet->setColumnFormat(array(
						"I6:I$contador_filas" => '"$" #,##0.00_-',
				));	
			});
			})->export('xls');
	}

	public function meses()
    {

		$mensajes = [
            'integer'      => "integer",
        ];

        $reglas = [
            'periodo'   => 'integer'
        ];

		$parametros = Input::only('periodo');

		$v = Validator::make($parametros, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
		}
		

		$filtro = "";

		$items = Pedido::select(DB::raw("MONTH(fecha) as mes"))->where('status',"!=","BR");
		if($parametros["periodo"]){
			$items = $items->where('fecha','>=',$parametros['periodo']."-01-01");
			//$filtro = "AND fecha >= '".$parametros['periodo']."-01-01'";
		}
		$items = $items->groupBy(DB::raw("mes"))->orderBy(DB::raw("mes"),"asc")->get();


		//$query = "SELECT Month(fecha) as mes FROM pedidos where status != 'BR' ".$filtro." group by mesa_que_mas_aplauda";
				
		//$items = DB::table(DB::raw($query))->get();

		$meses = array("ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE");
		
		$meses_disponibles = [];
		foreach($items as $item){
			$meses_disponibles[] = array('id'=> $item->mes, 'descripcion'=>$meses[$item->mes - 1]);
		}
		/*

        $mes = [];
        for($month = 1; $month <= Carbon::now()->month; $month++)
        {
            $mes[] = array('id'=>$month, 'descripcion' => $meses[$month-1]." ".Carbon::now()->year);
        }*/
        
        return Response::json([ 'data' => $meses_disponibles],200);
	}
	
	public function periodos()
    {


		$items = Pedido::select(DB::raw("YEAR(fecha) as periodo"))
				->where('status',"!=","BR")
				->groupBy(DB::raw("periodo"))
				->orderBy(DB::raw("periodo"),"desc")
				->get();
        
        return Response::json([ 'data' => $items],200);
    }
}