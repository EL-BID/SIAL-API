<?php

use Illuminate\Database\Seeder;

class ServidoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('servidores')->insert([
            [
                'id' => env('SERVIDOR_ID'),
                'nombre' => 'Servidor Local 1',
                'secret_key' => env('SECRET_KEY'),
                'principal' => false
            ]
        ]);
    }
}
