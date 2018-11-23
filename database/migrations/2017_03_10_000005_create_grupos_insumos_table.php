<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGruposInsumosTable extends Migration{
    /**
     * Run the migrations.
     * @table grupos_insumos
     *
     * @return void
     */
    public function up(){
        Schema::create('grupos_insumos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('tipo', 2)->nullable()->default('ME')->comment('ME = Medicamentos\nMC = Material de curación\nAD = Auxiliares de Diagnostico');
            $table->string('nombre', 255)->nullable()->default(null);
            $table->string('numero', 25)->nullable()->default(NULL )->comment( 'SOLO PARA TIPO ME');

        
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
       Schema::dropIfExists('grupos_insumos');
     }
}
