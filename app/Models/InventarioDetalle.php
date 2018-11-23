<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioDetalle extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'inventarios_detalles';

    protected $fillable = ['insumo_medico_clave','codigo_barras','lote','fecha_caducidad','cantidad','precio_unitario','monto'];

    public function detalles(){
        return $this->hasOne('App\Models\Insumo','clave','insumo_medico_clave')->withTrashed();
    }
}
