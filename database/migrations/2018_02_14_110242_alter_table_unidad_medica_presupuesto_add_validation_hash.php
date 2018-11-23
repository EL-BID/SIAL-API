<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableUnidadMedicaPresupuestoAddValidationHash extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unidad_medica_presupuesto', function (Blueprint $table) {
            $table->string('validation',255)->after('material_curacion_disponible')->nullable();
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
            $table->dropColumn('validation');
        });
    }
}
