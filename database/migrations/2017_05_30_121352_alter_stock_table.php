<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock', function (Blueprint $table) {
                $table->integer('unidosis_sueltas')->after('existencia_unidosis');
                $table->integer('envases_parciales')->after('unidosis_sueltas');
                $table->foreign('clave_insumo_medico')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock', function (Blueprint $table) {
                $table->dropColumn('unidosis_sueltas');
                $table->dropColumn('envases_parciales');
                $table->dropForeign(['clave_insumo_medico']);
        });
    }
}
