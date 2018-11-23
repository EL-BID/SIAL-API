<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaRecetasCampoPersonalClues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recetas', function (Blueprint $table) {
            $table->string('personal_clues_id')->nullable()->after('fecha_surtido');

            $table->foreign('personal_clues_id')->references('id')->on('personal_clues');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recetas', function (Blueprint $table) {
            $table->dropColumn('personal_clues_id');
        });
    }
}
