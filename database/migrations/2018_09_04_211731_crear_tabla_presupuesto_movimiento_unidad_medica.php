<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPresupuestoMovimientoUnidadMedica extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('presupuesto_movimiento_unidad_medica', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('presupuesto_unidad_medica_id')->unsigned();

            $table->decimal('causes_saldo_anterior',15,2)->default(0);
            $table->decimal('causes_cargo',15,2)->default(0);
            $table->decimal('causes_abono',15,2)->default(0);
            $table->decimal('causes_saldo',15,2)->default(0);

            $table->decimal('no_causes_saldo_anterior',15,2)->default(0);
            $table->decimal('no_causes_cargo',15,2)->default(0);
            $table->decimal('no_causes_abono',15,2)->default(0);
            $table->decimal('no_causes_saldo',15,2)->default(0);
            
            $table->string('usuario_id', 255);            

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('presupuesto_unidad_medica_id','presupuesto_movimiento_unidad_medica_fk')->references('id')->on('presupuesto_unidad_medica');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('presupuesto_movimiento_unidad_medica');
    }
}
