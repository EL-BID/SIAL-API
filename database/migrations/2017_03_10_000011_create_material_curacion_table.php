<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMaterialCuracionTable extends Migration{
    /**
     * Run the migrations.
     * @table material_curacion
     *
     * @return void
     */
    public function up(){
        Schema::create('material_curacion', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('insumo_medico_clave', 25);
            $table->text('nombre_generico_especifico')->nullable();
            $table->decimal('cantidad_x_envase', 15, 2);
            $table->integer('unidad_medida_id')->unsigened()->comment('Del contenido del envase');
            $table->text('funcion')->nullable();
        
            $table->unique('insumo_medico_clave','insumo_medico_clave_UNIQUE');
        
            //$table->index('insumo_medico_clave','fk_material_curacion_insumos_medicos1_idx');
            $table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
        
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down(){
       Schema::dropIfExists('material_curacion');
     }
}
