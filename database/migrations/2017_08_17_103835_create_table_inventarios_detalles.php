<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableInventariosDetalles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventarios_detalles', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('inventario_id', 255);

            $table->string('insumo_medico_clave', 45);
            $table->string('codigo_barras', 45)->nullable();
            $table->string('lote', 45)->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->integer('cantidad')->nullable();
            $table->decimal('precio_unitario',15,2)->nullable();
            $table->decimal('monto',15,2)->nullable();
            
            $table->string('usuario_id', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventario_id')->references('id')->on('inventarios');
            //$table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('inventarios_detalles');
    }
}
