<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCluesTurnosTable extends Migration{
    /**
     * Run the migrations.
     * @table clues_servicios
     *
     * @return void
     */
    public function up(){
        Schema::create('clues_turnos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('clues', 45);
            $table->integer('turno_id')->unsigned();
            $table->string('usuario_id', 255);
            
            $table->primary('id');

            $table->foreign('turno_id')->references('id')->on('turnos');
        
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
       Schema::dropIfExists('clues_turnos');
     }
}
