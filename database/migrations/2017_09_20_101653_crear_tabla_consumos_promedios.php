<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaConsumosPromedios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consumos_promedios', function (Blueprint $table) {
            
            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id',4);

            $table->string('clues', 45);
            $table->string('almacen_id',255);
            $table->string('clave_insumo_medico',45);
            $table->decimal('consumo_promedio_diario',15,2);
            $table->decimal('consumo_promedio_semanal',15,2);
            $table->decimal('consumo_promedio_mensual',15,2);

            $table->string('usuario_id', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
            $table->foreign('clues')->references('clues')->on('unidades_medicas');
            $table->foreign('almacen_id')->references('id')->on('almacenes');
            $table->foreign('clave_insumo_medico')->references('clave')->on('insumos_medicos');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consumos_promedios');
    }
}
