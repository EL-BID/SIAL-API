<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidosInsumosCambiarNombreDeCamposParaPedidosEntreAlmacen extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedidos_insumos', function (Blueprint $table) {
            $table->renameColumn('cantidad', 'cantidad_enviada');
            $table->renameColumn('monto', 'monto_enviado');
            $table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
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
            $table->renameColumn('cantidad_enviada', 'cantidad');
            $table->renameColumn('monto_enviado', 'monto');
            $table->dropForeing(['insumo_medico_clave']);
        });
    }
}
