<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaMovimientoAjustesCampoLoteNuevo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimiento_ajustes', function (Blueprint $table)
        {             
            $table->integer('lote_nuevo')->after('movimiento_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimiento_ajustes', function (Blueprint $table)
        {
            $table->dropColumn('lote_nuevo');
        });
    }
}
