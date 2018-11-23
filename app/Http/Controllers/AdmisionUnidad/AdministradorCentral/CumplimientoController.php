<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class CumplimientoController extends Controller
{
    

    public function statsGlobales(Request $request){
        try{
            
           

            $items = DB::table(
                        DB::raw('(
                            SELECT 
                                    proveedores.id as proveedor_id,
                                    proveedores.nombre_corto as proveedor,
                                    proveedores.nombre as proveedor_nombre_completo,
                                    SUM(total_monto_solicitado) total_monto_solicitado, 
                                    IF ((SUM(total_monto_recibido) * 100 / SUM(total_monto_solicitado)) IS NULL, 0.00, (SUM(total_monto_recibido) * 100 / SUM(total_monto_solicitado)) ) as porcentaje_entregado
                            FROM pedidos
                            LEFT JOIN proveedores on proveedores.id = pedidos.proveedor_id
                            where pedidos.status != "BR" 
                            GROUP BY pedidos.proveedor_id
                        ) as tabla')
                    )->get();
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function statsPorProveedor(Request $request, $id){
     	try {
		
			$proveedor = Proveedor::find($id);
			if(!$proveedor){
				throw new Exception("Proveedor no existe");
			} 
          	$items = DB::table(
						DB::raw('(
							SELECT
								anio,
								mes,
								mes_nombre,
								claves_solicitadas,
								claves_recibidas,
								ROUND((claves_recibidas * 100 / claves_solicitadas),2) as claves_cumplimiento,
								cantidad_solicitada,
								cantidad_recibida,
								ROUND((cantidad_recibida * 100 / cantidad_solicitada),2) as cantidad_cumplimiento,
								monto_solicitado,
								monto_recibido,
								ROUND((monto_recibido * 100 / monto_solicitado),2) as monto_cumplimiento
							FROM (
								SELECT 
									YEAR(pedidos.fecha) AS anio,
									MONTH(pedidos.fecha) AS mes,
									CASE MONTH(pedidos.fecha)
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
										ELSE "DICIEMBRE" END AS mes_nombre,
									SUM(pedidos.total_claves_solicitadas) as claves_solicitadas,
									IF(SUM(pedidos.total_claves_recibidas) IS NULL, 0, SUM(pedidos.total_claves_recibidas)) as claves_recibidas,
									SUM(pedidos.total_cantidad_solicitada) as cantidad_solicitada,
									IF(SUM(pedidos.total_cantidad_recibida) IS NULL, 0, SUM(pedidos.total_cantidad_recibida)) as cantidad_recibida,
									SUM(pedidos.total_monto_solicitado) as monto_solicitado,
									IF(SUM(pedidos.total_monto_recibido) is NULL, 0.00, SUM(pedidos.total_monto_recibido)) as monto_recibido
								FROM pedidos 
								WHERE proveedor_id = '.$id.' AND pedidos.status != "BR" 
								GROUP BY YEAR(pedidos.fecha), MONTH(pedidos.fecha)
							) AS TABLA_GLOBAL
						) AS TABLA_LARAVEL')
               )->get();
      
            	return Response::json([ 'data' => $items],200);
		} catch (\Exception $e) {
			return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		} 
    }




}
