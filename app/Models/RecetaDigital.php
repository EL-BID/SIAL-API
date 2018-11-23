<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class RecetaDigital extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'recetas_digitales';

    protected $fillable = ["tipo_receta_id", "fecha_receta", "medico_id", "paciente_id", "diagnostico"];

    public function recetaDigitalDetalles(){
        return $this->hasMany('App\Models\RecetaDigitalDetalles', 'receta_digital_id');
    }

    public function personalMedico(){
        return $this->belongsTo('App\Models\PersonalClues', 'medico_id');
    }

    public function unidadMedica(){
        return $this->belongsTo('App\Models\UnidadMedica', 'clues');
    }

    public function paciente(){
        return $this->belongsTo('App\Models\Paciente', 'paciente_id');
    }

}
