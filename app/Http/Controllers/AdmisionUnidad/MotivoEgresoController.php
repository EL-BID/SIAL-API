<?php

namespace App\Http\Controllers\AdmisionUnidad;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\MotivoEgreso;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
class MotivoEgresoController extends Controller
{
    
     public function index()
    {
    	$items = MotivoEgreso::all();

        return Response::json([ 'data' => $items],200);
    }
}
