<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePresentacionesMedicamentosTable extends Migration{
    /**
     * Run the migrations.
     * @table presentaciones_medicamentos
     *
     * @return void
     */
    public function up(){
        Schema::create('presentaciones_medicamentos', function(Blueprint $table) {
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
       Schema::dropIfExists('presentaciones_medicamentos');
     }
}
