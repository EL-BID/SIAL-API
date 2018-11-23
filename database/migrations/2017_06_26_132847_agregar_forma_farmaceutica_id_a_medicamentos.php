<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarFormaFarmaceuticaIdAMedicamentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->integer('forma_farmaceutica_id')->unsigned()->nullable()->after('insumo_medico_clave');
            $table->foreign('forma_farmaceutica_id')->references('id')->on('formas_farmaceuticas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->dropForeing(['forma_farmaceutica_id']);
            $table->dropColumn('forma_farmaceutica_id');
        });
    }
}
