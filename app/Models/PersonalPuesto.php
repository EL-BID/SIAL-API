<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalPuesto extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = false;
    protected $table = 'personal_clues_puesto';

    protected $fillable = [ 'personal_id', 'puesto_id', 'fecha_inicio','fecha_fin'];

    public function personal(){
        return $this->belongsTo('App\Models\PersonalClues', 'personal_id', 'id');
    }

    public function puesto(){
        return $this->hasOne('App\Models\Puesto', "id", "puesto_id");
    }

    public function puesto_firma(){
        return $this->hasOne('App\Models\Puesto', "id", "puesto_id")->where("firma",1);
    }
}
