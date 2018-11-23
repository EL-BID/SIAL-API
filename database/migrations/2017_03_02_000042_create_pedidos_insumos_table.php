<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePedidosInsumosTable extends Migration{
    /**
     * Run the migrations.
     * @table pedidos_insumos
     *
     * @return void
     */
    public function up(){
        Schema::create('pedidos_insumos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('pedido_id', 255);
            $table->string('insumo_medico_clave', 255);
            //$table->integer('cantidad_sugerida')->nullable()->comment('Cantidad sugerida por el sistema');
            $table->integer('cantidad')->nullable()->comment('Cantidad ingresada por la unidad medica, se utilizara en caso de ser un pedido por desabasto');
            $table->integer('cantidad_solicitada')->nullable()->comment('Cantidad validada enviada en el pedido, en caso de ser un pedido por desabasto, esta sera la cantidad modificada por la comisión en caso de ser necesario');
            $table->integer('cantidad_recibida')->nullable()->comment('Cantidad recibida');
            $table->decimal('precio_unitario',15,2)->nullable();
            $table->decimal('monto',15,2)->nullable();
            $table->decimal('monto_solicitado',15,2)->nullable();
            $table->decimal('monto_recibido',15,2)->nullable();
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
          // $table->index('pedido_id','fk_pedidos_articulos_pedidos1_idx');
        
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
       Schema::dropIfExists('pedidos_insumos');
     }
}
