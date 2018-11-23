<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;

use Illuminate\Http\Response as HttpResponse;

use \Validator,\Hash, \Response, DB;
use App\Models\PersonalClues;


class PersonalMedicoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parametros = Input::only('term','tipo_personal','clues','page','per_page');


        ///************************************************************************
        $data =  DB::table("configuracion_general")->where('clave', 'personal_medico');
        $data = $data->first();
        if(!$data){
            
        }

        $tipo_personal_id = $data->valor;
        $tipo_personal_id = str_replace('"','',$tipo_personal_id);

        $parametros['tipo_personal_id'] = $tipo_personal_id;


        ////***********************************************************************

        if ($parametros['term']) {
             $data =  PersonalClues::where('clues',$parametros['clues'])
                                   ->where('tipo_personal_id', $parametros['tipo_personal_id'])
                                   ->where('nombre','LIKE',"%".$parametros['term']."%");

        } else {
                $data =  PersonalClues::where("id","!=", "")->where('clues',$parametros['clues'])
                                        ->where('tipo_personal_id', $parametros['tipo_personal_id']);              
        }

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $data = $data->paginate($resultadosPorPagina);
        } else {
            $data = $data->get();
        }
       
        return Response::json([ 'data' => $data],200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
