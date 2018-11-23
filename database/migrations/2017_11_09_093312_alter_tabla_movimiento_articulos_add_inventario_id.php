<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaMovimientoArticulosAddInventarioId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimiento_articulos', function (Blueprint $table) {

            $table->string('inventario_id')->nullable()->after('articulo_id');
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
        Schema::table('movimiento_articulos', function (Blueprint $table) {

            $table->dropForeign(['inventario_id']);
            $table->dropColumn('inventario_id');
            
         });
    }
}
