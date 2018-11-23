<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Pedido;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class EntregasMesController extends Controller
{
    public function mesesAnioPresupuestos(Request $request){
        try{

            
            $anio = date('Y');
            $mes = date('n');

            $items = Pedido::select(DB::raw('CONCAT( MONTH(pedidos.fecha),"/",YEAR(pedidos.fecha)) as fecha'), DB::raw('MONTH(pedidos.fecha) as mes'), DB::raw('YEAR(pedidos.fecha) as anio'), DB::raw(
                'CASE MONTH(pedidos.fecha)
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
                    ELSE "DICIEMBRE" END AS mes_nombre'
            ))
            ->where('status','!=','BR')
            ->groupBy(DB::raw('YEAR(pedidos.fecha) DESC'))->groupBy(DB::raw('MONTH(pedidos.fecha) DESC'))->get();
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function statsMesAnio(Request $request){
        try{
            
            $input = Input::only('mes','anio');
            
            $mensajes = [
                'required'      => "required",
                'integer'      => "integer",
            ];
            $reglas = [
                'mes'        			=> 'required|integer',
                'anio'                  => 'required|integer'
            ];

            $input = Input::all();
        
            $v = Validator::make($input, $reglas, $mensajes);

            if ($v->fails()) {
                return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
            }

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
                            where pedidos.status != "BR" AND YEAR(pedidos.fecha) = '.$input['anio'].' AND MONTH(pedidos.fecha) = '.$input['mes'].'
                            GROUP BY pedidos.proveedor_id
                        ) as tabla')
                    )->get();
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function entregasPedidosStatsDiarias(Request $request){
        try{
            
            $input = Input::only('mes','anio','proveedor_id');
            
            $mensajes = [
                'required'      => "required",
                'integer'      => "integer",
            ];
            $reglas = [
                'mes'        			=> 'required|integer',
                'anio'                  => 'required|integer',
                'proveedor_id'          => 'required'
            ];

            
        
            $v = Validator::make($input, $reglas, $mensajes);

            if ($v->fails()) {
                return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
            }

            $items = DB::table(
                        DB::raw('(
                            SELECT 
                                movimientos.fecha_movimiento,
                                DAY(movimientos.fecha_movimiento) as dia,
                                CASE MONTH(movimientos.fecha_movimiento)
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

                                CAUSES.claves as causes_claves,
                                CAUSES.cantidad as causes_cantidad,
                                CAUSES.monto as causes_monto,
                                NO_CAUSES.claves as no_causes_claves,
                                NO_CAUSES.cantidad as no_causes_cantidad,
                                NO_CAUSES.monto as no_causes_monto,
                                MATERIAL_CURACION.claves as material_curacion_claves,
                                MATERIAL_CURACION.cantidad as material_curacion_cantidad,
                                MATERIAL_CURACION.monto as material_curacion_monto
                                
                            FROM
                            (
                                SELECT movimientos.fecha_movimiento
                                FROM pedidos
                                LEFT JOIN movimiento_pedido ON movimiento_pedido.pedido_id = pedidos.id
                                LEFT JOIN movimientos ON movimientos.id = movimiento_pedido.movimiento_id 
                                WHERE 

                                        YEAR(movimientos.fecha_movimiento)= '.$input['anio'].' AND 
                                        MONTH(movimientos.fecha_movimiento) = '.$input['mes'].' AND
                                        pedidos.proveedor_id = '.$input['proveedor_id'].'
                                GROUP BY  movimientos.fecha_movimiento 

                            ) as  movimientos 
                            INNER JOIN
                            (
                                SELECT 
                                    fecha_movimiento, 
                                    count(clave_insumo_medico) as claves, 
                                    CAST(sum(cantidad) AS UNSIGNED) as cantidad, 
                                    sum(monto) as monto 
                                FROM 
                                    (SELECT  
                                        movimientos.fecha_movimiento,
                                        DAY(movimientos.fecha_movimiento),
                                        MONTH(movimientos.fecha_movimiento),
                                        stock.clave_insumo_medico, 
                                        sum(movimiento_insumos.cantidad) as cantidad, 
                                        sum(movimiento_insumos.precio_total) as monto 

                                    FROM  pedidos
                                    INNER JOIN movimiento_pedido ON movimiento_pedido.pedido_id = pedidos.id
                                    INNER JOIN movimientos ON movimientos.id = movimiento_pedido.movimiento_id
                                    INNER JOIN  movimiento_insumos ON movimiento_insumos.movimiento_id = movimientos.id 
                                    INNER JOIN stock ON movimiento_insumos.stock_id = stock.id  
                                    INNER JOIN insumos_medicos ON insumos_medicos.clave = stock.clave_insumo_medico  
                                    WHERE 
                                        movimientos.fecha_movimiento  is not null AND
                                        movimientos.fecha_movimiento  != "0000-00-00" AND
                                        pedidos.status != "BR" AND
                                        insumos_medicos.tipo = "ME" AND
                                        insumos_medicos.es_causes = 1 AND
                                        YEAR(movimientos.fecha_movimiento)= '.$input['anio'].' AND 
                                        MONTH(movimientos.fecha_movimiento) = '.$input['mes'].' AND
                                        pedidos.proveedor_id = '.$input['proveedor_id'].'
                                    GROUP BY
                                        movimientos.fecha_movimiento,
                                        stock.clave_insumo_medico 
                                    ) as tabla
                                GROUP BY fecha_movimiento
                            ) AS CAUSES ON CAUSES.fecha_movimiento = movimientos.fecha_movimiento

                            INNER JOIN
                            (
                                SELECT 
                                    fecha_movimiento, 
                                    count(clave_insumo_medico) as claves, 
                                    CAST(sum(cantidad) AS UNSIGNED) as cantidad, 
                                    sum(monto) as monto 
                                FROM 
                                    (SELECT  
                                        movimientos.fecha_movimiento,
                                        DAY(movimientos.fecha_movimiento),
                                        MONTH(movimientos.fecha_movimiento),
                                        stock.clave_insumo_medico, 
                                        sum(movimiento_insumos.cantidad) as cantidad, 
                                        sum(movimiento_insumos.precio_total) as monto 

                                    FROM  pedidos
                                    INNER JOIN movimiento_pedido ON movimiento_pedido.pedido_id = pedidos.id
                                    INNER JOIN movimientos ON movimientos.id = movimiento_pedido.movimiento_id
                                    INNER JOIN  movimiento_insumos ON movimiento_insumos.movimiento_id = movimientos.id 
                                    INNER JOIN stock ON movimiento_insumos.stock_id = stock.id  
                                    INNER JOIN insumos_medicos ON insumos_medicos.clave = stock.clave_insumo_medico  
                                    WHERE 
                                        movimientos.fecha_movimiento  is not null AND
                                        movimientos.fecha_movimiento  != "0000-00-00" AND
                                        pedidos.status != "BR" AND
                                        insumos_medicos.tipo = "ME" AND
                                        insumos_medicos.es_causes = 0 AND
                                        YEAR(movimientos.fecha_movimiento)= '.$input['anio'].' AND 
                                        MONTH(movimientos.fecha_movimiento) = '.$input['mes'].' AND
                                        pedidos.proveedor_id = '.$input['proveedor_id'].'
                                    GROUP BY
                                        movimientos.fecha_movimiento,
                                        stock.clave_insumo_medico 
                                    ) as tabla
                                GROUP BY fecha_movimiento
                            ) AS NO_CAUSES ON NO_CAUSES.fecha_movimiento = movimientos.fecha_movimiento

                            INNER JOIN
                            (
                                SELECT 
                                    fecha_movimiento, 
                                    count(clave_insumo_medico) as claves, 
                                    CAST(sum(cantidad) AS UNSIGNED) as cantidad, 
                                    sum(monto) as monto 
                                FROM 
                                    (SELECT  
                                        movimientos.fecha_movimiento,
                                        DAY(movimientos.fecha_movimiento),
                                        MONTH(movimientos.fecha_movimiento),
                                        stock.clave_insumo_medico, 
                                        sum(movimiento_insumos.cantidad) as cantidad, 
                                        sum(movimiento_insumos.precio_total) as monto 

                                    FROM  pedidos
                                    INNER JOIN movimiento_pedido ON movimiento_pedido.pedido_id = pedidos.id
                                    INNER JOIN movimientos ON movimientos.id = movimiento_pedido.movimiento_id
                                    INNER JOIN  movimiento_insumos ON movimiento_insumos.movimiento_id = movimientos.id 
                                    INNER JOIN stock ON movimiento_insumos.stock_id = stock.id  
                                    INNER JOIN insumos_medicos ON insumos_medicos.clave = stock.clave_insumo_medico  
                                    WHERE 
                                        movimientos.fecha_movimiento  is not null AND
                                        movimientos.fecha_movimiento  != "0000-00-00" AND
                                        pedidos.status != "BR" AND
                                        insumos_medicos.tipo = "MC" AND
                                        YEAR(movimientos.fecha_movimiento)= '.$input['anio'].' AND 
                                        MONTH(movimientos.fecha_movimiento) = '.$input['mes'].' AND
                                        pedidos.proveedor_id = '.$input['proveedor_id'].'
                                    GROUP BY
                                        movimientos.fecha_movimiento,
                                        stock.clave_insumo_medico 
                                    ) as tabla
                                GROUP BY fecha_movimiento
                            ) AS MATERIAL_CURACION ON MATERIAL_CURACION.fecha_movimiento = movimientos.fecha_movimiento
                            GROUP BY movimientos.fecha_movimiento
                        
                        ) as tabla')
                    )->get();
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function pedidosRecepcionesClues(Request $request){
        try{
            $input = Input::only('mes','anio','clues','proveedor_id');
            $mensajes = [
                'required'      => "required",
                'integer'      => "integer",
            ];
            $reglas = [
                'mes'        			=> 'required|integer',
                'anio'                  => 'required|integer',
                'clues'                 => 'required',
                'proveedor_id'          => 'required'
            ];

        
            $v = Validator::make($input, $reglas, $mensajes);

            if ($v->fails()) {
                return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
            }

            $pedidos = Pedido::where('clues',$input['clues'])
                            ->where('proveedor_id',$input['proveedor_id'])
                            ->where(DB::raw('month(fecha)'),$input['mes'])
                            ->where(DB::raw('year(fecha)'),$input['anio'])
                            ->where('status','!=','BR')
                            ->with(['recepciones'=>function($entrada){
                                $entrada->select('movimiento_pedido.*','movimientos.fecha_movimiento')
                                        ->leftjoin('movimientos','movimientos.id','=','movimiento_pedido.movimiento_id')
                                        ->orderBy('movimientos.fecha_movimiento');
                            },'recepciones.entrada.insumosDetalles'])->get();
            
            return Response::json([ 'data' => $pedidos],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function pedidosAnioMesClues(Request $request){
        try{
            
            $input = Input::only('mes','anio','proveedor_id');
            
            $mensajes = [
                'required'      => "required",
                'integer'      => "integer",
            ];
            $reglas = [
                'mes'        			=> 'required|integer',
                'anio'                  => 'required|integer',
                'proveedor_id'          => 'required'
            ];

        
            $v = Validator::make($input, $reglas, $mensajes);

            if ($v->fails()) {
                return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
            }

            $items = DB::table(
                        DB::raw('(
                            SELECT 
                                unidades_medicas.total_pedidos,
                                unidades_medicas.clues,
                                unidades_medicas.nombre as unidad_medica,
                                IF(CAUSES.cantidad_recibida IS NULL, 0, CAUSES.cantidad_recibida) as causes_cantidad_recibida,
                                IF(CAUSES.cantidad_solicitada IS NULL,0, CAUSES.cantidad_solicitada) as causes_cantidad_solicitada,
                                IF(NO_CAUSES.cantidad_recibida IS NULL,0, NO_CAUSES.cantidad_recibida) as no_causes_cantidad_recibida,
                                IF(NO_CAUSES.cantidad_solicitada IS NULL,0, NO_CAUSES.cantidad_solicitada) as no_causes_cantidad_solicitada,
                                IF(MATERIAL_CURACION.cantidad_recibida IS NULL,0, MATERIAL_CURACION.cantidad_recibida) as material_curacion_cantidad_recibida,
                                IF(MATERIAL_CURACION.cantidad_solicitada IS NULL,0, MATERIAL_CURACION.cantidad_solicitada) as material_curacion_cantidad_solicitada
                                
                            FROM
                            (
                                SELECT
                                count(distinct pedidos.id) as total_pedidos,
                                unidades_medicas.clues,
                                unidades_medicas.nombre
                                FROM pedidos 
                                LEFT JOIN unidades_medicas ON unidades_medicas.clues = pedidos.clues
                                WHERE 
                                    pedidos.status != "BR" AND
                                    YEAR(pedidos.fecha)= '.$input['anio'].' AND 
                                    MONTH(pedidos.fecha) = '.$input['mes'].' AND
                                    pedidos.proveedor_id = '.$input['proveedor_id'].'
                                GROUP BY pedidos.clues
                            ) AS unidades_medicas
                            LEFT JOIN  (
                                SELECT 
                                    pedidos.clues,
                                    IF(SUM(pedidos_insumos.cantidad_recibida) IS NULL, 0, SUM(pedidos_insumos.cantidad_recibida)) as cantidad_recibida,
                                    IF(SUM(pedidos_insumos.cantidad_solicitada) IS NULL, 0, SUM(pedidos_insumos.cantidad_solicitada)) as cantidad_solicitada

                                FROM pedidos
                                INNER JOIN pedidos_insumos ON pedidos_insumos.pedido_id = pedidos.id
                                INNER JOIN insumos_medicos ON insumos_medicos.clave = pedidos_insumos.insumo_medico_clave
                                WHERE
                                    pedidos.status != "BR" AND
                                    insumos_medicos.tipo = "ME" AND
                                    insumos_medicos.es_causes = 1 AND
                                    YEAR(pedidos.fecha)= '.$input['anio'].' AND 
                                    MONTH(pedidos.fecha) = '.$input['mes'].' AND
                                    pedidos.proveedor_id = '.$input['proveedor_id'].'
                                GROUP BY pedidos.clues
                            ) AS CAUSES ON CAUSES.clues = unidades_medicas.clues

                            LEFT JOIN  (
                                SELECT 
                                    pedidos.clues,
                                    IF(SUM(pedidos_insumos.cantidad_recibida) IS NULL, 0, SUM(pedidos_insumos.cantidad_recibida)) as cantidad_recibida,
                                    IF(SUM(pedidos_insumos.cantidad_solicitada) IS NULL, 0, SUM(pedidos_insumos.cantidad_solicitada)) as cantidad_solicitada

                                FROM pedidos
                                INNER JOIN pedidos_insumos ON pedidos_insumos.pedido_id = pedidos.id
                                INNER JOIN insumos_medicos ON insumos_medicos.clave = pedidos_insumos.insumo_medico_clave
                                WHERE
                                    pedidos.status != "BR" AND
                                    insumos_medicos.tipo = "ME" AND
                                    insumos_medicos.es_causes = 0 AND
                                    YEAR(pedidos.fecha)= '.$input['anio'].' AND 
                                    MONTH(pedidos.fecha) = '.$input['mes'].' AND
                                    pedidos.proveedor_id = '.$input['proveedor_id'].'
                                GROUP BY pedidos.clues
                            ) AS NO_CAUSES ON NO_CAUSES.clues = unidades_medicas.clues

                            LEFT JOIN  (
                                SELECT 
                                    pedidos.clues,
                                    IF(SUM(pedidos_insumos.cantidad_recibida) IS NULL, 0, SUM(pedidos_insumos.cantidad_recibida)) as cantidad_recibida,
                                    IF(SUM(pedidos_insumos.cantidad_solicitada) IS NULL, 0, SUM(pedidos_insumos.cantidad_solicitada)) as cantidad_solicitada

                                FROM pedidos
                                INNER JOIN pedidos_insumos ON pedidos_insumos.pedido_id = pedidos.id
                                INNER JOIN insumos_medicos ON insumos_medicos.clave = pedidos_insumos.insumo_medico_clave
                                WHERE
                                    pedidos.status != "BR" AND
                                    insumos_medicos.tipo = "MC" AND
                                    YEAR(pedidos.fecha)= '.$input['anio'].' AND 
                                    MONTH(pedidos.fecha) = '.$input['mes'].' AND
                                    pedidos.proveedor_id = '.$input['proveedor_id'].'
                                GROUP BY pedidos.clues
                            ) AS MATERIAL_CURACION ON MATERIAL_CURACION.clues = unidades_medicas.clues
                        
                        ) as tabla')
                    )->get();
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

}
