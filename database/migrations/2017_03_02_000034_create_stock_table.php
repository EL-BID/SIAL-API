<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockTable extends Migration{
    /**
     * Run the migrations.
     * @table stock
     *
     * @return void
     */
    public function up(){
        Schema::create('stock', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 255);
            $table->string('almacen_id', 255);
            $table->string('clave_insumo_medico', 255)->nullable();
            $table->integer('marca_id')->unsigned()->nullable();
            $table->string('lote', 45)->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->string('codigo_barras', 45)->nullable();
            $table->integer('existencia');
            $table->integer('existencia_unidosis');
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
            //$table->index('almacenen_id','fk_stock_almacenes1_idx');
        
            $table->foreign('almacen_id')->references('id')->on('almacenes');
            $table->foreign('marca_id')->references('id')->on('marcas');
        
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
       Schema::dropIfExists('stock');
     }
}
