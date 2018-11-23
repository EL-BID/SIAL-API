<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\TablaA;


use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response;

class TablaAController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Si la tabla usuario tuviera una relacion con otra tabla por ejemplo: roles,
        // y la necesitaramos listar, entonces, en la funcion with pudieramos escribir
        // separado por "." de esta forma: TablaA::with('usuario.roles')->get();
        
        $tabla_a = TablaA::with('usuario')->get();
        return $tabla_a;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       $mensajes = [
            'required'      => "required",
        ];

        $reglas = [
            'campo_1'   => 'required'
        ];

        $inputs = Input::only('campo_1', 'campo_2');
        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
            
            $permiso = TablaA::create($inputs);
            

            return Response::json([ 'data' => $permiso ],200);

        } catch (\Exception $e) {
          
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
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
