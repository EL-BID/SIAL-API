<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiposPersonalMetadatos extends BaseModel {

	use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = true;

    protected $primaryKey = 'id';
    
    protected $table = 'tipos_personal_metadatos'; 

	public function TiposPersonal(){
		return $this->belongsTo('App\Models\TiposPersonal','tipo_personal_id','id');
    }

}
