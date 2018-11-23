<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPresupuestoEjercicio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('presupuesto_ejercicio', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ejercicio')->length(4);
            $table->decimal('causes',15,2)->default(0);
            $table->decimal('no_causes',15,2)->default(0);
            $table->boolean('activo')->default(0);
            $table->integer('factor_meses');
            $table->string('usuario_id', 255);
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
        Schema::drop('presupuesto_ejercicio');
    }
}
