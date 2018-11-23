<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PresupuestoUnidadMedica extends BaseModel
{
    //use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    protected $fillable = [ 
        "presupuesto_id",
        "clues", 
        "causes_autorizado", 
        "causes_modificado",
        "causes_comprometido",
        "causes_devengado",
        "causes_disponible",
        "no_causes_autorizado",
        "no_causes_modificado",
        "no_causes_comprometido",
        "no_causes_devengado",
        "no_causes_disponible", 
    ];
    
    protected $casts = [
        "presupuesto_id"=>"integer",
        "clues"=>"string",        
        "causes_autorizado"=>"double",
        "causes_modificado"=>"double",
        "causes_comprometido"=>"double",
        "causes_devengado"=>"double",
        "causes_disponible"=>"double",
        "no_causes_autorizado"=>"double",
        "no_causes_modificado"=>"double",
        "no_causes_comprometido"=>"double",
        "no_causes_devengado"=>"double",
        "no_causes_disponible"=>"double",
    ];

    protected $table = 'presupuesto_unidad_medica';

    public function unidadMedica(){
        return $this->belongsTo('App\Models\UnidadMedica','clues','clues');
    }
}
