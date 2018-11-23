<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaInicializacionInventarioDetalles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inicializacion_inventario_detalles', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 255);
		    $table->string('inicializacion_inventario_id', 255);
		    $table->string('almacen_id', 255);
		    $table->string('clave_insumo_medico', 55)->nullable();
		    $table->integer('programa_id')->nullable();
		    $table->integer('marca_id')->unsigned()->nullable();
		    $table->string('lote', 45)->nullable();
		    $table->date('fecha_caducidad')->nullable();
		    $table->string('codigo_barras', 45)->nullable();
		    $table->integer('existencia');
		    $table->integer('existencia_unidosis');
		    $table->integer('unidosis_sueltas');
		    $table->integer('envases_parciales');
		    $table->decimal('precio_unitario', 16, 2)->comment('precio individual sin iva');
		    $table->decimal('iva', 16, 2)->comment('iva unitario');
		    $table->decimal('importe', 16, 2)->comment('importe sin iva');
		    $table->decimal('iva_importe', 16, 2)->comment('iva * cantidad');
		    $table->decimal('importe_con_iva', 16, 2);
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
        Schema::drop('inicializacion_inventario_detalles');
    }
}
