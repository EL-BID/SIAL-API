<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PermisoRecetaElectronicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permisos')->insert([
            [
                'id' => '7pFIrhuM3trSzo9nnIgeU7cMUArsukS8',
                'descripcion' => 'Dispensar Recetas Electronicas',
                'grupo' => 'receta-electronica',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	    [
                'id' => 'qbmHSezvoY8IROFk3CJ7XBuLzp9rRoo6',
                'descripcion' => 'Ver Recetas Electronicas',
                'grupo' => 'receta-electronica',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	    [
                'id' => 'rEAgr2wrYx2AKLhIS7uLh7QIPJkmv4Jo',
                'descripcion' => 'Crear Recetas Electronicas',
                'grupo' => 'receta-electronica',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);
    }
}
