<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovimientoInsumosTable extends Migration{
    /**
     * Run the migrations.
     * @table movimientos_detalles
     *
     * @return void
     */
    public function up(){
        Schema::create('movimiento_insumos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 255);
            $table->string('movimiento_id', 255);
            $table->string('stock_id', 255);
            $table->decimal('cantidad', 15, 2);
            $table->decimal('precio_unitario', 16, 2);
            $table->decimal('iva', 5, 2);
            $table->decimal('precio_total', 16, 2);
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
            $table->foreign('stock_id')->references('id')->on('stock');
        
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
       Schema::dropIfExists('movimiento_insumos');
     }
}
