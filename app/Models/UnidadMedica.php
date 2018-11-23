<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadMedica extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    public $incrementing = false;

    protected $primaryKey = 'clues';
    
    protected $table = 'unidades_medicas';  
    protected $fillable = ["clues","nombre","activa","jurisdiccion_id"];
    protected $casts = ["activa"=>"boolean","clues"=>"string","director_id"=>"string","es_offline"=>"boolean","jurisdiccion_id"=>"integer","nombre"=>"string","tipo"=>"string"];
    

    public function almacenes(){
      return $this->hasMany('App\Models\Almacen','clues');
    }

    public function director(){
      return $this->hasOne('App\Models\PersonalClues','id','director_id');
    }

    public function jurisdiccion(){
      return $this->hasOne('App\Models\Jurisdiccion','id','jurisdiccion_id');
    } 

    public function clues_servicios(){
      return $this->hasMany('App\Models\CluesServicio','clues');
    }
}