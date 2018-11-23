<?php

/*
 * Este archivo es parte del procedimiento de sincronizacion de servidores offline
 *
 * (c) Hugo César Gutiérrez Corzo <hugo.corzo@outlook.com>
 *
 */


return [
    'api_version' => '1.0',
    /*
    |--------------------------------------------------------------------------
    | Catálogos cargados en el servidor
    |--------------------------------------------------------------------------
    |
    | Esta opcion devuelve la lista de catálogos que están implementados en
    | la versión instalada en el servidor local y sirve para que al momento de
    | sincronizar con el servidor remoto, se detecten las tablas que pueden ser r
    | comparadas con su ultima fecha de actualizacion.
    |
    */

    'catalogos' => [
        'catalogo_area_responsable',
        'catalogo_estado_triage',
        'catalogo_grado_lesion',
        'catalogo_localidades',
        'catalogo_motivo_egreso',
        'catalogo_municipios',
        'configuracion_general',
        'tipos_insumos',
        'tipos_movimientos',
        'tipos_pedidos',
        'tipos_personal',
        'tipos_personal_metadatos',
        'tipos_recetas',
        'tipos_sustancias',
        //'tipos_unidad', #no esta en migrations
        'unidades_medida',
        'vias_administracion',
        'medios_contacto',
        'jurisdicciones',
        'marcas',
        'turnos',
        'especialidades',
        'factores_riesgo_embarazo',
        'formas_farmaceuticas',
        'servicios',
        'genericos',
        'grupos_insumos',
        'generico_grupo_insumo', #updated
        'presupuestos',
        'programas',
        'proveedores',
        'puestos',
        'precios_base',
        'precios_base_detalles',
        'presentaciones_medicamentos',
        'presentaciones_sustancias',
        'categorias',
        'categorias_metadatos',
        'organismos',
        //'unidades_medicas',
        //'areas', #no esta en migrations
        'articulos',
        'insumos_medicos',
        'material_curacion',
        'medicamentos',
        'informacion_importante_medicamentos',
        'insumo_medico_especialidad',
        'insumo_medico_servicio',
        'listas_insumos',
        'lista_insumo_detalle',
        'auxiliares_diagnostico',
        'auxiliares_diagnostico_detalles_equipos',
        'claves_basicas',
        'claves_basicas_detalles',
        'claves_basicas_unidades_medicas',
        'comunicacion_contactos',
        'contratos',
        'contrato_clues',
        'contrato_proveedor',#updated
        'contratos_pedidos',
        'contratos_precios',
        'empresas_espejos',
        'extensiones_contratos',
        'roles',
        'permisos',
        'permiso_rol',
        'sustancias_laboratorio',
        'unidad_medica_abasto_configuracion',
        //'unidad_medica_presupuesto'
    ],

    /*
    |--------------------------------------------------------------------------
    | Tablas para sincronizar con el servidor remoto
    |--------------------------------------------------------------------------
    |
    | Esta opcion devuelve la lista de tablas que están implementados en
    | la versión instalada en el servidor local y sean estas las que se utilicen
    | en el proceso de sincronización con el servidor remoto. 
    | Dichas tablas deben ser ordenadas de manera ascendente para no comprometer
    | las llaves foráneas y marque error la sincronización.
    |
    */

    'tablas' => [
        'usuarios',
        //'personal_clues',
        'personal_clues_metadatos',
        'personal_clues_puesto',
        //'almacenes',
        'almacenes_servicios',
        'almacen_tipos_movimientos',
        'pedidos',
        'pedidos_insumos',
        'actas',
        'pedido_metadatos_sincronizaciones',
        'pedido_proveedor_insumos',
        'pedidos_alternos',
        'pedidos_insumos_clues',
        'clues_claves',
        'clues_servicios',
        'clues_turnos',
        'consumos_promedios',
        'cuadros_distribucion',
        'insumos_maximos_minimos',
        'stock',
        'stock_borrador',
        'movimientos',
        'movimiento_insumos',
        'movimiento_insumos_borrador',
        'movimiento_ajustes',
        'movimiento_detalles',
        'movimiento_metadatos',
        'movimiento_pedido',
        'log_pedido_borrador',
        'log_recepcion_borrador',
        'log_transferencias_canceladas',
        'historial_movimientos_transferencias',
        'negaciones_insumos',
        'pacientes',
        'pacientes_admision',
        'pacientes_area_responsable',
        'pacientes_responsable',
        'recetas',
        'recetas_digitales',
        'receta_detalles',
        'receta_digital_detalles',
        'receta_movimientos',
        'resguardos',
        'inicializacion_inventario',
        'inicializacion_inventario_detalles'
    ],
     /*
    |--------------------------------------------------------------------------
    | Tablas para sincronizar en dos direcciones
    |--------------------------------------------------------------------------
    |
    | En esta opción se debe especificar la tabla y los campos que se suben 
    | y los campos que se bajan del servidor remoto, asímismo, si hay que hacer
    | algú calculo en el proceso de subida o bajada, hay que escribir el nombre
    | del método que se encuentra en namespace App\Librerias\Sync, ahí se pueden
    | agregar los métodos necerios por si hay que hacer algún calculo en la subida
    | o bajada y especificarlos en la propiedad: calculo_bajada y calculo_subida
    | En las condiciones de subida o bajada, puedes usar la clave: {CLUES_QUE_SINCRONIZA}
    | para que al sincronizar se compare con la clues de quien sincroniza.
    |
    */
    'pivotes' => [
        'unidad_medica_presupuesto' => [
            'campos_subida' => [
                'causes_comprometido',
                'causes_devengado',
                'no_causes_comprometido',
                'no_causes_devengado',
                'material_curacion_comprometido',
                'material_curacion_devengado',
                'insumos_comprometido',
                'insumos_devengado',
                'created_at',
                'updated_at'
            ],
            'campos_bajada' => [
                'causes_autorizado',
                'causes_modificado',
                'causes_comprometido', // Akira: Agregue esto por los almacenes externos ya que estos no pueden comprometer presupuesto, si estoy mal borren esta línea
                'causes_devengado', // Akira: Agregue esto por las oficinas juris que tienen almacen externo ya que estos no pueden devengar presupuesto, si estoy mal borren esta línea
                'no_causes_autorizado',
                'no_causes_modificado',
                'no_causes_comprometido', // Akira: Agregue esto por los almacenes externos ya que estos no pueden comprometer presupuesto, si estoy mal borren esta línea
                'no_causes_devengado', // Akira: Agregue esto por las oficinas juris que tienen almacen externo ya que estos no pueden devengar presupuesto, si estoy mal borren esta línea
                'material_curacion_autorizado',
                'material_curacion_modificado',
                'material_curacion_comprometido', // Akira: Agregue esto por los almacenes externos ya que estos no pueden comprometer presupuesto, si estoy mal borren esta línea
                'material_curacion_devengado', // Akira: Agregue esto por las oficinas juris que tienen almacen externo ya que estos no pueden devengar presupuesto, si estoy mal borren esta línea
                'insumos_autorizado',
                'insumos_modificado',
                'insumos_comprometido', // Akira: Agregue esto por los almacenes externos ya que estos no pueden comprometer presupuesto, si estoy mal borren esta línea
                'insumos_devengado',  // Akira: Agregue esto por las oficinas juris que tienen almacen externo ya que estos no pueden devengar presupuesto, si estoy mal borren esta línea
                'validation',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => '(clues = "'.env('CLUES').'" or clues in (SELECT clues FROM almacenes WHERE externo = 1 and clues_perteneciente="'.env('CLUES').'" ))',       // Si quieren meter mas agrupen todo entre parentesis ( condicion1 AND condicion2 OR condicion3)
            'condicion_bajada' =>'',
            'calculo_subida' => '\App\Librerias\Sync\CalculosPivotesSync::calcularPresupuestoDisponible', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '\App\Librerias\Sync\CalculosPivotesSync::calcularPresupuestoDisponible',  // Esta funcion se ejecuta justo despues de haber bajado a local          
        ],
        'ajuste_presupuesto_pedidos_cancelados' => [
            'campos_subida' => [
                'mes_origen',
                'anio_origen',
                'mes_destion',
                'anio_destino',
                'causes',
                'no_causes',
                'material_curacion',
                'insumos',
                'status',
                'created_at',
                'updated_at',
                'deleted_at'
                // No es necesario poner los campos porque directamente hará un insert de todos los campos la primera vez
                // Pero si se "actualizara esta tabla hipotéticamente" estos serian los campos a subir
            ],
            'campos_bajada' => [
                'status',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => "status = 'P'", // Pendientes
            'condicion_bajada' => "status = 'AR'", // Aplicados en remoto
            'calculo_subida' => '\App\Librerias\Sync\CalculosPivotesSync::calcularAjustePresupuestoPedidosCanceladosRemoto', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '\App\Librerias\Sync\CalculosPivotesSync::calcularAjustePresupuestoPedidosCanceladosLocal',  // Esta funcion se ejecuta justo despues de haber bajado a local                
        ],
        /*'ajuste_presupuesto_pedidos_regresion' => [
            'campos_subida' => [
                'mes_origen',
                'anio_origen',
                'mes_destion',
                'anio_destino',
                'causes',
                'no_causes',
                'material_curacion',
                'insumos',
                'status',
                'created_at',
                'updated_at',
                'deleted_at'
                // No es necesario poner los campos porque directamente hará un insert de todos los campos la primera vez
                // Pero si se "actualizara esta tabla hipotéticamente" estos serian los campos a subir
            ],
            'campos_bajada' => [
                'status',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => "", // Expirado Pendientes
            'condicion_bajada' => "status = 'EP'", // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '\App\Librerias\Sync\CalculosPivotesSync::calcularAjustePresupuestoPedidosRegresionLocal',  // Esta funcion se ejecuta justo despues de haber bajado a local                
        ],'ajuste_pedido_presupuesto_apartado' => [
            'campos_subida' => [
                ''
                // No es necesario poner los campos porque directamente hará un insert de todos los campos la primera vez
                // Pero si se "actualizara esta tabla hipotéticamente" estos serian los campos a subir
            ],
            'campos_bajada' => [
                'clues',
                'pedido_id',
                'almacen_id',
                'mes',
                'anio',
                'causes_comprometido',
                'causes_devengado',
                'no_causes_comprometido',
                'no_causes_devengado',
                'material_curacion_comprometido',
                'material_curacion_devengado',
            ],
            'condicion_subida' => "", // Expirado Pendientes
            'condicion_bajada' => "status = 'AR'", // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '\App\Librerias\Sync\CalculosPivotesSync::calcularAjustePedidosPresupuestoApartadoLocal',  // Esta funcion se ejecuta justo despues de haber bajado a local                
        ],*/
        'personal_clues'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'clues',
                'tipo_personal_id',
                'usuario_asignado',
                'nombre',
                'surte_controlados',
                'licencia_controlados',
                'celular',
                'email',
                'usuario_id',
                'created_at', 
                'updated_at', 
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'clues',
                'tipo_personal_id',
                'usuario_asignado',
                'nombre',
                'surte_controlados',
                'licencia_controlados',
                'celular',
                'email',
                'usuario_id',
                'created_at', 
                'updated_at', 
                'deleted_at'
            ],
            'condicion_subida' => 'clues = "'.env('CLUES').'"', // Pendientes
            'condicion_bajada' => '', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local
        ],
        'almacenes'=>[
            'campos_subida'=>[
                'encargado_almacen_id',
                'usuario_id',
                'updated_at',
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'nivel_almacen',
                'tipo_almacen',
                'clues',
                'clues_perteneciente',
                'subrogado',
                'externo',
                'lista_insumo_id',
                'proveedor_id',
                'unidosis',
                'nombre',
                'encargado_almacen_id',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => 'clues = "'.env('CLUES').'"', // Pendientes
            'condicion_bajada' => '', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local                
        ],
        'unidades_medicas'=>[
            'campos_subida'=>[
                'director_id',
                'updated_at'
            ],
            'campos_bajada'=>[
                'clues',
                'jurisdiccion_id',
                'tipo',
                'nombre',
                'activa',
                'es_offline',
                'director_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => 'clues = "'.env('CLUES').'"', // Pendientes
            'condicion_bajada' => '', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local   
        ],
        'pedidos'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'clues',
                'clues_destino',
                'tipo_insumo_id',
                'tipo_pedido_id',
                'descripcion',
                'pedido_padre',
                'folio',
                'fecha',
                'fecha_concluido',
                'fecha_expiracion',
                'fecha_cancelacion',
                'almacen_solicitante',
                'almacen_proveedor',
                'organismo_dirigido',
                'acta_id',
                'status',
                'recepcion_permitida',
                'observaciones', 
                'usuario_validacion',
                'proveedor_id',
                'presupuesto_id',
                'total_monto_solicitado',
                'total_monto_recibido',
                'total_claves_solicitadas',
                'total_claves_recibidas',
                'total_cantidad_solicitada',
                'total_cantidad_recibida',
                'encargado_almacen_id',
                'director_id',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'clues',
                'clues_destino',
                'tipo_insumo_id',
                'tipo_pedido_id',
                'descripcion',
                'pedido_padre',
                'folio',
                'fecha',
                'fecha_concluido',
                'fecha_expiracion',
                'fecha_cancelacion',
                'almacen_solicitante',
                'almacen_proveedor',
                'organismo_dirigido',
                'acta_id',
                'status',
                'recepcion_permitida',
                'observaciones', 
                'usuario_validacion',
                'proveedor_id',
                'presupuesto_id',
                'total_monto_solicitado',
                'total_monto_recibido',
                'total_claves_solicitadas',
                'total_claves_recibidas',
                'total_cantidad_solicitada',
                'total_cantidad_recibida',
                'encargado_almacen_id',
                'director_id',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            /*'tablas_relacion'=>[
                'pedidos_insumos'=>[
                    'id_padre'=> 'id',
                    'id_local' => 'pedido_id',
                    ''
                ]
            ],*/
            'condicion_subida' => 'tipo_pedido_id = "PEA" AND servidor_id != "'.env('SERVIDOR_ID').'"', // Pendientes
            'condicion_bajada' => 'tipo_pedido_id = "PEA"', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local                
        ],
        'pedidos_insumos'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'pedido_id',
                'tipo_insumo_id',
                'insumo_medico_clave',
                'cantidad_enviada',
                'cantidad_solicitada',
                'cantidad_recibida',
                'precio_unitario',
                'monto_enviado',
                'monto_solicitado',
                'monto_recibido',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'pedido_id',
                'tipo_insumo_id',
                'insumo_medico_clave',
                'cantidad_enviada',
                'cantidad_solicitada',
                'cantidad_recibida',
                'precio_unitario',
                'monto_enviado',
                'monto_solicitado',
                'monto_recibido',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => 'pedido_id in (select id from pedidos where tipo_pedido_id = "PEA") AND servidor_id != "'.env('SERVIDOR_ID').'"', // Pendientes
            'condicion_bajada' => 'pedido_id in (select id from pedidos where tipo_pedido_id = "PEA")', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local    
        ],
        'historial_movimientos_transferencias'=>[
            'campos_subida'=>[
                'id',
                'servidor_id',
                'incremento',
                'almacen_origen',
                'almacen_destino',
                'clues_origen',
                'clues_destino',
                'pedido_id',
                'evento',
                'movimiento_id',
                'total_unidades',
                'total_claves',
                'total_monto',
                'fecha_inicio_captura',
                'fecha_finalizacion',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'servidor_id',
                'incremento',
                'almacen_origen',
                'almacen_destino',
                'clues_origen',
                'clues_destino',
                'pedido_id',
                'evento',
                'movimiento_id',
                'total_unidades',
                'total_claves',
                'total_monto',
                'fecha_inicio_captura',
                'fecha_finalizacion',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => 'pedido_id in (select id from pedidos where tipo_pedido_id = "PEA") AND servidor_id != "'.env('SERVIDOR_ID').'"', // Pendientes
            'condicion_bajada' => 'pedido_id in (select id from pedidos where tipo_pedido_id = "PEA")', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local    
        ],
        'movimientos'=>[
            'campos_subida'=>[
                'id',
                'servidor_id',
                'incremento',
                'almacen_id',
                'tipo_movimiento_id',
                'status',
                'fecha_movimiento', 
                'programa_id',
                'observaciones',
                'cancelado',
                'observaciones_cancelacion',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'servidor_id',
                'incremento',
                'almacen_id',
                'tipo_movimiento_id',
                'status',
                'fecha_movimiento', 
                'programa_id',
                'observaciones',
                'cancelado',
                'observaciones_cancelacion',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => 'id in (select movimiento_id from historial_movimientos_transferencias where pedido_id in (select id from pedidos where tipo_pedido_id = "PEA")) AND servidor_id != "'.env('SERVIDOR_ID').'"', // Pendientes
            'condicion_bajada' => 'id in (select movimiento_id from historial_movimientos_transferencias where pedido_id in (select id from pedidos where tipo_pedido_id = "PEA"))', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local    
        ],
        'movimiento_pedido'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'movimiento_id',
                'pedido_id',
                'recibe',
                'entrega',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'movimiento_id',
                'pedido_id',
                'recibe',
                'entrega',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => 'pedido_id in (select id from pedidos where tipo_pedido_id = "PEA") AND servidor_id != "'.env('SERVIDOR_ID').'"', // Pendientes
            'condicion_bajada' => 'pedido_id in (select id from pedidos where tipo_pedido_id = "PEA")', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local    
        ],
        'movimiento_insumos'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'movimiento_id',
                'tipo_insumo_id',
                'stock_id',
                'clave_insumo_medico',
                'modo_salida',
                'cantidad',
                'cantidad_unidosis',
                'precio_unitario',
                'iva',
                'precio_total',
                'usuario_id',
                'created_at', 
                'updated_at', 
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'movimiento_id',
                'tipo_insumo_id',
                'stock_id',
                'clave_insumo_medico',
                'modo_salida',
                'cantidad',
                'cantidad_unidosis',
                'precio_unitario',
                'iva',
                'precio_total',
                'usuario_id',
                'created_at', 
                'updated_at', 
                'deleted_at'
            ],
            'condicion_subida' => 'movimiento_id in (select movimiento_id from historial_movimientos_transferencias where pedido_id in (select id from pedidos where tipo_pedido_id = "PEA")) AND servidor_id != "'.env('SERVIDOR_ID').'"', // Pendientes
            'condicion_bajada' => 'movimiento_id in (select movimiento_id from historial_movimientos_transferencias where pedido_id in (select id from pedidos where tipo_pedido_id = "PEA"))', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local    
        ],
        'stock'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'almacen_id',
                'clave_insumo_medico',
                'programa_id',
                'marca_id',
                'lote',
                'fecha_caducidad', 
                'codigo_barras',
                'existencia',
                'existencia_unidosis',
                'unidosis_sueltas',
                'envases_parciales',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'almacen_id',
                'clave_insumo_medico',
                'programa_id',
                'marca_id',
                'lote',
                'fecha_caducidad', 
                'codigo_barras',
                'existencia',
                'existencia_unidosis',
                'unidosis_sueltas',
                'envases_parciales',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => 'id in (select stock_id from movimiento_insumos where movimiento_id in (select movimiento_id from historial_movimientos_transferencias where pedido_id in (select id from pedidos where tipo_pedido_id = "PEA"))) AND servidor_id != "'.env('SERVIDOR_ID').'"', // Pendientes
            'condicion_bajada' => 'id in (select stock_id from movimiento_insumos where movimiento_id in (select movimiento_id from historial_movimientos_transferencias where pedido_id in (select id from pedidos where tipo_pedido_id = "PEA")))', // Aplicados en remoto
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local    
        ],
        // Agregar más tablas copiando la estructura anterior

        //Akira: Pedidos en almacenes externos
        'pedidos'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'clues',
                'clues_destino',
                'tipo_insumo_id',
                'tipo_pedido_id',
                'descripcion',
                'pedido_padre',
                'folio',
                'fecha',
                'fecha_concluido',
                'fecha_expiracion',
                'fecha_cancelacion',
                'almacen_solicitante',
                'almacen_proveedor',
                'organismo_dirigido',
                'acta_id',
                'status',
                'recepcion_permitida',
                'observaciones', 
                'usuario_validacion',
                'proveedor_id',
                'presupuesto_id',
                'total_monto_solicitado',
                'total_monto_recibido',
                'total_claves_solicitadas',
                'total_claves_recibidas',
                'total_cantidad_solicitada',
                'total_cantidad_recibida',
                'encargado_almacen_id',
                'director_id',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'clues',
                'clues_destino',
                'tipo_insumo_id',
                'tipo_pedido_id',
                'descripcion',
                'pedido_padre',
                'folio',
                'fecha',
                'fecha_concluido',
                'fecha_expiracion',
                'fecha_cancelacion',
                'almacen_solicitante',
                'almacen_proveedor',
                'organismo_dirigido',
                'acta_id',
                'status',
                'recepcion_permitida',
                'observaciones', 
                'usuario_validacion',
                'proveedor_id',
                'presupuesto_id',
                'total_monto_solicitado',
                'total_monto_recibido',
                'total_claves_solicitadas',
                'total_claves_recibidas',
                'total_cantidad_solicitada',
                'total_cantidad_recibida',
                'encargado_almacen_id',
                'director_id',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => '(clues in (SELECT clues_perteneciente FROM almacenes WHERE externo = 1))',
            'condicion_bajada' => '(clues in (SELECT clues_perteneciente FROM almacenes WHERE externo = 1 AND clues = "{CLUES_QUE_SINCRONIZA}") OR servidor_id= "'.env('SERVIDOR_ID').'")', // Esto funciona muy bien en sync online, pero offline me va abajar todo
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local                
        ],
        'pedidos_insumos'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'pedido_id',
                'tipo_insumo_id',
                'insumo_medico_clave',
                'cantidad_enviada',
                'cantidad_solicitada',
                'cantidad_recibida',
                'precio_unitario',
                'monto_enviado',
                'monto_solicitado',
                'monto_recibido',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'pedido_id',
                'tipo_insumo_id',
                'insumo_medico_clave',
                'cantidad_enviada',
                'cantidad_solicitada',
                'cantidad_recibida',
                'precio_unitario',
                'monto_enviado',
                'monto_solicitado',
                'monto_recibido',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => '(pedido_id in (select id from pedidos where clues in (SELECT clues_perteneciente FROM almacenes WHERE externo = 1)))', // Deberia considerar si dejo vacio los campos de subida o bajada que pasa, total arriba se sincronizan
            'condicion_bajada' => '(pedido_id in (select id from pedidos where (clues in (SELECT clues_perteneciente FROM almacenes WHERE externo = 1 AND clues = "{CLUES_QUE_SINCRONIZA}")) OR servidor_id= "'.env('SERVIDOR_ID').'"))', 
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '',  // Esta funcion se ejecuta justo despues de haber bajado a local    
        ],
        'pedidos_insumos_clues'=>[
            'campos_subida'=>[
                'id',
                'incremento',
                'servidor_id',
                'pedido_insumo_id',
                'cantidad',
                'clues',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'campos_bajada'=>[
                'id',
                'incremento',
                'servidor_id',
                'pedido_insumo_id',
                'cantidad',
                'clues',
                'usuario_id',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            'condicion_subida' => '(pedido_insumo_id in ( select id from pedidos_insumos where pedido_id in (select id from pedidos where clues in (SELECT clues_perteneciente FROM almacenes WHERE externo = 1))))', 
            'condicion_bajada' => '(pedido_insumo_id in ( select id from pedidos_insumos where pedido_id in (select id from pedidos where (clues in (SELECT clues_perteneciente FROM almacenes WHERE externo = 1 AND clues = "{CLUES_QUE_SINCRONIZA}")) OR servidor_id= "'.env('SERVIDOR_ID').'")))', 
            'calculo_subida' => '', // Esta funcion se ejecuta despues de subir y antes de bajar
            'calculo_bajada' => '', // Esta funcion se ejecuta justo despues de haber bajado a local    
        ],
    ]
];