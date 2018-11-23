<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPacientesCamposConvertirCamposNullables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->time('hora_nacimiento')->nullable()->change();
            $table->string('domicilio',255)->nullable()->change();
            $table->string('colonia',255)->nullable()->change();
            $table->integer('municipio_id')->unsigned()->nullable()->change();
            $table->integer('localidad_id')->unsigned()->nullable()->change();
            $table->string('telefono',255)->nullable()->change();
            $table->string('no_expediente',255)->nullable()->change();
            $table->string('no_afiliacion',255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pacientes', function (Blueprint $table) {
            //
        });
    }
}
