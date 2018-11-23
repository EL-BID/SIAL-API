<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AvanceVisualizacion extends BaseModel
{
     use SoftDeletes;
    
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = false;
    
    protected $table = 'avance_visualizacion';  
    protected $fillable = ["avance_id","usuario_id", 'updated_at'];
}
