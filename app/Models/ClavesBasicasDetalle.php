<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClavesBasicasDetalle extends BaseModel
{
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;
    protected $table = 'claves_basicas_detalles';

    protected $fillable = [ 'claves_basicas_id', 'insumo_medico_clave'];


    public function insumo(){
        return $this->belongsTo('App\Models\Insumo','insumo_medico_clave','clave');        
    }

     public function insumoConDescripcion(){
        return $this->belongsTo('App\Models\Insumo','insumo_medico_clave','clave')->conDescripciones()->withTrashed();        
    }

    
}
