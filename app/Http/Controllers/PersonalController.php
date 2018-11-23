<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response as HttpResponse;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \Request;

use App\Models\PersonalClues;


class PersonalController extends Controller
{
   
	public function index()
    {
        $parametros = Input::only('q','page','per_page');

    	if ($parametros['q']) {
             $items =  PersonalClues::where('nombre','LIKE',"%".$parametros['q']."%")->orWhere('clues','LIKE',"%".$parametros['q']."%");
        } else {
             $items =  PersonalClues::select('*');
        }

        $items = $items->with("unidad");

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 10;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }

    	return Response::json([ 'data' => $items],200);
    }
}
