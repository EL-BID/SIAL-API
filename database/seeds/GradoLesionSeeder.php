<?php

use Illuminate\Database\Seeder;

class GradoLesionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         DB::table('catalogo_grado_lesion')->insert([
            [
                'id' => 1,
                'descripcion' => 'LEVES'
            ],
            [
                'id' => 2,
                'descripcion' => 'GRAVES'
            ],
            [
                'id' => 3,
                'descripcion' => 'PONEN EN RIESGO LA VIDA'
            ],
            [
                'id' => 4,
                'descripcion' => 'MARCAS VISIBLES EN ROSTRO'
            ],
            [
                'id' => 5,
                'descripcion' => 'INCAPACIDAD'
            ]

        ]);	
    }
}
