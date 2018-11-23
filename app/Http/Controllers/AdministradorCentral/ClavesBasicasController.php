<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\ClavesBasicas, App\Models\ClavesBasicasDetalle,App\Models\ClavesBasicasUnidadMedica,App\Models\UnidadMedica;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class ClavesBasicasController extends Controller
{

     

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
             $items =  ClavesBasicas::where('nombre','LIKE',"%".$parametros['q']."%");
        } else {
             $items =  ClavesBasicas::select('*');
        }

        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }
       
        return Response::json([ 'data' => $items],200);

    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $mensajes = [            
            'required'      => "required",
            'array'        => "array"
        ];

        $reglas = [
            'nombre'        => 'required',
            'items'        => 'required|array',
        ];

        $inputs = Input::only('nombre','items');

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {

            $clavesBasicas = ClavesBasicas::create($inputs);

            $items = [];
            foreach($inputs['items'] as $item){
                $items[] = new ClavesBasicasDetalle([
                    'insumo_medico_clave' => $item
                ]);
            }

            $clavesBasicas->detalles()->saveMany($items);

            DB::commit();
            return Response::json([ 'data' => $clavesBasicas ],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $object = ClavesBasicas::find($id);

        if(!$object){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        $object =  $object->load("detalles.insumoConDescripcion.informacion","detalles.insumoConDescripcion.generico.grupos");

        return Response::json([ 'data' => $object ], HttpResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $mensajes = [            
            'required'      => "required",
            'array'        => "array"
        ];

        $reglas = [
            'nombre'        => 'required',
            'items'        => 'required|array',
        ];

        $inputs = Input::only('nombre','items');
        $inputs['clues'] = $request->get('clues');

        $clavesBasicas = ClavesBasicas::find($id);

        if(!$clavesBasicas){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }
        
        if($inputs['clues'] != $clavesBasicas->clues){
            return Response::json(['error' =>  'No tienes permiso para editar esta lista'], HttpResponse::HTTP_FORBIDDEN);
        }
        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {

            


            $clavesBasicas->nombre = $inputs['nombre'];
            $clavesBasicas->save();

            $detalles = $clavesBasicas->detalles()->get();

            foreach($detalles as $item){
                 ClavesBasicasDetalle::destroy($item->id);
            }
           


            $items = [];
            foreach($inputs['items'] as $item){
                $items[] = new ClavesBasicasDetalle([
                    'insumo_medico_clave' => $item
                ]);
            }

            $clavesBasicas->detalles()->saveMany($items);

            DB::commit();
            return Response::json([ 'data' => $clavesBasicas ],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $object = ClavesBasicas::destroy($id);
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }


    /*/////////////////////////////////////////////////////////////////////*/
    /* SECCION PARA AGREGAR UNIDADES MÉDICAS */
    /*/////////////////////////////////////////////////////////////////////*/

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function unidadesMedicas(Request $request, $id)
    {
        $object = ClavesBasicas::find($id);

        if(!$object){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        $parametros = Input::only('q','page','per_page','sin_asignar');
        
        if ($parametros['sin_asignar']) {

            $items = UnidadMedica::select('unidades_medicas.*')
                ->leftjoin(DB::raw('
                    (
                        SELECT *  FROM claves_basicas_unidades_medicas WHERE deleted_at IS NULL AND claves_basicas_id = '.$id.'
                    ) AS claves_basicas_unidades_medicas
                '),'unidades_medicas.clues','=','claves_basicas_unidades_medicas.clues')
                
                ->whereNull('claves_basicas_unidades_medicas.clues')
                ->where('activa','1')->get();
           
        } else {
            $items = ClavesBasicasUnidadMedica::select('claves_basicas_unidades_medicas.*','unidades_medicas.nombre')
                                        ->leftjoin('unidades_medicas','unidades_medicas.clues','=','claves_basicas_unidades_medicas.clues')
                                        ->where('claves_basicas_unidades_medicas.claves_basicas_id',$id);
            if ($parametros['q']) {
                $items =  $items->where('unidades_medicas.nombre','LIKE',"%".$parametros['q']."%")
                                ->orWhere('unidades_medicas.clues','LIKE',"%".$parametros['q']."%");
            } 
            
            if(isset($parametros['page'])){
                $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
                $items = $items->paginate($resultadosPorPagina);
            } else {
                $items = $items->get();
            }
        }
       
        return Response::json([ 'data' => $items],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function agregarUnidadMedica(Request $request)
    {
        $mensajes = [            
            'required'      => "required",
            'array'        => "array"
        ];

        $reglas = [
            'claves_basicas_id'        => 'required',
            'lista_clues'        => 'array',
        ];

        $input = Input::only('claves_basicas_id','clues','lista_clues');

        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        $clavesBasicas = ClavesBasicas::find($input['claves_basicas_id']);

        if(!$clavesBasicas){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();
        try {

            if(isset($input['lista_clues'])){
                $items = [];
                foreach($input['lista_clues'] as $item){
                    $items[] = new ClavesBasicasUnidadMedica([
                        'clues' => $item
                    ]);
                }

                $clavesBasicas->unidadesMedicas()->saveMany($items);
            } else {
                $clavesBasicas->unidadesMedicas()->save(
                    new ClavesBasicasUnidadMedica([
                        'clues' => $input['clues']
                    ])
                );
            }

            

            DB::commit();
            return Response::json([ 'data' => $clavesBasicas ],200);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function quitarUnidadMedica($id)
    {
        try {
            $object = ClavesBasicasUnidadMedica::destroy($id);
            return Response::json(['data'=>$object],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
