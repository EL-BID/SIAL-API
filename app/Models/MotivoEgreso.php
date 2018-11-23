<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotivoEgreso extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = false;

    protected $primaryKey = 'id';
    
    protected $table = 'catalogo_motivo_egreso';  
    protected $fillable = ["descripcion"];
}
