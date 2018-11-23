<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterInventarioAgregarPrimeraVezInventarioLoteCaducidadPatrimonio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventario', function (Blueprint $table) {

            $table->integer('programa_id')->unsigned()->nullable()->after('movimiento_articulo_id');
            $table->boolean('primera_vez_inventario')->after('programa_id');
            $table->string('lote',55)->nullable()->after('primera_vez_inventario');
            $table->date('fecha_caducidad')->nullable()->after('lote');
            $table->boolean('es_patrimonio')->after('fecha_caducidad');

            $table->foreign('programa_id')->references('id')->on('programas');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
