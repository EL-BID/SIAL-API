<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrupoInsumo extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    //protected $guardarIDUsuario = false;
    //public $incrementing = false;
    
    protected $table = 'grupos_insumos';  
    //protected $primaryKey = 'clave';
    protected $fillable = ["id","tipo","nombre","numero"];
    
    //Hack para relacionar los insumos con los grupos, ya que se adecuo a una relación de muchos a muchos, esto nos podria ayudar a realizar la busqueda por grupo (Esta relación pertenece al modelo de Generico, pero lo podemos utilizar aca)
    public function genericos(){
        return $this->belongsToMany('App\Models\Generico', 'generico_grupo_insumo', 'grupo_insumo_id', 'generico_id');
    }
}