<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaInventarioMovimientoArticulosAddTimestampsSoftdeletes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('inventario_movimiento_articulos', function (Blueprint $table) {

            $table->string('id',255)->first();
            $table->integer('incremento')->after('id');
            $table->string('servidor_id', 4)->after('incremento');

            $table->string('usuario_id', 255)->after('inventario_id');
            $table->timestamps();
            $table->softDeletes();
            $table->primary('id');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
