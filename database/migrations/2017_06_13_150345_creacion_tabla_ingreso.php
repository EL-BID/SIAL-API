<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreacionTablaIngreso extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pacientes_admision', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('paciente_id');
            $table->integer('referido');
            $table->string('unidad_referido');
            $table->integer('urgencia_calificada');
            $table->integer('registro_triage');
            $table->integer('estado_triage_id')->unsigned();
            $table->integer('grado_lesion_id')->unsigned();
            $table->integer('estatus_admision')->unsigned();
            
            $table->date('fecha_hora_ingreso');
            $table->integer('motivo_egreso_id');
            $table->date('fecha_hora_egreso');
            $table->integer('contrareferencia');

            $table->string('unidad_contrareferencia');
            $table->string('usuario_id');
            $table->string('clues');
            
            $table->primary('id');

            $table->foreign('paciente_id')->references('id')->on('pacientes');
            $table->foreign('estado_triage_id')->references('id')->on('catalogo_estado_triage');
            $table->foreign('grado_lesion_id')->references('id')->on('catalogo_grado_lesion');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pacientes_admision');
    }
}
