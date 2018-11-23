<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaPersonalCluesMetadatosCampoMetadatosId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personal_clues_metadatos', function (Blueprint $table)
        {             
             $table->integer('metadatos_id')->unsigned()->after('personal_clues_id');
             $table->foreign('metadatos_id')->references('id')->on('tipos_personal_metadatos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_clues_metadatos', function (Blueprint $table)
        {
            $table->dropForeign('personal_clues_metadatos_metadatos_id_foreign');
            $table->dropColumn('metadatos_id');
        });
    }
}
