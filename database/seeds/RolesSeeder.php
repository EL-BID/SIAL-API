<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            [
                'id' => '1',
                'nombre' => 'ADMIN',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '2',
                'nombre' => 'ALMACEN',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);

        DB::table('permiso_rol')->insert([
            [
                'permiso_id' => 'U2f38x7uVNFMQMQFeUjrFOJelBt3tbCA',
                'rol_id' => 1
            ],
            [
                'permiso_id' => 'dlV1H4gX0nqEgHauHC8BRIlwl6SGUoUt',
                'rol_id' => 1
            ],
            [
                'permiso_id' => 'mGKikN0aJaeF2XrHwwYK3XNw0f9CSZDe',
                'rol_id' => 1
            ],
            [
                'permiso_id' => 'xJqy3csU5WyOX7pmXL7VBs680uTVGxU3',
                'rol_id' => 1
            ],
            [
                'permiso_id' => '2nC6GUf6E737QwZSxuLORT6rZUDy5YUO',
                'rol_id' => 2
            ],
            [
                'permiso_id' => 'Zz02R1xvYQBSBx35Bs45F7komHUnvBet',
                'rol_id' => 2
            ],
            [
                'permiso_id' => 'hytR4HSKgea9JJ1HEfjpfjijQ8UAtyQn',
                'rol_id' => 2
            ],
            [
                'permiso_id' => 'q9ppCvhWdeCJI85YtCrKvtHLaoPipeaT',
                'rol_id' => 2
            ],
            [
                'permiso_id' => 'z9MQHY1YAIlYWsPLPF9OZYN94HKjOuDk',
                'rol_id' => 2
            ]
        ]);
    }
}