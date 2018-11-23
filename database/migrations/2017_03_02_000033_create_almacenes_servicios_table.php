<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlmacenesServiciosTable extends Migration{
    /**
     * Run the migrations.
     * @table almacenes_servicios
     *
     * @return void
     */
    public function up(){
        Schema::create('almacenes_servicios', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->string('id', 255);
          $table->integer('incremento');
          $table->string('servidor_id', 4);
          $table->string('clues', 45);
          $table->string('almacen_id', 255);
          $table->string('usuario_id', 255);
      
          $table->unique('id','id_UNIQUE');
      
          $table->index('almacen_id','fk_almacenes_servicios_almacenes1_idx');
      
          $table->foreign('almacen_id')->references('id')->on('almacenes');
      
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
       Schema::dropIfExists('almacenes_servicios');
     }
}
