<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuxiliaresDiagnosticoDetallesEquiposTable extends Migration{
    /**
     * Run the migrations.
     * @table auxiliares_diagnostico_detalles_equipos
     *
     * @return void
     */
    public function up(){
        Schema::create('auxiliares_diagnostico_detalles_equipos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->integer('auxiliar_diagnostico_id')->unsigned();
            $table->text('descripcion')->nullable();
            $table->text('refacciones')->nullable();
            $table->text('accesorios')->nullable();
            $table->text('consumibles')->nullable();
            $table->text('instalacion')->nullable();
            $table->text('operacion')->nullable();
            $table->text('mantenimiento')->nullable();
            
            //$table->primary('auxiliar_diagnostico_id');
        
            $table->unique('auxiliar_diagnostico_id','auxiliar_diagnostico_id_UNIQUE');
        
            $table->foreign('auxiliar_diagnostico_id','fk_aux_diag_idx1')->references('id')->on('auxiliares_diagnostico');
        
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
       Schema::dropIfExists('auxiliares_diagnostico_detalles_equipos');
     }
}
