<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransferenciasPresupuestoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transferencias_presupuesto', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('presupuesto_id')->unsigned();

            $table->string('clues_origen', 12);
            $table->integer('mes_origen');
            $table->integer('anio_origen');

            $table->decimal('causes',15,2)->default(0);
            $table->decimal('no_causes',15,2)->default(0);
            $table->decimal('material_curacion',15,2)->default(0);

            $table->string('clues_destino', 12);
            $table->integer('mes_destino');
            $table->integer('anio_destino');

            $table->foreign('presupuesto_id')->references('id')->on('presupuestos');
            $table->foreign('clues_origen')->references('clues')->on('unidades_medicas');
            $table->foreign('clues_destino')->references('clues')->on('unidades_medicas');
            
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
        Schema::drop('transferencias_presupuesto');
    }
}
