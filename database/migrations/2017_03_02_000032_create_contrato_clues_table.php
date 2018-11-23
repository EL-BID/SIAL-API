<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContratoCluesTable extends Migration{
    /**
     * Run the migrations.
     * @table contrato_clues
     *
     * @return void
     */
    public function up(){
        Schema::create('contrato_clues', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->integer('contrato_id')->unsigned();
            $table->string('clues', 12);
            
            $table->index('contrato_id','fk_contrato_clues_contratos1_idx');
        
            $table->foreign('contrato_id')->references('id')->on('contratos');
        
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
       Schema::dropIfExists('contrato_clues');
     }
}
