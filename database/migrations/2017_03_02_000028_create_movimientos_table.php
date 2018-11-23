<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovimientosTable extends Migration{
    /**
     * Run the migrations.
     * @table movimientos
     *
     * @return void
     */
    public function up(){
        Schema::create('movimientos', function(Blueprint $table) {
		    $table->engine = 'InnoDB';

		    $table->string('id', 255);
		    $table->string('servidor_id', 4);
		    $table->integer('incremento');
		    $table->string('almacen_id', 255);
		    $table->integer('tipo_movimiento_id')->unsigned();
        $table->string('status', 10)->comment('BR BORRADOR\nFI FINALIZADO\n');
		    $table->date('fecha_movimiento');
		    $table->string('observaciones', 255)->nullable();
		    $table->boolean('cancelado');
		    $table->string('observaciones_cancelacion', 255)->nullable();
		    $table->string('usuario_id', 255);
		    
		    $table->primary('id');
		    
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
       Schema::dropIfExists('movimientos');
     }
}
