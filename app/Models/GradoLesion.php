<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradoLesion extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = false;

    protected $primaryKey = 'id';
    
    protected $table = 'catalogo_grado_lesion';  
    protected $fillable = ["descripcion"];
}