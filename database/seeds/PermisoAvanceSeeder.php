<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PermisoAvanceSeeder extends Seeder
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
                'id' => 'WbBYhMFZkGsAYeN13hY1hylZkNPJbHOE',
                'descripcion' => 'Modulo de Avances',
                'grupo' => 'Avances',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	    [
                'id' => '79B3qKuUbuEiR2qKS0CFgHy2zRWfmO4r',
                'descripcion' => 'Usuario General',
                'grupo' => 'Avances',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	    [
                'id' => 'f0CT1EvFcj4hqK1rNEpsSXhAlhdE9duM',
                'descripcion' => 'Usuario Normal',
                'grupo' => 'Avances',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]]);
    }
}
