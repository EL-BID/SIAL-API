<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['middleware' => 'servidor.instalado'], function () {
    Route::get('install', 'InstallController@iniciarInstalacion');
    Route::get('instalar', 'InstallController@instalar');
});


Route::get('instalado', 'InstallController@instalado');

Route::post('obtener-token',                    'AutenticacionController@autenticar');
Route::post('refresh-token',                    'AutenticacionController@refreshToken');
Route::get('check-token',                       'AutenticacionController@verificar');

Route::get('informacion-servidor',              'ServidorController@informacionServidorLocal');

Route::post('reset-password/email',                 'ResetPasswordController@enviarEmail');
Route::post('reset-password/validar-token',         'ResetPasswordController@validarToken');
Route::put('reset-password/password-nuevo/{id}',    'ResetPasswordController@passwordNuevo');
Route::get('reset-password/pregunta-secreta/{id}',  'ResetPasswordController@obtenerPreguntaSecreta');
Route::post('reset-password/validar-respuesta',     'ResetPasswordController@validarRespuesta');

 
Route::get('grupo-permiso',                     'AutoCompleteController@grupo_permiso');
Route::get('clues-auto',                        'AutoCompleteController@clues');
Route::get('clues-auto-pcc',                    'AutoCompleteController@cluesPedidosCC');
Route::get('insumos-auto',                      'AutoCompleteController@insumos');
Route::get('insumos-entrada-auto',              'AutoCompleteController@insumos_entradas');
Route::get('insumos-laboratorio-clinico-auto',  'AutoCompleteController@insumosLaboratorioClinico');
Route::get('articulos-auto',                    'AutoCompleteController@articulos');
Route::get('inventario-articulo-auto',          "AutoCompleteController@articulos_inventarios");

