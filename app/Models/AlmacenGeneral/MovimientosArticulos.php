<?php 
namespace App\Models\AlmacenGeneral;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;

/**
* Modelo MovimientosArticulos
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Modelo `MovimientosArticulos`: Manejo de los grupos de usuario
*
*/
class MovimientosArticulos extends BaseModel {

	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'movimientos_articulos';

    public function Movimientos(){
		return $this->belongsTo('App\Models\AlmacenGeneral\Movimientos','movimiento_id','id');
    }

    public function MovimientoAI(){
		return $this->hasOne('App\Models\AlmacenGeneral\MovimientoArticulosInventario','id');
    }

    public function Articulos(){
		return $this->belongsTo('App\Models\Articulos','articulo_id','id');
    }
}