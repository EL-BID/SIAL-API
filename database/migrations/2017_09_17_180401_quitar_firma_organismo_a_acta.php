<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QuitarFirmaOrganismoAActa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->dropForeign(['firma_organismo_id']);
            $table->dropColumn('firma_organismo_id');   
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->string('firma_organismo_id', 255);
            $table->foreign('firma_organismo_id')->references('id')->on('firmas_organismos');
        });
    }
}
