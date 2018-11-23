<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInsumosATransferenciasPresupuestoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transferencias_presupuesto', function (Blueprint $table) {
            $table->decimal('insumos',15,2)->default(0)->after('anio_origen');
            $table->decimal('causes',15,2)->nullable()->change();
            $table->decimal('material_curacion',15,2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transferencias_presupuesto', function (Blueprint $table) {
            $table->dropColumn('insumos');
            $table->decimal('causes',15,2)->nullable(false)->change();
            $table->decimal('material_curacion',15,2)->nullable(false)->change();
        });
    }
}
