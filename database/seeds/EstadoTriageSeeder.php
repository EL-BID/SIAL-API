<?php

use Illuminate\Database\Seeder;

class EstadoTriageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('catalogo_estado_triage')->insert([
            [
                'id' => 1,
                'descripcion' => 'OBSERVACION (1ER. CONTACTO)'
            ],
            [
                'id' => 2,
                'descripcion' => 'SALA DE LABOR'
            ],
            [
                'id' => 3,
                'descripcion' => 'SALA DE SOCK'
            ],
            [
                'id' => 4,
                'descripcion' => 'UNIDAD DE TERAPIA INTENSIVA OBSTÉTRICA'
            ],
            [
                'id' => 5,
                'descripcion' => 'QUIRÓFANO'
            ],
            [
                'id' => 6,
                'descripcion' => 'HOSPITALIZACIÓN'
            ],

        ]);	
    }
}
