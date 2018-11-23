<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarAlmacenIdAUnidadMedicaPresupuesto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unidad_medica_presupuesto', function (Blueprint $table) {
            $table->string('almacen_id')->nullable()->after('clues');
            $table->foreign('almacen_id')->references('id')->on('almacenes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unidad_medica_presupuesto', function (Blueprint $table) {
            $table->dropForeing(['almacen_id']);
            $table->dropColumn('almacen_id');
        });
    }
}
