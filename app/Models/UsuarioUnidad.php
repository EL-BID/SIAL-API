<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class UsuarioUnidad extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    //protected $guardarIDUsuario = false;
    public $incrementing = true;

    
    protected $table = 'usuario_unidad_medica';  
    protected $fillable = ["usuario_id","clues"];
}
