<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Almacen extends BaseModel
{
    //
  	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = ["created_at","updated_at"];
    protected $table = 'almacenes';
    protected $casts = ["clues"=>"string","encargado_almacen_id"=>"string","id"=>"string","nivel_almacen"=>"integer","nombre"=>"string","proveedor_id"=>"integer","servidor_id"=>"string","subrogado"=>"boolean","tipo_almacen"=>"string","unidosis"=>"boolean"];
    
    public function usuarios(){
      return $this->hasMany('App\Models\AlmacenUsuarios','almacen_id');
    }

    public function encargado(){
      return $this->hasOne('App\Models\PersonalClues','id','encargado_almacen_id');
    }

    public function tiposMovimientos(){
      return $this->hasMany('App\Models\AlmacenTiposMovimientos','almacen_id')->with('TipoMovimiento');
    }

    public function AlmacenTiposMovimientos(){
      return $this->hasMany('App\Models\AlmacenTiposMovimientos','almacen_id');
    }

    public function unidadMedica(){
      return $this->belongsTo('App\Models\UnidadMedica','clues','clues');
    }
   
}
