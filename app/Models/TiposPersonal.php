<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiposPersonal extends BaseModel {

	use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = true;

    protected $primaryKey = 'id';
    
    protected $table = 'tipos_personal'; 

    public function TiposPersonalMetadatos(){
		return $this->hasmany('App\Models\TiposPersonalMetadatos','tipo_personal_id','id');
    }
}
