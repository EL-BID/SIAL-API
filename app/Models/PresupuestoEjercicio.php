<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PresupuestoEjercicio extends BaseModel
{
    //use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;
     
    protected $fillable = [ "ejercicio", "causes", "no_causes", "activo", "factor_meses"];
    protected $casts = [
        "ejercicio"=>"integer",
        "factor_meses"=>"integer",
        "activo" =>"boolean",
        "causes"=>"double",
        "no_causes"=>"double",
    ];
    protected $table = 'presupuesto_ejercicio';

    public function presupuestoUnidadesMedicas(){
        return $this->hasMany('App\Models\PresupuestoUnidadMedica','presupuesto_id');
    }
    
}
