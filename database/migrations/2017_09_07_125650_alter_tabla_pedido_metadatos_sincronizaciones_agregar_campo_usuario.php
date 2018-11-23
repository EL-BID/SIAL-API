<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaPedidoMetadatosSincronizacionesAgregarCampoUsuario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_metadatos_sincronizaciones', function (Blueprint $table)
        {             
            $table->string('usuario_id',255)->after('total_colectivos_repetidos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_metadatos_sincronizaciones', function (Blueprint $table)
        {
            $table->dropColumn('usuario_id');
        });
    }
}
