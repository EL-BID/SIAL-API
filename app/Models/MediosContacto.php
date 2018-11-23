<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediosContacto extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    
    public function roles(){ 
		return $this->belongsToMany('App\Models\Rol', 'permiso_rol', 'permiso_id', 'rol_id');
	}
}