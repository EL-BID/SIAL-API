<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmpresasEspejosTable extends Migration{
    /**
     * Run the migrations.
     * @table empresas_espejos
     *
     * @return void
     */
    public function up(){
      //Schema::disableForeignKeyConstraints();

        Schema::create('empresas_espejos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('ejercicio', 45);
            $table->integer('proveedor_id')->unsigned();
            $table->boolean('vigente');
            $table->string('usuario_id', 255);

            //$table->primary('id');
            
            //$table->index('proveedor_id','fk_empresas_espejos_proveedores1_idx');
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
        
            $table->timestamps();
            $table->softDeletes();
        });
    //Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down(){
       Schema::dropIfExists('empresas_espejos');
     }
}
