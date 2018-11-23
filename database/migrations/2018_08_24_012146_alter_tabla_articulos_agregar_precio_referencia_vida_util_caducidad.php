<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaArticulosAgregarPrecioReferenciaVidaUtilCaducidad extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->decimal('vida_util',16,2)->nullable()->after('es_activo_fijo');
            $table->decimal('precio_referencia',16,2)->after('vida_util');
            $table->boolean('tiene_caducidad')->after('precio_referencia');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
