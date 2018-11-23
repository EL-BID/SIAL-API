<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistorialMovimientoTransferencia extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'historial_movimientos_transferencias';

    protected $fillable = ['almacen_origen','almacen_destino','clues_origen','clues_destino','pedido_id','evento','movimiento_id','total_unidades','total_claves','total_monto','fecha_inicio_captura','fecha_finalizacion'];

    public function scopeMovimientoConStatus($query){
        $query->select('historial_movimientos_transferencias.*','movimientos.almacen_id','movimientos.tipo_movimiento_id','movimientos.status','movimientos.fecha_movimiento','movimientos.observaciones')
            ->leftjoin('movimientos','movimientos.id','=','historial_movimientos_transferencias.movimiento_id');
    }

    public function movimiento(){
        return $this->belongsTo('App\Models\Movimiento','movimiento_id');
    }

    public function pedido(){
        return $this->belongsTo('App\Models\Pedido','pedido_id');
    }

}