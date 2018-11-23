<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFactoresRiesgoEmbarazoTable extends Migration{
    /**
     * Run the migrations.
     * @table factores_riesgo_embarazo
     *
     * @return void
     */
    public function up(){
        Schema::create('factores_riesgo_embarazo', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 2);
            $table->string('descripcion', 255)->nullable()->default(null);
            
            $table->primary('id');
        
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
       Schema::dropIfExists('factores_riesgo_embarazo');
     }
}
