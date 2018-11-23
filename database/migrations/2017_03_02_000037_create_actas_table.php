<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActasTable extends Migration{
    /**
     * Run the migrations.
     * @table actas
     *
     * @return void
     */
    public function up(){
        Schema::create('actas', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 4);
		    $table->string('nombre', 255);
		    $table->string('folio', 45);
		    $table->string('clues', 45);
		    $table->integer('numero');
		    $table->integer('numero_oficio');
		    $table->integer('numero_oficio_pedido');
		    $table->string('ciudad', 255);
		    $table->date('fecha');
		    $table->date('fecha_solicitud');
		    $table->date('fecha_pedido');
		    $table->time('hora_inicio');
		    $table->time('hora_termino');
		    $table->string('lugar_reunion', 255);
		    $table->string('lugar_entrega', 255);
		    $table->string('director_unidad', 255);
		    $table->string('administrador_unidad', 255);
		    $table->string('encargado_almacen', 255);
		    $table->string('firma_organismo_id', 255);
		    $table->integer('proveedor_id')->unsigned();
		    
			$table->timestamp('fecha_cancelacion')->nullable();
		    $table->text('motivo_cancelacion')->nullable();
		    $table->string('usuario_id', 255);

			$table->timestamps();
			$table->softDeletes();

		    $table->primary('id');
		/*
		    $table->index('director_unidad','fk_actas_firmas_documentos1_idx');
		    $table->index('administrador_unidad','fk_actas_firmas_documentos2_idx');
		    $table->index('encargado_almacen','fk_actas_firmas_documentos3_idx');
		    $table->index('firma_organismo_id','fk_actas_firmas_organismos1_idx');
		    $table->index('proveedor_id','fk_actas_proveedores1_idx');
		*/
		  
		    $table->foreign('director_unidad')->references('id')->on('personal_clues');
		
		    $table->foreign('administrador_unidad')->references('id')->on('personal_clues');
		
		    $table->foreign('encargado_almacen')->references('id')->on('personal_clues');
		
		    $table->foreign('firma_organismo_id')->references('id')->on('firmas_organismos');
		
		    $table->foreign('proveedor_id')->references('id')->on('proveedores');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down(){
       Schema::dropIfExists('actas');
     }
}
