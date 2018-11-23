<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use JWTAuth;
use App\Http\Requests;

use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

use App\Models\Almacen;
use App\Models\Usuario;

use App\Models\AlmacenUsuarios;
use App\Models\Almacenes;


/** 
* Controlador Almacen
* 
* @package    SIAL API
* @subpackage Controlador
* @author     Joram Roblero Pérez <joram.roblero@gmail.com>
* @created    2017-03-22
*
* Controlador `Proveedor`: Controlador  para la administración de proveedores
*
*/
class AlmacenController extends Controller
{
    /**
	 * Inicia el contructor para los permisos de visualizacion
	 *	 
	 */
     /*
    public function __construct()
    {
        $this->middleware('permisos:GET.LISTAR_USUARIOS|POST.ADMIN_USUARIOS|PUT.ADMIN_USUARIOS|DELETE.ADMIN_USUARIOS');
    }
    */
    /**
	 * Muestra una lista de los recurso según los parametros a procesar en la petición.
	 *
	 * <h3>Lista de parametros Request:</h3>
	 * <Ul>Paginación
	 * <Li> <code>$pagina</code> numero del puntero(offset) para la sentencia limit </ li>
	 * <Li> <code>$limite</code> numero de filas a mostrar por página</ li>	 
	 * </Ul>
	 * <Ul>Busqueda
	 * <Li> <code>$valor</code> string con el valor para hacer la busqueda</ li>
	 * <Li> <code>$order</code> campo de la base de data por la que se debe ordenar la información. Por Defaul es ASC, pero si se antepone el signo - es de manera DESC</ li>	 
	 * </Ul>
	 *
	 * Conceptos ordenamiento con respecto a id:
	 * <code>
	 * http://url?pagina=1&limite=5&order=id ASC 
	 * </code>
	 * <code>
	 * http://url?pagina=1&limite=5&order=-id DESC
	 * </code>
	 *
	 * Todo Los parametros son opcionales, pero si existe pagina debe de existir tambien limite
	 * @return Response 
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 404, "messages": "No hay resultados"),status) </code>
	 */
    public function index(Request $request)
    {
        $parametros = Input::all();

        //if(count($parametros)){
        if (isset($parametros['q'])) {
            
            $almacenes =  Almacen::where(function($query) use ($parametros) {
                $query->where('nombre','LIKE',"%".$parametros['q']."%")
                   ->orWhere('tipo_almacen','LIKE',"%".$parametros['q']."%")
                    ->orWhere('clues','LIKE',"%".$parametros['q']."%");
            });
        } else {
            $almacenes = Almacen::getModel();
        }

        if(isset($parametros['filtro_usuario'])){
            $almacen = Almacen::find($request->get('almacen_id'));

            $almacenes = $almacenes->where('clues',$almacen->clues)->where('nivel_almacen',1)->orderBy('nivel_almacen')->orderBy('tipo_almacen')->orderBy('nombre');
            //$obj =  JWTAuth::parseToken()->getPayload();
            //$usuario = Usuario::find($obj->get('id'));
            //$almacenes_id = $usuario->almacenes()->lists('almacenes.id');
            //$almacenes = $almacenes->whereNotIn('id',$almacenes_id);
        }else{
            $almacenes = $almacenes->with('usuarios','tiposMovimientos');
        }
        
        if(isset($parametros['tipo'])){
            $almacenes = $almacenes->where('tipo_almacen',$parametros['tipo']);
        }

        if(isset($parametros['subrogado'])){
             $almacenes = $almacenes->where('subrogado',$parametros['subrogado']);
        }
        //$pedido = Pedido::with("insumos", "acta", "TipoInsumo", "TipoPedido")->get();
        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 25;
            $almacenes = $almacenes->paginate($resultadosPorPagina);
        } else {
            $almacenes = $almacenes->get();
        }
        //}else{
           // $almacenes = Almacen::all();
        //}
        return Response::json([ 'data' => $almacenes],200);

