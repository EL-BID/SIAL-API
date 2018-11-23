<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClavesBasicas extends BaseModel
{
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;
    protected $table = 'claves_basicas';

    protected $fillable = [ 'nombre', 'clues','tipo'];
   
    public function detalles(){
        return $this->hasMany('App\Models\ClavesBasicasDetalle','claves_basicas_id');
    }

    public function unidadesMedicas(){
        return $this->hasMany('App\Models\ClavesBasicasUnidadMedica','claves_basicas_id');
    }

    
}
