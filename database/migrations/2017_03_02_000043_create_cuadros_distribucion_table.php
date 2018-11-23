<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCuadrosDistribucionTable extends Migration{
    /**
     * Run the migrations.
     * @table cuadros_distribucion
     *
     * @return void
     */
    public function up(){
       Schema::create('cuadros_distribucion', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->string('id', 255);
          $table->integer('incremento');
          $table->string('servidor_id', 4);
          $table->string('folio', 45);
          $table->string('jurisdiccion', 45);
          $table->string('clues', 45);
          $table->string('mes_pedido', 45);
          $table->string('pedido_id', 255);
          $table->integer('usuario_id');
          
          $table->primary('id');
      
          //$table->index('pedido_id','fk_cuadros_distribucion_pedidos1_idx');
          $table->foreign('pedido_id')->references('id')->on('pedidos');
      
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
       Schema::dropIfExists('cuadros_distribucion');
     }
}
