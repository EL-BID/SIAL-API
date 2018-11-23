<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Generico extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    //protected $guardarIDUsuario = false;
    //public $incrementing = false;
    
    //protected $table = 'insumos_medicos';  
    //protected $primaryKey = 'clave';
    protected $fillable = ["id","tipo","nombre","es_cuadro_basico"];
    
    //Hack para relacionar los insumos con los grupos, ya que se adecuo a una relación de muchos a muchos, esto nos podria ayudar a realizar la busqueda por grupo (Esta relación pertenece al modelo de Generico, pero lo podemos utilizar aca)
    public function grupos(){
        return $this->belongsToMany('App\Models\GrupoInsumo', 'generico_grupo_insumo', 'generico_id', 'grupo_insumo_id');
    }
}