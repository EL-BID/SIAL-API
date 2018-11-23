<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTiposMovimientosTable extends Migration{
    /**
     * Run the migrations.
     * @table tipos_movimientos
     *
     * @return void
     */
    public function up(){
        Schema::create('tipos_movimientos', function(Blueprint $table) 
        {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('tipo', 45)->comment('* E\n* S\n* E/S\n');
            $table->string('nombre', 255);
            
            //$table->primary('id');

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
       Schema::dropIfExists('tipos_movimientos');
     }
}
