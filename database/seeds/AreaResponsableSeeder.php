<?php

use Illuminate\Database\Seeder;

class AreaResponsableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('catalogo_area_responsable')->insert([
            [
                'id' => 1,
                'descripcion' => 'TRABAJO SOCIAL'
            ],
            [
                'id' => 2,
                'descripcion' => 'ASUNTOS JURIDICOS'
            ],
            [
                'id' => 3,
                'descripcion' => 'MINISTERIO PÚBLICO'
            ]

        ]);	
    }
}
