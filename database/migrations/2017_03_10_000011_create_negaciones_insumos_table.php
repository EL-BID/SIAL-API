<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNegacionesInsumosTable extends Migration{
    /**
     * Run the migrations.
     * @table material_curacion
     *
     * @return void
     */
    public function up(){
        Schema::create('negaciones_insumos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('clues', 15);
            $table->string('almacen_id', 255);
            $table->integer('tipo_insumo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('cantidad_acumulada',16,2);
            $table->date('ultima_entrada');
            $table->decimal('cantidad_entrada',16,2);

            $table->string('clave_insumo_medico', 25);
            $table->string('usuario_id', 255);
        

            $table->foreign('clave_insumo_medico')->references('clave')->on('insumos_medicos');
        
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
       Schema::dropIfExists('negaciones_insumos');
     }
}
