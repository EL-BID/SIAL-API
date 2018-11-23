<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaLogPedidosCanceladosCampoDeleted extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('log_pedidos_cancelados', function ($table) {
            $table->timestamp('deleted_at')->nullable();
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('log_pedidos_cancelados', function ($table) {
            $table->dropColumn('deleted_at');
        });
    }
}
