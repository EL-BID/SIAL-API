<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalClues extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = false;
    protected $table = 'personal_clues';

    protected $fillable = [ 'clues', 'usuario_asignado', 'nombre','celular', 'email'];

    public function puesto(){
        return $this->belongsToMany('App\Models\Puesto','personal_clues_puesto', "personal_id", "puesto_id")
        			->withpivot("id");
    }

    public function unidad(){
        return $this->belongsTo('App\Models\UnidadMedica','clues', "clues");
    }

    public function PersonalCluesMetadatos(){
        return $this->hasMany('App\Models\PersonalCluesMetadatos','personal_clues_id', "id");
    }

    public function TiposPersonal(){
        return $this->belongsTo('App\Models\TiposPersonal','tipo_personal_id', "id");
    }
    
}
