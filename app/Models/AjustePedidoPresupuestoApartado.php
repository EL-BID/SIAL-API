<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AjustePedidoPresupuestoApartado extends BaseModel
{
    //
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = ['clues','pedido_id','almacen_id','mes','anio','causes_comprometido','causes_devengado','no_causes_comprometido','no_causes_devengado','material_curacion_comprometido','material_curacion_devengado','usuario_id', 'status'];
    protected $table = 'ajuste_pedidos_presupuesto_apartado';  

    
}
