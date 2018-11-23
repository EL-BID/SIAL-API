<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAnioAPedidoPresupuestoApartadoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_presupuesto_apartado', function (Blueprint $table) {
            $table->integer('anio')->nullable()->after('mes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_presupuesto_apartado', function (Blueprint $table) {
            $table->dropColumn('anio');
        });
    }
}
