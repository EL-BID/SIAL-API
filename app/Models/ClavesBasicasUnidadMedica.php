<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClavesBasicasUnidadMedica extends BaseModel
{
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;
    protected $table = 'claves_basicas_unidades_medicas';

    protected $fillable = [ 'claves_basicas_id', 'clues'];


    public function unidadMedica(){
        return $this->belongsTo('App\Models\UnidadMedica','clues','clues');    
    }

    
}
