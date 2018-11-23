<?php

use Illuminate\Database\Seeder;

class CatalogosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('catalogos')->insert([
            [
                'id' => 'permisos',
                'fecha_actualizacion' => null
            ],
            [
                'id' => 'roles',
                'fecha_actualizacion' => null
            ]
        ]);
    }
}
