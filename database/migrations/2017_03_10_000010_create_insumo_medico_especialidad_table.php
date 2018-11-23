<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInsumoMedicoEspecialidadTable extends Migration{
    /**
     * Run the migrations.
     * @table insumo_medico_especialidad
     *
     * @return void
     */
    public function up(){
        Schema::create('insumo_medico_especialidad', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('insumo_medico_clave', 25);
            $table->integer('especialidad_id')->unsigned();
            
            $table->primary('insumo_medico_clave', ' especialidad_id');
          // $table->index('especialidad_id','fk_insumos_medicos_has_especialidades_especialidades1_idx');
          // $table->index('insumo_medico_clave','fk_insumos_medicos_has_especialidades_insumos_medicos1_idx');
        
            $table->foreign('especialidad_id')->references('id')->on('especialidades');
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
       Schema::dropIfExists('insumo_medico_especialidad');
     }
}
