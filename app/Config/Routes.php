<?php

namespace Config;

// ============================================================================
// CONDORIRI SG - SISTEMA DE GESTIÓN EMPRESARIAL
// Configuración de Rutas del Sistema
// ============================================================================

$routes = Services::routes();

// ============================================================================
// CONFIGURACIÓN DEL ROUTER
// ============================================================================
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false); // Seguridad: Solo rutas explícitas

// ============================================================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================================================

// Ruta de prueba para desarrollo
$routes->get('/home/prueba', 'Home::prueba');

// Sistema de Autenticación
$routes->get('/login', 'login\loginController::index');
$routes->post('/login', 'login\loginController::authenticate');
$routes->get('/logout', 'login\loginController::logout');

// ============================================================================
// RUTA PRINCIPAL - DASHBOARD
// Roles: Todos los usuarios autenticados
// ============================================================================
$routes->group('/', ['filter' => 'auth'], function ($routes) {
    $routes->get('', 'paneles\panelesController::panel', ['filter' => 'role:admin,vendedor,almacen,contabilidad,agropecuario,ganaderia,dev']);
});

// ============================================================================
// GESTIÓN DE USUARIOS Y PERFILES
// Roles: admin (gestión) | todos (perfil propio)
// ============================================================================
$routes->group('usuarios', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'usuarios\usuariosController::all', ['filter' => 'role:admin']);
    $routes->get('register', 'usuarios\usuariosController::register', ['filter' => 'role:admin']);
    $routes->post('create', 'usuarios\usuariosController::create', ['filter' => 'role:admin']);
    $routes->get('edit/(:num)', 'usuarios\usuariosController::edit/$1', ['filter' => 'role:admin']);
    $routes->post('update', 'usuarios\usuariosController::update', ['filter' => 'role:admin']);
    $routes->get('delete/(:num)', 'usuarios\usuariosController::delete/$1', ['filter' => 'role:admin']);
    $routes->get('activate/(:num)', 'usuarios\usuariosController::activate/$1', ['filter' => 'role:admin']);
});

// Perfil de Usuario (Accesible para todos los usuarios autenticados)
$routes->group('perfil', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'usuarios\usuariosController::profile');
    $routes->post('update', 'usuarios\usuariosController::updateProfile');
});

// ============================================================================
// CATÁLOGOS MAESTROS
// Roles: admin, almacen
// ============================================================================

// Unidades de Medida (kg, litros, unidades, etc.)
$routes->group('unidades', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'unidad\unidadesController::index', ['filter' => 'role:admin,almacen']);
    $routes->get('register', 'unidad\unidadesController::register', ['filter' => 'role:admin,almacen']);
    $routes->post('create', 'unidad\unidadesController::create', ['filter' => 'role:admin,almacen']);
    $routes->get('edit/(:num)', 'unidad\unidadesController::edit/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('update/(:num)', 'unidad\unidadesController::update/$1', ['filter' => 'role:admin,almacen']);
    $routes->get('delete/(:num)', 'unidad\unidadesController::delete/$1', ['filter' => 'role:admin,almacen']);
});

// Categorías de Productos (lácteos, agropecuarios, etc.)
$routes->group('categorias', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'categorias\categoriasController::index', ['filter' => 'role:admin,almacen']);
    $routes->get('register', 'categorias\categoriasController::register', ['filter' => 'role:admin,almacen']);
    $routes->post('create', 'categorias\categoriasController::create', ['filter' => 'role:admin,almacen']);
    $routes->get('edit/(:num)', 'categorias\categoriasController::edit/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('update/(:num)', 'categorias\categoriasController::update/$1', ['filter' => 'role:admin,almacen']);
    $routes->get('delete/(:num)', 'categorias\categoriasController::delete/$1', ['filter' => 'role:admin,almacen']);
});

