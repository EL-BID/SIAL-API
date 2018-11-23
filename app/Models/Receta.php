<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class Receta extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'recetas';

    //protected $fillable = ["folio","tipo_receta", "fecha_receta", "doctor", "paciente", "diagnostico", "imagen_receta"];

    public function recetaDetalles(){
        return $this->hasMany('App\Models\RecetaDetalle', 'receta_id');
    }

    public function personalMedico(){
        return $this->belongsTo('App\Models\PersonalClues', 'personal_clues_id');
    }

}
