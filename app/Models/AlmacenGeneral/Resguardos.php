<?php 
namespace App\Models\AlmacenGeneral;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;

/**
* Modelo Resguardos
* 
* @package    Plataforma API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-07-20
*
* Modelo `Resguardos`: Manejo de los grupos de usuario
*
*/
class Resguardos extends BaseModel {

	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'resguardos';

	public function ResguardosArticulos(){
        return $this->hasMany('App\Models\AlmacenGeneral\ResguardosArticulos','resguardos_id')
        ->with("Inventarios", "Resguardos", "InventarioMetadatoUnico");
    }

    public function Usuarios(){
        return $this->belongsTo('App\Models\Usuario','usuario_id','id');
    }

    public function Almacen(){
        return $this->belongsTo('App\Models\Almacen','almacen_id','id');
    }
}