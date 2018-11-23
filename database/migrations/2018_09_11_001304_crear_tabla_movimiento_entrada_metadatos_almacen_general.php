<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaMovimientoEntradaMetadatosAlmacenGeneral extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

     
    public function up()
    {
        Schema::create('movimiento_entrada_metadatos_ag', function (Blueprint $table)
        {

            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('movimiento_id', 255);
            $table->boolean('donacion')->nullable();
            $table->string('donante',255)->nullable();
            $table->string('numero_pedido',55)->nullable();
            $table->date('fecha_referencia')->nullable();
            $table->string('folio_factura',55)->nullable();
            $table->integer('proveedor_id')->unsigned()->nullable();
            $table->string('persona_entrega',255)->nullable();
            $table->string('persona_recibe',255)->nullable();

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
            $table->primary('id');

            $table->foreign('movimiento_id')->references('id')->on('movimientos');
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
