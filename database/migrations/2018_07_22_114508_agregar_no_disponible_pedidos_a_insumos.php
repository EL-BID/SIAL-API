<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarNoDisponiblePedidosAInsumos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insumos_medicos', function (Blueprint $table) {
            $table->boolean('no_disponible_pedidos')->default(false)->nullable()->after('tiene_fecha_caducidad');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insumos_medicos', function (Blueprint $table) {
            $table->dropColumn('no_disponible_pedidos');
        });
    }
}
