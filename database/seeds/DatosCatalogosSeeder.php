<?php

use Illuminate\Database\Seeder;
use Illuminate\Http\Response as HttpResponse;

class DatosCatalogosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        $lista_csv = [
            'catalogo_area_responsable',
            'catalogo_estado_triage',
            'catalogo_grado_lesion',
            'catalogo_localidades',
            'catalogo_motivo_egreso',
            'catalogo_municipios',
            'tipos_insumos',
            'tipos_movimientos',
            'tipos_pedidos',
            'tipos_personal',
            //'tipos_personal_metadatos', #vacio
            'tipos_recetas',
            'tipos_sustancias', #no esta en producion
            //'tipos_unidad', # actualizar #no esta en migrations
            'unidades_medida',
            'vias_administracion',
            //'medios_contacto',  #vacio
            'jurisdicciones',
            //'marcas',  #vacio
            'turnos',
            //'especialidades',  #vacio
            //'factores_riesgo_embarazo',  #vacio
            'formas_farmaceuticas',
            'servicios',
            'genericos',
            'grupos_insumos',
            'generico_grupo_insumo', #actualizar
            'presupuestos',
            'programas',
            'proveedores',
            'presentaciones_medicamentos',
            //'presentaciones_sustancias',  #vacio
            //'categorias', #no esta en produccion
            //'categorias_metadatos', #no esta en produccion
            //'organismos',  #vacio
            'unidades_medicas',
            //'areas', #actualizar #no esta en migrations
            //'articulos', #no esta en produccion
            'insumos_medicos',
            'material_curacion',
            'medicamentos',
            //'informacion_importante_medicamentos',  #vacio
            //'insumo_medico_especialidad',  #vacio
            //'insumo_medico_servicio',  #vacio
            //'listas_insumos', #vacio
            //'lista_insumo_detalle',  #vacio
            //'auxiliares_diagnostico', #vacio
            //'auxiliares_diagnostico_detalles_equipos',  #vacio
            'claves_basicas',
            'claves_basicas_detalles',
            //'claves_basicas_unidades_medicas',  #vacio
            //'comunicacion_contactos',  #vacio
            'contratos',
            'contrato_clues',
            //'contrato_proveedor', #actualizar
            //'contratos_pedidos',  #vacio
            'contratos_precios',
            //'empresas_espejos', #vacio
            //'extensiones_contratos',  #vacio
            'roles',
            'permisos',
            'permiso_rol', #actualziar
            //'sustancias_laboratorio',  #vacio
            'unidad_medica_abasto_configuracion',
            'unidad_medica_presupuesto',
            'precios_base',
            'precios_base_detalles'
        ];

        foreach($lista_csv as $csv){
            try{
                DB::beginTransaction();

                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                $archivo_csv = storage_path().'/app/seeds/'.$csv.'.csv';

                $query = sprintf("
                    LOAD DATA local INFILE '%s' 
                    REPLACE 
                    INTO TABLE $csv 
                    CHARACTER SET utf8
                    FIELDS TERMINATED BY ',' 
                    OPTIONALLY ENCLOSED BY '\"' 
                    ESCAPED BY '\"' 
                    LINES TERMINATED BY '\\n' 
                    IGNORE 1 LINES", addslashes($archivo_csv));
                DB::connection()->getpdo()->exec($query);

                DB::statement('SET FOREIGN_KEY_CHECKS=1');

                DB::commit();
            } catch (\Illuminate\Database\QueryException $e){
                DB::rollback();
                return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
            } catch(\Exception $e ){
                return \Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
            }
        }
    }
}
