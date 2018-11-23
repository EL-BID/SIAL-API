<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;

use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use DateTime;


use App\Models\Movimiento;
use App\Models\Stock;
use App\Models\MovimientoInsumos;
use App\Models\TiposMovimientos;
use App\Models\Insumo;
use App\Models\MovimientoMetadato;
use App\Models\MovimientoDetalle;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\RecetaMovimiento;
use App\Models\ContratoPrecio;
use App\Models\NegacionInsumo;
use App\Models\Almacen;
use App\Models\CluesTurno;
use App\Models\CluesServicio;
use App\Models\Turno;
use App\Models\Servicio;
use App\Models\Programa;
use App\Models\PersonalClues;


/** 
* Controlador FuncionesGenerales
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2018-02-20
*
* Controlador `Funciones Generales`: Controlador que contiene funciones cotidianas para calculos genericos 
*
*/
class FuncionesGeneralesController extends Controller
{
     
    public function index(Request $request)
    {
       
    }
  
    public function store(Request $request)
    {

    }
 
    public function show($id)
    {
        
    }
  
    public function update(Request $request, $id)
    {
 
    }
     
    public function destroy($id)
    {
        
    }


///**************************************************************************************************************************
///                  F U N C I O N      C O N S E G U I R      P R E C I O    I N S U M O  
///**************************************************************************************************************************
 public function conseguirPrecioInsumo($insumo_medico_clave)
 {
    $precio_unitario = 0; 
    $iva             = 0;
    $tipo            = NULL;
    $response = new \stdClass();

    $parametros = NULL;

    $data =  DB::table("insumos_medicos AS im")->select(DB::raw("pbd.precio as precio_unitario"),"im.clave", "im.tipo", "im.es_causes", "im.es_unidosis", "im.descripcion")
            ->leftJoin('precios_base AS pb', 'pb.activo','=',DB::raw("1"))
            ->leftJoin('precios_base_detalles AS pbd', function($join){
                    $join->on('im.clave', '=', 'pbd.insumo_medico_clave');
                    $join->on('pbd.precio_base_id', '=', 'pb.id');
                })
            ->where('im.clave',$insumo_medico_clave)
            ->where('im.deleted_at',NULL)
            ->where(function($query2) use ($parametros) {
                $query2->where('im.tipo','ME')
                ->orWhere('im.tipo','MC');
            })->orderBy('im.clave', 'asc')->first();
            
    if($data)
    {
        $precio_unitario = $data->precio_unitario;
        $tipo            = $data->tipo;
        if($tipo == "MC")
        {   $iva = $data->precio_unitario * 0.16;}

    }else {
            $precio_unitario = NULL;
            $iva             = NULL;
            $tipo            = NULL;
          }

    $response->precio_unitario = $precio_unitario;
    $response->iva             = $iva;
    $response->tipo            = $tipo;

    return $response;
 }


}
