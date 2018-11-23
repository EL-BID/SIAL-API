<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Stock;


use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response;

use Carbon\Carbon;

class ComprobarStockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $parametros = Input::only('almacen','clave','programa_id');

        $parametros['almacen'] = $parametros['almacen'] ? $parametros['almacen']:$request->get('almacen_id');

        $stocks = array();
        $programa_id = $parametros['programa_id'] ;

        

        if( ($parametros['programa_id']== "") || ($parametros['programa_id']== 'null') )
        {

            $stocks = Stock::with('programa','movimientoInsumo')
                        ->where('clave_insumo_medico',$parametros['clave'])
                        ->where('existencia','>',0)
                        ->where('almacen_id',$parametros['almacen'])
                        ->orderBy('fecha_caducidad','ASC')
                        ->get();
            
        }else{
                $stocks = Stock::with('programa','movimientoInsumo')
                        ->where('clave_insumo_medico',$parametros['clave'])
                        ->where('existencia','>',0)
                        ->where('programa_id',$programa_id)
                        ->where('almacen_id',$parametros['almacen'])
                        ->orderBy('fecha_caducidad','ASC')
                        ->get();
             }  

        

        $existencia = 0;
        $existencia_unidosis = 0;

        $stocks_monitor = array();

        foreach($stocks as $stock)
        {
            $existencia += $stock->existencia;
            $existencia_unidosis += $stock->existencia_unidosis;

            $fecha_caducidad = $stock->fecha_caducidad;
            $tipo_caducidad  = null;

            $fecha_hoy    = Carbon::now()->format("Y-m-d");
            $fecha_optima = Carbon::now()->addYears(1)->format("Y-m-d");
            $fecha_media  = Carbon::now()->addMonths(6)->format("Y-m-d");
            $fecha_pronta = Carbon::now()->addMonths(6)->format("Y-m-d");

            if($fecha_caducidad >= $fecha_optima )
            {
                $tipo_caducidad = "OPTIMA";
            } 
            if($fecha_caducidad >= $fecha_media && $fecha_caducidad < $fecha_optima )
            {
                $tipo_caducidad = "MEDIA";
            } 
            if($fecha_caducidad >= $fecha_hoy && $fecha_caducidad < $fecha_media )
            {
                $tipo_caducidad = "PRONTA";
            } 
            if($fecha_caducidad < $fecha_hoy )
            {
                $tipo_caducidad = "CADUCADO";
            } 

            $stock->tipo_caducidad = $tipo_caducidad;
            array_push($stocks_monitor, $stock);

        }               

        $objeto_response = array('almacen_id' => $parametros['almacen'],
                                        'clave' => $parametros['clave'],
                                        'existencia' => $existencia,
                                        'existencia_unidosis' => $existencia_unidosis,
                                        'data' => $stocks_monitor,
                                        'status' => 200,
                                        'messages' => 'Operación realizada con exito',
                                        'total' => count($stocks));

        return Response::json($objeto_response,HttpResponse::HTTP_OK);
        //return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data, "total" => count($data)), 200);

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
