<?php

use Illuminate\Database\Seeder;

class InsumosMedicosSustanciasLaboratorioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $archivo_csv = storage_path().'/app/seeds/unidades-medida-nuevas.csv';
        $query = sprintf("
            LOAD DATA local INFILE '%s' 
            INTO TABLE unidades_medida
            FIELDS TERMINATED BY ',' 
            OPTIONALLY ENCLOSED BY '\"' 
            ESCAPED BY '\"' 
            LINES TERMINATED BY '\\n'", addslashes($archivo_csv));
        DB::connection()->getpdo()->exec($query); 

        $archivo_csv = storage_path().'/app/seeds/tipos-sustancias.csv';
        $query = sprintf("
            LOAD DATA local INFILE '%s' 
            INTO TABLE tipos_sustancias
            FIELDS TERMINATED BY ',' 
            OPTIONALLY ENCLOSED BY '\"' 
            ESCAPED BY '\"' 
            LINES TERMINATED BY '\\n'", addslashes($archivo_csv));
        DB::connection()->getpdo()->exec($query);

        $archivo_csv = storage_path().'/app/seeds/presentaciones-sustancias.csv';
        $query = sprintf("
            LOAD DATA local INFILE '%s' 
            INTO TABLE presentaciones_sustancias
            FIELDS TERMINATED BY ',' 
            OPTIONALLY ENCLOSED BY '\"' 
            ESCAPED BY '\"' 
            LINES TERMINATED BY '\\n'", addslashes($archivo_csv));
        DB::connection()->getpdo()->exec($query);

        $archivo_csv = storage_path().'/app/seeds/insumos-medicos-sustancias-laboratorio-clinico.csv';
        $query = sprintf("
            LOAD DATA local INFILE '%s' 
            INTO TABLE insumos_medicos
            FIELDS TERMINATED BY ',' 
            OPTIONALLY ENCLOSED BY '\"' 
            ESCAPED BY '\"' 
            LINES TERMINATED BY '\\n'", addslashes($archivo_csv));
        DB::connection()->getpdo()->exec($query);

        $archivo_csv = storage_path().'/app/seeds/sustancias-laboratorio.csv';
        $query = sprintf("
            LOAD DATA local INFILE '%s' 
            INTO TABLE sustancias_laboratorio
            FIELDS TERMINATED BY ',' 
            OPTIONALLY ENCLOSED BY '\"' 
            ESCAPED BY '\"' 
            LINES TERMINATED BY '\\n'", addslashes($archivo_csv));
        DB::connection()->getpdo()->exec($query);

         

    }
}
