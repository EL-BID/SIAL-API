<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PermisosPacientesSeeder extends Seeder
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
                'id' => 'CKXlt7sNZCiWBhvk33xqWEVTnv2lP022',
                'descripcion' => 'Egreso Pacientes',
                'grupo' => 'Admision',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	    [
                'id' => 'PpXKhxdG8dGheNKm1rRSCT4EXZYyhRMm',
                'descripcion' => 'Ver modulo Admision',
                'grupo' => 'Admision',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	    [
                'id' => 'XE1pZJV6ZprLc0vP45Pkkpm2UlOJEBqV',
                'descripcion' => 'Ver lista Pacientes',
                'grupo' => 'Admision',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
 	    [
                'id' => 'eS7qbng49qtMSs88wsflDnzMBVUfVMlc',
                'descripcion' => 'Ingresar Pacientes',
                'grupo' => 'Admision',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]

        ]);
    }
}
