<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediosContactoTable extends Migration{
    /**
     * Run the migrations.
     * @table medios_contacto
     *
     * @return void
     */
    public function up(){
        Schema::create('medios_contacto', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('nombre', 45);
            $table->text('icon');
            
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
       Schema::dropIfExists('medios_contacto');
     }
}
