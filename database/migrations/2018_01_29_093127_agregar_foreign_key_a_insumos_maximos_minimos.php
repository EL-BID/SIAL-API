<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarForeignKeyAInsumosMaximosMinimos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insumos_maximos_minimos', function (Blueprint $table) {            
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
        Schema::table('insumos_maximos_minimos', function (Blueprint $table) {
            $table->dropForeign(['insumo_medico_clave']);
        });
    }
}
