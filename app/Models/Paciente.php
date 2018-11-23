<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    
    protected $primaryKey = 'id';
    
    protected $table = 'pacientes';  
    protected $fillable = ["clues","nombre","sexo","fecha_nacimiento", "hora_nacimiento", "domicilio", "colonia", "municipio_id", "localidad_id", "telefono", "no_expediente", "no_afiliacion", "clues"];

    
    public function Ingresoactivos(){
      return $this->hasMany('App\Models\Admision','paciente_id')->where("estatus_admision", 0);
    }

    public function Ingreso(){
      return $this->hasMany('App\Models\Admision','paciente_id');
    }
    /*public function Ingresoactivos(){
      return $this->hasMany('App\Models\Ingreso','paciente_id')->where("estatus_ingreso_id", 0);
    }

    public function Ingreso()
    {
        return $this->hasMany('App\Models\Ingreso','paciente_id');
    }*/

    public function localidad(){
      return $this->belongsTo('App\Models\Localidad','localidad_id');
    }

    public function responsable()
    {
        return $this->hasOne('App\Models\Responsable','paciente_id');   
    }

    public function areas(){
      return $this->hasMany('App\Models\AreaResponsable','paciente_id');
    }
}
