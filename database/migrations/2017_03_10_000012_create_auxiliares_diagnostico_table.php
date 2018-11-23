<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuxiliaresDiagnosticoTable extends Migration{
    /**
     * Run the migrations.
     * @table auxiliares_diagnostico
     *
     * @return void
     */
    public function up(){
        Schema::create('auxiliares_diagnostico', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('insumo_medico_clave', 25);
            $table->text('articulo_especifico')->nullable();
            $table->text('presentacion')->nullable();
        
            $table->unique('insumo_medico_clave','insumo_medico_clave_UNIQUE');
            //$table->index('insumo_medico_clave','fk_auxiliares_diagnostico_insumos_medicos1_idx');
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
       Schema::dropIfExists('auxiliares_diagnostico');
     }
}
