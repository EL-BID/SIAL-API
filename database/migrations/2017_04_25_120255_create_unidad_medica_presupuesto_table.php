<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnidadMedicaPresupuestoTable extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('unidad_medica_presupuesto', function (Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('clues', 12);
            $table->integer('mes');
            $table->integer('anio');

            $table->integer('presupuesto_id')->unsigned();
            $table->integer('proveedor_id')->unsigned();

            $table->decimal('causes_autorizado',15,2)->default(0);
            $table->decimal('causes_modificado',15,2)->default(0);
            $table->decimal('causes_comprometido',15,2)->default(0);
            $table->decimal('causes_devengado',15,2)->default(0);
            $table->decimal('causes_disponible',15,2)->default(0);

            $table->decimal('no_causes_autorizado',15,2)->default(0);
            $table->decimal('no_causes_modificado',15,2)->default(0);
            $table->decimal('no_causes_comprometido',15,2)->default(0);
            $table->decimal('no_causes_devengado',15,2)->default(0);
            $table->decimal('no_causes_disponible',15,2)->default(0);

            $table->decimal('material_curacion_autorizado',15,2)->default(0);
            $table->decimal('material_curacion_modificado',15,2)->default(0);
            $table->decimal('material_curacion_comprometido',15,2)->default(0);
            $table->decimal('material_curacion_devengado',15,2)->default(0);
            $table->decimal('material_curacion_disponible',15,2)->default(0);

            $table->foreign('presupuesto_id')->references('id')->on('presupuestos');
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
            $table->foreign('clues')->references('clues')->on('unidades_medicas');
            
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
        Schema::dropIfExists('unidad_medica_presupuesto');
    }
}
