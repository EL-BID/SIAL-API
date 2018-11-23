<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFirmasOrganismosTable extends Migration{
    /**
     * Run the migrations.
     * @table firmas_organismos
     *
     * @return void
     */
    public function up(){
       Schema::create('firmas_organismos', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id',4);
		    $table->integer('organismo_id')->unsigned();
		    $table->string('puesto_id');
		    $table->string('nombre', 255);
		    $table->string('usuario_id', 255);
		    
		    $table->primary('id');
		/*
		    $table->index('organismo_id','fk_firmas_organismos_organismos1_idx');
		    $table->index('puesto_id','fk_firmas_organismos_puestos1_idx');
		*/
		    $table->foreign('organismo_id')->references('id')->on('organismos');
		    $table->foreign('puesto_id')->references('id')->on('puestos');
		
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
       Schema::dropIfExists('firmas_organismos');
     }
}
