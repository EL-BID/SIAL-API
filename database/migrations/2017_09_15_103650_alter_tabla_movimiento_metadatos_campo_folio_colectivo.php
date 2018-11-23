<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaMovimientoMetadatosCampoFolioColectivo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimiento_metadatos', function (Blueprint $table)
        {             
            $table->string('folio_colectivo',45)->nullable()->after('movimiento_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('movimiento_metadatos', function (Blueprint $table)
        {
            $table->dropColumn('folio_colectivo');
        });
    }
}
