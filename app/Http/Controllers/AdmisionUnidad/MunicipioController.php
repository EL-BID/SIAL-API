<?php

namespace App\Http\Controllers\AdmisionUnidad;


use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\Municipio;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class MunicipioController extends Controller
{
    public function index()
    {
        $items = Municipio::orderBy("nombre")
        					->get();

        return Response::json([ 'data' => $items],200);
    }
    public function show($id)
    {
        $items = Municipio::where("id",$id)->get();
        return Response::json([ 'data' => $items ],200);
    }
}
