<?php

namespace App\Controllers\ventas;

use App\Controllers\BaseController;

use App\Libraries\CierreVentaPdf;


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
        $searchTerm = $this->request->getGet('dip');
        $searchPattern = '%' . $searchTerm . '%';

        $sql = "
            SELECT 
                p.id_persona, 
                p.nombre_completo AS nombre, 
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
                AND (p.dip ILIKE ? OR p.nombre_completo ILIKE ?)
        ";

        $query = $db->query($sql, [$searchPattern, $searchPattern]);
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
        $productos = $this->stockSucursalModel->where('stock >', 0)->findAll();
        $clientes = $this->clienteModel->findAll();
        $categorias = $this->categoriaModel->findAll();

        $data = [
            'title'     => 'Registrar Venta',
            'productos' => $productos,
            'clientes'  => $clientes,
            'categorias' => $categorias,
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
                ->select('p.nombre_completo, p.dip, p.telefono, p.celular, c.cargo, s.seccion')
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

        $db = \Config\Database::connect();
        $usuarioGenerador = $db->table('condoriri.usuarios')
            ->select('nombre, apellidos')
            ->where('id', session()->get('id'))
            ->get()->getRowArray();
        $nombreUsuario = $usuarioGenerador
            ? ucwords(strtolower(trim(($usuarioGenerador['nombre'] ?? '') . ' ' . ($usuarioGenerador['apellidos'] ?? ''))))
            : 'Usuario';

        $pdfGenerator = new CierreVentaPdf();

        $pdfGenerator->generarReporteVentas($reportData, [
            'fecha_inicio'  => $fecha_inicio,
            'fecha_fin'     => $fecha_fin,
            'tipo'          => $tipo,
            'nombre_usuario'=> $nombreUsuario,
        ]);
    }

    public function exportarExcelVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? date('Y-m-d');
        $fecha_fin    = $this->request->getGet('fecha_fin')    ?? date('Y-m-d');
        $tipo         = $this->request->getGet('tipo')         ?? 'general';

        $service = new \App\Services\Shared\ExcelVentasMatrizService();
        $service->exportar($fecha_inicio, $fecha_fin, $tipo, \App\Services\Shared\ExcelVentasMatrizService::TIENDA_CEAC);
    }
}
