<?php

use Illuminate\Database\Seeder;

class CatalogoInsumosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
         $lista_csv = [
            'vias-administracion'           =>'vias_administracion',
            'unidades-medida'               =>'unidades_medida',
            'presentaciones-medicamentos'   =>'presentaciones_medicamentos',
            'grupos-insumos'                =>'grupos_insumos',
            'genericos'                     =>'genericos',
            'generico-grupo-insumo'         =>'generico_grupo_insumo',
            'insumos-medicos'               =>'insumos_medicos',
            'medicamentos'                  =>'medicamentos',
            'unidades-medicas'              =>'unidades_medicas',
        ];

        foreach($lista_csv as $csv => $tabla){
            $archivo_csv = storage_path().'/app/seeds/'.$csv.'.csv';
            $query = sprintf("
                LOAD DATA local INFILE '%s' 
                INTO TABLE $tabla 
                FIELDS TERMINATED BY ',' 
                OPTIONALLY ENCLOSED BY '\"' 
                ESCAPED BY '\"' 
                LINES TERMINATED BY '\\n' 
                IGNORE 1 LINES", addslashes($archivo_csv));
            DB::connection()->getpdo()->exec($query);
        }
    }
}
