<?php

use Illuminate\Database\Seeder;

class MotivoEgresoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('catalogo_motivo_egreso')->insert([
            [
                'id' => 1,
                'descripcion' => 'MEJORÍA'
            ],
            [
                'id' => 2,
                'descripcion' => 'DEFUNCIÓN'
            ],
            [
                'id' => 3,
                'descripcion' => 'MAXIMO BENEFICIO'
            ]

        ]);	
    }
}
