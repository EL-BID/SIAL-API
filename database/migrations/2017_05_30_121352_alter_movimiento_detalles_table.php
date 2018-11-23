<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMovimientoDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimiento_detalles', function (Blueprint $table) {
            $table->string('modo_salida', 1)->after('clave_insumo_medico');
            $table->integer('cantidad_solicitada_unidosis')->after('cantidad_solicitada');
            $table->integer('cantidad_existente_unidosis')->after('cantidad_existente');
            $table->integer('cantidad_surtida_unidosis')->after('cantidad_surtida');
            $table->integer('cantidad_negada_unidosis')->after('cantidad_negada');
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
        Schema::table('movimiento_detalles', function (Blueprint $table) {
                $table->dropColumn('modo_salida');
                $table->dropColumn('cantidad_solicitada_unidosis');
                $table->dropColumn('cantidad_existente_unidosis');
                $table->dropColumn('cantidad_surtida_unidosis');
                $table->dropColumn('cantidad_negada_unidosis');
                $table->dropForeign(['clave_insumo_medico']);
        });
    }
}
