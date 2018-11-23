<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AvanceUsuarioPrivilegio extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = false;
    
    protected $table = 'avance_usuario_privilegio';  
    protected $fillable = ["usuario_id","avance_id", "ver", "agregar", "editar", "eliminar"];

    public function usuario(){
      return $this->belongsTo('App\Models\Usuario','usuario_id');
    }
}
