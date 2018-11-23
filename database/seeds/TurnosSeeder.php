<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TurnosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('turnos')->insert([
            [
                'id' => 1,
                'nombre' => "MATUTINO",
                'descripcion' => "",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'nombre' => "VESPERTINO",
                'descripcion' => "",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 3,
                'nombre' => "NOCTURNO A",
                'descripcion' => "LUNES, MIERCOLES Y VIERNES",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 4,
                'nombre' => "NOCTURNO B",
                'descripcion' => "MARTES, JUEVES Y SABADO",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 5,
                'nombre' => "ESPECIAL A",
                'descripcion' => "DOMINGO Y 2 DIAS ENTRE SEMANA",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 6,
                'nombre' => "ESPECIAL B",
                'descripcion' => "NOCTURNOS FESTIVOS",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 7,
                'nombre' => "FIN DE SEMANA",
                'descripcion' => "SABADO DOMINGO Y DIAS FESTIVOS",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
            
        ]);
    }
}
