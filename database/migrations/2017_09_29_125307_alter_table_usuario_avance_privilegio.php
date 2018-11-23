<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableUsuarioAvancePrivilegio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avance_usuario_privilegio', function (Blueprint $table) {
            $table->string('ver', 255)->default(0)->after('avance_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('avance_usuario_privilegio', function (Blueprint $table) {
            $table->dropColumn('ver');
        });
    }
}
