<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInformacionImportanteMedicamentosTable extends Migration{
    /**
     * Run the migrations.
     * @table informacion_importante_medicamentos
     *
     * @return void
     */
    public function up(){
        Schema::create('informacion_importante_medicamentos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 45)->nullable()->default(null);
            $table->integer('generico_id')->unsigned()->comment('Solo usar medicamentos tipo: ME');
            $table->string('factor_riesgo_embarazo_id', 1)->default('A');
            $table->text('generalidades')->nullable();
            $table->text('efectos_adversos')->nullable();
            $table->text('contraindicaciones_precauciones')->nullable();
            $table->text('interacciones')->nullable();
            
            $table->primary('generico_id', ' factor_riesgo_embarazo_id');
            $table->unique('generico_id','generico_id_UNIQUE');
        
            //$table->index('factor_riesgo_embarazo_id','fk_informacion_medicamentos_f_r_embarazo1_idx');
        
            $table->foreign('factor_riesgo_embarazo_id','fk_inf_med_fact_ries_embarazo1_idx')->references('id')->on('factores_riesgo_embarazo');
            $table->foreign('generico_id')->references('id')->on('genericos');
        
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
       Schema::dropIfExists('informacion_importante_medicamentos');
     }
}
