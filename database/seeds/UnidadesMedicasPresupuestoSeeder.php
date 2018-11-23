<?php

use Illuminate\Database\Seeder;

class UnidadesMedicasPresupuestoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        DB::table('presupuestos')->insert([
            [
                'id' => 1,
                'anio' => 2017,
                'causes' => 83791023.12,
                'no_causes' => 53153308.80,
                'material_curacion' => 80469852.57,
                'activo' => 1
            ]
        ]);
        
        $archivo_csv = storage_path().'/app/seeds/unidades-medicas-presupuesto.csv';
        $query = sprintf("
            LOAD DATA local INFILE '%s' 
            INTO TABLE unidad_medica_presupuesto 
            FIELDS TERMINATED BY ',' 
            OPTIONALLY ENCLOSED BY '\"' 
            ESCAPED BY '\"' 
            LINES TERMINATED BY '\\n' 
            IGNORE 1 LINES", addslashes($archivo_csv));
        DB::connection()->getpdo()->exec($query);
    }
}
