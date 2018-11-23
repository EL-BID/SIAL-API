<?php 
namespace App\Models\AlmacenGeneral;

//use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
* Modelo AlmacenConfiguracion
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Modelo `AlmacenConfiguracion`: Configuracion general del sistema
*
*/
class MovimientoArticulosInventarioBorrador extends BaseModel {	

    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    //protected $guardarIDUsuario = true;

    protected $table = 'inventario_movimiento_articulos_borrador';

	public function Inventarios(){
        return $this->belongsTo('App\Models\AlmacenGeneral\InventarioArticulo','inventario_id','id');
    }
}