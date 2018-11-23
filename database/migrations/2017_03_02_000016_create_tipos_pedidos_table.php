<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTiposPedidosTable extends Migration{
    /**
     * Run the migrations.
     * @table tipos_pedidos
     *
     * @return void
     */
    public function up(){
        Schema::create('tipos_pedidos', function(Blueprint $table) {
            $table->string('id',4);
            $table->string('nombre', 255)->comment('1- PEDIDO REABASTESIMIENTO\n\n2. PEDIDO DESABASTO INCUMPLIMENTO ( ACTA )');
            
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
       Schema::dropIfExists('tipos_pedidos');
     }
}
