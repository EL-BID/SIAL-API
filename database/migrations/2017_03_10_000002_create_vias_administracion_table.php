<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateViasAdministracionTable extends Migration{
    /**
     * Run the migrations.
     * @table vias_administracion
     *
     * @return void
     */
    public function up(){
        Schema::create('vias_administracion', function(Blueprint $table) {
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
       Schema::dropIfExists('vias_administracion');
     }
}
