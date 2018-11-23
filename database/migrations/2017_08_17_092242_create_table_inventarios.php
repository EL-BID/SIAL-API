<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableInventarios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('clues', 12);
            $table->string('almacen_id', 255);

            $table->string('descripcion', 255);
            $table->string('observaciones', 255);

            $table->string('status', 10);

            $table->timestamp('fecha_inicio_captura');
            $table->timestamp('fecha_conclusion_captura')->nullable();

            $table->integer('total_claves')->default(0);
            $table->decimal('total_monto_causes',15,2)->default(0);
            $table->decimal('total_monto_no_causes',15,2)->default(0);
            $table->decimal('total_monto_material_curacion',15,2)->default(0);
            
            $table->string('usuario_id', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('clues')->references('clues')->on('unidades_medicas');
            $table->foreign('almacen_id')->references('id')->on('almacenes');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
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
        Schema::drop('inventarios');
    }
}
