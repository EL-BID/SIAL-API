<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;


use App\Models\Movimiento;
use App\Models\Stock;
use App\Models\MovimientoInsumos;
use App\Models\TiposMovimientos;
use App\Models\Insumo;
use App\Models\MovimientoMetadato;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\RecetaMovimiento;


/** 
* Controlador Movimientos
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-05-20
*
* Controlador `ReportePedido`: Controlador  para reportes de pedidos ( graficas y status de entrega de pedidos )
*
*/
class ReportePedidoController extends Controller
{
     
    public function graficaEntregas()
    {
        $parametros = Input::all();
        
        $proveedor_id = 2; 
        $fecha_inicial = date("2017-04-09 0000:00:00");        
        $fecha_final = date("Y-m-d 0000:00:00");
        if (isset($parametros['proveedor_id'])) {
           $proveedor_id = $parametros['proveedor_id'];
        }
        if (isset($parametros['fecha_inicial'])) {
           $fecha_inicial = $parametros['fecha_inicial'];
        }
        if (isset($parametros['fecha_final'])) {
           $fecha_final = $parametros['fecha_final'];
        }

        $proveedor = Proveedor::find($proveedor_id);

        $insumos =DB::table('pedidos AS PE')
        ->select('PE.fecha',DB::raw(
            '
            sum(PEI.cantidad_solicitada) as total_solicitado,
            sum(
                case when IMED.tipo = "ME" then PEI.cantidad_solicitada else null end
            ) as mat_curacion_solicitado,
            sum(
                case when IMED.tipo != "ME" && IMED.es_causes = 0 then PEI.cantidad_solicitada else null end
            ) as no_causes_solicitado,
            sum(
                case when IMED.tipo != "ME" && IMED.es_causes = 1 then PEI.cantidad_solicitada else null end
            ) as causes_solicitado,
            sum(PEI.cantidad_recibida) as total_recibido,
            sum(
                case when IMED.tipo = "ME" then PEI.cantidad_recibida else null end
            ) as mat_curacion_recibido,
            sum(
                case when IMED.tipo != "ME" && IMED.es_causes = 0 then PEI.cantidad_recibida else null end
            ) as no_causes_recibido,
            sum(
                case when IMED.tipo != "ME" && IMED.es_causes = 1 then PEI.cantidad_recibida else null end
            ) as causes_recibido
            '
        ))
        ->leftJoin('pedidos_insumos AS PEI','PE.id','=','PEI.pedido_id')
        ->leftJoin('insumos_medicos AS IMED', 'PEI.insumo_medico_clave','=','IMED.clave')
        ->where('PE.tipo_pedido_id', '=', 'PA')
        ->where('PE.proveedor_id', $proveedor_id) 
        ->where('PE.deleted_at', NULL)  
        ->where('PEI.deleted_at', NULL) 
        ->where('IMED.deleted_at', NULL)                          
        ->whereBetween('PE.fecha', [$fecha_inicial, $fecha_final])
        ->orderBy('PE.fecha', 'ASC')
        ->groupBy('PE.fecha')
        ->get();

        $claves =DB::table('pedidos AS PE')
        ->select('PE.fecha',DB::raw(
            '
            count(1) as total,
            count(
                case when IMED.tipo = "ME" then 1 else null end
            ) as mat_curacion,
            count(
                case when IMED.tipo != "ME" && IMED.es_causes = 0 then 1 else null end
            ) as no_causes,
            count(
                case when IMED.tipo != "ME" && IMED.es_causes = 1 then 1 else null end
            ) as causes
            '
        ))
        ->distinct('IMED.clave')
        ->leftJoin('pedidos_insumos AS PEI','PE.id','=','PEI.pedido_id')
        ->leftJoin('insumos_medicos AS IMED', 'PEI.insumo_medico_clave','=','IMED.clave')
        ->where('PE.tipo_pedido_id', '=', 'PA')
        ->where('PE.proveedor_id', $proveedor_id) 
        ->where('PE.deleted_at', NULL)  
        ->where('PEI.deleted_at', NULL) 
        ->where('IMED.deleted_at', NULL)                          
        ->whereBetween('PE.fecha', [$fecha_inicial, $fecha_final])
        ->orderBy('PE.fecha', 'ASC')
        ->groupBy('PE.fecha')
        ->get();

        $monto =DB::table('pedidos AS PE')
        ->select('PE.fecha',DB::raw(
            '
            sum(PEI.monto_solicitado) as total_solicitado,
            sum(
                case when IMED.tipo = "ME" then PEI.monto_solicitado else null end
            ) as mat_curacion_solicitado,
             sum(
                case when IMED.tipo != "ME" && IMED.es_causes = 0 then PEI.monto_solicitado else null end
            ) as no_causes_solicitado,
             sum(
                case when IMED.tipo != "ME" && IMED.es_causes = 1 then PEI.monto_solicitado else null end
            ) as causes_solicitado,

            sum(PEI.monto_recibido) as total_recibido,
            sum(
                case when IMED.tipo = "ME" then PEI.monto_recibido else null end
            ) as mat_curacion_recibido,
             sum(
                case when IMED.tipo != "ME" && IMED.es_causes = 0 then PEI.monto_recibido else null end
            ) as no_causes_recibido,
             sum(
                case when IMED.tipo != "ME" && IMED.es_causes = 1 then PEI.monto_recibido else null end
            ) as causes_recibido
            '
        ))
        ->leftJoin('pedidos_insumos AS PEI','PE.id','=','PEI.pedido_id')
        ->leftJoin('insumos_medicos AS IMED', 'PEI.insumo_medico_clave','=','IMED.clave')
        ->where('PE.tipo_pedido_id', '=', 'PA')
        ->where('PE.proveedor_id', $proveedor_id) 
        ->where('PE.deleted_at', NULL)  
        ->where('PEI.deleted_at', NULL) 
        ->where('IMED.deleted_at', NULL)                  
        ->whereBetween('PE.fecha', [$fecha_inicial, $fecha_final])
        ->orderBy('PE.fecha', 'ASC')
        ->groupBy('PE.fecha')
        ->get();

        return Response::json(array("data" => array("proveedor" => $proveedor, "claves" =>  $claves, "insumos" =>  $insumos, "montos" =>  $monto), "status" => 200,"messages" => "Grafica Entregas"), 200);
    }
    
///***************************************************************************************************************************
///***************************************************************************************************************************

   public function estatusEntregaPedidos(Request $request)
    {
        return Response::json(array("status" => 200,"messages" => "Estatus Entrega Pedidos"), 200);
    }

///***************************************************************************************************************************
///***************************************************************************************************************************
   
    public function index(Request $request)
    {
        
    }

 ///***************************************************************************************************************************
///***************************************************************************************************************************
 
 public function store(Request $request)
    {
        
    }

 ///***************************************************************************************************************************
///***************************************************************************************************************************
 
    public function show($id)
    {

        
    }

///***************************************************************************************************************************
///***************************************************************************************************************************

    public function update(Request $request, $id)
    {
 
    }

///***************************************************************************************************************************
///***************************************************************************************************************************
      
    public function destroy($id)
    {
        
    }

///***************************************************************************************************************************
///***************************************************************************************************************************
 
	 

}
