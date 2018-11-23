<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableUsuariosRepucerarPassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('email',255)->nullable()->after('avatar');
            $table->string('pregunta_secreta',255)->nullable()->after('email');
            $table->string('respuesta',255)->nullable()->after('pregunta_secreta');
            $table->string('reset_token',255)->nullable()->after('respuesta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->dropColumn('pregunta_secreta');
            $table->dropColumn('respuesta');
            $table->dropColumn('reset_token');
        });
    }
}
