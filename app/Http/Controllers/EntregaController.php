<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use JWTAuth;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Models\PedidoInsumo;
use App\Models\Movimiento, App\Models\MovimientoInsumos,
    App\Models\Stock, 
    App\Models\Insumo, 
    App\Models\Medicamento, 
    App\Models\MaterialCuracion,
    App\Models\MovimientoPedido;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;


class EntregaController extends Controller
{
    public function stats(Request $request){

        // Hay que obtener la clues del usuario
        $pedidos = Pedido::select(DB::raw(
            '
            count(
                case when status = "PS" then 1 else null end
            ) as por_surtir,
            count(
                case when status = "FI" then 1 else null end
            ) as finalizados
            '
        ))->where('almacen_proveedor',$request->get('almacen_id'))->first();

        return Response::json($pedidos,200);
    }
    public function index(Request $request)
    {
        
        
        
        $parametros = Input::only('status','q','page','per_page');

        if(isset($parametros['status'])) {
            if ($parametros['q']) {
                $pedidos =  Pedido::with("insumos", "acta", "tipoInsumo", "tipoPedido","almacenSolicitante","almacenProveedor")->where('almacen_proveedor',$request->get('almacen_id'))->where('id','LIKE',"%".$parametros['q']."%");
            } else {
                $pedidos = Pedido::with("insumos", "acta", "tipoInsumo", "tipoPedido","almacenSolicitante","almacenProveedor")->where('almacen_proveedor',$request->get('almacen_id'));
            }
        
            $pedidos = $pedidos->where("pedidos.status",$parametros['status'])->orderBy('created_at','desc');

            
            if(isset($parametros['page'])){
                $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
                $pedidos = $pedidos->paginate($resultadosPorPagina);
            } else {
                $pedidos = $pedidos->get();
            }

            return Response::json([ 'data' => $pedidos],200);
        } else {
            $movimientos = Movimiento::where('tipo_movimiento_id', 3)->with('movimientoPedido.pedido.almacenSolicitante');
            $movimientos = $movimientos->orderBy('created_at','desc');
            if(isset($parametros['page'])){
                $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
                $movimientos = $movimientos->paginate($resultadosPorPagina);
            } else {
                $movimientos = $movimientos->get();
            }

            return Response::json([ 'data' => $movimientos],200);
        }
    }

    public function show($id)
    {
    	$object = Movimiento::find($id);
        

        
        if(!$object){
            return Response::json(['error' => "No se encuentra el pedido que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        $movimientosInsumos = $object->movimientoInsumos;
        $movimientoPedido = $object->movimientoPedido;
        
        $pedido = $movimientoPedido->pedido;
        $pedido->load("insumos.insumosConDescripcion","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos");


        return Response::json([ 'data' => $object],200);
    }

    public function store(Request $request)
    {
        $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'pedido_id'        => 'required',
            'recibe'           => 'required',
            'entrega'           => 'required',
            'lista'             => 'array|required'
        ];

        $input = Input::all();
       
        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
           
            $pedido = Pedido::find($input['pedido_id']);


            if(!$pedido){
                throw new Exception("El pedido no existe");
            }

           /* $obj =  JWTAuth::parseToken()->getPayload();
            $usuario = Usuario::with('almacenes')->find($obj->get('id'));

            if(count($usuario->almacenes) > 1){
                //Harima: Aqui se checa si el usuario tiene asignado mas de un almacen, se busca en el request si se envio algun almacen seleccionado desde el cliente, si no marcar error
                return Response::json(['error' => 'El usuario tiene asignado mas de un almacen'], HttpResponse::HTTP_CONFLICT);
            }else{
                $almacen = $usuario->almacenes[0];
            }*/

            // deberíamos mandar el id del almacen desde el cliente 
            //y corroborar que o tenga el usuario asignado y con el pedido correspondiente;

            $input['almacen_id'] =  $request->get('almacen_id');   //$pedido->almacen_proveedor;

            // Movimiento de tipo salida por entrega
            $input['tipo_movimiento_id'] = 3;

            
            
            $object = Movimiento::create($input);
            $object->movimientoPedido()->create([                
                'pedido_id' => $input['pedido_id'],
                'recibe' => $input['recibe'],
                'entrega' => $input['entrega']
            ]);
            $listaInsumos = [];
            
            //Que onda con el ¿precio? ¿Como se calcula?
            foreach($input['lista'] as $item){
                $listaInsumos[] = new MovimientoInsumos([
                    'stock_id' => $item['id'],
                    'cantidad' => -$item['cantidad']
                ]);

                $stock  = Stock::find($item['id']);
                $stock->existencia = $stock->existencia - $item['cantidad'];

                $insumo = Insumo::find($stock->clave_insumo_medico);
                if($insumo->es_unidosis){
                    if($insumo->tipo == "ME"){
                        $insumo = Medicamento::where("insumo_medico_clave",$stock->clave_insumo_medico)->first();
                        $stock->existencia_unidosis = $stock->existencia_unidosis - ($insumo->cantidad_x_envase * $item['cantidad']);
                    }
                    if($insumo->tipo == "MC"){
                        $insumo = MaterialCuracion::where("insumo_medico_clave",$stock->clave_insumo_medico)->first();
                        $stock->existencia_unidosis = $stock->existencia_unidosis - ($insumo->cantidad_x_envase * $item['cantidad']);
                    }
                }
                $stock->save();

                

            }

            
            $object->movimientoInsumos = $object->insumos()->saveMany($listaInsumos);

            DB::commit();
            return Response::json([ 'data' => $object ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }



    function destroy($id)
    {
        // Cancelar en todo caso
        return false;
        try {
            $object = Pedido::destroy($id);
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }

    }
}
