<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaSustanciasLaboratorio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sustancias_laboratorio', function (Blueprint $table) {
            
            $table->increments('id');
            $table->string('insumo_medico_clave',255);
            $table->decimal('cantidad_x_envase',15,2);
            $table->integer('unidad_medida_id')->unsigned();
            $table->integer('presentacion_id')->unsigned();
            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
            $table->foreign('unidad_medida_id')->references('id')->on('unidades_medida');
            $table->foreign('presentacion_id')->references('id')->on('presentaciones_sustancias');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sustancias_laboratorio');
    }
    
}
