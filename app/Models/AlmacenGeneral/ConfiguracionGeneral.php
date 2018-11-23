<?php 
namespace App\Models\AlmacenGeneral;

use Illuminate\Database\Eloquent\Model;
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
class ConfiguracionGeneral extends Model {	
	protected $table = 'configuracion_general';
}