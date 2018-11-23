<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovimientoDetallesTable extends Migration{
    /**
     * Run the migrations.
     * @table movimientos_detalles
     *
     * @return void
     */
    public function up(){
        Schema::create('movimiento_detalles', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 255);
            $table->string('movimiento_id', 255);
            $table->string('clave_insumo_medico', 255);
            $table->decimal('cantidad_solicitada', 16, 2);
            $table->decimal('cantidad_existente', 16, 2);
            $table->decimal('cantidad_surtida', 16, 2);
            $table->decimal('cantidad_negada', 16, 2);
            $table->string('usuario_id', 255);
            
            $table->primary('id');
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
         
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
       Schema::dropIfExists('movimiento_detalles');
     }
}
