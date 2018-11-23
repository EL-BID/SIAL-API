<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Stock, App\Models\Insumo, App\Models\Almacen;


use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $parametros = Input::only('q','clave');

        if (isset($parametros['q']) && $parametros['q'] != "") {
            /*$insumos = Insumo::select('clave')->where('descripcion','LIKE','%'.$parametros['q'].'%')->get();
            $clavesInsumos = [];
            foreach($insumos as $valor){
                $clavesInsumos[] = $valor->clave;
            }*/
            $stock =  Stock::select('stock.*','insumos_medicos.descripcion')->with("insumo","almacen")->leftjoin('insumos_medicos','stock.clave_insumo_medico','=','insumos_medicos.clave')->where(function($query) use ($parametros) {
                $query->where('codigo_barras','=',$parametros['q'])
                ->orWhere('lote','=',$parametros['q'])
                ->orWhere('clave_insumo_medico','LIKE','%'.$parametros['q'].'%')
                ->orWhere('descripcion','LIKE','%'.$parametros['q'].'%');
                //$query->where('codigo_barras','=',$parametros['q'])->orWhere('lote','=',$parametros['q']);
            });//->whereIn("clave_insumo_medico",$clavesInsumos);
        } else {
             $stock = Stock::with("insumo","almacen");
        }
        //"insumos.tipoInsumo","insumos.insumosConDescripcion.informacion","insumos.insumosConDescripcion.generico.grupos"
        

        if (isset($parametros['clave'])){
            $stock = $stock->where("clave_insumo_medico",'LIKE','%'.$parametros['clave'].'%');
        }
        
        $stock = $stock->where("stock.existencia",">","0")->where('stock.almacen_id',$request->get('almacen_id'))->get();
        
        $almacen = Almacen::find($request->get('almacen_id'));
        
        foreach($stock as $item){
            //$item->load("insumosConDescripcion.informacion","insumosConDescripcion.generico.grupos");
            $item->insumo->informacion;
            $genericos = $item->insumo->generico;
            $genericos->grupos;
            if($almacen){
                $r =  DB::table('contratos_precios')->where('proveedor_id',  $almacen->proveedor_id)->where('insumo_medico_clave',$item->clave_insumo_medico)->orderBy("contrato_id","desc")->first();
                $item->precio = $r->precio;
                $item->tipo_insumo_id = $r->tipo_insumo_id;
            }
            
            //clave_insumo_medico
        }

        return Response::json([ 'data' => $stock],200);
    }

    public function stockInsumoMedico(Request $request){
        if(Input::get('clave')){
            $stock = Stock::where('almacen_id',$request->get('almacen_id'))->where('clave_insumo_medico',Input::get('clave'))->where('existencia','>',0)->orderBy('fecha_caducidad')->get();
            return Response::json([ 'data' => $stock],200);
        }else{
            return Response::json(['error' => "No se especifico la clave del insumo."], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
