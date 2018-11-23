<?php 
namespace App\Models\AlmacenGeneral;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;

/**
* Modelo Movimientos
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Modelo `Movimientos`: Manejo de los grupos de usuario
*
*/
class Movimientos extends BaseModel {

	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'movimientos';

	public function MovimientosArticulos(){
        return $this->hasMany('App\Models\AlmacenGeneral\MovimientosArticulos','movimiento_id')
        ->with("Articulos");
    }


    public function TiposMovimientos(){
		return $this->belongsTo('App\Models\TiposMovimientos','tipo_movimiento_id','id');
    }

    public function Programa(){
		return $this->belongsTo('App\Models\Programa','programa_id','id');
    }

    public function Usuarios(){
        return $this->belongsTo('App\Models\Usuario','usuario_id','id');
    }
}