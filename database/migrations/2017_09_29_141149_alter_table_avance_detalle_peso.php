<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAvanceDetallePeso extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avance_detalles', function (Blueprint $table) {
            $table->decimal('peso', 15,2)->default(0)->after('extension');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('avance_detalles', function (Blueprint $table) {
            $table->dropColumn('peso');
        });
    }
}
