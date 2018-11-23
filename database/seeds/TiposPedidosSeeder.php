<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TiposPedidosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tipos_pedidos')->insert([
            [
                'id' => 'PA',
                'nombre' => "Pedido de Abastecimiento",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 'PI',
                'nombre' => "Pedido Interno",             
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 'PD',
                'tipo' => "Pedido por Desabasto",           
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 'PFS',
                'tipo' => "Pedido a Farmacia Subrogada",           
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
