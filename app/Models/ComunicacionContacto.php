<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComunicacionContacto extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;

    public function MediosContacto()
    {
		  return $this->belongsTo('App\Models\MediosContacto','medio_contacto_id');
    }
}