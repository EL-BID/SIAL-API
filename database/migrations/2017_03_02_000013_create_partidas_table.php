<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartidasTable extends Migration{
    /**
     * Run the migrations.
     * @table partidas
     *
     * @return void
     */
    public function up(){
        Schema::create('partidas', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('clave', 45);
            $table->string('nombre', 45);
            
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
       Schema::dropIfExists('partidas');
     }
}
