<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaHistorialSincronizacionesProveedores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historial_sincronizaciones_proveedores', function (Blueprint $table) {
            
            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id',4);

            $table->string('movimiento_id', 255);
            $table->string('clues', 45);
            $table->string('almacen_id',45);
            $table->integer('proveedor_id');
            $table->integer('pedido_id');
            $table->date('fecha_surtimiento');
            $table->integer('recetas_validas');
            $table->integer('colectivos_validos');
            $table->integer('recetas_duplicadas');
            $table->integer('colectivos_duplicados');
            $table->text('json');
            $table->string('usuario_id', 255);
             
            $table->timestamps();
            $table->softDeletes();

            

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
        Schema::dropIfExists('historial_sincronizaciones_proveedores');
    }
}
