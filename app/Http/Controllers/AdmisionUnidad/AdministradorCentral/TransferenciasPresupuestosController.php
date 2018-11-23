<?php

namespace App\Http\Controllers\AdministradorCentral;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Presupuesto, App\Models\UnidadMedicaPresupuesto,  App\Models\TransferenciaPresupuesto;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;
use \Excel;

class TransferenciasPresupuestosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lista()
    {
        $parametros = Input::only('clues_origen','clues_destino','mes_origen','mes_destino','anio_origen','anio_destino','page','per_page');
        
        $items = TransferenciaPresupuesto::select('transferencias_presupuesto.*','unidades_medicas_origen.nombre as unidad_medica_origen', 'unidades_medicas_destino.nombre as unidad_medica_destino',
                                                    'almacenes_origen.nombre as almacen_origen_nombre', 'almacenes_destino.nombre as almacen_destino_nombre',
                                                    'almacenes_origen.tipo_almacen as almacen_origen_tipo', 'almacenes_destino.tipo_almacen as almacen_destino_tipo')
                ->leftjoin(DB::raw('unidades_medicas as unidades_medicas_origen'),'unidades_medicas_origen.clues','=','transferencias_presupuesto.clues_origen')
                ->leftjoin(DB::raw('unidades_medicas as unidades_medicas_destino'),'unidades_medicas_destino.clues','=','transferencias_presupuesto.clues_destino')
                ->leftjoin(DB::raw('almacenes as almacenes_origen'),'almacenes_origen.id','=','transferencias_presupuesto.almacen_origen')
                ->leftjoin(DB::raw('almacenes as almacenes_destino'),'almacenes_destino.id','=','transferencias_presupuesto.almacen_destino')
                ->orderBy('created_at','desc');

        if (isset($parametros['clues_origen']) &&  $parametros['clues_origen'] != "") {

            $items = $items->where(function($query) use ($parametros) {
                    $query->where('clues_origen','LIKE',"%".$parametros['clues_origen']."%")
                            ->orWhere('unidades_medicas_origen.nombre','LIKE',"%".$parametros['clues_origen']."%");
            });
          
        }
        if (isset($parametros['clues_destino']) &&  $parametros['clues_destino'] != "") {
           $items = $items->where(function($query) use ($parametros) {
                    $query->where('clues_destino','LIKE',"%".$parametros['clues_destino']."%")
                            ->orWhere('unidades_medicas_destino.nombre','LIKE',"%".$parametros['clues_destino']."%");
            });
        }

        if (isset($parametros['mes_origen']) &&  $parametros['mes_origen'] != "-1") {
           $items = $items->where('mes_origen',$parametros['mes_origen']);
        }

        if (isset($parametros['mes_destino']) &&  $parametros['mes_destino'] != "-1") {
           $items = $items->where('mes_destino',$parametros['mes_destino']);
        }


        if (isset($parametros['anio_origen']) &&  $parametros['anio_origen'] != "-1") {
           $items = $items->where('anio_origen',$parametros['anio_origen']);
        }

        if (isset($parametros['anio_destino']) &&  $parametros['anio_destino'] != "-1") {
           $items = $items->where('anio_destino',$parametros['anio_destino']);
        }
        
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
            $items = $items->paginate($resultadosPorPagina);
        } else {
            $items = $items->get();
        }

        return Response::json([ 'data' => $items],200);
    }

    public function unidadesMedicasConPresupuesto(Request $request){
        try{
            $parametros = Input::only('mes','anio','clues_con_saldo');

            $presupuesto_activo = Presupuesto::where('activo',1)->first();
            if(!$presupuesto_activo){              
                throw new Exception("No hay presupuesto activo.");
            }
            
            $items = [];
            if(isset($parametros['clues_con_saldo']) && isset($parametros['mes']) && isset($parametros['anio'])){
                $items = UnidadMedicaPresupuesto::select('unidad_medica_presupuesto.*')
                            ->where(function($query) use ($parametros) {
                                    $query->where('unidad_medica_presupuesto.insumos_disponible','>',0)
                                            ->orWhere('unidad_medica_presupuesto.no_causes_disponible','>',0);
                                            //->where('unidad_medica_presupuesto.causes_disponible','>',0)
                                            //->orWhere('unidad_medica_presupuesto.material_curacion_disponible','>',0);
                            })
                            ->where('mes',$parametros['mes'])
                            ->where('anio',$parametros['anio'])
                            ->where('presupuesto_id',$presupuesto_activo->id)->with('unidadMedica','almacen')->groupBy('clues')->orderBy('clues','asc')->get();
            } else {
                $items = UnidadMedicaPresupuesto::select('clues')->where('presupuesto_id',$presupuesto_activo->id)->with('unidadMedica.almacenes')->groupBy('clues')->orderBy('clues','asc')->get();
            }
            
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }
    
    public function presupuestoUnidadMedica(Request $request){
        try{

            $input = Input::only('clues','mes','anio','almacen');
            
            $item = UnidadMedicaPresupuesto::where('clues',$input['clues'])->where('almacen_id',$input['almacen'])->where('mes',$input['mes'])->where('anio',$input['anio'])->first();
      
            return Response::json([ 'data' => $item],200,[], JSON_NUMERIC_CHECK);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function mesesPresupuestoActual(Request $request){
        try{

            $presupuesto_activo = Presupuesto::where('activo',1)->first();
            if(!$presupuesto_activo){              
                throw new Exception("No hay presupuesto activo.");
            }
            
            $items = UnidadMedicaPresupuesto::select('mes', DB::raw(
                'CASE mes
                    WHEN 1 THEN "ENERO" 
                    WHEN 2 THEN "FEBRERO" 
                    WHEN 3 THEN "MARZO" 
                    WHEN 4 THEN "ABRIL" 
                    WHEN 5 THEN "MAYO" 
                    WHEN 6 THEN "JUNIO" 
                    WHEN 7 THEN "JULIO" 
                    WHEN 8 THEN "AGOSTO" 
                    WHEN 9 THEN "SEPTIEMBRE" 
                    WHEN 10 THEN "OCTUBRE" 
                    WHEN 11 THEN "NOVIEMBRE" 
                    ELSE "DICIEMBRE" END AS nombre'
            ))->where('presupuesto_id',$presupuesto_activo->id)->groupBy('mes')->orderBy('mes','asc')->get();
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    public function mesesAnioPresupuestoActualAnteriorFechaActual(Request $request){
        try{

            $presupuesto_activo = Presupuesto::where('activo',1)->first();
            if(!$presupuesto_activo){              
                throw new Exception("No hay presupuesto activo.");
            }
            $anio = date('Y');
            $mes = date('n');

            $items = UnidadMedicaPresupuesto::select(DB::raw('CONCAT(mes,"/",anio) as fecha'),'mes','anio', DB::raw(
                'CASE mes
                    WHEN 1 THEN "ENERO" 
                    WHEN 2 THEN "FEBRERO" 
                    WHEN 3 THEN "MARZO" 
                    WHEN 4 THEN "ABRIL" 
                    WHEN 5 THEN "MAYO" 
                    WHEN 6 THEN "JUNIO" 
                    WHEN 7 THEN "JULIO" 
                    WHEN 8 THEN "AGOSTO" 
                    WHEN 9 THEN "SEPTIEMBRE" 
                    WHEN 10 THEN "OCTUBRE" 
                    WHEN 11 THEN "NOVIEMBRE" 
                    ELSE "DICIEMBRE" END AS mes_nombre'
            ))->where('presupuesto_id',$presupuesto_activo->id)
            ->where('mes','<',$mes)
            ->where('anio','<=',$anio)
            ->groupBy('mes')->orderBy('mes','asc')->get();
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }
    public function aniosPresupuestoActual(Request $request){
        try{

            $presupuesto_activo = Presupuesto::where('activo',1)->first();
            if(!$presupuesto_activo){              
                throw new Exception("No hay presupuesto activo.");
            }
            
            $items = UnidadMedicaPresupuesto::select('anio')->where('presupuesto_id',$presupuesto_activo->id)->groupBy('anio')->orderBy('anio','asc')->get();
      
            return Response::json([ 'data' => $items],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        } 
    }

    /**
     * Transfiere recursos de una clus origen a una destino.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function transferir(Request $request)
    {
        
        $mensajes = [
            
            'required'      => "required",
            'numeric'       => "numeric"
        ];

        $reglas = [
            'clues_origen'      => 'required',
            'clues_destino'     => 'required',
            'almacen_origen'    => 'required',
            'almacen_destino'   => 'required',
            'mes_origen'        => 'required',
            'mes_destino'       => 'required',
            'anio_origen'       => 'required',
            'anio_destino'      => 'required',
            'causes'            => 'numeric',
            'no_causes'         => 'numeric',
            'material_curacion' => 'numeric'
        ];

        $input = Input::only('clues_origen','clues_destino','almacen_origen','almacen_destino', 'mes_origen','mes_destino', 'anio_origen','anio_destino','causes','no_causes','material_curacion');

        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {

            $unidad_medica_origen_presupuesto = UnidadMedicaPresupuesto::where('clues',$input['clues_origen'])->where('almacen_id',$input['almacen_origen'])->where('mes',$input['mes_origen'])->where('anio',$input['anio_origen'])->first();
            $unidad_medica_destino_presupuesto = UnidadMedicaPresupuesto::where('clues',$input['clues_destino'])->where('almacen_id',$input['almacen_destino'])->where('mes',$input['mes_destino'])->where('anio',$input['anio_destino'])->first();
            
            if(!$unidad_medica_origen_presupuesto || !$unidad_medica_destino_presupuesto){
                throw new Exception("Una de las clues no tiene presupuesto configurado para los valores proporcionados.");
            }

            if(isset($input['insumos'])){
                $input['insumos'] += 0.0;
                if($input['insumos'] > $unidad_medica_origen_presupuesto->insumos_disponible){
                    throw new Exception("La cantidad de insumos es mayor al presupuesto disponible del origen.");
                }
                if( $input['insumos'] > 0){
                    $unidad_medica_origen_presupuesto->insumos_modificado = $unidad_medica_origen_presupuesto->insumos_modificado - $input['insumos'];
                    $unidad_medica_origen_presupuesto->insumos_disponible = $unidad_medica_origen_presupuesto->insumos_disponible - $input['insumos'];

                    $unidad_medica_destino_presupuesto->insumos_modificado = $unidad_medica_destino_presupuesto->insumos_modificado + $input['insumos'];
                    $unidad_medica_destino_presupuesto->insumos_disponible = $unidad_medica_destino_presupuesto->insumos_disponible + $input['insumos'];
                } else {
                    $input['insumos'] = 0.0;
                }
            } else {
                $input['insumos'] = 0.0;
            }
            

            if(isset($input['no_causes'])){
                $input['no_causes'] += 0.0;
                if($input['no_causes'] > $unidad_medica_origen_presupuesto->no_causes_disponible){
                    throw new Exception("La cantidad de No causes es mayor al presupuesto disponible del origen.");
                }
                if( $input['no_causes'] > 0){
                    $unidad_medica_origen_presupuesto->no_causes_modificado = $unidad_medica_origen_presupuesto->no_causes_modificado - $input['no_causes'];
                    $unidad_medica_origen_presupuesto->no_causes_disponible = $unidad_medica_origen_presupuesto->no_causes_disponible - $input['no_causes'];

                    $unidad_medica_destino_presupuesto->no_causes_modificado = $unidad_medica_destino_presupuesto->no_causes_modificado + $input['no_causes'];
                    $unidad_medica_destino_presupuesto->no_causes_disponible = $unidad_medica_destino_presupuesto->no_causes_disponible + $input['no_causes'];
                } else {
                    $input['no_causes'] = 0.0;
                }
            } else {
                $input['no_causes'] = 0.0;
            }

            /*
            if(isset($input['causes'])){
                $input['causes'] += 0.0;
                if($input['causes'] > $unidad_medica_origen_presupuesto->causes_disponible){
                    throw new Exception("La cantidad de Causes es mayor al presupuesto disponible del origen.");
                }
                if( $input['causes'] > 0){
                    $unidad_medica_origen_presupuesto->causes_modificado = $unidad_medica_origen_presupuesto->causes_modificado - $input['causes'];
                    $unidad_medica_origen_presupuesto->causes_disponible = $unidad_medica_origen_presupuesto->causes_disponible - $input['causes'];

                    $unidad_medica_destino_presupuesto->causes_modificado = $unidad_medica_destino_presupuesto->causes_modificado + $input['causes'];
                    $unidad_medica_destino_presupuesto->causes_disponible = $unidad_medica_destino_presupuesto->causes_disponible + $input['causes'];
                } else {
                    $input['causes'] = 0.0;
                }
            } else {
                $input['causes'] = 0.0;
            }

            if(isset($input['material_curacion'])){
                $input['material_curacion'] += 0.0;
                if($input['material_curacion'] > $unidad_medica_origen_presupuesto->material_curacion_disponible){
                    throw new Exception("La cantidad de No causes es mayor al presupuesto disponible del origen.");
                }
                if( $input['material_curacion'] > 0){
                    $unidad_medica_origen_presupuesto->material_curacion_modificado = $unidad_medica_origen_presupuesto->material_curacion_modificado - $input['material_curacion'];
                    $unidad_medica_origen_presupuesto->material_curacion_disponible = $unidad_medica_origen_presupuesto->material_curacion_disponible - $input['material_curacion'];

                    $unidad_medica_destino_presupuesto->material_curacion_modificado = $unidad_medica_destino_presupuesto->material_curacion_modificado + $input['material_curacion'];
                    $unidad_medica_destino_presupuesto->material_curacion_disponible = $unidad_medica_destino_presupuesto->material_curacion_disponible + $input['material_curacion'];
                } else {
                    $input['material_curacion'] = 0.0;
                }
            } else {
                $input['material_curacion'] = 0.0;
            }
            */

            //Crear Hash de validación
            $secret = env('SECRET_KEY') . 'HASH-' . $unidad_medica_origen_presupuesto->clues . $unidad_medica_origen_presupuesto->mes . $unidad_medica_origen_presupuesto->anio . $unidad_medica_origen_presupuesto->insumos_modificado . $unidad_medica_origen_presupuesto->no_causes_modificado . '-HASH';
            $cadena_validacion = Hash::make($secret);
            $unidad_medica_origen_presupuesto->validation = $cadena_validacion;

            $secret = env('SECRET_KEY') . 'HASH-' . $unidad_medica_destino_presupuesto->clues . $unidad_medica_destino_presupuesto->mes . $unidad_medica_destino_presupuesto->anio . $unidad_medica_destino_presupuesto->insumos_modificado . $unidad_medica_destino_presupuesto->no_causes_modificado . '-HASH';
            $cadena_validacion = Hash::make($secret);
            $unidad_medica_destino_presupuesto->validation = $cadena_validacion;

            $unidad_medica_origen_presupuesto->save();
            $unidad_medica_destino_presupuesto->save();
            $input['presupuesto_id'] = $unidad_medica_origen_presupuesto->presupuesto_id;
            $transferencia = TransferenciaPresupuesto::create($input);

           
            DB::commit();
            return Response::json([ 'data' => $transferencia ],200);
           

        } catch (\Exception $e) {
             DB::rollBack();
            return Response::json(['error' => $e->getMessage()], 500);
        } 
    }

    /**
     * Transfiere recursos de una clus origen a una destino.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function transferirSaldosAlMesActual(Request $request)
    {
        
        $mensajes = [
            
            'required'      => "required",
            'array'       => "array"
        ];

        $reglas = [
            'mes'      => 'required',
            'anio'     => 'required',
            'lista_clues'        => 'array|required'
        ];

        $input = Input::only('mes','anio','lista_clues');

        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }
        DB::beginTransaction();
        try {

            $presupuesto_activo = Presupuesto::where('activo',1)->first();
            if(!$presupuesto_activo){              
                throw new Exception("No hay presupuesto activo.");
            }

            $unidades_medicas_presupuesto = UnidadMedicaPresupuesto::whereIn('clues',$input['lista_clues'])
                                                ->where('mes',$input['mes'])
                                                ->where('anio',$input['anio'])->get();
            
            $anio = date('Y');
            $mes = date('n');

            foreach($unidades_medicas_presupuesto as $unidad_medica_presupuesto_pasado){
                $unidad_medica_presupuesto_actual = UnidadMedicaPresupuesto::where('mes',$mes)->where('anio',$anio)->where('clues',$unidad_medica_presupuesto_pasado->clues)->first();
                
                if($unidad_medica_presupuesto_actual){


                    TransferenciaPresupuesto::create([
                        'clues_origen' => $unidad_medica_presupuesto_pasado->clues,
                        'clues_destino' => $unidad_medica_presupuesto_pasado->clues,
                        'mes_origen' => $input['mes'],
                        'anio_origen' => $input['anio'],
                        'mes_destino' => $mes,
                        'anio_destino' => $anio,
                        'causes' => $unidad_medica_presupuesto_pasado->causes_disponible,
                        'no_causes' => $unidad_medica_presupuesto_pasado->no_causes_disponible,
                        'material_curacion' => $unidad_medica_presupuesto_pasado->material_curacion_disponible,
                        'presupuesto_id' => $presupuesto_activo->id
                    ]);


                    /*
                    $unidad_medica_presupuesto_actual->causes_modificado = $unidad_medica_presupuesto_actual->causes_modificado +  $unidad_medica_presupuesto_pasado->causes_disponible;
                    $unidad_medica_presupuesto_actual->causes_disponible = $unidad_medica_presupuesto_actual->causes_disponible +  $unidad_medica_presupuesto_pasado->causes_disponible;
                    $unidad_medica_presupuesto_pasado->causes_modificado = $unidad_medica_presupuesto_pasado->causes_modificado - $unidad_medica_presupuesto_pasado->causes_disponible;
                    $unidad_medica_presupuesto_pasado->causes_disponible = 0;

                    $unidad_medica_presupuesto_actual->material_curacion_modificado = $unidad_medica_presupuesto_actual->material_curacion_modificado +  $unidad_medica_presupuesto_pasado->material_curacion_disponible;
                    $unidad_medica_presupuesto_actual->material_curacion_disponible = $unidad_medica_presupuesto_actual->material_curacion_disponible +  $unidad_medica_presupuesto_pasado->material_curacion_disponible;
                    $unidad_medica_presupuesto_pasado->material_curacion_modificado = $unidad_medica_presupuesto_pasado->material_curacion_modificado - $unidad_medica_presupuesto_pasado->material_curacion_disponible;
                    $unidad_medica_presupuesto_pasado->material_curacion_disponible = 0;
                    */
                    $unidad_medica_presupuesto_actual->insumos_modificado = $unidad_medica_presupuesto_actual->insumos_modificado +  $unidad_medica_presupuesto_pasado->insumos_disponible;
                    $unidad_medica_presupuesto_actual->insumos_disponible = $unidad_medica_presupuesto_actual->insumos_disponible +  $unidad_medica_presupuesto_pasado->insumos_disponible;
                    $unidad_medica_presupuesto_pasado->insumos_modificado = $unidad_medica_presupuesto_pasado->insumos_modificado - $unidad_medica_presupuesto_pasado->insumos_disponible;
                    $unidad_medica_presupuesto_pasado->insumos_disponible = 0;

                    $unidad_medica_presupuesto_actual->no_causes_modificado = $unidad_medica_presupuesto_actual->no_causes_modificado +  $unidad_medica_presupuesto_pasado->no_causes_disponible;
                    $unidad_medica_presupuesto_actual->no_causes_disponible = $unidad_medica_presupuesto_actual->no_causes_disponible +  $unidad_medica_presupuesto_pasado->no_causes_disponible;
                    $unidad_medica_presupuesto_pasado->no_causes_modificado = $unidad_medica_presupuesto_pasado->no_causes_modificado - $unidad_medica_presupuesto_pasado->no_causes_disponible;
                    $unidad_medica_presupuesto_pasado->no_causes_disponible = 0;

                    //Crear Hash de validación
                    $secret = env('SECRET_KEY') . 'HASH-' . $unidad_medica_presupuesto_pasado->clues . $unidad_medica_presupuesto_pasado->mes . $unidad_medica_presupuesto_pasado->anio . $unidad_medica_presupuesto_pasado->insumos_modificado . $unidad_medica_presupuesto_pasado->no_causes_modificado . '-HASH';
                    $cadena_validacion = Hash::make($secret);
                    $unidad_medica_presupuesto_pasado->validation = $cadena_validacion;

                    //Crear Hash de validación
                    $secret = env('SECRET_KEY') . 'HASH-' . $unidad_medica_presupuesto_actual->clues . $unidad_medica_presupuesto_actual->mes . $unidad_medica_presupuesto_actual->anio . $unidad_medica_presupuesto_actual->insumos_modificado . $unidad_medica_presupuesto_actual->no_causes_modificado . '-HASH';
                    $cadena_validacion = Hash::make($secret);
                    $unidad_medica_presupuesto_actual->validation = $cadena_validacion;

                    $unidad_medica_presupuesto_pasado->save();
                    $unidad_medica_presupuesto_actual->save();
                }
                
            }
           
            DB::commit();
            return Response::json([ 'data' => "Transacción realizada" ],200);
           

        } catch (\Exception $e) {
             DB::rollBack();
            return Response::json(['error' => $e->getMessage()], 500);
        } 
    }

}
