<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;


class MovimientoAjuste extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'movimiento_ajustes';

    public function movimientoAjusteInsumo(){
        return $this->hasOne('App\Models\Insumo','clave','clave_insumo_medico');
    } 
   
}