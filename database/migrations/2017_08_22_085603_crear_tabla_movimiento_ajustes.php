<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaMovimientoAjustes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimiento_ajustes', function (Blueprint $table) {
            
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id',4);

            $table->string('movimiento_id', 255);
            $table->string('stock_id', 255)->nullable();
            $table->string('clave_insumo_medico',255);
            $table->decimal('existencia_anterior', 16, 2);
            $table->decimal('existencia_unidosis_anterior', 16, 2);
            $table->decimal('nueva_existencia', 16, 2);
            $table->decimal('nueva_existencia_unidosis', 16, 2);
            $table->string('observaciones', 255);
            $table->string('usuario_id', 255);
             
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
            $table->foreign('stock_id')->references('id')->on('stock');
            $table->foreign('clave_insumo_medico')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movimiento_ajustes');
    }
}
