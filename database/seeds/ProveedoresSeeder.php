<?php

use Illuminate\Database\Seeder;

class ProveedoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('proveedores')->insert([
            [
                'id' => 1,
                'nombre' =>  'Distribuidora Médica del Soconusco S.A. de C.V.',
                'rfc' => null,
                'direccion' => '4ª. Poniente Sur No. 360',
                'ciudad' => 'Tuxtla Gutiérrez, Chiapas',
                'contacto' => 'C.P. Francisco Javier Velasco Álvarez',
                'cargo_contacto' => 'Gerente General',
                'telefono' => '61-3-34-00',
                'celular' => '961-579-2940',
                'email' => 'javier.velasco@grupodms.com.mx',
                'activo' => 1,
                'usuario_id' => 'root'
            ],
            [
                'id' => 2,
                'nombre' =>  'FARMACIAS DE GENERICOS S.A. de C.V.',
                'rfc' => null,
                'direccion' => null,
                'ciudad' => null,
                'contacto' => null,
                'cargo_contacto' => null,
                'telefono' => null,
                'celular' => null,
                'email' => null,
                'activo' => 1,
                'usuario_id' => 'root'
            ],
            [
                'id' => 3,
                'nombre' =>  'EXFARMA S.A. DE C.V.',
                'rfc' => 'EXF071009BB4',
                'direccion' => 'Av. Constituyentes No. 1000 PB. Col. Lomas Altas Del. Miguel Hidalgo, C.P. 11950',
                'ciudad' => 'México, D. F.',
                'contacto' => null,
                'cargo_contacto' => null,
                'telefono' => '55702719',
                'celular' => null,
                'email' => null,
                'activo' => 1,
                'usuario_id' => 'root'
            ]
        ]);
    }
}
