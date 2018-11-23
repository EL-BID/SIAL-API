<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecetaDigitalDetalles extends BaseModel
{
     use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'receta_digital_detalles';

    protected $fillable = ["receta_digital_id", "clave_insumo_medico", "cantidad", "dosis", "frecuencia","duracion"];

    public function insumo(){
        return $this->hasOne('App\Models\Insumo','clave','clave_insumo_medico')->conDescripciones()->withTrashed();
    }

}
