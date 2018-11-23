<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarAdministradorATablaUnidadesMedicas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unidades_medicas', function (Blueprint $table) {
            $table->string('administrador_id', 255)->nullable()->after('director_id');
            $table->foreign('administrador_id')->references('id')->on('personal_clues');
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
            $table->dropForeign(['administrador_id']);
            $table->dropColumn('administrador_id');   
        });
    }
}
