<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPaciente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('nombre');
            $table->integer('sexo');
            $table->date('fecha_nacimiento');
            $table->time('hora_nacimiento');

            $table->string('domicilio');
            $table->string('colonia');
            $table->integer('municipio_id')->unsigned();
            $table->integer('localidad_id')->unsigned();
            $table->string('telefono');
            $table->string('no_expediente');
            $table->string('no_afiliacion');
            $table->string('clues');
            $table->string('usuario_id');
            
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pacientes');
    }
}
