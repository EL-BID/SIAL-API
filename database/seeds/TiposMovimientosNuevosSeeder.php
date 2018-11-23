<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TiposMovimientosNuevosSeeder extends Seeder
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
                'id' => 9,
                'tipo' => "E",
                'nombre' => "Recepción Pedido entre Almacenes",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
            ,
            [
                'id' => 11,
                'tipo' => "E",
                'nombre' => "Entrada Almacén Gral.",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 12,
                'tipo' => "S",
                'nombre' => "Salida Almacén Gral.",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 13,
                'tipo' => "E",
                'nombre' => "Entrada a Laboratorio Clinico",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 14,
                'tipo' => "S",
                'nombre' => "Salida de Laboratorio Clinico",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 15,
                'tipo' => "S",
                'nombre' => "Surtimiento de Recetas Proveedor",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 16,
                'tipo' => "S",
                'nombre' => "Surtimiento de Colectivos Proveedor",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);

    }
}
