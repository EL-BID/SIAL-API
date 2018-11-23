<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlmacenTiposMovimientosTable extends Migration{
    /**
     * Run the migrations.
     * @table almacen_tipos_salidas
     *
     * @return void
     */
    public function up(){
        Schema::create('almacen_tipos_movimientos', function(Blueprint $table){
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 4);
            $table->string('almacen_id', 255);
		    $table->integer('tipo_movimiento_id')->unsigned();
		    $table->string('usuario_id', 255);
		    
		    $table->primary('id');
		/*
		    $table->index('almacen_id','fk_almacenes_tipos_salidas_almacenes1_idx');
		    $table->index('tipo_movimiento_id','fk_almacen_tipos_salidas_tipos_movimientos1_idx');
		*/
		    $table->foreign('almacen_id')->references('id')->on('almacenes');
		    $table->foreign('tipo_movimiento_id')->references('id')->on('tipos_movimientos');
		
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
       Schema::dropIfExists('almacen_tipos_movimientos');
     }
}
