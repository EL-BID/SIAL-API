<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoTriage extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = false;

    protected $primaryKey = 'id';
    
    protected $table = 'catalogo_estado_triage';  
    protected $fillable = ["descripcion"];
}