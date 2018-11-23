<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaMovimientoEntradaMetadatosAgregarCampoFirmantes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimiento_entrada_metadatos_ag', function (Blueprint $table)
        {  
            $table->text('firmantes')->nullable()->after('persona_recibe');   
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimiento_entrada_metadatos_ag', function (Blueprint $table) {
            $table->dropColumn('firmantes');
        });
    }
}
