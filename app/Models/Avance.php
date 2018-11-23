<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Avance extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = false;
    
    protected $table = 'avances';  
    protected $fillable = ["tema","responsable", "area", "comentario", "usuario_id", 'prioridad', 'estatus'];

    public function avanceDetalles(){
      return $this->hasMany('App\Models\AvanceDetalles','avance_id');
    }

    public function usuario(){
      return $this->belongsTo('App\Models\Usuario','usuario_id');
    }
}
