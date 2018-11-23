<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response as HttpResponse;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \Request;

use App\Models\PersonalPuesto;
use App\Models\PersonalClues;

class PersonalPuestoController extends Controller
{
    public function index()
    {
        $parametros = Input::only('q','page','per_page', 'clues', 'firmas');
        
        if(isset($parametros['firmas']) && $parametros['firmas'] == 1)
        {

        	$data = PersonalClues::join('personal_clues_puesto', "personal_clues_puesto.personal_id", "=", "personal_clues.id")
        						->join('puestos', "puestos.id", "=", "personal_clues_puesto.puesto_id")
        						->where(function ($query) {
					                $query->whereNull('personal_clues_puesto.fecha_fin')
					                      ->orWhere('personal_clues_puesto.fecha_fin', '>=', date('Y-m-d'));
					            })
					            ->select("personal_clues_puesto.id", "personal_clues.nombre", "puestos.nombre as puesto")
					            ->where("puestos.firma", 1);

        	if(isset($parametros['clues']))
        	{
        		$data = $data->where("clues", $parametros['clues']);
        	}					    

        	$data = $data->get();
        	return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data, "total" => count($data)), 200);
        	
        }

    }
}
