<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Localidad extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = false;

    
    protected $table = 'catalogo_localidades';  

    public function municipio(){
      return $this->belongsTo('App\Models\Municipio','municipio_id');
    }
}
