<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaInicializacionInventario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inicializacion_inventario', function(Blueprint $table) {
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 255);
		    $table->string('clues', 255);
		    $table->string('almacen_id', 255);
		    $table->string('estatus', 17)->comment('INICIALIZADO / NOINICIALIZADO');
		    $table->date('fecha_inicio');
		    $table->date('fecha_fin')->default(null);
		    $table->integer('cantidad_programas');
		    $table->integer('cantidad_claves');
		    $table->integer('cantidad_insumos');
		    $table->integer('cantidad_lotes');
		    $table->decimal('monto_total', 16, 2);
		    $table->string('observaciones', 500);
		    $table->string('usuario_id', 255);
		
		    $table->timestamps();
            $table->softDeletes();
		
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('inicializacion_inventario');
    }
}
