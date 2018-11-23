<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearRecetaDigital extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recetas_digitales', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('clues', 45);
            $table->date('fecha_receta');
            $table->string('medico_id',255);
            $table->string('paciente_id',255);
            $table->integer('tipo_receta_id')->unsigned();
            $table->string('diagnostico', 500);

            $table->string('usuario_id', 255);
            
            $table->primary('id');

            $table->foreign('paciente_id')->references('id')->on('pacientes');
            $table->foreign('medico_id')->references('id')->on('personal_clues');
            $table->foreign('tipo_receta_id')->references('id')->on('tipos_recetas');
        
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
        Schema::drop('recetas_digitales');
    }
}
