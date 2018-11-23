<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarDescontinuadoAInsumosMedicos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insumos_medicos', function (Blueprint $table) {
            $table->integer('descontinuado')->default(0)->after('tiene_fecha_caducidad');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insumos_medicos', function (Blueprint $table) {
            $table->dropColumn('descontinuado');
        });
    }
}
