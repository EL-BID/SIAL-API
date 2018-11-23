<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSincronizaciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sincronizaciones', function (Blueprint $table) {
            $table->string('usuario_id', 255)->after('fecha_generacion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sincronizaciones', function (Blueprint $table) {
            $table->dropColumn('usuario_id');
        });
    }
}
