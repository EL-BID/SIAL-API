<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jurisdiccion extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    //protected $guardarIDUsuario = false;
    public $incrementing = true;

    
    protected $table = 'jurisdicciones';  
    protected $fillable = ["numero","nombre"];

    public function unidadesMedicas(){
      return $this->hasMany('App\Models\UnidadMedica','jurisdiccion_id');
    }

}