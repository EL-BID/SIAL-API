<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrganismosTable extends Migration{
    /**
     * Run the migrations.
     * @table organismos
     *
     * @return void
     */
    public function up(){
       Schema::create('organismos', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->increments('id');
          $table->string('clave', 255);
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
       Schema::dropIfExists('organismos');
     }
}
