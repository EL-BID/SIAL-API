<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableUnidadMedicaPresupuestoJoinCausesMateriaCuracionFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unidad_medica_presupuesto', function (Blueprint $table) {
            $table->decimal('insumos_autorizado',15,2)->default(0)->after('causes_disponible');
            $table->decimal('insumos_modificado',15,2)->default(0)->after('insumos_autorizado');
            $table->decimal('insumos_comprometido',15,2)->default(0)->after('insumos_modificado');
            $table->decimal('insumos_devengado',15,2)->default(0)->after('insumos_comprometido');
            $table->decimal('insumos_disponible',15,2)->default(0)->after('insumos_devengado');
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
            $table->dropColumn('insumos_autorizado');
            $table->dropColumn('insumos_modificado');
            $table->dropColumn('insumos_comprometido');
            $table->dropColumn('insumos_devengado');
            $table->dropColumn('insumos_disponible');
        });
    }
}
