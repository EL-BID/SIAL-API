<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogoAreaResponsable extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = false;

    protected $primaryKey = 'id';
    
    protected $table = 'catalogo_area_responsable';  
    protected $fillable = ["descripcion"];
}

