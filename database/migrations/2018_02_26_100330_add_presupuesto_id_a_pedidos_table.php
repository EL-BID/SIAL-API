<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPresupuestoIdAPedidosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->integer('presupuesto_id')->unsigned()->nullable()->after('proveedor_id');
            $table->foreign('presupuesto_id')->references('id')->on('presupuestos');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['presupuesto_id']);
            $table->dropColumn('presupuesto_id');
        });
    }
}