// Sucursales del Sistema
$routes->group('sucursales', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'sucursales\sucursalesController::index', ['filter' => 'role:admin,almacen,contabilidad']);
    $routes->get('register', 'sucursales\sucursalesController::register', ['filter' => 'role:admin']);
    $routes->post('create', 'sucursales\sucursalesController::create', ['filter' => 'role:admin']);
    $routes->get('edit/(:num)', 'sucursales\sucursalesController::edit/$1', ['filter' => 'role:admin']);
    $routes->post('update/(:num)', 'sucursales\sucursalesController::update/$1', ['filter' => 'role:admin']);
    $routes->get('delete/(:num)', 'sucursales\sucursalesController::delete/$1', ['filter' => 'role:admin']);
    $routes->get('toggle/(:num)', 'sucursales\sucursalesController::toggleEstado/$1', ['filter' => 'role:admin']);
    $routes->get('show/(:num)', 'sucursales\sucursalesController::show/$1', ['filter' => 'role:admin,contabilidad']);
});

// ============================================================================
// GESTIÓN DE INVENTARIOS Y PRODUCTOS
// Roles: admin, almacen (+ contabilidad para consultas)
// ============================================================================

// Inventarios de Materia Prima (leche, suero, etc.)
$routes->group('inventarios', ['filter' => 'auth'], function ($routes) {
    // CRUD básico
    $routes->get('/', 'inventarios\inventariosController::index', ['filter' => 'role:admin,almacen,contabilidad']);
    $routes->get('register', 'inventarios\inventariosController::register', ['filter' => 'role:admin,almacen']);
    $routes->post('create', 'inventarios\inventariosController::create', ['filter' => 'role:admin,almacen']);
    $routes->get('show/(:num)', 'inventarios\inventariosController::show/$1');
    $routes->get('edit/(:num)', 'inventarios\inventariosController::edit/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('update/(:num)', 'inventarios\inventariosController::update/$1', ['filter' => 'role:admin,almacen']);
    $routes->get('delete/(:num)', 'Inventarios\InventariosController::delete/$1', ['filter' => 'role:admin,almacen']);
    
    // Filtros y reportes
    $routes->get('filtered', 'inventarios\inventariosController::filtered', ['filter' => 'role:admin,almacen,contabilidad']);
    $routes->get('exportarPdf', 'inventarios\inventariosController::exportarPdf', ['filter' => 'role:admin,almacen,contabilidad']);
    $routes->get('resumen', 'inventarios\inventariosController::getResumen', ['filter' => 'role:admin,almacen']);
    $routes->get('resumenNombre', 'inventarios\inventariosController::getResumenPorNombre', ['filter' => 'role:admin,almacen']);
    $routes->get('reporteInventario', 'inventarios\inventariosController::reporteInventario', ['filter' => 'role:admin,almacen']);
    
    // Sistema de ventas integrado (legacy)
    $routes->get('ventas', 'inventarios\inventariosController::indexVenta', ['filter' => 'role:admin,almacen,contabilidad']);
    $routes->get('registerVenta', 'inventarios\inventariosController::registerVenta', ['filter' => 'role:admin,almacen']);
    $routes->get('recibo/(:num)', 'inventarios\inventariosController::generarRecibo/$1');
    $routes->get('cierre', 'inventarios\inventariosController::exportarPdfVentas', ['filter' => 'role:admin,almacen,contabilidad']);
    $routes->get('cierre/rango', 'inventarios\inventariosController::exportarPdfVentas', ['filter' => 'role:admin,almacen,contabilidad']);
    $routes->get('credito', 'inventarios\inventariosController::credito', ['filter' => 'role:admin,almacen']);
    $routes->get('buscarPersonalUto', 'inventarios\inventariosController::buscarPersonalUto', ['filter' => 'role:admin,almacen']);
    $routes->post('guardarVenta', 'inventarios\inventariosController::guardarVenta', ['filter' => 'role:admin,almacen']);
    $routes->post('guardarCreditoVenta', 'inventarios\inventariosController::guardarCreditoVenta', ['filter' => 'role:admin,almacen']);
    $routes->get('buscar-clientes', 'inventarios\inventariosController::buscarClientes', ['filter' => 'role:admin,almacen']);
    $routes->get('buscar-productos', 'inventarios\inventariosController::buscarProductos', ['filter' => 'role:admin,almacen']);
    $routes->post('guardar-cliente', 'inventarios\inventariosController::guardarCliente', ['filter' => 'role:admin,almacen']);
    $routes->post('registerClienteInve', 'cliente\clienteController::registerClienteInve', ['filter' => 'role:admin,almacen']);
    $routes->post('guardar-calidad/(:num)', 'inventarios\inventariosController::guardarCalidad/$1', ['filter' => 'role:admin,almacen']);
    $routes->get('control-calidad-pdf/(:num)', 'inventarios\inventariosController::controlCalidadPdf/$1', ['filter' => 'role:admin,almacen']);
});

// Productos Terminados (quesos, yogurt, etc.)
$routes->group('productos', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'productos\productosController::index', ['filter' => 'role:admin,almacen']);
    $routes->get('register/(:num)', 'productos\productosController::register/$1', ['filter' => 'role:admin,almacen']);
    $routes->get('register', 'productos\productosController::register', ['filter' => 'role:admin,almacen']);
    $routes->post('create', 'productos\productosController::create', ['filter' => 'role:admin,almacen']);
    $routes->get('edit/(:num)', 'productos\productosController::edit/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('update/(:num)', 'productos\productosController::update/$1', ['filter' => 'role:admin,almacen']);
    $routes->get('show/(:num)', 'productos\productosController::show/$1');
    $routes->get('delete/(:num)', 'productos\productosController::delete/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('merma/(:num)', 'productos\productosController::merma/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('agregar/(:num)', 'productos\productosController::agregar/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('subproducto', 'productos\productosController::subproducto', ['filter' => 'role:admin,almacen']);
    $routes->post('createSuero', 'productos\productosController::createSuero', ['filter' => 'role:admin,almacen']);
});

// ============================================================================
// GESTIÓN DE TRANSFERENCIAS Y ENVÍOS
// Roles: admin, almacen, vendedor (+ ganaderia, agropecuario para envíos)
// ============================================================================

// Transferencias de Productos (sistema legacy)
$routes->group('transferencias', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'transferencias\transferenciasController::index', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->post('store', 'transferencias\transferenciasController::store', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->post('create', 'transferencias\transferenciasController::create', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->get('edit/(:num)', 'transferencias\transferenciasController::edit/$1', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->post('update/(:num)', 'transferencias\transferenciasController::update/$1', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->delete('delete/(:num)', 'transferencias\transferenciasController::delete/$1', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->get('envioPdf/(:num)', 'transferencias\transferenciasController::generarFactura/$1', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->post('confirmarEnvio', 'transferencias\transferenciasController::confirmarEnvio', ['filter' => 'role:admin,almacen,vendedor']);
});

// Envíos entre Sucursales (sistema principal)
$routes->group('envios', ['filter' => 'auth'], function ($routes) {
    // CRUD básico
    $routes->get('/', 'envios\enviosController::index', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->get('register', 'envios\enviosController::register', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->post('create', 'envios\enviosController::create', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->get('edit/(:num)', 'envios\enviosController::edit/$1', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->post('update/(:num)', 'envios\enviosController::update/$1', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->get('show/(:num)', 'envios\enviosController::show/$1', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->delete('delete/(:num)', 'envios\enviosController::delete/$1', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    
    // Operaciones de envío
    $routes->get('confirmar/(:num)', 'envios\enviosController::confirmarEnvio/$1', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->post('confirmarConProductos/(:num)', 'envios\enviosController::confirmarConProductos/$1', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->post('recepcionar/(:num)', 'envios\enviosController::recepcionar/$1', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    
    // Reportes y devoluciones
    $routes->get('reporte', 'envios\enviosController::generarReporte', ['filter' => 'role:admin,almacen,ganaderia,agropecuario,vendedor']);
    $routes->get('devoluciones', 'envios\enviosController::devIndex', ['filter' => 'role:admin,vendedor,almacen,ganaderia,agropecuario']);
    $routes->get('devoluciones/show/(:num)', 'envios\enviosController::devShow/$1', ['filter' => 'role:admin,vendedor,almacen']);
});

// Recepciones de Envíos
$routes->group('resepciones', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'resepciones\resepcionesController::index', ['filter' => 'role:admin,dev,vendedor,almacen']);
    $routes->post('recepcionar/(:num)', 'resepciones\resepcionesController::recepcionar/$1', ['filter' => 'role:admin,dev,vendedor,almacen']);
    $routes->get('show/(:num)', 'resepciones\resepcionesController::show/$1');
    $routes->get('reporteGeneral', 'resepciones\resepcionesController::reporteGeneral', ['filter' => 'role:admin,dev,vendedor,almacen']);
    $routes->get('reporteDetallado/(:num)', 'resepciones\resepcionesController::reporteDetallado/$1', ['filter' => 'role:admin,dev,vendedor,almacen']);
    
    // Rutas duplicadas (legacy - revisar)
    $routes->get('register', 'envios\enviosController::register', ['filter' => 'role:admin,dev,vendedor,almacen']);
    $routes->post('create', 'envios\enviosController::create', ['filter' => 'role:admin,dev,vendedor,almacen']);
    $routes->get('edit/(:num)', 'envios\enviosController::edit/$1', ['filter' => 'role:admin,dev,vendedor,almacen']);
    $routes->post('update/(:num)', 'envios\enviosController::update/$1', ['filter' => 'role:admin,dev,vendedor,almacen']);
    $routes->delete('delete/(:num)', 'envios\enviosController::delete/$1', ['filter' => 'role:admin,dev,vendedor,almacen']);
    $routes->get('confirmar/(:num)', 'envios\enviosController::confirmarEnvio/$1');
    $routes->get('resepcion', 'envios\enviosController::indexResepcion', ['filter' => 'role:admin,dev,vendedor,almacen']);
});

// Devoluciones de Productos
$routes->group('devoluciones', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Devoluciones\DevolucionesController::index', ['filter' => 'role:admin,almacen,vendedor,ganaderia,agropecuario']);
    $routes->post('create', 'Devoluciones\DevolucionesController::create', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->get('confirmarEnvio/(:num)', 'Devoluciones\DevolucionesController::confirmarEnvio/$1', ['filter' => 'role:admin,almacen,vendedor']);
    $routes->get('recibo/(:num)', 'Devoluciones\DevolucionesController::generarRecibo/$1', ['filter' => 'role:admin,almacen,vendedor']);
    
    // Rutas duplicadas (legacy - revisar)
    $routes->post('store', 'transferencias\transferenciasController::store', ['filter' => 'role:admin,almacen']);
    $routes->post('create', 'transferencias\transferenciasController::create', ['filter' => 'role:admin,almacen']);
    $routes->get('edit/(:num)', 'transferencias\transferenciasController::edit/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('update/(:num)', 'transferencias\transferenciasController::update/$1', ['filter' => 'role:admin,almacen']);
    $routes->delete('delete/(:num)', 'transferencias\transferenciasController::delete/$1', ['filter' => 'role:admin,almacen']);
    $routes->get('envioPdf/(:num)', 'transferencias\transferenciasController::generarFactura/$1', ['filter' => 'role:admin,almacen']);
    $routes->post('confirmarEnvio', 'transferencias\transferenciasController::confirmarEnvio', ['filter' => 'role:admin,almacen,vendedor']);
});

// ============================================================================
// GESTIÓN DE VENTAS Y CLIENTES
// Roles: admin, vendedor (+ agropecuario, ganaderia para productos agro)
// ============================================================================

// Clientes del Sistema
$routes->group('cliente', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ventas\ventasController::index', ['filter' => 'role:admin,vendedor,agropecuario,ganaderia']);
    $routes->post('create', 'cliente\clienteController::create', ['filter' => 'role:admin,vendedor,agropecuario,ganaderia,almacen']);
    $routes->get('lista', 'cliente\clienteController::index', ['filter' => 'role:admin,vendedor,agropecuario,ganaderia']);
    $routes->get('get/(:num)', 'cliente\clienteController::get/$1', ['filter' => 'role:admin,vendedor,agropecuario,ganaderia,almacen']);
    $routes->post('update', 'cliente\clienteController::update', ['filter' => 'role:admin,vendedor,agropecuario,ganaderia,almacen']);
});

// Personal UTO (Créditos especiales)
$routes->group('personal_uto', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'creditoUto\creditoUtoController::index', ['filter' => 'role:admin,vendedor']);
    $routes->post('create', 'cliente\clienteController::create', ['filter' => 'role:admin,vendedor']);
});

// Ventas de Productos Lácteos
$routes->group('ventas', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ventas\ventasController::index', ['filter' => 'role:admin,vendedor,contabilidad']);
    $routes->get('register', 'ventas\ventasController::register', ['filter' => 'role:admin,vendedor']);
    $routes->post('guardarVenta', 'ventas\ventasController::guardarVenta', ['filter' => 'role:admin,vendedor']);
    $routes->post('guardarCreditoVenta', 'ventas\ventasController::guardarCreditoVenta', ['filter' => 'role:admin,vendedor']);
    
    // Funciones auxiliares
    $routes->get('buscarPersonalUto', 'ventas\ventasController::buscarPersonalUto', ['filter' => 'role:admin,vendedor']);
    $routes->get('buscar-clientes', 'ventas\ventasController::buscarClientes', ['filter' => 'role:admin,vendedor']);
    $routes->get('buscar-productos', 'ventas\ventasController::buscarProductos', ['filter' => 'role:admin,vendedor']);
    $routes->post('guardar-cliente', 'ventas\ventasController::guardarCliente', ['filter' => 'role:admin,vendedor']);
    
    // Reportes y documentos
    $routes->get('recibo/(:num)', 'ventas\ventasController::generarRecibo/$1');
    $routes->get('credito', 'ventas\ventasController::credito', ['filter' => 'role:admin,vendedor']);
    $routes->get('cierre', 'ventas\ventasController::exportarPdfVentas', ['filter' => 'role:admin,vendedor,contabilidad']);
    $routes->get('cierre/rango', 'ventas\ventasController::exportarPdfVentas', ['filter' => 'role:admin,vendedor,contabilidad']);
});

// ============================================================================
// PRODUCTOS AGROPECUARIOS
// Roles: admin, agropecuario, ganaderia (+ contabilidad para consultas)
// ============================================================================
$routes->group('productosagro', ['filter' => 'auth'], function ($routes) {
    // CRUD básico
    $routes->get('/', 'productosAgro\productosAgroController::index', ['filter' => 'role:admin,agropecuario,ganaderia,contabilidad']);
    $routes->get('create', 'productosAgro\productosAgroController::create', ['filter' => 'role:admin,agropecuario,ganaderia']);
    $routes->post('store', 'productosAgro\productosAgroController::store', ['filter' => 'role:admin,agropecuario,ganaderia']);
    $routes->get('edit/(:num)', 'productosAgro\productosAgroController::edit/$1', ['filter' => 'role:admin,ganaderia,agropecuario']);
    $routes->post('update/(:num)', 'productosAgro\productosAgroController::update/$1', ['filter' => 'role:admin,almacen,agropecuario,ganaderia']);
    $routes->get('delete/(:num)', 'productosAgro\productosAgroController::delete/$1', ['filter' => 'role:admin,almacen']);
    
    // Gestión de clientes
    $routes->post('registerCliente', 'cliente\clienteController::registerCliente', ['filter' => 'role:admin,vendedor,agropecuario,ganaderia']);
    
    // Ventas agropecuarias
    $routes->get('ventas', 'productosAgro\ventasAgroController::index', ['filter' => 'role:admin,ganaderia,agropecuario']);
    $routes->get('registerVentas', 'productosAgro\ventasAgroController::register', ['filter' => 'role:admin,ganaderia,agropecuario']);
    $routes->post('guardarVenta', 'productosAgro\ventasAgroController::guardarVenta', ['filter' => 'role:admin, ganaderia,agropecuario']);
    $routes->post('guardarCreditoVenta', 'productosAgro\ventasAgroController::guardarCreditoVenta', ['filter' => 'role:admin,ganaderia,agropecuario']);
    
    // Funciones auxiliares
    $routes->get('buscarPersonalUto', 'productosAgro\ventasAgroController::buscarPersonalUto', ['filter' => 'role:admin,ganaderia,agropecuario']);
    $routes->get('credito', 'productosAgro\ventasAgroController::credito', ['filter' => 'role:admin,ganaderia,agropecuario']);
    $routes->get('recibo/(:num)', 'productosAgro\ventasAgroController::generarRecibo/$1');
});

// ============================================================================
// REPORTES Y CONTABILIDAD
// Roles: admin, contabilidad
// ============================================================================
$routes->group('contabilidad', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'paneles\panelesController::contabilidad', ['filter' => 'role:admin,contabilidad']);
    $routes->get('show/(:num)', 'contabilidad\contabilidadController::show/$1', ['filter' => 'role:admin,contabilidad']);
    $routes->get('reporte', 'contabilidad\contabilidadController::exportarPdfVentas', ['filter' => 'role:admin,contabilidad']);
    $routes->get('reportes/grafico', 'contabilidad\contabilidadController::grafico',['filter' => 'role:admin,contabilidad']);
});

// ============================================================================
// INVENTARIOS POR SUCURSAL
// Roles: admin, vendedor (consulta stock por sucursal)
// ============================================================================
$routes->group('inventariosucursales', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'sucursales\stockSucursalesController::index', ['filter' => 'role:admin,vendedor']);
    $routes->get('filtered', 'sucursales\stockSucursalesController::filtered', ['filter' => 'role:admin,vendedor']);
    $routes->get('reporteStock', 'sucursales\stockSucursalesController::reporteStock', ['filter' => 'role:admin,vendedor']);
});

// Stock de Inventarios (Materia Prima)
$routes->group('stockinventario', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'inventarios\stockinventarioController::index', ['filter' => 'role:admin,almacen']);
    $routes->get('filtered', 'inventarios\stockinventarioController::filtered', ['filter' => 'role:admin,almacen']);
    $routes->get('reporteStock', 'inventarios\stockinventarioController::reporteStock', ['filter' => 'role:admin,almacen']);
});

// ============================================================================
// BAJAS Y PÉRDIDAS
// Roles: admin, almacen
// ============================================================================
$routes->group('baja', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'baja\bajaController::index', ['filter' => 'role:admin,almacen']);
    $routes->post('store', 'baja\bajaController::store', ['filter' => 'role:admin,almacen']);
    $routes->get('reportePDF', 'baja\bajaController::reportePDF', ['filter' => 'role:admin,almacen']);
});

// ============================================================================
// RUTAS CATCH-ALL (Manejo de errores)
// ============================================================================
$routes->group('', ['filter' => 'auth'], function ($routes) {
    $routes->get('login', 'login\loginController::index');
    $routes->post('login', 'login\loginController::authenticate');
    $routes->get('logout', 'login\loginController::logout');
    $routes->get('/(:any)', 'Home::root/$1');
});

// ============================================================================
// CONFIGURACIÓN ADICIONAL
// ============================================================================
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
