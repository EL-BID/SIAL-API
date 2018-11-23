<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPreciosBaseDetalles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('precios_base_detalles', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->integer('precio_base_id')->unsigned();
            $table->string('insumo_medico_clave',25);
            $table->boolean('es_causes');
            $table->decimal('precio',15,2)->nullable();
            $table->string('usuario_id', 255);
                  
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('precio_base_id')->references('id')->on('precios_base');
            $table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('precios_base_detalles');
    }
}
