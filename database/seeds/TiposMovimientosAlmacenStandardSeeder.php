<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TiposMovimientosAlmacenStandardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        DB::table('tipos_movimientos')->insert([
            [
                'id' => 17,
                'tipo' => "E",
                'nombre' => "Entrada a Almacén Modelo Standard.",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
            ,
            [
                'id' => 18,
                'tipo' => "S",
                'nombre' => "Salida de Almacén Modelo Standard.",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
            ,
            [
                'id' => 19,
                'tipo' => "E",
                'nombre' => "Inicialización Inventario Medicamentos.",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);

    }
}
