<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AvanceDetalles extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    
    protected $table = 'avance_detalles';  
    protected $fillable = ["nombre","extension", "comentario", "porcentaje", "usuario_id", "avance_id", "peso"];
}
