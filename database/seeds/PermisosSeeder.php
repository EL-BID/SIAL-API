<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PermisosSeeder extends Seeder
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
                'id' => '2EA8UKzKrNFzxQxBBSjQ2fHggyrScu9f',
                'descripcion' => 'Sincronización manual',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '2nC6GUf6E737QwZSxuLORT6rZUDy5YUO',
                'descripcion' => 'Agregar pedidos',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => '9Z9XwxmNbrISFyF5sJkU3HyGUssSPfU5',
                'descripcion' => 'Editar roles',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'ay8Rv5iuSih9ZoHVSFwZ8Ic4memuYm4O',
                'descripcion' => 'Eliminar permisos	Super',
                'grupo' => 'Usuario',
                'su' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'cpxMSLOq5aI7owPAgtJzSIgts5AS73J4',
                'descripcion' => 'Eliminar salidas manuales',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'dlV1H4gX0nqEgHauHC8BRIlwl6SGUoUt',
                'descripcion' => 'Editar usuarios',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'dsvilCCjRtFJ9QXuunzbR1CYLKJQ5MYA',
                'descripcion' => 'Agregar permisos	Super',
                'grupo' => 'Usuario',
                'su' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'DYwQAxJbpHWw07zT09scEogUeFKFdGSu',
                'descripcion' => 'Ver permisos	Super',
                'grupo' => 'Usuario',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'hA2wLCnNDQ5Z1OtvdW8lgX5D6wkM6zBE',
                'descripcion' => 'Agregar roles',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'hAeTBeuyxHcAi2OerU7NsVpTA5isktJ7',
                'descripcion' => 'Ver entradas manuales',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'HMjOPUqMyhrFCn5YtiOnxrI7IWO9itTD',
                'descripcion' => 'Editar entradas manuales',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'hytR4HSKgea9JJ1HEfjpfjijQ8UAtyQn',
                'descripcion' => 'Editar pedidos',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'ICmOKw3HxhgRna4a78OP0QmKrIX0bNsp',
                'descripcion' => 'Ver roles',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'iSxK0TpoYpnzf8KIQTWOq9Web7WnSKhz',
                'descripcion' => 'Ver entregas',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'jayjoQxeKJbZ2nTtlwEDDKH1VAZyXcGW',
                'descripcion' => 'Agregar entradas manuales',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'l4SBZlAj2SYrdFi747wo8yuvJ0sAE0U9',
                'descripcion' => 'Editar permisos	Super',
                'grupo' => 'Usuario',
                'su' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'mGKikN0aJaeF2XrHwwYK3XNw0f9CSZDe',
                'descripcion' => 'Ver usuarios',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'q9ppCvhWdeCJI85YtCrKvtHLaoPipeaT',
                'descripcion' => 'Recibir pedido',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'qkm5NZQYN4ePs43axd0MgERhx5ia8rwh',
                'descripcion' => 'Eliminar roles',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'st1d5Ij0UPcyewVXw4yWfUvCcKRJPSBf',
                'descripcion' => 'Editar entregas',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'U2f38x7uVNFMQMQFeUjrFOJelBt3tbCA',
                'descripcion' => 'Eliminar usuarios',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'uwYMBRsBHyq3iBtJUGrjCtGRTbK3efIc',
                'descripcion' => 'Eliminar entradas manuales',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'uZrbQ7smC8jpWIqAYq5cKwNHhyFDh2G7',
                'descripcion' => 'Eliminar entregas',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'xJqy3csU5WyOX7pmXL7VBs680uTVGxU3',
                'descripcion' => 'Agregar usuarios',
                'grupo' => 'Administrador',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'xNITkDTyysTNLusR5nVbgGCsNZceqnLo',
                'descripcion' => 'Agregar entregas',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'yfCW43bf3JCaE92d6HPxWljxo3SRlslP',
                'descripcion' => 'Editar salidas manuales',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'z9MQHY1YAIlYWsPLPF9OZYN94HKjOuDk',
                'descripcion' => 'Ver pedidos',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'zCO7XCCWisuNUoiwImd4MnDmp8I2CfTV',
                'descripcion' => 'Agregar salidas manuales',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'ZNrN0e8cQL8cIAcXHJfczGpFEC2Ap9QA',
                'descripcion' => 'Ver salidas manuales',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'Zz02R1xvYQBSBx35Bs45F7komHUnvBet',
                'descripcion' => 'Eliminar pedidos',
                'grupo' => 'Farmacia',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'bsIbPL3qv6XevcAyrRm1GxJufDbzLOax',
                'descripcion' => 'Ver pedidos hechos en la plataforma',
                'grupo' => 'Administrador central',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 'bwWWUufmEBRFpw9HbUJQUP8EFnagynQv',
                'descripcion' => 'Ver abasto de insumos por clues',
                'grupo' => 'Administrador central',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => 's8kSv2Gj9DZwRvClVRmZohp92Rtvi26i',
                'descripcion' => 'Realizar transferencia de recursos',
                'grupo' => 'Administrador central',
                'su' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);
    }
}
