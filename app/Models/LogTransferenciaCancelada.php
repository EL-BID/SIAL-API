<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\Authenticatable;

class LogTransferenciaCancelada extends BaseModel{
	use SoftDeletes;
    protected $generarID = false;
    //protected $guardarIDUsuario = true;
    protected $fillable = ['servidor_id','pedido_id','usuario_id','motivos','ip','navegador','updated_at'];
    protected $table = 'log_transferencias_canceladas';
    public $timestamps = false;
    
}