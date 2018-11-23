<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TiposRecetasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tipos_recetas')->insert([
            [
                'id' => 1,
                'nombre' => "NORMAL",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'nombre' => "CONTROLADA",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
        ]);
    }
}
