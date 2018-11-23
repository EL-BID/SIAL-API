<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUnidadesMedicasRemoveAdministradorIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('unidades_medicas', 'administrador_id')){
            Schema::table('unidades_medicas', function (Blueprint $table) {
                $table->dropForeign(['administrador_id']);
                $table->dropColumn('administrador_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unidades_medicas', function (Blueprint $table) {
            $table->string('administrador_id', 255)->nullable()->after('director_id');
            $table->foreign('administrador_id')->references('id')->on('personal_clues');
        });
    }
}
