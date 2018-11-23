<?php

namespace App\Http\Controllers\Inventario;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;

use App\Models\Usuario, App\Models\Insumo, App\Models\Almacen, App\Models\Inventario, App\Models\InventarioDetalle;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class InicializacionInventarioController extends Controller{

    public function index(Request $request){
        $parametros = Input::only('status','q','page','per_page');
        
        $inventarios = Inventario::getModel();

        if ($parametros['q']) {
            $inventarios =  $inventarios->where(function($query) use ($parametros) {
                    $query->where('descripcion','LIKE',"%".$parametros['q']."%")->orWhere('observaciones','LIKE',"%".$parametros['q']."%")->orWhere('fecha_conclusion_captura','LIKE',"%".$parametros['q']."%")->orWhere('fecha_inicio_captura','LIKE',"%".$parametros['q']."%");
                });
        }

        //$pedidos = $pedidos->where('almacen_solicitante',$almacen->id)->where('clues',$almacen->clues);
        $inventarios = $inventarios->where('almacen_id',$request->get('almacen_id'));

        if(isset($parametros['status'])) {
            $inventarios = $inventarios->where("status",$parametros['status']);
        }
        
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
            $inventarios = $inventarios->paginate($resultadosPorPagina);
        } else {
            $inventarios = $inventarios->get();
        }

        return Response::json([ 'data' => $inventarios],200);
    }

    public function store(Request $request){
        $mensajes = [
            'required'      => "required",
        ];
        
        $reglas = [
            'descripcion' => 'required',
            'lista_insumos' => 'required|array|min:1'
            //'fecha_inicio_captura',
            //'fecha_conclusion_captura',
            //'observaciones',
            //'status',
            //'almacen_id',
            //'clues',
            //'total_claves',
            //'total_monto_causes',
            //'total_monto_no_causes',
            //'total_monto_material_curacion'
        ];

        $inventario = Inventario::where('almacen_id',$request->get('almacen_id'))->first();

        if($inventario){
            return Response::json(['error' => 'Ya hay una inicialización de inventario capturada, no se puede inicializar el inventario'], HttpResponse::HTTP_CONFLICT);
        }
        
        $parametros = Input::all();

        $v = Validator::make($parametros, $reglas, $mensajes);
        
        if ($v->fails()) {
            return Response::json(['error' => $v->errors(), 'error_formulario'=>1], HttpResponse::HTTP_CONFLICT);
        }

        $almacen = Almacen::find($request->get('almacen_id'));

        try{
            DB::beginTransaction();
            $datos_inventario = [
                'descripcion' => $parametros['descripcion'],
                'observaciones' => ($parametros['observaciones'])?$parametros['observaciones']:null,
                'fecha_inicio_captura',
                'status' => 'BR',
                'almacen_id' => $almacen->id,
                'clues' => $almacen->clues,
                'total_claves' => 0,
                'total_monto_causes' => 0,
                'total_monto_no_causes' => 0,
                'total_monto_material_curacion' => 0
            ];
            
            $nuevo_inventario = Inventario::create($datos_inventario);
            DB::commit();

            $response = $this->guardarDatosInventario($nuevo_inventario,$parametros['lista_insumos']);

            if($response['status'] == 200){
                return Response::json([ 'data' => $nuevo_inventario],200);
            }else{
                return Response::json(['error' => $response['error']], HttpResponse::HTTP_CONFLICT);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    private function guardarDatosInventario($inventario,$lista_insumos_form){
        try{
            DB::beginTransaction();

            $lista_insumos_db = InventarioDetalle::where('inventario_id',$inventario->id)->withTrashed()->get();
            
            if(count($lista_insumos_db) > count($lista_insumos_form)){
                $total_max_insumos = count($lista_insumos_db);
            }else{
                $total_max_insumos = count($lista_insumos_form);
            }
            
            for ($i=0; $i < $total_max_insumos ; $i++) {
                if(isset($lista_insumos_db[$i])){ //Si existen en la base de datos se editan o eliminan.
                    $insumo_db = $lista_insumos_db[$i];
                    if(isset($lista_insumos_form[$i])){ //Si hay insumos desde el fomulario, editamos el insumo de la base de datos.
                        $insumo_form = $lista_insumos_form[$i];
    
                        $insumo_db->deleted_at = null; //Por si el elemento ya esta liminado, lo restauramos
                        $insumo_db->insumo_medico_clave = $lista_insumos_form['clave'];
                        $insumo_db->codigo_barras = $lista_insumos_form['codigo_barras'];
                        $insumo_db->lote = $lista_insumos_form['lote'];
                        $insumo_db->fecha_caducidad = $lista_insumos_form['fecha_caducidad'];
                        $insumo_db->cantidad = $lista_insumos_form['cantidad'];
                        $insumo_db->precio_unitario = $lista_insumos_form['precio_unitario'];
                        $insumo_db->monto = $lista_insumos_form['monto'];
    
                        $insumo_db->save();
                    }else{ //de lo contrario eliminamos el insumo de la base de datos.
                        $insumo_db->delete();
                    }
                }else{ //SI no existen en la base de datos, se crean nuevos
                    $insumo_db = new InventarioDetalle();

                    $insumo_db->inventario_id = $inventario->id;
                    $insumo_db->insumo_medico_clave = $lista_insumos_form['clave'];
                    $insumo_db->codigo_barras = $lista_insumos_form['codigo_barras'];
                    $insumo_db->lote = $lista_insumos_form['lote'];
                    $insumo_db->fecha_caducidad = $lista_insumos_form['fecha_caducidad'];
                    $insumo_db->cantidad = $lista_insumos_form['cantidad'];
                    $insumo_db->precio_unitario = $lista_insumos_form['precio_unitario'];
                    $insumo_db->monto = $lista_insumos_form['monto'];

                    $insumo_db->save();
                }
            }

            //Falta sumar los totales, de claves y montos

            DB::commit();

            return array('status'=>200, 'msg'=>'Exito');

        }catch (\Exception $e) {
            DB::rollBack();
            return array('status'=>HttpResponse::HTTP_CONFLICT, 'error'=>$e->getMessage());
        }
    }
}