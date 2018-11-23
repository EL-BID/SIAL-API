<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPivotInventarioMovimientoArticulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_movimiento_articulos', function (Blueprint $table) {
            
            $table->string('movimiento_articulos_id',255);
            $table->string('inventario_id',255);

            $table->foreign('movimiento_articulos_id')->references('id')->on('movimiento_articulos');
            $table->foreign('inventario_id')->references('id')->on('inventario');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventario_movimiento_articulos');
    }
}
