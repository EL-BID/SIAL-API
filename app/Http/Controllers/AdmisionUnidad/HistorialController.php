<?php

namespace App\Http\Controllers\AdmisionUnidad;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests;
use App\Models\Admision;
use App\Models\Paciente;
use App\Models\AreaResponsable;
use App\Models\Usuario;


use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class HistorialController extends Controller
{
    public function show($id)
    {
        $historial = Paciente::where("id", $id)->with("Ingreso.Unidad", "Ingreso.EstadoTriage", "Ingreso.MotivoEgreso", "localidad.municipio")->first();
        return Response::json([ 'data' => $historial ],200);
    }
}