Route::resource('personal-clues',               'PersonalCluesController',    ['only' => ['index', 'show', 'store','update','destroy']]);
Route::resource('personal-medico',              'PersonalMedicoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
 
// reportes y graficas
Route::get('grafica-entregas',                  'ReportePedidoController@graficaEntregas');
Route::get('estatus-pedidos',                   'ReportePedidoController@estatusEntregaPedidos');

// Akira: tuve que agregar esto aqui porque no me iba a poner a batallar para interceptar la peticion de conchita
// con su auto complete, y no se puede refrescar el token con la libreria del cliente
Route::group(['prefix' => 'medicos','namespace' => 'Medicos'], function () {
    Route::resource('pacientes',         'PacientesController',    ['only' => ['index', 'show', 'store','update','destroy']]);
});

Route::group(['middleware' => 'jwt'], function () {
    Route::put('editar-perfil/{id}',               'EditarPerfilController@editar');
    Route::resource('usuarios',                 'UsuarioController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('roles',                    'RolController',           ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('permisos',                 'PermisoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
     
    Route::resource('jurisdicciones',            'JurisdiccionesController',    ['only' => ['index']]);
    Route::resource('unidades-medicas',          'UnidadesMedicasController',    ['only' => ['index']]);
    Route::resource('proveedores',               'ProveedoresController',    ['only' => ['index']]);

    Route::resource('presupuestos',               'PresupuestoController',    ['only' => ['index']]);

    //Route::resource('unidades-medicas', 'UnidadesMedicasController',    ['only' => ['index']]);
    Route::resource('almacenes',                 'AlmacenController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('proveedor',                 'ProveedorController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('personal-puesto',           'PersonalPuestoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('personal',                  'PersonalController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('reporte-salida',            'ReporteSalidaController',    ['only' => ['index']]);
    Route::get('reporte-salida/catalogos',       'ReporteSalidaController@catalogos');
    Route::get('reporte-salida-reporte-excel',   'ReporteSalidaController@reporteExcel');
    


    // Akira: Estos 3 recursos que hacen aca si estan abajo dentro del middleware almacen??? :
    // Harima: Se colaron en algun merge que hicieron, los tomo como cambios en el commit, probablemente marco conflicto y lo arreglaron dejando estas lineas, los comento, si no hay problemas los elimino en el proximo commit
    //Route::resource('pedidos',          'PedidoController',     ['only' => ['index', 'show', 'store','update','destroy']]);    
    //Route::get('pedidos-stats',         'PedidoController@stats');
    //Route::get('pedidos-presupuesto',   'PedidoController@obtenerDatosPresupuesto');


    

    // # SECCION: Administrador central
    Route::group(['prefix' =>                           'administrador-central','namespace' => 'AdministradorCentral'], function () {
        Route::get('abasto',                            'AbastoController@lista');
        Route::get('abasto-excel',                      'AbastoController@excel');

        Route::get('reporte-financiero',                'ReporteFinancieroController@lista');
        Route::get('reporte-financiero-excel',          'ReporteFinancieroController@excel');
        
        Route::get('presupuesto-pedidos',               'PedidosController@presupuesto');
        Route::get('pedidos',                           'PedidosController@lista');
        Route::get('mes-disponible',                    'PedidosController@mesDisponible');
        Route::get('pedidos-excel',                     'PedidosController@excel');
        Route::get('pedidos-archivos-proveedor/{id}',   'PedidosController@listaArchivosProveedor');
        
        // PENAS CONVENCIONALES
        Route::get('meses',                                 'PenasConvencionalesController@meses');
        Route::get('periodos',                              'PenasConvencionalesController@periodos');
        Route::get('penas-convencionales-resumen',          'PenasConvencionalesController@resumen');
        Route::get('penas-convencionales-detalle',          'PenasConvencionalesController@detalle');
        Route::get('penas-convencionales-excel-individual/{id}', 'PenasConvencionalesController@excel');
        Route::get('penas-convencionales-excel-resumen',    'PenasConvencionalesController@excelResumen');


        // TRANSFERENCIAS DE PRESUPUESTO
        Route::get('unidades-medicas-con-presupuesto',  'TransferenciasPresupuestosController@unidadesMedicasConPresupuesto');
        Route::get('meses-presupuesto-actual',          'TransferenciasPresupuestosController@mesesPresupuestoActual');
        Route::get('anios-presupuesto-actual',          'TransferenciasPresupuestosController@aniosPresupuestoActual');
        // Este método es para obtener la lista de meses y años del presupuesto actual anteriores al mes y año actual
        Route::get('meses-anios-presupuesto-actual-anterior-fecha-actual', 'TransferenciasPresupuestosController@mesesAnioPresupuestoActualAnteriorFechaActual');        
        Route::get('presupuesto-unidad-medica',         'TransferenciasPresupuestosController@presupuestoUnidadMedica');
        Route::get('transferencias-presupuestos',       'TransferenciasPresupuestosController@lista');
        Route::post('transferencias-presupuestos',      'TransferenciasPresupuestosController@transferir');
        Route::post('transferencias-saldos-mes-actual', 'TransferenciasPresupuestosController@transferirSaldosAlMesActual');

        // ENTREGAS DEL MES
        Route::get('meses-anios-pedidos',                   'EntregasMesController@mesesAnioPresupuestos');
        Route::get('entregas-pedidos-stats-mes-anio',       'EntregasMesController@statsMesAnio');
        Route::get('entregas-pedidos-stats-diarias',        'EntregasMesController@entregasPedidosStatsDiarias');
        Route::get('pedidos-clues-mes-anio',                'EntregasMesController@pedidosAnioMesClues');
        Route::get('pedidos-recepciones-clues-mes-anio',    'EntregasMesController@pedidosRecepcionesClues');

        // CUMPLIMIENTO
        Route::get('cumplimiento-stats-globales',           'CumplimientoController@statsGlobales');
        Route::get('cumplimiento-stats-proveedor/{id}',     'CumplimientoController@statsPorProveedor');

        // CLAVES BASICAS
        Route::resource('claves-basicas',                   'ClavesBasicasController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::get('claves-basicas-clues/{id}',             'ClavesBasicasController@unidadesMedicas');
        Route::post('claves-basicas-clues',                 'ClavesBasicasController@agregarUnidadMedica');
        Route::delete('claves-basicas-clues/{id}',          'ClavesBasicasController@quitarUnidadMedica');

         //Configuuración de pedidos
        Route::get('pedidos-recepcion/{id}',                'PedidosController@recepcion');
        Route::get('pedidos-borrador/{id}',                 'PedidosController@regresarBorrador');
        Route::get('pedidos-borrador-cancelado/{id}',       'PedidosController@regresarBorradorCancelado');
        Route::get('recepcion-borrador/{id}',               'RecepcionPedidoController@borrarRecepcion');
        Route::put('pedidos-permitir-recepcion/{id}',       'PedidosController@permitirRecepcion');

        // Pedidos Alternos
        Route::get('pedidos-alternos',                      'PedidosAlternosController@lista');     
        Route::get('pedidos-alternos/{id}',                 'PedidosAlternosController@ver');      
        Route::put('pedidos-alternos/validacion/{id}',      'PedidosAlternosController@validar');
        Route::put('pedidos-alternos/proveedor/{id}',       'PedidosAlternosController@asignarProveedor');


        // Insumos médicos
        Route::resource('insumos-medicos',                  'InsumosMedicosController',['only' => ['index', 'show', 'store','update','destroy']]);
        Route::get('presentaciones',                        'InsumosMedicosController@presentaciones');     
        Route::get('unidades-medida',                       'InsumosMedicosController@unidadesMedida');     
        Route::get('vias-administracion',                   'InsumosMedicosController@viasAdministracion');     
        Route::get('insumos-medicos-excel',                 'InsumosMedicosController@exportarExcel');
        Route::get('formato-insumos-medicos-excel',         'InsumosMedicosController@descargarFormato');
        Route::post('cargar-insumos-excel',                 'InsumosMedicosController@cargarExcel');
        Route::post('confirmar-carga-masiva-insumos',       'InsumosMedicosController@confirmarCargaMasiva');

        // Contratos
        Route::get('proveedores',                           'ContratosController@proveedores');
        Route::resource('contratos',                        'ContratosController',['only' => ['index', 'show', 'store','update','destroy']]);
        Route::put('contratos/activar/{id}',                'ContratosController@activar');
        Route::get('formato-contrato-lista-precios-excel',  'ContratosController@descargarFormato');
        Route::post('cargar-lista-precios-excel',           'ContratosController@cargarExcel');
        Route::get('exportar-contrato-lista-precios-excel/{id}',  'ContratosController@exportarExcel');

        // Presupuesto
        Route::get('presupuesto/ejercicios',                            'PresupuestoController@ejercicios');
        Route::get('presupuesto/presupuesto-unidades-medicas/{id}',     'PresupuestoController@presupuestoUnidadesMedicas');
        Route::get('presupuesto/excel/{id}',                            'PresupuestoController@exportarExcel');
        Route::get('presupuesto/ultimo',                                'PresupuestoController@ultimoPresupuesto');
        Route::get('presupuesto/tipos-unidad-medica',                   'PresupuestoController@tiposUnidadMedica');
        Route::get('presupuesto/jurisdicciones',                        'PresupuestoController@jurisdicciones');
        Route::get('presupuesto/unidades-medicas',                      'PresupuestoController@unidadesMedicas');        
        Route::get('presupuesto/formato-carga-presupuesto-excel',       'PresupuestoController@descargarFormato');
        Route::post('presupuesto/procesar-presupuesto-excel',           'PresupuestoController@cargarExcel');
        Route::post('presupuesto',                                      'PresupuestoController@store');

        
    });
    // # FIN SECCION


    Route::group(['middleware' => 'proveedor'], function () {
        Route::get('presupuesto-pedidos-administrador-proveedores', 'PedidosAdministradorProveedoresController@presupuesto');
        Route::get('pedidos-administrador-proveedores',             'PedidosAdministradorProveedoresController@lista');
        Route::get('pedidos-administrador-proveedores-excel',       'PedidosAdministradorProveedoresController@excel');
        Route::get('pedidos-administrador-proveedores-pedido/{id}', 'PedidosAdministradorProveedoresController@pedido');
 
        Route::get('listar-pedidos-proveedor',                      'SincronizacionProveedorController@listarPedidos');
        Route::post('analizar-json-proveedor',                      'SincronizacionProveedorController@analizarJson');
        Route::post('procesar-json-proveedor',                      'SincronizacionProveedorController@procesarJson');

        Route::resource('repository',                               'RepositorioController',    ['only' => ['index', 'show', 'store','update','destroy']]);
 
    });
    
    Route::get('repository-download/{id}',                  'RepositorioController@registro_descarga'); 
    Route::get('download-file/{id}',                        'RepositorioController@descargar'); 

    Route::group(['middleware' => 'almacen'], function () 
    {

        // # SECCION: Inventario 
        Route::group(['prefix' => 'inventario','namespace' => 'Inventario'], function () {
            Route::resource('inicializacion-inventario',    'InicializacionInventarioController',  ['only' => ['index', 'show', 'store','update','destroy']]);
        });

        // # SECCION: Almacen 
        Route::group(['prefix' => 'almacen','namespace' => 'Almacen'], function () {
            Route::get('transferencias-stats',                     'TransferenciaAlmacenController@stats');
            Route::resource('transferencias',                      'TransferenciaAlmacenController',['only' => ['index', 'show', 'store','update','destroy']]);
            Route::put('surtir-transferencia/{id}',                'TransferenciaAlmacenController@surtir');
            Route::put('actualizar-transferencia/{id}',             'TransferenciaAlmacenController@actualizarTransferencia');
        });


        // # SECCIÓN: Almacén activo fijo
        Route::group(['namespace' => 'AlmacenGeneral'], function () {
        
            Route::resource('categoria',                        'CategoriaController',    ['only' => ['index', 'show', 'store','update','destroy']]); 
            Route::get('articulos-inventarios/{id}',            'ArticuloController@inventarioArticulo'); 
            Route::resource('articulos',                        'ArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
            Route::get('inventario-articulos-excel',            'InventarioArticuloController@excel');
            Route::resource('inventario-articulos',             'InventarioArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
    
            Route::resource('tipo-personal',                    'TipoPersonalController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
            Route::resource('entrada-articulo',                 'EntradaArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
            Route::resource('salida-articulo',                  'SalidaArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);
            Route::resource('resguardos-articulo',              'ResguardoController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
            Route::post('resguardos-articulo/devolucion',       'ResguardoController@devolucion');
            Route::resource('configuracion-general',            'ConfiguracionGeneralController',    ['only' => ['show', 'update']]);
             
        });

        Route::resource('inicializar-inventario-me',    'InicializarInventarioMedicamentosController',  ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('almacenes',                    'AlmacenController',    ['only' => ['index']]);
        Route::resource('entregas',                     'EntregaController',  ['only' => ['index', 'show', 'store','update','destroy']]);
 
        Route::resource('stock',                        'StockController',    ['only' => ['index']]);
        Route::resource('comprobar-stock',              'ComprobarStockController',    ['only' => ['index']]);

        Route::get('entregas-stats',                    'EntregaController@stats');
        Route::resource('movimientos',                  'MovimientoController',    ['only' => ['index', 'show', 'store','update','destroy']]);

        Route::resource('entrada-almacen',              'EntradaAlmacenController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('entrada-almacen-standard',     'EntradaAlmacenStandardController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('salida-almacen-standard',      'SalidaAlmacenStandardController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('entrada-laboratorio',          'EntradaLaboratorioController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('salida-laboratorio',           'SalidaLaboratorioController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('inventario-insumos',           'InventarioInsumosController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('caducidad-insumos',            'CaducidadInsumosController',    ['only' => ['index', 'show', 'store','update','destroy']]);

        Route::resource('inventario-laboratorio',       'InventarioLaboratorioController',    ['only' => ['index', 'show', 'store','update','destroy']]);        

        Route::resource('ajuste-mas-inventario',        'AjusteMasInventarioController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('ajuste-menos-inventario',      'AjusteMenosInventarioController',  ['only' => ['index', 'show', 'store','update','destroy']]);

 
        //Pedidos
        Route::resource('pedidos',                      'PedidoController',     ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::get('pedidos-stats',                     'PedidoController@stats');
        Route::get('pedidos-presupuesto',               'PedidoController@obtenerDatosPresupuesto');
        Route::put('cancelar-pedido-transferir/{id}',   'CancelarPedidosController@cancelarYTransferir');
        Route::put('cancelar-transferencia/{id}',       'CancelarPedidosController@cancelarTransferencia');

        
        // Actas
        Route::put('generar-pedido-alterno/{id}',       'PedidoController@generarAlterno');
        Route::resource('actas',                        'ActasController',     ['only' => ['index', 'show']]);
 

        //Route::resource('pedidos-jurisdiccionales',         'PedidoJurisdiccionalController',     ['only' => ['index', 'show', 'store','update','destroy']]);
        //Route::get('pedidos-jurisdiccionales-stats',        'PedidoJurisdiccionalController@stats');
        //Route::get('pedidos-jurisdiccionales-presupuesto',  'PedidoJurisdiccionalController@obtenerDatosPresupuesto');

        Route::resource('recepcion-pedido',             'RecepcionPedidoController',    ['only' => ['show', 'update','destroy']]);

        Route::resource('mis-almacenes',                'MiAlmacenController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('mis-servicios',                'MisServiciosController',    ['only' => ['index', 'show', 'store','update','destroy']]);
	    Route::resource('mis-turnos',                   'MisTurnosController',    ['only' => ['index', 'show', 'store','update','destroy']]);
	    Route::resource('mis-claves',                   'MisClavesController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('firmantes',                    'FirmantesController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('documentos-firmantes',         'DocumentoAlmacenFirmanteController',    ['only' => ['index', 'show', 'store','update','destroy']]);

        //Ruta para listado de medicamentos a travez de un autocomplete, soporta paginación y busqueda
        Route::resource('catalogo-insumos',             'CatalogoInsumoController',     ['only' => ['index', 'show']]);

        Route::get('unidades-medicas-dependientes',     'UnidadesMedicasController@unidadesMedicasDependientes');
        Route::get('stock-insumo-medico',               'StockController@stockInsumoMedico');
    });

    
    Route::get('generar-excel-pedido/{id}',                 'PedidoController@generarExcel');
    Route::get('generar-excel-pedido-jurisdiccional/{id}',  'PedidoJurisdiccionalController@generarExcel');

    
    Route::get('inventario-insumos-excel',                  'InventarioInsumosController@excel');
    Route::get('caducidad-insumos-excel',                   'CaducidadInsumosController@excel');
    
    Route::get('entregas-stats',                            'EntregaController@stats'); 
 
    Route::resource('clues-servicio',                       'CluesServicioController',    ['only' => ['index', 'show', 'store','update','destroy']]);

    //  pedidos Compra consolidada.
    Route::resource('pedidos-cc-dam',                       'PedidoCompraConsolidadaDamController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('pedidos-cc-um',                        'PedidoCompraConsolidadaUmController',     ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('pedidos-cc-daf',                       'PedidoCompraConsolidadaDafController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::post('concentrar-pedido-cc-dam',                 'PedidoCompraConsolidadaDamController@concentrarPedidoDam');
    Route::get('pedido-concentrado-cc-dam/{id}',            'PedidoCompraConsolidadaDamController@verPedidoConcentradoDam');

    //catalogos  
    Route::resource('unidad-medida',                        'UnidadMedidaController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('unidades-medicas',                     'UnidadesMedicasController',    ['only' => ['index', 'show', 'store','update','destroy']]);	
    Route::resource('via-administracion',                   'ViaAdministracionController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('tipo-pedido',                          'TipoPedidoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('tipo-movimiento',                      'TipoMovimientoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('tipo-insumo',                          'TipoInsumoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('servidor',                             'ServidorController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('marcas',                               'MarcaController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('servicios',                            'ServicioController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('grupos-insumos',                       'GrupoInsumoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('presentaciones-medicamentos',          'PresentacionMedicamentoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('turnos',                               'TurnoController',    ['only' => ['index', 'show', 'store','update','destroy']]);

    Route::resource('programa',                             'ProgramaController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('presentacion-medicamento',             'PresentacionMedicamentoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('material-curacion',                    'MaterialCuracionController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('medicamentos',                         'MedicamentoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('unidad-medica',                        'UnidadMedicaController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
    Route::resource('forma-farmaceutica',                   'FormaFarmaceuticaController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    //Route::resource('condicion-articulo',                   'CondicionArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);
    
    
    // catalogos  

    
    Route::group(['prefix' => 'admision','namespace' => 'AdmisionUnidad'], function () {
        Route::resource('paciente',                         'PacienteController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('paciente-egreso',                  'PacienteEgresoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('admision',                         'AdmisionController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('egreso',                           'EgresoController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('historial',                        'HistorialController',    ['only' => ['show']]);
        
        /*Catálogos*/
        Route::resource('municipio',                        'MunicipioController',    ['only' => ['index', 'show']]);
        Route::resource('localidad',                        'LocalidadController',    ['only' => ['index', 'show']]);
        Route::resource('motivo-egreso',                    'MotivoEgresoController',    ['only' => ['index', 'show']]);
        Route::resource('triage',                           'TriageController',    ['only' => ['index', 'show']]);
        Route::resource('grado-lesion',                     'GradoLesionController',    ['only' => ['index', 'show']]);
        
       
    });


    Route::group(['namespace' => 'AlmacenGeneral'], function () {
        
        Route::resource('categoria',                        'CategoriaController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
        Route::resource('articulos',                        'ArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
        Route::resource('inventario-articulos',             'InventarioArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);  

        Route::resource('tipo-personal',                    'TipoPersonalController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
        Route::resource('entrada-articulo',                 'EntradaArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
        Route::resource('salida-articulo',                  'SalidaArticuloController',    ['only' => ['index', 'show', 'store','update','destroy']]);  
        Route::resource('configuracion-general',            'ConfiguracionGeneralController',    ['only' => ['show', 'update']]);
         
    });

    Route::group(['prefix' => 'medicos','namespace' => 'Medicos'], function () {
        //Route::resource('pacientes',         'PacientesController',    ['only' => ['index', 'show', 'store','update','destroy']]);
        Route::resource('recetas',           'RecetasController',             ['only' => ['index', 'show', 'store','update','destroy']]);
    });
    
    

    // Akira: Esto nose que onda a que módulo pertenece
    Route::resource('receta',           'RecetaController',             ['only' => ['index', 'show', 'store','update','destroy']]);

    //Modulo de Avances
    Route::resource('avance',                               'AvanceController',             ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('avance-detalle',                       'AvanceDetalleController',             ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::resource('avance-usuario-privilegio',            'AvanceUsuarioPrivilegioController',             ['only' => ['index', 'show', 'store','update','destroy']]);
    Route::get('download-file-avance/{id}',                 'AvanceDetalleController@descargar'); 
    Route::get('view-file-avance/{id}',                     'AvanceDetalleController@view'); 
    Route::resource('avance_repository',                    'AvanceDetalleController@store');
    Route::resource('avance_area',                          'AvanceController@areas');

    Route::group(['prefix' => 'sync','namespace' => 'Sync'], function () {
        Route::get('log',                                 'SincronizacionController@log');
        Route::get('lista',                                 'SincronizacionController@lista');
        Route::get('manual',                                'SincronizacionController@manual');        
        Route::get('auto',                                  'SincronizacionController@auto');
        Route::post('importar',                             'SincronizacionController@importarSync');
        Route::post('confirmar',                            'SincronizacionController@confirmarSync');
        Route::resource('servidores',                       'ServidoresController',['only' => ['index', 'show', 'store','update','destroy']]);
    });


    // # SECCION: Opciones avanzadas 
    Route::group(['prefix' => 'opciones-avanzadas','namespace' => 'OpcionesAvanzadas'], function () {
        Route::get('fecha-hora-servidor',               'FechaHoraServidorController@get');
        Route::post('fecha-hora-servidor/actualizar',   'FechaHoraServidorController@update');
        Route::get('actualizar-plataforma-git',         'ActualizarPlataformaController@git');
        Route::get('exportar-base-datos',               'BaseDatosController@exportar');
        Route::post('importar-base-datos',              'BaseDatosController@importar');
        Route::get('obtener-datos-central',             'DatosServidorCentralController@exportar');
        Route::post('cargar-datos-servidor-central',    'DatosServidorCentralController@importar');
    });
    // #SECCION: Parches
    Route::group(['prefix' => 'patches','namespace' => 'Patches'], function () {
        Route::get('lista',                                 'PatchesController@lista');
        Route::post('ejecutar-parche',                      'PatchesController@ejecutarParche');
        Route::post('ejecutar',                             'PatchesController@ejecutar');
    });
    
    
});
