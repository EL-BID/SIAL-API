<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;

use App\Http\Requests;
use App\Models\Pedido;
use App\Models\Acta;
use App\Models\Usuario;
use App\Models\Almacen;
use App\Models\UnidadMedica;
use Carbon\Carbon;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, DB;


class ActasController extends Controller{
	public function index(Request $request){
		$almacen = Almacen::find($request->get('almacen_id'));
		
		$parametros = Input::only('q','page','per_page');
  
		
		$actas = Acta::getModel();
  
	    if ($parametros['q']) {
		    $actas =  $actas->where(function($query) use ($parametros) {
				$query->where('nombre','LIKE',"%".$parametros['q']."%")->orWhere('folio','LIKE',"%".$parametros['q']."%")
				->orWhere('fecha','LIKE',"%".$parametros['q']."%");
			});
		}
  
		
		$actas = $actas->where('clues',$almacen->clues);
  
		$actas = $actas->orderBy('incremento','desc');
		if(isset($parametros['page'])){
		    $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
		    $actas = $actas->paginate($resultadosPorPagina);
		} else {
		    $actas = $actas->get();
		}
  
		return Response::json([ 'data' => $actas],200);
	 }
  
	 public function show(Request $request, $id){
		$almacen = Almacen::find($request->get('almacen_id'));
		$acta = Acta::where('clues',$almacen->clues)->find($id);
		
		if(!$acta){
		    return Response::json(['error' => "No se encuentra el acta que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
		}else{  
			$acta->pedidos;
			$acta->director;
			$acta->administrador;
			$proveedor = $acta->proveedor;
			$proveedor->contratoActivo;
			$acta->unidadMedica;
			$acta->personaEncargadaAlmacen;
			
		}
		return Response::json([ 'data' => $acta],200);
	 }
}
