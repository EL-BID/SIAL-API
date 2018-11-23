<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarOfflineAUnidadesMedicas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unidades_medicas', function (Blueprint $table) {
            $table->boolean('es_offline')->after('activa')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unidades_medicas', function (Blueprint $table) {
            $table->dropColumn('es_offline');
        });
    }
}
