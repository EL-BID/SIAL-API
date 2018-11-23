<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaMovimientosCamposEntradaTradicional extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimiento_metadatos', function (Blueprint $table) 
        {

            $table->tinyInteger('donacion')->nullable()->after('movimiento_id');
            $table->string('donante')->nullable()->after('donacion');
            $table->string('numero_pedido')->nullable()->after('donante');
            $table->string('folio_factura')->nullable()->after('numero_pedido');
            $table->integer('proveedor_id')->nullable()->after('folio_factura');
            $table->string('persona_entrega')->nullable()->after('proveedor_id');
            

         });

         
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimiento_metadatos', function (Blueprint $table) {
            
            $table->dropColumn('donacion');
            $table->dropColumn('donante');
            $table->dropColumn('numero_pedido');
            $table->dropColumn('folio_factura');
            $table->dropColumn('proveedor_id');
            $table->dropColumn('persona_entrega');
            
         });
    }
}
