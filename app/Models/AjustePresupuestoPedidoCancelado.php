<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AjustePresupuestoPedidoCancelado extends BaseModel
{
    //
  	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = ['unidad_medica_presupuesto_id','pedido_id','clues','mes_origen','anio_origen','mes_destino','anio_destino','causes','no_causes','material_curacion','insumos','status'];
    protected $table = 'ajuste_presupuesto_pedidos_cancelados';  

    public function pedido(){
      return $this->hasOne('App\Models\Pedido','id','pedido_id');
    }
}
