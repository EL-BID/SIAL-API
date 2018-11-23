<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearRecetaDigitalDetalle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receta_digital_detalles', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('receta_digital_id', 255);
            $table->string('clave_insumo_medico', 45);
            $table->integer('cantidad');
            $table->decimal('dosis', 15, 2);
            $table->decimal('frecuencia', 15, 2);
            $table->string('duracion', 45);
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
            $table->foreign('receta_digital_id')->references('id')->on('recetas_digitales');
        
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
        Schema::drop('receta_digital_detalles');
    }
}
