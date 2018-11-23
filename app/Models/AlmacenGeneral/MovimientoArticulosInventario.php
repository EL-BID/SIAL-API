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
* @author     
* @created    2017-07-20
*
* Modelo 
*
*/
class MovimientoArticulosInventario extends BaseModel {	

    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

	protected $table = 'inventario_movimiento_articulos';

	public function Inventarios(){
        return $this->belongsTo('App\Models\AlmacenGeneral\InventarioArticulo','inventario_id','id');
    }
}