<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = true;
    
    protected $table = 'roles';  
    protected $fillable = ["nombre","es_offline"];
    
    
    public function permisos(){
      return $this->belongsToMany('App\Models\Permiso', 'permiso_rol', 'rol_id', 'permiso_id');
    }

    public function usuarios(){
      return $this->belongsToMany('App\Models\Usuario', 'rol_usuario', 'rol_id', 'usuario_id');
    }
}