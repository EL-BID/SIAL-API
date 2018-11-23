<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarModuloInicioAUsuariosYRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('modulo_inicio')->nullable()->after('avatar');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->string('modulo_inicio')->nullable()->after('nombre');
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
            $table->dropColumn('modulo_inicio');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('modulo_inicio');
        });
    }
}