        /*
        $parametros = Input::only('q','page','per_page');
        if($parametros['q']) {
            $data = Almacenes::with('AlmacenUsuarios','AlmacenTiposMovimientos')->where(function($query) use ($parametros) {
                $query->where('nombre','LIKE',"%".$parametros['q']."%");
            });
        }else{
            $data = Almacenes::with('AlmacenUsuarios','AlmacenTiposMovimientos');
        }

        if(isset($parametros['page'])){
            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $data = $data->paginate($resultadosPorPagina);
        }else{
            $data = $data->get();
        }

        if(count($data) <= 0){
            return Response::json(array("status" => 404,"messages" => "No hay resultados"), 200);
        }else{
            return Response::json(array("status" => 200,"messages" => "Operación realizada con exito", "data" => $data, "total" => count($data)), 200);
        }
        */
    }

    /**
	 * Crear un nuevo registro en la base de data con los data enviados
	 *
	 * <h4>Request</h4>
	 * Recibe un input request tipo json de los data a almacenar en la tabla correspondiente
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 201, "messages": "Creado", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 500, "messages": "Error interno del servidor"),status) </code>
	 */
    public function store(Request $request)
    {
        var_dump(  $request  ); die();
        /*
        $validacion = $this->ValidarParametros("", NULL, Input::json()->all());
		if($validacion != ""){
			return Response::json(['error' => $validacion], HttpResponse::HTTP_CONFLICT);
		}
        $datos = (object) Input::json()->all();	
        $success = false;

        DB::beginTransaction();
        try{

            $data = new Proveedores;

            $success = $this->campos($datos, $data);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 200);
        } 
        if ($success){
            DB::commit();
            return Response::json(array("status" => 201,"messages" => "Creado","data" => $data), 201);
        } 
        else{
            DB::rollback();
            return Response::json(array("status" => 409,"messages" => "Conflicto"), 200);
        }
        */
    }

    /**
	 * Devuelve la información del registro especificado.
	 *
	 * @param  int  $id que corresponde al identificador del recurso a mostrar
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 404, "messages": "No hay resultados"),status) </code>
	 */
    public function show($id)
    {
        /*
        $data = Proveedores::with('Contactos','ComunicacionContacto')->find($id);
        if(!$data){
			return Response::json(array("status" => 404,"messages" => "No hay resultados"), 200);
		} 
		else{
			return Response::json(array("status" => 200,"messages" => "Operación realizada con exito","data" => $data), 200);
		}
        */
    }

    /**
	 * Actualizar el  registro especificado en el la base de data
	 *
	 * <h4>Request</h4>
	 * Recibe un Input Request con el json de los data
	 *
	 * @param  int  $id que corresponde al identificador del dato a actualizar 	 
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 304, "messages": "No modificado"),status) </code>
	 */
    public function update(Request $request, $id)
    {
/*
        $validacion = $this->ValidarParametros("", NULL, Input::json()->all());
		if($validacion != ""){
			return Response::json(['error' => $validacion], HttpResponse::HTTP_CONFLICT);
		}
        $data = Proveedores::find($id);
        $datos = (object) Input::json()->all();		

        $success = false;

        DB::beginTransaction();
        try{
            if(!$data){
                return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
            }
            $success = $this->campos($datos, $data);

        } catch (\Exception $e) {
            DB::rollback();
            return Response::json(["status" => 500, 'error' => $e->getMessage()], 200);
        } 
        if($success){
			DB::commit();
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito", "data" => $data), 200);
		} 
		else {
			DB::rollback();
			return Response::json(array("status" => 304, "messages" => "No modificado"),200);
		}
        */
    }
    
     /**
	 * Elimine el registro especificado del la base de data (softdelete).
	 *
	 * @param  int  $id que corresponde al identificador del dato a eliminar
	 *
	 * @return Response
	 * <code style="color:green"> Respuesta Ok json(array("status": 200, "messages": "Operación realizada con exito", "data": array(resultado)),status) </code>
	 * <code> Respuesta Error json(array("status": 500, "messages": "Error interno del servidor"),status) </code>
	 */
    public function destroy($id)
    {
        /*
        $success = false;
        DB::beginTransaction();
        try {
			$data = Proveedores::find($id);
			if($data->delete())
			    $success = true;
		} catch (Exception $e) {
            DB::rollback(); 
		    return Response::json(["status" => 500, 'error' => $e->getMessage()], 200);
		}
        if ($success){
			DB::commit();
			return Response::json(array("status" => 200, "messages" => "Operación realizada con exito","data" => $data), 200);
		} 
		else {
			DB::rollback();
			return Response::json(array("status" => 404, "messages" => "No se encontro el registro"), 200);
		}

        */
    }

    /**
	 * Funcion que recive todos los campos del formulario que se envia desde el cliente
	 *
	 * @param  Request  $datos que corresponde a los datos del form enviados por el cliente
     * @param  Request  $data que corresponde a el objeto ORM
	 *
	 * @return Response
	 * <code> Respuesta Error json con los errores encontrados </code>
	 */

     /*
    private function campos($datos, $data){
		$success = false;
        //comprobar que el servidor id no me lo envian por parametro, si no poner el servidor por default de la configuracion local, si no seleccionar el servidor del parametro
        $servidor_id = property_exists($datos, "servidor_id") ? $datos->servidor_id : env('SERVIDOR_ID');
        
        //agregar al modelo los datos
        $data->razon_social      =  $datos->razon_social;
        $data->rfc               =  $datos->rfc;
        $data->direccion         =  property_exists($datos, "direccion")     ? $datos->direccion      : '';
        $data->colonia           =  property_exists($datos, "colonia")       ? $datos->colonia        : '';
        $data->codigo_postal     =  property_exists($datos, "codigo_postal") ? $datos->codigo_postal  : '';
        $data->localidad         =  property_exists($datos, "localidad")     ? $datos->localidad      : '';
        $data->municipio         =  property_exists($datos, "municipio")     ? $datos->municipio      : '';
        $data->estado            =  property_exists($datos, "estado")        ? $datos->estado         : '';
        $data->pais              =  property_exists($datos, "pais")          ? $datos->pais           : '';
        $data->activo            =  property_exists($datos, "activo")        ? $datos->activo         : '';

        // si se guarda el maestro tratar de guardar el detalle   
        if( $data->save() ){
            $success = true;
        
            //verificar si existe contacto, en caso de que exista proceder a guardarlo
            if(property_exists($datos, "contactos")){
                
                //limpiar el arreglo de posibles nullos
                $detalle = array_filter($datos->contactos, function($v){return $v !== null;});

                //borrar los datos previos de articulo para no duplicar información
                Contactos::where("proveedor_id", $data->id)->delete();

                //recorrer cada elemento del arreglo
                foreach ($detalle as $key => $value) {
                    //validar que el valor no sea null
                    if($value != null){
                        //comprobar si el value es un array, si es convertirlo a object mas facil para manejar.
                        if(is_array($value))
                            $value = (object) $value;

                        //comprobar que el dato que se envio no exista o este borrado, si existe y esta borrado poner en activo nuevamente
                        DB::update("update contactos set deleted_at = null where proveedor_id = ".$data->id." and nombre = '".$value->nombre."' and puesto = '".$value->puesto."'");
                        
                        //si existe el elemento actualizar
                        $item = Contactos::where("proveedor_id", $data->id)->where("nombre", $value->nombre)->where("puesto", $value->puesto)->first();
                        //si no existe crear
                        if(!$item)
                            $item = new Contactos;

                        //llenar el modelo con los datos

                        $item->nombre          = $value->nombre; 
                        $item->proveedor_id    = $data->id; 
                        $item->puesto          = $value->puesto; 

                        $item->save();         
                    }
                }
            }

            //verificar si existe comunicacion_contacto, en caso de que exista proceder a guardarlo
            if(property_exists($datos, "comunicacion_contacto")){
                
                //limpiar el arreglo de posibles nullos
                $detalle = array_filter($datos->comunicacion_contacto, function($v){return $v !== null;});

                //borrar los datos previos de articulo para no duplicar información
                ComunicacionContacto::where("proveedor_id", $data->id)->delete();

                //recorrer cada elemento del arreglo
                foreach ($detalle as $key => $value) {
                    //validar que el valor no sea null
                    if($value != null){
                        //comprobar si el value es un array, si es convertirlo a object mas facil para manejar.
                        if(is_array($value))
                            $value = (object) $value;

                        //comprobar que el dato que se envio no exista o este borrado, si existe y esta borrado poner en activo nuevamente
                        DB::update("update comunicacion_contactos set deleted_at = null where proveedor_id = ".$data->id." and contacto_id = ".$value->contacto_id." and medio_contacto_id = ".$value->medio_contacto_id." and valor = '".$value->valor."'");
                        
                        //si existe el elemento actualizar
                        $item = ComunicacionContacto::where("proveedor_id", $data->id)->where("contacto_id", $value->contacto_id)->where("medio_contacto_id", $value->medio_contacto_id)->where("valor", $value->valor)->first();
                        //si no existe crear
                        if(!$item)
                            $item = new ComunicacionContacto;

                        //llenar el modelo con los datos

                        $item->tipo                 = $value->tipo; 
                        $item->proveedor_id         = $data->id; 
                        $item->contacto_id          = $value->contacto_id; 
                        $item->medio_contacto_id    = $value->medio_contacto_id; 
                        $item->valor                = $value->valor; 

                        $item->save();         
                    }
                }
            }            
        }
        return $success;
    }
*/
    /**
	 * Validad los parametros recibidos, Esto no tiene ruta de acceso es un metodo privado del controlador.
	 *
	 * @param  Request  $request que corresponde a los parametros enviados por el cliente
	 *
	 * @return Response
	 * <code> Respuesta Error json con los errores encontrados </code>
	 */


    /* 
	private function ValidarParametros($key, $id, $request){ 
        $mensajes = [
            'required'      => "required",
            'email'         => "email",
            'unique'        => "unique"
        ];

        $reglas = [
            'razon_social'  => 'required|min:3|max:255',
            'rfc'           => 'required'
        ];
        
		$v = \Validator::make($request, $reglas, $mensajes );

			
        if ($v->fails()){
			$mensages_validacion = array();
            foreach ($v->errors()->messages() as $indice => $item) { // todos los mensajes de todos los campos
			$msg_validacion = array();
				foreach ($item as $msg) {
					array_push($msg_validacion, $msg);
				}
				array_push($mensages_validacion, array($indice.''.$key => $msg_validacion));
			}
			return $mensages_validacion;
        }else{
            return ;
        }
	}
    */
}
