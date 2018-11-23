<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExtensionesContratosTable extends Migration{
    /**
     * Run the migrations.
     * @table extensiones_contratos
     *
     * @return void
     */
    public function up(){
        Schema::create('extensiones_contratos', function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->integer('contrato_id')->unsigned();
            $table->date('fecha_fin');

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
       Schema::dropIfExists('extensiones_contratos');
     }
}
