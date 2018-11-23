<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNegacionesInsumosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('negaciones_insumos', function (Blueprint $table) {
            $table->datetime('ultima_entrada')->nullable()->change();
            $table->integer('cantidad_acumulada_unidosis')->after('cantidad_acumulada');
            $table->integer('cantidad_entrada_unidosis')->after('cantidad_entrada');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('negaciones_insumos', function (Blueprint $table) {
            $table->date('ultima_entrada')->change();
            $table->dropColumn('cantidad_acumulada_unidosis');
            $table->dropColumn('cantidad_entrada_unidosis');
        });
    }
}
