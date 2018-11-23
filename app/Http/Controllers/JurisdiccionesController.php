<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Jurisdiccion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class JurisdiccionesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //return Response::json([ 'data' => []],200);
        //return Response::json(['error' => "NO EXSITE LA BASE"], 500);
        $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
             $items =  Jurisdiccion::where('nombre','LIKE',"%".$parametros['q']."%");
        } else {
             $items =  Jurisdiccion::select('*');
        }

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }
       
        return Response::json([ 'data' => $items],200);
    }
}
