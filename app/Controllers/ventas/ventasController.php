<?php

namespace App\Controllers\ventas;

use App\Controllers\BaseController;

use App\Libraries\CierreVentaPdf;
use App\Libraries\CierreVentaPdf1;


use App\Models\Categoria\CategoriaModel;
use App\Models\Cliente\ClienteModel;
use App\Models\StockSucursal\StockSucursalModel;
use App\Models\Venta\DetalleModel;
use App\Models\Venta\VentaModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Database\Exceptions\DatabaseException;

class ventasController extends BaseController
{
    /**
     * @var CategoriaModel 
     */
    protected $categoriaModel;
    protected $clienteModel;
    protected $stockSucursalModel;
    protected $ventaModel;
    protected $detalleModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {

        $this->categoriaModel = new CategoriaModel();
        $this->clienteModel = new ClienteModel();
        $this->stockSucursalModel = new StockSucursalModel();
        $this->ventaModel = new VentaModel();
        $this->detalleModel = new DetalleModel();
    }

    /**
     * Muestra la lista de ventas (Operación READ - todas).
     *
     * @return string
     */

    public function index()
    {
        $userId = session()->get('id');
        
        // 🔑 Modificación 1: Fijamos la sucursal_id a 2, ignorando la sesión.
        $sucursalId = 2; 

        if (empty($userId)) {
            return redirect()->to(base_url('login'))->with('error', 'Debe iniciar sesión para ver las ventas.');
        }

        // ❌ Modificación 2: Eliminamos la verificación de sucursal_id de la sesión
        /*
        if (empty($sucursalId)) {
            return redirect()->to(base_url('login'))->with('error', 'No tiene una sucursal asignada. Contacte al administrador.');
        }
        */

        $hoy = date('Y-m-d');

        $fecha_desde = $this->request->getGet('fecha_desde') ?? $hoy;
        $fecha_hasta = $this->request->getGet('fecha_hasta') ?? $hoy;
        $per_page = (int)($this->request->getGet('per_page') ?? 10);
        $page = (int)($this->request->getGet('page') ?? 1);

        if (!strtotime($fecha_desde)) $fecha_desde = $hoy;
        if (!strtotime($fecha_hasta)) $fecha_hasta = $hoy;
        if ($fecha_desde > $fecha_hasta) {
            [$fecha_desde, $fecha_hasta] = [$fecha_hasta, $fecha_desde];
        }

        $valid_per_page = [10, 20, 30, 50, 100];
        $per_page = in_array($per_page, $valid_per_page) ? $per_page : 10;
        $page = max(1, $page);

        $inicio = $fecha_desde . ' 00:00:00';
        $fin = $fecha_hasta . ' 23:59:59';

        $offset = ($page - 1) * $per_page;

        $db = \Config\Database::connect();

        // Los parámetros base usan el $sucursalId fijo = 2
        $baseParams = [$sucursalId, $inicio, $fin];

        //---------------------------------------------------------
        // 1. Conteo total de ventas (por sucursal)
        $countSql = "
            SELECT COUNT(*) as total
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL 
              AND v.sucursal_id = ?  -- ✅ sucursal_id = 2
              AND v.created_at >= ?
              AND v.created_at <= ?
        ";

        $countQuery = $db->query($countSql, $baseParams);
        if ($countQuery === false) {
            throw new \RuntimeException('Error en la consulta de Conteo de Ventas: ' . $db->error()['message']);
        }

        $countResult = $countQuery->getRow();
        $total = (int)($countResult->total ?? 0);

        //---------------------------------------------------------
        // 2. Monto total de ventas finalizadas (estado = '1')
        $totalVentasSql = "
            SELECT SUM(v.monto_total) AS total_monto
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL 
              AND v.sucursal_id = ?  -- ✅ sucursal_id = 2
              AND v.created_at >= ?
              AND v.created_at <= ?
              AND v.estado = '1'
        ";

        $totalVentasQuery = $db->query($totalVentasSql, $baseParams);
        if ($totalVentasQuery === false) {
            throw new \RuntimeException('Error en la consulta de Monto Total de Ventas: ' . $db->error()['message']);
        }

        $totalVentasResult = $totalVentasQuery->getRow();
        $totalVentas = (float)($totalVentasResult->total_monto ?? 0);

        //---------------------------------------------------------
        // 3. Monto total de ventas AL CONTADO
        $totalContadoSql = "
            SELECT SUM(v.monto_total) AS total_contado
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL 
              AND v.sucursal_id = ?  -- ✅ sucursal_id = 2
              AND v.created_at >= ?
              AND v.created_at <= ?
              AND v.estado = '1'
              AND v.tipo_pago = 'contado' 
        ";

        $totalContadoQuery = $db->query($totalContadoSql, $baseParams);
        if ($totalContadoQuery === false) {
            throw new \RuntimeException('Error en la consulta de Monto Total Contado: ' . $db->error()['message']);
        }

        $totalContadoResult = $totalContadoQuery->getRow();
        $totalContado = (float)($totalContadoResult->total_contado ?? 0);

        //---------------------------------------------------------
        // 4. Monto total de ventas A CRÉDITO
        $totalCreditoSql = "
            SELECT SUM(v.monto_total) AS total_credito
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL 
              AND v.sucursal_id = ?  -- ✅ sucursal_id = 2
              AND v.created_at >= ?
              AND v.created_at <= ?
              AND v.estado = '1'
              AND v.tipo_pago = 'credito' 
        ";

        $totalCreditoQuery = $db->query($totalCreditoSql, $baseParams);
        if ($totalCreditoQuery === false) {
            throw new \RuntimeException('Error en la consulta de Monto Total Crédito: ' . $db->error()['message']);
        }

        $totalCreditoResult = $totalCreditoQuery->getRow();
        $totalCredito = (float)($totalCreditoResult->total_credito ?? 0);

        //---------------------------------------------------------
        // 5. Listado de ventas con datos del cliente y personal UTO
        $sql = "
            SELECT 
                v.*,
                COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
                -- Campos del personal UTO (solo si es venta a crédito)
                p.nombre AS nombre_personal,
                p.dip,
                cargos.cargo,
                secciones.seccion
            FROM condoriri.ventas v
            LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
            -- JOIN con personal UTO (solo relevante para créditos, pero lo dejamos)
            LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
            LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.\"id_estado\" = true
            LEFT JOIN rrhh.cargos cargos ON cargos.id_cargo = e.id_cargo
            LEFT JOIN rrhh.secciones secciones ON secciones.id_seccion = e.id_seccion
            WHERE v.deleted_at IS NULL 
              AND v.sucursal_id = ?  -- ✅ sucursal_id = 2
              AND v.created_at >= ?
              AND v.created_at <= ?
            ORDER BY v.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $ventasParams = array_merge($baseParams, [$per_page, $offset]); // sucursal_id, inicio, fin, per_page, offset

        $ventasQuery = $db->query($sql, $ventasParams);
        if ($ventasQuery === false) {
            throw new \RuntimeException('Error en la consulta de Listado de Ventas: ' . $db->error()['message']);
        }

        $ventas = $ventasQuery->getResult();

        //---------------------------------------------------------
        // 6. Paginación
        $totalPages = ceil($total / $per_page);
        $hasPrev = $page > 1;
        $hasNext = $page < $totalPages;

        $pagerLinks = '';
        if ($totalPages > 1) {
            $baseUrl = base_url('ventas');
            $pagerLinks = '<nav aria-label="Paginación"><ul class="pagination justify-content-center mb-0">';

            $prevPage = $page - 1;
            $queryArr = [
                'fecha_desde' => $fecha_desde,
                'fecha_hasta' => $fecha_hasta,
                'per_page' => $per_page
            ];

            $prevUrl = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $prevPage]));
            $pagerLinks .= '<li class="page-item' . (!$hasPrev ? ' disabled' : '') . '"><a class="page-link" href="' . ($hasPrev ? $prevUrl : '#') . '">Anterior</a></li>';

            $start = max(1, $page - 2);
            $end = min($totalPages, $start + 4);
            if ($end - $start < 4 && $totalPages > 5) {
                $start = max(1, $end - 4);
            }

            for ($i = $start; $i <= $end; $i++) {
                $isActive = $i === $page ? ' active' : '';
                $pageUrl = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $i]));
                $pagerLinks .= '<li class="page-item' . $isActive . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
            }

            $nextPage = $page + 1;
            $nextUrl = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $nextPage]));
            $pagerLinks .= '<li class="page-item' . (!$hasNext ? ' disabled' : '') . '"><a class="page-link" href="' . ($hasNext ? $nextUrl : '#') . '">Siguiente</a></li>';
            $pagerLinks .= '</ul></nav>';
        }

        return view('ventas/index', [
            'ventas' => $ventas,
            'title' => 'Listado de Ventas',
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'per_page' => $per_page,
            'totalVentas' => $totalVentas,
            'totalContado' => $totalContado,
            'totalCredito' => $totalCredito,
            'pagerLinks' => $pagerLinks,
            'totalRegistros' => $total,
        ]);
    }
    public function index1()
{
    $userId = session()->get('id');
    $sucursalId = session()->get('sucursal_id'); // ✅ Obtenemos sucursal_id

    if (empty($userId)) {
        return redirect()->to(base_url('login'))->with('error', 'Debe iniciar sesión para ver las ventas.');
    }

    if (empty($sucursalId)) {
        return redirect()->to(base_url('login'))->with('error', 'No tiene una sucursal asignada. Contacte al administrador.');
    }

    $hoy = date('Y-m-d');

    $fecha_desde = $this->request->getGet('fecha_desde') ?? $hoy;
    $fecha_hasta = $this->request->getGet('fecha_hasta') ?? $hoy;
    $per_page = (int)($this->request->getGet('per_page') ?? 10);
    $page = (int)($this->request->getGet('page') ?? 1);

    if (!strtotime($fecha_desde)) $fecha_desde = $hoy;
    if (!strtotime($fecha_hasta)) $fecha_hasta = $hoy;
    if ($fecha_desde > $fecha_hasta) {
        [$fecha_desde, $fecha_hasta] = [$fecha_hasta, $fecha_desde];
    }

    $valid_per_page = [10, 20, 30, 50, 100];
    $per_page = in_array($per_page, $valid_per_page) ? $per_page : 10;
    $page = max(1, $page);

    $inicio = $fecha_desde . ' 00:00:00';
    $fin = $fecha_hasta . ' 23:59:59';

    $offset = ($page - 1) * $per_page;

    $db = \Config\Database::connect();

    // ✅ Cambiado: ahora usamos $sucursalId en lugar de $userId
    $baseParams = [$sucursalId, $inicio, $fin]; // ← PRIMERO: sucursal_id

    //---------------------------------------------------------
    // 1. Conteo total de ventas (por sucursal)
    $countSql = "
        SELECT COUNT(*) as total
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- ✅ Aquí
          AND v.created_at >= ?
          AND v.created_at <= ?
    ";

    $countQuery = $db->query($countSql, $baseParams);
    if ($countQuery === false) {
        throw new \RuntimeException('Error en la consulta de Conteo de Ventas: ' . $db->error()['message']);
    }

    $countResult = $countQuery->getRow();
    $total = (int)($countResult->total ?? 0);

    //---------------------------------------------------------
    // 2. Monto total de ventas finalizadas (estado = '1')
    $totalVentasSql = "
        SELECT SUM(v.monto_total) AS total_monto
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- ✅
          AND v.created_at >= ?
          AND v.created_at <= ?
          AND v.estado = '1'
    ";

    $totalVentasQuery = $db->query($totalVentasSql, $baseParams);
    if ($totalVentasQuery === false) {
        throw new \RuntimeException('Error en la consulta de Monto Total de Ventas: ' . $db->error()['message']);
    }

    $totalVentasResult = $totalVentasQuery->getRow();
    $totalVentas = (float)($totalVentasResult->total_monto ?? 0);

    //---------------------------------------------------------
    // 3. Monto total de ventas AL CONTADO
    $totalContadoSql = "
        SELECT SUM(v.monto_total) AS total_contado
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- ✅
          AND v.created_at >= ?
          AND v.created_at <= ?
          AND v.estado = '1'
          AND v.tipo_pago = 'contado' 
    ";

    $totalContadoQuery = $db->query($totalContadoSql, $baseParams);
    if ($totalContadoQuery === false) {
        throw new \RuntimeException('Error en la consulta de Monto Total Contado: ' . $db->error()['message']);
    }

    $totalContadoResult = $totalContadoQuery->getRow();
    $totalContado = (float)($totalContadoResult->total_contado ?? 0);

    //---------------------------------------------------------
    // 4. Monto total de ventas A CRÉDITO
    $totalCreditoSql = "
        SELECT SUM(v.monto_total) AS total_credito
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- ✅
          AND v.created_at >= ?
          AND v.created_at <= ?
          AND v.estado = '1'
          AND v.tipo_pago = 'credito' 
    ";

    $totalCreditoQuery = $db->query($totalCreditoSql, $baseParams);
    if ($totalCreditoQuery === false) {
        throw new \RuntimeException('Error en la consulta de Monto Total Crédito: ' . $db->error()['message']);
    }

    $totalCreditoResult = $totalCreditoQuery->getRow();
    $totalCredito = (float)($totalCreditoResult->total_credito ?? 0);

    //---------------------------------------------------------
    // 5. Listado de ventas con datos del cliente y personal UTO
    $sql = "
        SELECT 
            v.*,
            COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre,
            -- Campos del personal UTO (solo si es venta a crédito)
            p.nombre AS nombre_personal,
            p.dip,
            cargos.cargo,
            secciones.seccion
        FROM condoriri.ventas v
        LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
        -- JOIN con personal UTO (solo relevante para créditos, pero lo dejamos)
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.\"id_estado\" = true
        LEFT JOIN rrhh.cargos cargos ON cargos.id_cargo = e.id_cargo
        LEFT JOIN rrhh.secciones secciones ON secciones.id_seccion = e.id_seccion
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- ✅
          AND v.created_at >= ?
          AND v.created_at <= ?
        ORDER BY v.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $ventasParams = array_merge($baseParams, [$per_page, $offset]); // sucursal_id, inicio, fin, per_page, offset

    $ventasQuery = $db->query($sql, $ventasParams);
    if ($ventasQuery === false) {
        throw new \RuntimeException('Error en la consulta de Listado de Ventas: ' . $db->error()['message']);
    }

    $ventas = $ventasQuery->getResult();

    //---------------------------------------------------------
    // 6. Paginación
    $totalPages = ceil($total / $per_page);
    $hasPrev = $page > 1;
    $hasNext = $page < $totalPages;

    $pagerLinks = '';
    if ($totalPages > 1) {
        $baseUrl = base_url('ventas');
        $pagerLinks = '<nav aria-label="Paginación"><ul class="pagination justify-content-center mb-0">';

        $prevPage = $page - 1;
        $queryArr = [
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'per_page' => $per_page
        ];

        $prevUrl = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $prevPage]));
        $pagerLinks .= '<li class="page-item' . (!$hasPrev ? ' disabled' : '') . '"><a class="page-link" href="' . ($hasPrev ? $prevUrl : '#') . '">Anterior</a></li>';

        $start = max(1, $page - 2);
        $end = min($totalPages, $start + 4);
        if ($end - $start < 4 && $totalPages > 5) {
            $start = max(1, $end - 4);
        }

        for ($i = $start; $i <= $end; $i++) {
            $isActive = $i === $page ? ' active' : '';
            $pageUrl = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $i]));
            $pagerLinks .= '<li class="page-item' . $isActive . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
        }

        $nextPage = $page + 1;
        $nextUrl = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $nextPage]));
        $pagerLinks .= '<li class="page-item' . (!$hasNext ? ' disabled' : '') . '"><a class="page-link" href="' . ($hasNext ? $nextUrl : '#') . '">Siguiente</a></li>';
        $pagerLinks .= '</ul></nav>';
    }

    return view('ventas/index', [
        'ventas' => $ventas,
        'title' => 'Listado de Ventas',
        'fecha_desde' => $fecha_desde,
        'fecha_hasta' => $fecha_hasta,
        'per_page' => $per_page,
        'totalVentas' => $totalVentas,
        'totalContado' => $totalContado,
        'totalCredito' => $totalCredito,
        'pagerLinks' => $pagerLinks,
        'totalRegistros' => $total,
    ]);
}


    public function credito()
    {

          $productos = $this->stockSucursalModel
                          ->where('stock' . '>' . 0) 
                          ->findAll();




        $data = [
            'title'     => 'Registrar Venta Crédito',
            'productos' => $productos,

        ];


        return view('ventas/ventasCredito', $data);
    }


    public function buscarPersonalUto()
    {
        $db = db_connect();
        $dipPattern = $this->request->getGet('dip') . '%';

        $sql = "
            SELECT 
                p.id_persona, 
                p.nombre, 
                p.dip, 
                p.telefono, 
                p.celular, 
                COALESCE(e.\"id_estado\", false) AS es_empleado_uto, 
                c.cargo, 
                s.seccion 
            FROM public.personas p 
            LEFT JOIN rrhh.empleados e ON (p.id_persona = e.id_persona AND e.\"id_estado\") 
            LEFT JOIN rrhh.cargos c ON (e.id_cargo = c.id_cargo) 
            LEFT JOIN rrhh.secciones s ON (e.id_seccion = s.id_seccion) 
            WHERE 
                p.\"id_estado\" = true
                AND e.\"id_estado\" = true  
                AND p.dip ILIKE ?              
        ";

        $query = $db->query($sql, [$dipPattern]);
        $results = $query->getResult();


        $personal = array_filter($results, function ($person) {
            return $person->es_empleado_uto == true;
        });

        return $this->response->setJSON(array_values($personal));
    }



    /**
     * Muestra el formulario de registro de venta (Punto de Venta/POS).
     *
     * @return string
     */
    public function register()
    {


        $productos = $this->stockSucursalModel
                          ->where('stock' . '>' . 0) 
                          ->findAll();

        $clientes = $this->clienteModel->findAll();

        $data = [
            'title'     => 'Registrar Venta',
            'productos' => $productos,
            'clientes'  => $clientes,
        ];


        return view('ventas/ventasIndex', $data);
    }

    









    /**
     * Guarda la venta y actualiza el stock.
     *
     * @return RedirectResponse
     */
    public function guardarVenta(): RedirectResponse
    {
        // 1. Validación de método
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método no permitido.');
        }


        // 2. Obtención de datos del formulario
        $clienteId = (int)$this->request->getPost('cliente_id');
        $tipoPago = $this->request->getPost('tipo_pago');
        $productosJson = $this->request->getPost('productos');


        // 3. Validación de campos obligatorios
        if ($this->request->getPost('cliente_id') === null) {
            return redirect()->back()->with('error', 'Faltan datos obligatorios (ID de Cliente no enviado).');
        }


        if (empty($tipoPago)) {
            return redirect()->back()->with('error', 'Faltan datos obligatorios (Tipo de Pago).');
        }


        if (empty($productosJson)) {
            return redirect()->back()->with('error', 'Faltan datos obligatorios (Carrito vacío).');
        }


        // 4. Decodificación y validación de formato del carrito
        $productos = json_decode($productosJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($productos) || empty($productos)) {
            return redirect()->back()->with('error', 'Formato de productos inválido o carrito vacío.');
        }


        // 5. Configuración de IDs de contexto (Sucursal/Usuario)
        // Nota: Asegúrate de que estas variables de sesión están correctamente establecidas
        $sucursalId = session()->get('sucursal_id') ?? 1;
        $userId = session()->get('id') ?? 1;


        // 6. Validación de cliente
        if ($clienteId !== 0) {
            $cliente = $this->clienteModel->find($clienteId);


            if (!$cliente || (int)$cliente['estado'] == true) {
                return redirect()->back()->with('error', 'Cliente no válido o inactivo.');
            }
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $totalVenta = 0;
            $itemsDetalle = [];
            $stockItems = [];


            // 7. Procesamiento y validación de cada ítem del carrito
            foreach ($productos as $item) {

                if (!isset($item['id'])) {
                    throw new \Exception("Error de datos en el carrito: Un producto no tiene un ID (stock_id) asociado. Verifique la estructura JSON enviada.");
                }

                $stockId = (int)$item['id']; // El ID del producto en el carrito corresponde al stock_id
                $cantidad = (int) $item['quantity'];
                $precioUnitario = (float) $item['price'];
                $descuentoPorcentaje = (float) $item['discount'];

                if ($cantidad <= 0 || $precioUnitario <= 0) {
                    throw new \Exception("Cantidad o precio unitario inválido para el producto ID {$stockId}.");
                }


                // --- BÚSQUEDA Y VALIDACIÓN DEL PRODUCTO/STOCK ---
                if (!isset($stockItems[$stockId])) {

                    // Intento 1: Buscar por PK (la forma en que está configurado)
                    $stockItem = $this->stockSucursalModel->find($stockId);

                    if (!$stockItem) {
                        // Si falla la búsqueda por PK, intentamos buscar por producto_id y sucursal_id.
                        // Esto diagnostica si el ID enviado es el ID de Producto y no el PK de Stock.
                        $stockItem = $this->stockSucursalModel
                            ->where('producto_id', $stockId)
                            ->where('sucursal_id', $sucursalId)
                            ->first();

                        if ($stockItem) {
                            // Si lo encontramos, reasignamos el ID para usar el ID de stock real en el resto del proceso
                            $stockId = (int)$stockItem['id'];
                        }
                    }

                    $stockItems[$stockId] = $stockItem;
                }


                $stockItem = $stockItems[$stockId];

                // VALIDACIÓN 1: Existencia (Problema de Clave Primaria)
                if (!$stockItem) {
                    throw new \Exception("El producto (ID {$stockId}) no fue encontrado en la tabla 'stock_sucursal'. Confirme si el ID enviado es la CLAVE PRIMARIA de la tabla.");
                }

                // VALIDACIÓN 2: Estado (Problema de estado inactivo)
                if ((int)$stockItem['estado'] == 1) {
                    $estadoActual = (int)$stockItem['estado'];
                    $debugInfo = var_export($stockItem, true);

                    // Aquí se incluye el diagnóstico
                    throw new \Exception("Producto: {$stockItem['producto']} (ID {$stockId}) está INACTIVO. Estado actual: {$estadoActual}. Datos devueltos para inspección: " . $debugInfo);
                }

                // VALIDACIÓN 3: Stock suficiente
                if ($stockItem['stock'] < $cantidad) {
                    throw new \Exception("Stock insuficiente para: {$stockItem['producto']}. Stock disponible: {$stockItem['stock']}.");
                }

                // --- CÁLCULOS ---
                $subtotalBruto = $precioUnitario * $cantidad;
                $descuentoMonto = $subtotalBruto * ($descuentoPorcentaje / 100);
                $subtotalFinal = $subtotalBruto - $descuentoMonto;

                $totalVenta += $subtotalFinal;


                // Preparar datos para el detalle
                $itemsDetalle[] = [
                    'stock_id' => $stockId, // Se utiliza el ID de stock real (PK de la tabla)
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'subtotal' => $subtotalFinal,
                    'stock_actual' => $stockItem['stock'],
                    'producto_nombre' => $stockItem['producto']
                ];
            }


            // 8. Inserción de la Cabecera de Venta
            $ventaData = [
                'code' => 'TEMP',
                'cliente_id' => $clienteId,
                'sucursal_id' => $sucursalId,
                'tipo_pago' => $tipoPago,
                'monto_total' => $totalVenta,
                'estado' => 1,
                'observaciones' => '',
                'user_id' => $userId,
            ];

            $ventaId = $this->ventaModel->insert($ventaData);
            if (!$ventaId) {
                throw new \Exception('Error al crear la cabecera de la venta.');
            }

            // Actualizar código con formato correlativo basado en ID
            $codigoVenta = 'VENTA-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT);
            $this->ventaModel->update($ventaId, ['code' => $codigoVenta]);


            // 9. Inserción de Detalles y Actualización de Stock
            foreach ($itemsDetalle as $item) {

                $detalleData = [
                    'venta_id' => $ventaId,
                    'stock_id' => $item['stock_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                    'observaciones' => '',

                ];

                if (!$this->detalleModel->insert($detalleData)) {
                    throw new \Exception('Error al registrar el detalle de venta para el producto: ' . $item['producto_nombre'] . '.');
                }


                // Actualizar stock
                $newStock = $item['stock_actual'] - $item['cantidad'];
                if (!$this->stockSucursalModel->update($item['stock_id'], ['stock' => $newStock])) {
                    throw new \Exception('Error al actualizar el stock del producto ' . $item['producto_nombre'] . '.');
                }
            }

            // 10. Commit y respuesta exitosa
            $db->transCommit();

            // Redirigir a la función de generación de recibo
            return redirect()->to('/ventas/recibo/' . $ventaId)->with('success', 'Venta registrada exitosamente.');
        } catch (\Exception $e) {
            // 11. Rollback y respuesta de error
            $db->transRollback();

            $error = $e->getMessage();
            if ($e instanceof DatabaseException) {
                $error .= " (Error DB: {$db->error()['message']})";
            }

            return redirect()->back()->withInput()->with('error', 'Error al procesar la venta: ' . $error);
        }
    }

    public function guardarCreditoVenta(): RedirectResponse
    {

        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método no permitido.');
        }


        $personalUtoId = (int)$this->request->getPost('cliente_id');
        $tipoPago = $this->request->getPost('tipo_pago');
        $productosJson = $this->request->getPost('productos');

        var_dump($personalUtoId, $tipoPago, $productosJson);
        if ($personalUtoId <= 0) {
            return redirect()->back()->with('error', 'Debe seleccionar un personal UTO válido.');
        }

        if (empty($tipoPago)) {
            return redirect()->back()->with('error', 'Faltan datos obligatorios (Tipo de Pago).');
        }

        if (empty($productosJson)) {
            return redirect()->back()->with('error', 'Faltan datos obligatorios (Carrito vacío).');
        }


        $productos = json_decode($productosJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($productos) || empty($productos)) {
            return redirect()->back()->with('error', 'Formato de productos inválido o carrito vacío.');
        }

        // 5. Contexto
        $sucursalId = session()->get('sucursal_id') ?? 1;
        $userId = session()->get('id') ?? 1;

        // 6. ✅ VALIDAR QUE EL PERSONAL EXISTA Y SEA EMPLEADO ACTIVO
        $db = \Config\Database::connect();
        $persona = $db->table('public.personas p')
            ->select('p.id_persona, p.nombre')
            ->join('rrhh.empleados e', 'p.id_persona = e.id_persona AND e."id_estado" = true', 'inner')
            ->where('p.id_persona', $personalUtoId)
            ->where('p."id_estado"', true)
            ->get()
            ->getRow();

        if (!$persona) {
            return redirect()->back()->with('error', 'El personal seleccionado no es un empleado UTO activo.');
        }

        // 7. Iniciar transacción
        $db->transBegin();

        try {
            $totalVenta = 0;
            $itemsDetalle = [];
            $stockItems = [];

            // 8. Procesar productos
            foreach ($productos as $item) {
                $stockId = (int)($item['id'] ?? 0);
                $cantidad = (int)($item['quantity'] ?? 0);
                $precioUnitario = (float)($item['price'] ?? 0);
                $descuentoPorcentaje = (float)($item['discount'] ?? 0);

                if ($cantidad <= 0 || $precioUnitario <= 0 || $stockId <= 0) {
                    throw new \Exception("Datos inválidos en el carrito para el producto ID {$stockId}.");
                }

                if (!isset($stockItems[$stockId])) {
                    $stockItem = $this->stockSucursalModel->find($stockId);
                    if (!$stockItem) {
                        // Intento alternativo (por si se envía producto_id en lugar de stock_id)
                        $stockItem = $this->stockSucursalModel
                            ->where('producto_id', $stockId)
                            ->where('sucursal_id', $sucursalId)
                            ->first();
                        if ($stockItem) {
                            $stockId = (int)$stockItem['id'];
                        }
                    }
                    $stockItems[$stockId] = $stockItem;
                }

                $stockItem = $stockItems[$stockId];

                if (!$stockItem) {
                    throw new \Exception("Producto no encontrado en stock (ID: {$stockId}).");
                }

                if ((int)$stockItem['estado'] !== 0) { // asumiendo 0 = activo, 1 = inactivo
                    throw new \Exception("Producto {$stockItem['producto']} está inactivo.");
                }

                if ($stockItem['stock'] < $cantidad) {
                    throw new \Exception("Stock insuficiente para: {$stockItem['producto']}. Disponible: {$stockItem['stock']}.");
                }

                $subtotalBruto = $precioUnitario * $cantidad;
                $descuentoMonto = $subtotalBruto * ($descuentoPorcentaje / 100);
                $subtotalFinal = $subtotalBruto - $descuentoMonto;
                $totalVenta += $subtotalFinal;

                $itemsDetalle[] = [
                    'stock_id' => $stockId,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'subtotal' => $subtotalFinal,
                    'stock_actual' => $stockItem['stock'],
                    'producto_nombre' => $stockItem['producto']
                ];
            }

            // 9. ✅ INSERTAR VENTA CON personal_uto_id
            $ventaData = [
                'code' => 'TEMP',
                'cliente_id' => null,
                'sucursal_id' => $sucursalId,
                'tipo_pago' => $tipoPago,
                'monto_total' => $totalVenta,
                'estado' => 1,
                'observaciones' => 'Venta a crédito para personal UTO',
                'user_id' => $userId,
                'personal_uto_id' => $personalUtoId,
            ];

            $ventaId = $this->ventaModel->insert($ventaData);
            if (!$ventaId) {
                throw new \Exception('Error al crear la cabecera de la venta.');
            }

            // Actualizar código con formato correlativo basado en ID
            $codigoVenta = 'VENTA-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT);
            $this->ventaModel->update($ventaId, ['code' => $codigoVenta]);

            // 10. Insertar detalles y actualizar stock
            foreach ($itemsDetalle as $item) {
                $detalleData = [
                    'venta_id' => $ventaId,
                    'stock_id' => $item['stock_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                    'observaciones' => '',
                ];

                if (!$this->detalleModel->insert($detalleData)) {
                    throw new \Exception('Error al registrar el detalle de venta para: ' . $item['producto_nombre']);
                }

                $newStock = $item['stock_actual'] - $item['cantidad'];
                if (!$this->stockSucursalModel->update($item['stock_id'], ['stock' => $newStock])) {
                    throw new \Exception('Error al actualizar el stock de: ' . $item['producto_nombre']);
                }
            }

            $db->transCommit();
            return redirect()->to('/ventas/recibo/' . $ventaId)->with('success', 'Venta a crédito registrada exitosamente.');
        } catch (\Exception $e) {
            $db->transRollback();
            $error = $e->getMessage();
            if ($db->error()['code'] ?? false) {
                $error .= " (DB: {$db->error()['message']})";
            }
            return redirect()->back()->withInput()->with('error', 'Error al procesar la venta: ' . $error);
        }
    }







    /**
     * Muestra el recibo de venta para impresión térmica en una nueva ventana.
     *
     * @param int $ventaId El ID de la venta recién creada.
     * @return string
     */
    public function generarRecibo(int $ventaId): string
    {
        $venta = $this->ventaModel->find($ventaId);

        if (!$venta) {
            return view('errors/html/error_404', [
                'message' => 'Venta no encontrada.'
            ]);
        }

        // --- Obtener detalles de la venta ---
        $db = \Config\Database::connect();
        $detalleTableName = 'condoriri.detalle_venta';
        $stockTableName = 'condoriri.stock_sucursales';
        $stockAlias = 'SS';

        $builder = $db->table($detalleTableName);
        $builder->select("{$detalleTableName}.*, {$stockAlias}.producto");
        $builder->join("{$stockTableName} AS {$stockAlias}", "{$stockAlias}.id = {$detalleTableName}.stock_id");
        $builder->where('venta_id', $ventaId);

        $query = $builder->get();
        if ($query === false) {
            $dbError = $db->error();
            $errorMessage = 'Error FATAL en la consulta de detalles de la venta (Recibo). ';
            $errorMessage .= 'CAUSA DB: [' . $dbError['code'] . '] ' . $dbError['message'] . '. ';
            $errorMessage .= 'SQL: ' . $db->getLastQuery();
            log_message('error', $errorMessage);
            throw new \RuntimeException($errorMessage);
        }
        $detalles = $query->getResultArray();

        // --- Obtener datos del cliente o personal UTO ---
        $cliente = null;
        $personal = null;

        // Si tiene cliente_id (y no es 0 o null), cargar cliente
        if (!empty($venta->cliente_id) && $venta->cliente_id != 0) {
            $cliente = $this->clienteModel->find($venta->cliente_id);
        }
        // Si tiene personal_uto_id, cargar datos del personal UTO
        elseif (!empty($venta->personal_uto_id)) {
            $personal = $db->table('public.personas p')
                ->select('p.nombre, p.dip, p.telefono, p.celular, c.cargo, s.seccion')
                ->join('rrhh.empleados e', 'p.id_persona = e.id_persona', 'left')
                ->join('rrhh.cargos c', 'e.id_cargo = c.id_cargo', 'left')
                ->join('rrhh.secciones s', 'e.id_seccion = s.id_seccion', 'left')
                ->where('p.id_persona', $venta->personal_uto_id)
                ->get()
                ->getRowArray();
        }

        // --- Información de la sucursal ---
        $sucursalInfo = [
            'nombre' => 'Mi Tienda POS',
            'direccion' => 'Av. Principal #123',
            'telefono' => '+591 555-1234',
            'nit' => '123456789-0'
        ];

        // --- Obtener usuario que generó la venta ---
        $usuarioGenerador = $db->table('condoriri.usuarios')
            ->select('nombre, apellidos')
            ->where('id', $venta->user_id)
            ->get()
            ->getRowArray();

        $nombreUsuario = $usuarioGenerador 
            ? trim(($usuarioGenerador['nombre'] ?? '') . ' ' . ($usuarioGenerador['apellidos'] ?? '')) 
            : 'Usuario Desconocido';

        // --- Pasar datos a la vista ---
        $data = [
            'title' => 'Recibo de Venta #' . $ventaId,
            'venta' => $venta,
            'detalles' => $detalles,
            'cliente' => $cliente,
            'personal' => $personal, // 👈 NUEVO: se pasa a la vista
            'sucursal' => $sucursalInfo,
            'nombreUsuario' => $nombreUsuario,
        ];

        return view('ventas/recibo_print', $data);
    }






    public function exportarPdfVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio');
        $fecha_fin    = $this->request->getGet('fecha_fin');
        $tipo         = $this->request->getGet('tipo'); 

        $hoy = date('Y-m-d');
        $fecha_inicio = $fecha_inicio ?: $hoy;
        $fecha_fin    = $fecha_fin ?: $hoy;

       
        $tipoConsulta = $tipo;
        if ($tipo === 'deposito_contado') {
            $tipoConsulta = 'contado';
        }

        if (!in_array($tipoConsulta, ['contado', 'credito', 'general'])) {
            $tipoConsulta = 'general';
            $tipo = 'general';
        }

        $reportData = $this->ventaModel->getDailySalesReportData($fecha_inicio, $fecha_fin, $tipoConsulta);

        
        if ($tipo === 'deposito_contado') {
            $pdfGenerator = new CierreVentaPdf1();
        } else {
            $pdfGenerator = new CierreVentaPdf();
        }

        $pdfGenerator->generarReporteVentas($reportData, [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin'    => $fecha_fin,
            'tipo'         => $tipo,
        ]);
    }

    public function exportarExcelVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? date('Y-m-d');
        $fecha_fin = $this->request->getGet('fecha_fin') ?? date('Y-m-d');
        $tipo = $this->request->getGet('tipo') ?? 'general';
        $sucursal_id = 2;

        $db = \Config\Database::connect();
        $inicio = $fecha_inicio . ' 00:00:00';
        $fin = $fecha_fin . ' 23:59:59';

        $sql = "SELECT v.*, COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre, p.nombre AS nombre_personal, p.dip
                FROM condoriri.ventas v
                LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
                LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
                WHERE v.deleted_at IS NULL AND v.sucursal_id = ? AND v.created_at >= ? AND v.created_at <= ?";
        
        $params = [$sucursal_id, $inicio, $fin];
        if ($tipo === 'contado') {
            $sql .= " AND v.tipo_pago = 'contado'";
        } elseif ($tipo === 'credito') {
            $sql .= " AND v.tipo_pago = 'credito'";
        }
        $sql .= " ORDER BY v.created_at DESC";

        $ventas = $db->query($sql, $params)->getResult();
        $totalVentas = array_sum(array_column($ventas, 'monto_total'));
        $totalRegistros = count($ventas);

        // Obtener detalles de productos para cada venta
        $ventasConDetalle = [];
        foreach ($ventas as $venta) {
            $sqlDetalle = "SELECT dv.*, ss.producto, ss.unidad
                          FROM condoriri.detalle_venta dv
                          LEFT JOIN condoriri.stock_sucursales ss ON ss.id = dv.stock_id
                          WHERE dv.venta_id = ? AND dv.deleted_at IS NULL
                          ORDER BY dv.id";
            $detalles = $db->query($sqlDetalle, [$venta->id])->getResult();
            $venta->detalles = $detalles;
            $ventasConDetalle[] = $venta;
        }

        $filename = 'ventas_' . $tipo . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        
        echo '<Styles>';
        echo '<Style ss:ID="titulo_uto"><Font ss:Bold="1" ss:Size="14" ss:Color="#1F4E78" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="subtitulo_uto"><Font ss:Bold="1" ss:Size="11" ss:Color="#1F4E78" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="info_uto"><Font ss:Size="9" ss:Color="#404040" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="header"><Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF" ss:FontName="Calibri"/><Interior ss:Color="#2E5090" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/></Borders></Style>';
        echo '<Style ss:ID="subheader"><Font ss:Bold="1" ss:Size="10" ss:Color="#000000" ss:FontName="Calibri"/><Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="number"><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="integer"><NumberFormat ss:Format="#,##0"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="center"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="total"><Font ss:Bold="1" ss:Size="11" ss:Color="#000000" ss:FontName="Calibri"/><Interior ss:Color="#FFF2CC" ss:Pattern="Solid"/><Borders><Border ss:Position="Top" ss:LineStyle="Double" ss:Weight="3" ss:Color="#000000"/></Borders></Style>';
        echo '<Style ss:ID="venta_header"><Font ss:Bold="1" ss:Size="10" ss:Color="#000000" ss:FontName="Calibri"/><Interior ss:Color="#E2EFDA" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#70AD47"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#70AD47"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#70AD47"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#70AD47"/></Borders></Style>';
        echo '<Style ss:ID="venta_total"><Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Calibri"/><Interior ss:Color="#70AD47" ss:Pattern="Solid"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#70AD47"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#70AD47"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#70AD47"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#70AD47"/></Borders></Style>';
        echo '<Style ss:ID="detalle_producto"><Font ss:Size="10" ss:FontName="Calibri"/><Interior ss:Color="#F8F9FA" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="detalle_number"><NumberFormat ss:Format="#,##0.00"/><Interior ss:Color="#F8F9FA" ss:Pattern="Solid"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="detalle_integer"><NumberFormat ss:Format="#,##0"/><Interior ss:Color="#F8F9FA" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '</Styles>';

        echo '<Worksheet ss:Name="Resumen">';
        echo '<Table>';
        echo '<Column ss:Width="500"/><Column ss:Width="150"/>';
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="1" ss:StyleID="titulo_uto"><Data ss:Type="String">UNIVERSIDAD TÉCNICA DE ORURO</Data></Cell></Row>';
        echo '<Row ss:Height="18"><Cell ss:MergeAcross="1" ss:StyleID="subtitulo_uto"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRARIAS Y NATURALES</Data></Cell></Row>';
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">CONDORIRI - LABORATORIO DE INNOVACIÓN</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Telf.: 5281745 – Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Email: dpdi@uto.edu.bo | www.uto.edu.bo</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="1" ss:StyleID="header"><Data ss:Type="String">REPORTE DE VENTAS - ' . strtoupper($tipo) . '</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Generado:</Data></Cell><Cell><Data ss:Type="String">' . date('d/m/Y H:i:s') . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Desde:</Data></Cell><Cell><Data ss:Type="String">' . $fecha_inicio . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Hasta:</Data></Cell><Cell><Data ss:Type="String">' . $fecha_fin . '</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">Total Ventas</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . $totalRegistros . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">Monto Total (Bs)</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . number_format($totalVentas, 2, '.', '') . '</Data></Cell></Row>';
        echo '</Table></Worksheet>';

        echo '<Worksheet ss:Name="Detalle Ventas">';
        echo '<Table>';
        echo '<Column ss:Width="50"/><Column ss:Width="120"/><Column ss:Width="200"/><Column ss:Width="180"/><Column ss:Width="70"/><Column ss:Width="90"/><Column ss:Width="90"/><Column ss:Width="100"/><Column ss:Width="110"/><Column ss:Width="100"/><Column ss:Width="130"/><Column ss:Width="80"/>';
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">ID Venta</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cliente/Personal</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Producto</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cantidad</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Unidad</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Precio Unit.</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Subtotal</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Monto Total</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Tipo Pago</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Estado</Data></Cell>';
        echo '</Row>';

        foreach ($ventasConDetalle as $venta) {
            $cliente = !empty($venta->personal_uto_id) ? ($venta->nombre_personal . ' - CI: ' . $venta->dip) : $venta->cliente_nombre;
            $rowCount = max(1, count($venta->detalles));
            
            if (empty($venta->detalles)) {
                echo '<Row>';
                echo '<Cell ss:StyleID="venta_header"><Data ss:Type="Number">' . ($venta->id ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . htmlspecialchars($venta->code ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . htmlspecialchars($cliente, ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="detalle_producto"><Data ss:Type="String">Sin productos</Data></Cell>';
                echo '<Cell ss:StyleID="detalle_integer"><Data ss:Type="Number">0</Data></Cell>';
                echo '<Cell ss:StyleID="detalle_producto"><Data ss:Type="String">-</Data></Cell>';
                echo '<Cell ss:StyleID="detalle_number"><Data ss:Type="Number">0</Data></Cell>';
                echo '<Cell ss:StyleID="detalle_number"><Data ss:Type="Number">0</Data></Cell>';
                echo '<Cell ss:StyleID="venta_total"><Data ss:Type="Number">' . ($venta->monto_total ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . ucfirst($venta->tipo_pago ?? '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($venta->created_at)) . '</Data></Cell>';
                echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . ($venta->estado == 1 ? 'Finalizada' : 'Cancelada') . '</Data></Cell>';
                echo '</Row>';
            } else {
                foreach ($venta->detalles as $idx => $detalle) {
                    echo '<Row>';
                    if ($idx === 0) {
                        echo '<Cell ss:StyleID="venta_header"><Data ss:Type="Number">' . ($venta->id ?? 0) . '</Data></Cell>';
                        echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . htmlspecialchars($venta->code ?? '', ENT_XML1) . '</Data></Cell>';
                        echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . htmlspecialchars($cliente, ENT_XML1) . '</Data></Cell>';
                    } else {
                        echo '<Cell></Cell><Cell></Cell><Cell></Cell>';
                    }
                    echo '<Cell ss:StyleID="detalle_producto"><Data ss:Type="String">' . htmlspecialchars($detalle->producto ?? 'N/A', ENT_XML1) . '</Data></Cell>';
                    echo '<Cell ss:StyleID="detalle_integer"><Data ss:Type="Number">' . ($detalle->cantidad ?? 0) . '</Data></Cell>';
                    echo '<Cell ss:StyleID="detalle_producto"><Data ss:Type="String">' . htmlspecialchars($detalle->unidad ?? 'und', ENT_XML1) . '</Data></Cell>';
                    echo '<Cell ss:StyleID="detalle_number"><Data ss:Type="Number">' . ($detalle->precio_unitario ?? 0) . '</Data></Cell>';
                    echo '<Cell ss:StyleID="detalle_number"><Data ss:Type="Number">' . ($detalle->subtotal ?? 0) . '</Data></Cell>';
                    if ($idx === 0) {
                        echo '<Cell ss:StyleID="venta_total"><Data ss:Type="Number">' . ($venta->monto_total ?? 0) . '</Data></Cell>';
                        echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . ucfirst($venta->tipo_pago ?? '') . '</Data></Cell>';
                        echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($venta->created_at)) . '</Data></Cell>';
                        echo '<Cell ss:StyleID="venta_header"><Data ss:Type="String">' . ($venta->estado == 1 ? 'Finalizada' : 'Cancelada') . '</Data></Cell>';
                    } else {
                        echo '<Cell></Cell><Cell></Cell><Cell></Cell><Cell></Cell>';
                    }
                    echo '</Row>';
                }
            }
        }
        
        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
    }
}
