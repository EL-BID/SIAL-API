<?php 
namespace App\Models\AlmacenGeneral;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;

/**
* Modelo MovimientoArticulosBorrador
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     
* @created    2017-07-20
*
* Modelo `MovimientoArticulosBorrador`: 
*
*/
class MovimientoArticulosBorrador extends BaseModel {

	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    //protected $guardarIDUsuario = true;

    protected $primaryKey = 'id';

    protected $table = 'movimiento_articulos_borrador';

    public function Movimiento(){
		return $this->belongsTo('App\Models\AlmacenGeneral\Movimiento','movimiento_id','id');
    }

    public function Articulos(){
		return $this->belongsTo('App\Models\Articulos','articulo_id','id');
    }

    public function Inventarios(){
        return $this->belongsToMany('App\Models\AlmacenGeneral\InventarioArticulo', 'inventario_movimiento_articulos', 'movimiento_articulos_id', 'inventario_id')->with("InventarioMetadato", "Programa", "ResguardosArticulos", "InventarioMetadatoUnico");
    }
}