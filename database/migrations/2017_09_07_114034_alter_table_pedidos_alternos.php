<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidosAlternos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedidos_alternos', function (Blueprint $table) {
            $table->dropColumn('nombre_firma_1');
            $table->dropColumn('nombre_firma_2');
            $table->dropColumn('cargo_firma_1');
            $table->dropColumn('cargo_firma_2');
            $table->string('firma_1_id', 255)->after('folio')->nullable();
            $table->string('firma_2_id', 255)->after('firma_1_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedidos_alternos', function (Blueprint $table) {
            $table->string('nombre_firma_1', 255);
            $table->string('nombre_firma_2', 255);
            $table->string('cargo_firma_1', 255);
            $table->string('cargo_firma_2', 255);
            $table->dropColumn('firma_1_id');
            $table->dropColumn('firma_2_id');
        });
    }
}
