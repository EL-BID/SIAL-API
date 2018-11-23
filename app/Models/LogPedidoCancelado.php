<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\Authenticatable;

class LogPedidoCancelado extends BaseModel{
	use SoftDeletes;
    protected $generarID = false;
    //protected $guardarIDUsuario = true;
    protected $fillable = ['servidor_id','pedido_id','usuario_id','total_monto_restante','mes_destino','anio_destino','ip','navegador','updated_at'];
    protected $table = 'log_pedidos_cancelados';
    public $timestamps = false;
    
}