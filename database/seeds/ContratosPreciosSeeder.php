<?php

use Illuminate\Database\Seeder;

class ContratosPreciosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        DB::table('contratos')->insert([
            [
                'id' => 1,
                'proveedor_id' => 1,
                'monto_minimo' => 217414184.49,
                'monto_maximo' => 217414184.49,
                'fecha_inicio' => '2017-04-01',
                'fecha_fin' => '2017-12-31',
                'activo' => 1,
                'usuario_id' => 'root'
            ],
            [
                'id' => 2,
                'proveedor_id' => 2,
                'monto_minimo' => 217414184.49,
                'monto_maximo' => 217414184.49,
                'fecha_inicio' => '2017-04-01',
                'fecha_fin' => '2017-12-31',
                'activo' => 1,
                'usuario_id' => 'root'
            ],
            [
                'id' => 3,
                'proveedor_id' => 3,
                'monto_minimo' => 217414184.49,
                'monto_maximo' => 217414184.49,
                'fecha_inicio' => '2017-04-01',
                'fecha_fin' => '2017-12-31',
                'activo' => 1,
                'usuario_id' => 'root'
            ]
        ]);

        $archivo_csv = storage_path().'/app/seeds/contratos-precios.csv';
        $query = sprintf("
            LOAD DATA local INFILE '%s' 
            INTO TABLE contratos_precios 
            FIELDS TERMINATED BY ',' 
            OPTIONALLY ENCLOSED BY '\"' 
            ESCAPED BY '\"' 
            LINES TERMINATED BY '\\n' 
            IGNORE 1 LINES", addslashes($archivo_csv));
        DB::connection()->getpdo()->exec($query);
    }
}
