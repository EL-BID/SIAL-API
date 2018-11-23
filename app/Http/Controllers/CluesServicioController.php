<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Servicio;


use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;
 

class CluesServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    { 

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
        $array_clues_servicios = array();
        $clues_servicios = DB::table('clues_servicios')->where('clues',$id)->get();

        foreach($clues_servicios as $item_cs)
        {
            $servicio = Servicio::find($item_cs->servicio_id);
            $servicio = (object) $servicio;

            $item_cs->servicio = $servicio;

            array_push($array_clues_servicios,$item_cs);
        }

    return Response::json(array("status" => 200,"messages" => "Operación con exito","data" => $array_clues_servicios), 200);

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
