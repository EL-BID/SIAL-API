<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaMovimientoInsumosBorrador extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('movimiento_insumos_borrador', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 255);
            $table->string('movimiento_id', 255);
            $table->integer('tipo_insumo_id')->unsigned()->nullable();
            $table->string('stock_id', 255)->nullable();
            $table->string('clave_insumo_medico', 255)->nullable();
            $table->string('modo_salida', 1);           
            $table->decimal('cantidad', 15, 2);
            $table->decimal('cantidad_unidosis', 15, 2);
            $table->decimal('precio_unitario', 16, 2);
            $table->decimal('iva', 16, 2);
            $table->decimal('precio_total', 16, 2);
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
            $table->foreign('stock_id')->references('id')->on('stock_borrador');
            $table->foreign('clave_insumo_medico')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
        
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
         Schema::dropIfExists('movimiento_insumos_borrador');
    }
}
