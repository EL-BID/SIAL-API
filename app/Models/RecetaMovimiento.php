<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecetaMovimiento extends BaseModel
{
     use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'receta_movimientos';


     public function receta(){
        return $this->belongsTo('App\Models\Receta','receta_id')->with('recetaDetalles');
    } 

}
