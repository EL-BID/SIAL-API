<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCluesServiciosTable extends Migration{
    /**
     * Run the migrations.
     * @table clues_servicios
     *
     * @return void
     */
    public function up(){
        Schema::create('clues_servicios', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('clues', 45);
            $table->integer('servicio_id')->unsigned();
            $table->string('nombre', 255);
            $table->string('usuario_id', 255);
            
            $table->primary('id');

            $table->foreign('servicio_id')->references('id')->on('servicios');
        
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
       Schema::dropIfExists('clues_servicios');
     }
}
