<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePedidosTable extends Migration{
    /**
     * Run the migrations.
     * @table pedidos
     *
     * @return void
     */
    public function up(){
        Schema::create('pedidos', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 4);

			$table->string('clues', 45);
		    $table->integer('tipo_insumo_id')->unsigned()->nullable();
		    $table->string('tipo_pedido_id',4);
			$table->string('descripcion', 255);
		    $table->string('pedido_padre', 255)->nullable();
		    $table->string('folio', 45)->nullable();
			$table->date('fecha');
			$table->date('fecha_expiracion');
		    $table->string('almacen_solicitante', 255);
		    $table->string('almacen_proveedor', 255)->nullable();
		    $table->integer('organismo_dirigido')->unsigned()->nullable();
		    $table->string('acta_id', 255)->nullable();
		    $table->string('status', 45)->comment('BR BORRADOR\n ET EN TRANSITO\nPS POR SURTIR\nFI FINALIZADO\n');
			$table->text('observaciones')->nullable();
		    $table->integer('usuario_validacion')->nullable();
		    $table->integer('proveedor_id')->unsigned()->nullable();

            $table->decimal('total_monto_solicitado',15,2)->nullable();
            $table->decimal('total_monto_recibido',15,2)->nullable();
			$table->integer('total_claves_solicitadas')->nullable();
            $table->integer('total_claves_recibidas')->nullable();
			$table->integer('total_cantidad_solicitada')->nullable();
            $table->integer('total_cantidad_recibida')->nullable();

			$table->string('encargado_almacen_id', 255)->nullable();
			$table->string('director_id', 255)->nullable();

		    $table->string('usuario_id', 255);
		    
		    $table->primary('id');
			
		    $table->foreign('almacen_solicitante')->references('id')->on('almacenes');
		    $table->foreign('almacen_proveedor')->references('id')->on('almacenes');
		    $table->foreign('organismo_dirigido')->references('id')->on('organismos');
		    $table->foreign('acta_id')->references('id')->on('actas');
		    $table->foreign('pedido_padre')->references('id')->on('pedidos');
		    $table->foreign('tipo_insumo_id')->references('id')->on('tipos_insumos');
		    $table->foreign('tipo_pedido_id')->references('id')->on('tipos_pedidos');
		    $table->foreign('proveedor_id')->references('id')->on('proveedores');
			$table->foreign('encargado_almacen_id')->references('id')->on('personal_clues');
			$table->foreign('director_id')->references('id')->on('personal_clues');
		
		    $table->timestamps();
			$table->softDeletes();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down(){
       Schema::dropIfExists('pedidos');
     }
}
