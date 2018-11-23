<?php

namespace App\Http\Controllers\AdmisionUnidad;


use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\Localidad;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;


class LocalidadController extends Controller
{
    public function index()
    {
    	$parametros = Input::all();
        $items = Localidad::where("municipio_id", $parametros['municipio_id'])
        					->orderBy("nombre")
        					->get();

        return Response::json([ 'data' => $items],200);
    }

     public function show($id)
    {
    	$parametros = Input::all();
        $items = Localidad::where("id",$id)
        					->where("municipio_id", $parametros['municipio_id'])->get();
        return Response::json([ 'data' => $items ],200);
    }
}
