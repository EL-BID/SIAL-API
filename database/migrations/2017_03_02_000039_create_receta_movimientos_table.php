<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecetaMovimientosTable extends Migration{
    /**
     * Run the migrations.
     * @table receta_movimientos
     *
     * @return void
     */
    public function up(){
        Schema::create('receta_movimientos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('receta_id', 255);
            $table->string('movimiento_id', 255);
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
            //$table->index('receta_id','fk_receta_movimientos_recetas1_idx');
            //$table->index('movimiento_id','fk_receta_movimientos_movimientos1_idx');
        
            $table->foreign('receta_id')->references('id')->on('recetas');
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
       Schema::dropIfExists('receta_movimientos');
     }
}
