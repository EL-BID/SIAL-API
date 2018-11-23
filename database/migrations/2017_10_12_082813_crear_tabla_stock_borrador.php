<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaStockBorrador extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_borrador', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 255);
            $table->string('almacen_id', 255);
            $table->string('clave_insumo_medico', 55)->nullable();
            $table->integer('marca_id')->unsigned()->nullable();
            $table->string('lote', 45)->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->string('codigo_barras', 45)->nullable();
            $table->integer('existencia');
            $table->integer('existencia_unidosis');
            $table->integer('unidosis_sueltas');
            $table->integer('envases_parciales');
            $table->string('usuario_id', 255);
            
            $table->primary('id');
            
            $table->foreign('almacen_id')->references('id')->on('almacenes');
        
            $table->timestamps();
            $table->softDeletes();
        });
    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_borrador');
    }
}
