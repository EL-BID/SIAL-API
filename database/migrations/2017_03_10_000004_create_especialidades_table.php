<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEspecialidadesTable extends Migration{
    /**
     * Run the migrations.
     * @table especialidades
     *
     * @return void
     */
    public function up(){
        Schema::create('especialidades', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('nombre', 255)->nullable()->default(null);
        
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
       Schema::dropIfExists('especialidades');
     }
}
