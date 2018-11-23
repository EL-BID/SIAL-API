<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContratoPrecio extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;

    protected $table = 'contratos_precios';
    public $incrementing = true;
    
    protected $fillable = ["id","proveedor_id","contrato_id","tipo_insumo_id","contrato_pedido_id","lote","insumo_medico_clave","precio"];
    protected $casts = ["proveedor_id"=>"integer","contrato_id"=>"integer", "tipo_insumo_id"=>"integer","contrato_pedido_id"=>"integer", "lote"=>"string","insumo_medico_clave" =>"string", "precio"=>"double"];


    public function tipo(){
        return $this->belongsTo('App\Models\TipoInsumo','tipo_insumo_id');
    }
    public function insumo(){
        return $this->belongsTo('App\Models\Insumo', 'insumo_medico_clave','clave');
    }
}