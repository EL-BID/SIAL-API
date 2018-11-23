<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contactos extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    
    public function ComunicacionContacto(){
        return $this->hasMany('App\Models\ComunicacionContacto','contacto_id');
    }
}