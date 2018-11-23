<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInsumosMaximosMinimosTable extends Migration{
    /**
     * Run the migrations.
     * @table insumos_maximos_minimos
     *
     * @return void
     */
    public function up(){
        Schema::create('insumos_maximos_minimos', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 4);
		    $table->string('almacenes_id', 255);
		    $table->string('insumo_medico_clave', 255)->nullable();
		    $table->integer('maximo');
		    $table->integer('minimo');
		    $table->string('usuario_id', 255);
		    
		    $table->primary('id');
		
		    $table->index('almacenes_id','fk_articulos_maximos_minimos_almacenes1_idx');
		
        $table->foreign('almacenes_id')->references('id')->on('almacenes');
		
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
       Schema::dropIfExists('insumos_maximos_minimos');
     }
}
