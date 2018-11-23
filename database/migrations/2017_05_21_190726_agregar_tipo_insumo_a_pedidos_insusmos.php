<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarTipoInsumoAPedidosInsusmos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedidos_insumos', function (Blueprint $table) {
            $table->integer('tipo_insumo_id')->unsigned()->nullable()->after('pedido_id');
            $table->foreign('tipo_insumo_id')->references('id')->on('tipos_insumos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedidos_insumos', function (Blueprint $table) {
            $table->dropForeing(['tipo_insumo_id']);
            $table->dropColumn('tipo_insumo_id');
        });
    }
}
