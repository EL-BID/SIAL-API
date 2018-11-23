<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contrato extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    //protected $guardarIDUsuario = false;
    public $incrementing = true;
    
    protected $table = 'contratos';  
    //protected $primaryKey = 'clave';
    protected $fillable = ["id","proveedor_id","monto_minimo","monto_maximo","fecha_inicio","fecha_fin","activo"];
    protected $casts = ["proveedor_id"=>"integer","monto_minimo"=>"double", "monto_maximo"=>"double","fecha_inicio"=>"string", "fecha_fin"=>"string","activo" =>"boolean"];
    
    public function proveedor(){
        return $this->belongsTo('App\Models\Proveedor');
    }

    public function unidadesMedicas(){
        return $this->belongsToMany('App\Models\UnidadMedica', 'contrato_clues', 'contrato_id', 'clues');
    }

    public function precios(){
        return $this->hasMany('App\Models\ContratoPrecio','contrato_id','id');
    }    
}