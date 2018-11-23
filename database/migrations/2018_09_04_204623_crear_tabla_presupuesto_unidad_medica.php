<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPresupuestoUnidadMedica extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('presupuesto_unidad_medica', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('presupuesto_id')->unsigned();
            $table->string('clues', 12);


            $table->decimal('causes_autorizado',15,2)->default(0);
            $table->decimal('causes_modificado',15,2)->default(0);
            $table->decimal('causes_comprometido',15,2)->default(0);
            $table->decimal('causes_devengado',15,2)->default(0);
            $table->decimal('causes_disponible',15,2)->default(0);

            $table->decimal('no_causes_autorizado',15,2)->default(0);
            $table->decimal('no_causes_modificado',15,2)->default(0);
            $table->decimal('no_causes_comprometido',15,2)->default(0);
            $table->decimal('no_causes_devengado',15,2)->default(0);
            $table->decimal('no_causes_disponible',15,2)->default(0);
            
            $table->string('usuario_id', 255);            

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('presupuesto_id')->references('id')->on('presupuesto_ejercicio');
            $table->foreign('clues')->references('clues')->on('unidades_medicas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('presupuesto_unidad_medica');
    }
}
