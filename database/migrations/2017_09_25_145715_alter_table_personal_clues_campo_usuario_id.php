<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePersonalCluesCampoUsuarioId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personal_clues', function (Blueprint $table) {
            $table->string('usuario_id')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('personal_clues', function (Blueprint $table) {
            $table->dropColumn('usuario_id');
        });
    }
}
