<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permiso extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    protected $fillable = ["id","descripcion","grupo","su","created_at","updated_at"];
    
    public function roles(){
		return $this->belongsToMany('App\Models\Rol', 'permiso_rol', 'permiso_id', 'rol_id');
	}
}