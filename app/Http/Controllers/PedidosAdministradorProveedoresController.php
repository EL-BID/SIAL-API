<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Proveedor, App\Models\Presupuesto, App\Models\UnidadMedicaPresupuesto, App\Models\Contrato, App\Models\Usuario, App\Models\Pedido;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;
use JWTAuth;

class PedidosAdministradorProveedoresController extends Controller
{
    public function presupuesto(Request $request){
        try{
            $proveedor_id = $request->get('proveedor_id');
            $contrato = Contrato::where('activo',1)->where('proveedor_id',$proveedor_id)->first();

            $parametros = Input::only('q','status','jurisdicciones','page','per_page', 'mes','anio', 'ordenar_causes','ordenar_no_causes','ordenar_material_curacion');
            $parametros['proveedor_id'] = $proveedor_id;
            $parametros['contrato_id'] = $contrato->id;

            $items = self::getItemsQuery($parametros);

            //$items = $items;

            $presupuesto_pedidos_proveedor = [];

            $presupuesto_pedidos_proveedor['causes_comprometido'] = $items->sum('total_monto_causes') - $items->sum('total_monto_causes_recibido');
            $presupuesto_pedidos_proveedor['causes_devengado'] = $items->sum('total_monto_causes_recibido');
            $presupuesto_pedidos_proveedor['no_causes_comprometido'] = $items->sum('total_monto_no_causes') - $items->sum('total_monto_no_causes_recibido');
            $presupuesto_pedidos_proveedor['no_causes_devengado'] = $items->sum('total_monto_no_causes_recibido');
            $presupuesto_pedidos_proveedor['material_curacion_comprometido'] = $items->sum('total_monto_material_curacion') - $items->sum('total_monto_material_curacion_recibido');
            $presupuesto_pedidos_proveedor['material_curacion_devengado'] = $items->sum('total_monto_material_curacion_recibido');

            return Response::json([ 'data' => $presupuesto_pedidos_proveedor],200);
            /*
            $parametros = Input::all();

            $presupuesto = Presupuesto::where('activo',1)->first();

            //DB::raw('sum(causes_autorizado) as causes_autorizado'),DB::raw('sum(causes_modificado) as causes_modificado'),DB::raw('sum(causes_disponible) as causes_disponible'),
            //DB::raw('sum(no_causes_autorizado) as no_causes_autorizado'),DB::raw('sum(no_causes_modificado) as no_causes_modificado'),DB::raw('sum(no_causes_disponible) as no_causes_disponible'),
            //DB::raw('sum(material_curacion_autorizado) as material_curacion_autorizado'),DB::raw('sum(material_curacion_modificado) as material_curacion_modificado'),DB::raw('sum(material_curacion_disponible) as material_curacion_disponible'),
            $presupuesto_unidad_medica = UnidadMedicaPresupuesto::select(
                                            DB::raw('sum(causes_comprometido) as causes_comprometido'),DB::raw('sum(causes_devengado) as causes_devengado'),
                                            DB::raw('sum(no_causes_comprometido) as no_causes_comprometido'),DB::raw('sum(no_causes_devengado) as no_causes_devengado'),
                                            DB::raw('sum(material_curacion_comprometido) as material_curacion_comprometido'),DB::raw('sum(material_curacion_devengado) as material_curacion_devengado'))
                                            ->where('presupuesto_id',$presupuesto->id);
                                           // ->where('clues',$almacen->clues)
                                            //->where('proveedor_id',$almacen->proveedor_id)
                                            //->groupBy('clues');
            if(isset($parametros['mes'])){
                if($parametros['mes']){
                    $presupuesto_unidad_medica = $presupuesto_unidad_medica->where('mes',$parametros['mes']);
                }
            }

            if(isset($parametros['anio'])){
                if($parametros['anio']){
                    $presupuesto_unidad_medica = $presupuesto_unidad_medica->where('anio',$parametros['anio']);
                }
            }
            
            $presupuesto_unidad_medica = $presupuesto_unidad_medica->first();
            return Response::json([ 'data' => $presupuesto_unidad_medica],200);
            */
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function pedido(Request $request, $id){
        $pedido = Pedido::where('proveedor_id',$request->get('proveedor_id'))->find($id);
        
        if(!$pedido){
            return Response::json(['error' => "No se encuentra el pedido que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }else{
            $pedido = $pedido->load("insumos.tipoInsumo","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos","almacenProveedor","almacenSolicitante.unidadMedica","proveedor","encargadoAlmacen","director");
        }
        return Response::json([ 'data' => $pedido],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lista(Request $request)
    {
        $proveedor_id = $request->get('proveedor_id');
        $contrato = Contrato::where('activo',1)->where('proveedor_id',$proveedor_id)->first();

        $parametros = Input::only('q','status','jurisdicciones','page','per_page', 'mes','anio', 'ordenar_causes','ordenar_no_causes','ordenar_material_curacion');
        $parametros['proveedor_id'] = $proveedor_id;
        $parametros['contrato_id'] = $contrato->id;

        $items = self::getItemsQuery($parametros);

        //$items = $items->where('proveedor_id',$proveedor_id);
        
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }
       
        return Response::json([ 'data' => $items],200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function excel(Request $request)
    {
        $obj =  JWTAuth::parseToken()->getPayload();
        $usuario = Usuario::find($obj->get('id'));

        if($usuario->su){
            $proveedor_id = $request->get('proveedor_id');
        }else{
            $proveedor_id = $usuario->proveedor_id;
        }

        $contrato = Contrato::where('activo',1)->where('proveedor_id',$proveedor_id)->first();

        $parametros = Input::only('q','status','jurisdicciones', 'mes', 'anio', 'ordenar_causes','ordenar_no_causes','ordenar_material_curacion');
        $parametros['proveedor_id'] = $proveedor_id;
        $parametros['contrato_id'] = $contrato->id;

        $items = self::getItemsQuery($parametros);
        $items = $items->get();

         Excel::create("Pedidos reporte ".date('Y-m-d'), function($excel) use($items) {

            $excel->sheet('Reporte de pedidos', function($sheet) use($items) {
                $sheet->setAutoSize(true);
                
                $sheet->mergeCells('A1:D1');
                $sheet->mergeCells('E1:F1');
                $sheet->mergeCells('G1:H1');
                $sheet->mergeCells('I1:J1');
                $sheet->mergeCells('K1:L1');
                
                $sheet->row(1, array('','','','','Total','','Causes','','No Causes','','Material de curación',''));
                
                $sheet->row(2, array(
                    'Folio','Nombre', 'Unidad médica','Fecha','Claves','Monto','Claves','Monto','Claves','Monto','Claves','Monto','Status'
                ));
                $sheet->cells("A1:M2", function($cells) {
                    $cells->setAlignment('center');
                });
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
                $contador_filas = 2;
                foreach($items as $item){
                    $contador_filas++;
                    $status = '';
                    switch($item->status){
                        case 'PS': $status = 'Por Surtir'; break;
                        case 'FI': $status = 'Finalizado'; break;
                        case 'EX': $status = 'Expirado'; break;
                        default: $status = 'Otro';
                    }
                    
                    $sheet->appendRow(array(
                        
                        $item->folio,
                        $item->descripcion,
                        
                        $item->unidad_medica,
                        $item->fecha,

                        $item->total_claves_solicitadas,
                        $item->total_monto_solicitado,

                        $item->total_claves_causes,
                        $item->total_monto_causes,

                        $item->total_claves_no_causes,
                        $item->total_monto_no_causes,

                        $item->total_claves_material_curacion,
                        $item->total_monto_material_curacion,

                        $status
                    )); 
                }
                $sheet->setBorder("A1:M$contador_filas", 'thin');

                $sheet->setColumnFormat(array(
                        "E3:E$contador_filas" => '#,##0',
                        "F3:F$contador_filas" => '"$" #,##0.00_-',
                        "G3:G$contador_filas" => '#,##0',
                        "H3:H$contador_filas" => '"$" #,##0.00_-',
                        "I3:I$contador_filas" => '#,##0',
                        "J3:J$contador_filas" => '"$" #,##0.00_-',
                        "K3:K$contador_filas" => '#,##0',
                        "L3:L$contador_filas" => '"$" #,##0.00_-',
                    ));
            });
         })->export('xls');
    }

    private function getItemsQuery($parametros){       

        $items = DB::table(DB::raw('(
                select
                    P.clues,
                    P.proveedor_id,
                    P.id as pedido_id, 
                    P.folio, 
                    UM.nombre as unidad_medica, 
                    UM.jurisdiccion_id,
                    P.fecha, 
                    P.fecha_concluido,
                    P.fecha_expiracion,
                    datediff(P.fecha_expiracion,current_date()) as expira_en_dias,
                    P.descripcion, 
                    
                    P.total_claves_solicitadas, 
                    P.total_cantidad_solicitada, 
                    P.total_monto_solicitado,

                    IF(P.total_claves_recibidas is null, 0, P.total_claves_recibidas) AS total_claves_recibidas,
                    IF(P.total_cantidad_recibida is null, 0, P.total_cantidad_recibida) AS total_cantidad_recibida,
                    IF(P.total_monto_recibido is null, 0, P.total_monto_recibido) AS total_monto_recibido,

                    IF(IC.total_claves_causes is null, 0, IC.total_claves_causes) AS total_claves_causes, 
                    IF(IC.total_cantidad_causes is null, 0, IC.total_cantidad_causes) AS total_cantidad_causes, 
                    IF(IC.total_monto_causes is null, 0, IC.total_monto_causes) AS total_monto_causes,

                    IF(IC.total_claves_causes_recibidas is null, 0, IC.total_claves_causes_recibidas) AS total_claves_causes_recibidas, 
                    IF(IC.total_cantidad_causes_recibida is null, 0, IC.total_cantidad_causes_recibida) AS total_cantidad_causes_recibida, 
                    IF(IC.total_monto_causes_recibido is null, 0, IC.total_monto_causes_recibido) AS total_monto_causes_recibido,
                    
                    IF(INC.total_claves_no_causes is null, 0, INC.total_claves_no_causes) AS total_claves_no_causes, 
                    IF(INC.total_cantidad_no_causes is null, 0, INC.total_cantidad_no_causes) AS total_cantidad_no_causes, 
                    IF(INC.total_monto_no_causes is null, 0, INC.total_monto_no_causes) AS total_monto_no_causes,

                    IF(INC.total_claves_no_causes_recibidas is null, 0, INC.total_claves_no_causes_recibidas) AS total_claves_no_causes_recibidas, 
                    IF(INC.total_cantidad_no_causes_recibida is null, 0, INC.total_cantidad_no_causes_recibida) AS total_cantidad_no_causes_recibida, 
                    IF(INC.total_monto_no_causes_recibido is null, 0, INC.total_monto_no_causes_recibido) AS total_monto_no_causes_recibido,
                    
                    IF(IMC.total_claves_material_curacion is null, 0, IMC.total_claves_material_curacion) AS total_claves_material_curacion, 
                    IF(IMC.total_cantidad_material_curacion is null, 0, IMC.total_cantidad_material_curacion) AS total_cantidad_material_curacion, 
                    IF(IMC.total_monto_material_curacion is null, 0, round(IMC.total_monto_material_curacion+(IMC.total_monto_material_curacion*16/100),2)) AS total_monto_material_curacion,

                    IF(IMC.total_claves_material_curacion_recibidas is null, 0, IMC.total_claves_material_curacion_recibidas) AS total_claves_material_curacion_recibidas, 
                    IF(IMC.total_cantidad_material_curacion_recibida is null, 0, IMC.total_cantidad_material_curacion_recibida) AS total_cantidad_material_curacion_recibida, 
                    IF(IMC.total_monto_material_curacion_recibido is null, 0, round(IMC.total_monto_material_curacion_recibido+(IMC.total_monto_material_curacion_recibido*16/100),2)) AS total_monto_material_curacion_recibido,

                    P.status,
                    (select count(*) from repositorio where pedido_id=P.id and deleted_at is null) as repositorio

                    from pedidos P

                    left join unidades_medicas UM on UM.clues = P.clues

                    left join (
                        select PC.pedido_id, count(PC.insumo_medico_clave) as total_claves_causes, sum(PC.cantidad_solicitada) as total_cantidad_causes, sum(PC.monto_solicitado) as total_monto_causes,
                        sum(if(PC.cantidad_recibida>0,1,0)) as total_claves_causes_recibidas, sum(PC.cantidad_recibida) as total_cantidad_causes_recibida, sum(PC.monto_recibido) as total_monto_causes_recibido
                        from pedidos_insumos PC
                        join insumos_medicos IM on IM.clave = PC.insumo_medico_clave and IM.tipo = "ME" and IM.es_causes = 1
                        where PC.deleted_at is null
                        group by PC.pedido_id
                    ) as IC on IC.pedido_id = P.id

                    left join (
                        select PNC.pedido_id, count(PNC.insumo_medico_clave) as total_claves_no_causes, sum(PNC.cantidad_solicitada) as total_cantidad_no_causes, sum(PNC.monto_solicitado) as total_monto_no_causes,
                        sum(if(PNC.cantidad_recibida>0,1,0)) as total_claves_no_causes_recibidas, sum(PNC.cantidad_recibida) as total_cantidad_no_causes_recibida, sum(PNC.monto_recibido) as total_monto_no_causes_recibido
                        from pedidos_insumos PNC
                        join insumos_medicos IM on IM.clave = PNC.insumo_medico_clave and IM.tipo = "ME" and IM.es_causes = 0
                        where PNC.deleted_at is null
                        group by PNC.pedido_id
                    ) as INC on INC.pedido_id = P.id

                    left join (
                        select PMC.pedido_id, count(PMC.insumo_medico_clave) as total_claves_material_curacion, sum(PMC.cantidad_solicitada) as total_cantidad_material_curacion, sum(PMC.monto_solicitado) as total_monto_material_curacion,
                        sum(if(PMC.cantidad_recibida>0,1,0)) as total_claves_material_curacion_recibidas, sum(PMC.cantidad_recibida) as total_cantidad_material_curacion_recibida, sum(PMC.monto_recibido) as total_monto_material_curacion_recibido
                        from pedidos_insumos PMC
                        join insumos_medicos IM on IM.clave = PMC.insumo_medico_clave and IM.tipo = "MC"
                        where PMC.deleted_at is null
                        group by PMC.pedido_id
                    ) as IMC on IMC.pedido_id = P.id

                    where P.deleted_at is null
                
            ) as pedidos'));
        
        if (isset($parametros['q']) &&  $parametros['q'] != "") {
            $items = $items->where(function($query) use ($parametros){
                $query
                    ->where('unidad_medica','LIKE',"%".$parametros['q']."%")
                    ->orWhere('clues','LIKE',"%".$parametros['q']."%")
                    ->orWhere('folio','LIKE',"%".$parametros['q']."%")
                    ->orWhere('descripcion','LIKE',"%".$parametros['q']."%");
            });
        } 


        //$mes = isset($parametros['mes']) ? $parametros['mes'] : null;
        if($parametros['mes']){
            $mes = $parametros['mes'];
        }else{
            $mes = null;
        }

        if ($mes) {
            $items = $items->where(DB::raw('month(fecha)'),"=",$mes);
        }

        $anio = isset($parametros['anio']) ? $parametros['anio'] : null;

        if ($anio) {
            $items = $items->where(DB::raw('year(fecha)'),"=",$anio);
        }

        $itmes = $items->where('proveedor_id',$parametros['proveedor_id']);
        //$itmes = $items->where('contrato_id',$parametros['contrato_id']);
        
        if(isset($parametros['status']) && $parametros['status'] != ""){
            $status = explode(',',$parametros['status']);            
            if(count($status)>0){
                $items = $items->whereIn('status',$status);
            }              
        }else{
            $items = $items->whereIn('status',['PS','FI','EX','EF','EX-CA']);
        }

        if(isset($parametros['jurisdicciones']) && $parametros['jurisdicciones'] != ""){
            $jurisdicciones = explode(',',$parametros['jurisdicciones']);            
            if(count($jurisdicciones)>0){
                $items = $items->whereIn('jurisdiccion_id',$jurisdicciones);
            }              
        }

        if(isset($parametros['ordenar_causes'])){
            if($parametros['ordenar_causes'] == 'ASC'){
                $items = $items->orderBy('porcentaje_causes','ASC');
            }
            if($parametros['ordenar_causes'] == 'DESC'){
                $items = $items->orderBy('porcentaje_causes','DESC');
            }
        }

        if(isset($parametros['ordenar_no_causes'])){
            if($parametros['ordenar_no_causes'] == 'ASC'){
                $items = $items->orderBy('porcentaje_no_causes','ASC');
            }
            if($parametros['ordenar_no_causes'] == 'DESC'){
                $items = $items->orderBy('porcentaje_no_causes','DESC');
            }
        }
        if(isset($parametros['ordenar_material_curacion'])){
            if($parametros['ordenar_material_curacion'] == 'ASC'){
                $items = $items->orderBy('porcentaje_material_curacion','ASC');
            }
            if($parametros['ordenar_material_curacion'] == 'DESC'){
                $items = $items->orderBy('porcentaje_material_curacion','DESC');
            }
        }
        return $items;
    }

}
