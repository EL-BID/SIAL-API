<?php

namespace App\Http\Controllers\AdmisionUnidad;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\EstadoTriage;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class TriageController extends Controller
{
     public function index()
    {
    	$parametros = Input::all();
    	if(isset($parametros['triage_id']))
    	{
			$items = EstadoTriage::where("id", $parametros['triage_id'])->get();    		
    	}else
    	  	$items = EstadoTriage::all();

        return Response::json([ 'data' => $items],200);
    }
}
