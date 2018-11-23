<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnidadesMedidaTable extends Migration{
    /**
     * Run the migrations.
     * @table unidades_medida
     *
     * @return void
     */
    public function up(){
        Schema::create('unidades_medida', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('clave', 10)->nullable();
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
       Schema::dropIfExists('unidades_medida');
     }
}
