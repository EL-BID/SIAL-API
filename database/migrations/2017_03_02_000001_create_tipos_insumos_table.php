<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTiposInsumosTable extends Migration{
    /**
     * Run the migrations.
     * @table tipos_insumos
     *
     * @return void
     */
    public function up(){
       Schema::create('tipos_insumos', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->increments('id');
          $table->string('nombre', 255);
          
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
       Schema::dropIfExists('tipos_insumos');
     }
}
