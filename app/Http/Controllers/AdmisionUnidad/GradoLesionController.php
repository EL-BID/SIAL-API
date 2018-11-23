<?php

namespace App\Http\Controllers\AdmisionUnidad;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\GradoLesion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class GradoLesionController extends Controller
{
     public function index()
    {
    	$parametros = Input::all();
    	if(isset($parametros['grado_lesion_id']))
    	{
			$items = GradoLesion::where("id", $parametros['grado_lesion_id'])->get();    		
		}else
    		$items = GradoLesion::all();

        return Response::json([ 'data' => $items],200);
    }
}
