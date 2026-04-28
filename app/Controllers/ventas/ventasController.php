<?php

namespace App\Controllers\ventas;

use App\Controllers\BaseController;

use App\Libraries\CierreVentaPdf;
use App\Libraries\ArqueoVentasPdf;


use App\Models\Categoria\CategoriaModel;
use App\Models\Cliente\ClienteModel;
use App\Models\ClienteExterno\ClienteExternoModel;
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
    protected $clienteExternoModel;
    protected $stockSucursalModel;
    protected $ventaModel;
    protected $detalleModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->categoriaModel       = new CategoriaModel();
        $this->clienteModel         = new ClienteModel();
        $this->clienteExternoModel  = new ClienteExternoModel();
        $this->stockSucursalModel   = new StockSucursalModel();
        $this->ventaModel           = new VentaModel();
        $this->detalleModel         = new DetalleModel();
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

        // Filtro que excluye ventas agro (tienen producto_agro_id en detalle)
        $filtroNoAgro = "
            AND NOT EXISTS (
                SELECT 1 FROM condoriri.detalle_venta dv
                WHERE dv.venta_id = v.id AND dv.producto_agro_id IS NOT NULL
            )
        ";

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
              {$filtroNoAgro}
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
              {$filtroNoAgro}
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
              {$filtroNoAgro}
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
              {$filtroNoAgro}
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
                p.nombre AS nombre_personal,
                p.dip,
                cargos.cargo,
                secciones.seccion,
                ce.nombre AS nombre_externo,
                ce.dip AS dip_externo,
                ce.segmento
            FROM condoriri.ventas v
            LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
            LEFT JOIN condoriri.clientes_externos ce ON ce.id = v.cliente_externo_id
            LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
            LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.\"id_estado\" = true
            LEFT JOIN rrhh.cargos cargos ON cargos.id_cargo = e.id_cargo
            LEFT JOIN rrhh.secciones secciones ON secciones.id_seccion = e.id_seccion
            WHERE v.deleted_at IS NULL 
              AND v.sucursal_id = ?  -- ✅ sucursal_id = 2
              AND v.created_at >= ?
              AND v.created_at <= ?
              {$filtroNoAgro}
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
        $db      = db_connect();
        $termino = $this->request->getGet('dip');
        $pattern = '%' . $termino . '%';

        // Personal UTO
        $sqlUto = "
            SELECT
                p.id_persona,
                p.nombre AS nombre,
                p.dip,
                p.telefono,
                p.celular,
                c.cargo,
                s.seccion,
                'uto' AS tipo
            FROM public.personas p
            LEFT JOIN rrhh.empleados e ON (p.id_persona = e.id_persona AND e.\"id_estado\")
            LEFT JOIN rrhh.cargos c    ON (e.id_cargo = c.id_cargo)
            LEFT JOIN rrhh.secciones s ON (e.id_seccion = s.id_seccion)
            WHERE p.\"id_estado\" = true
              AND e.\"id_estado\" = true
              AND (p.dip ILIKE ? OR p.nombre ILIKE ?)
            LIMIT 3
        ";
        $uto = $db->query($sqlUto, [$pattern, $pattern])->getResult();

        // Clientes externos
        $sqlExt = "
            SELECT
                id,
                nombre,
                dip,
                NULL AS telefono,
                NULL AS celular,
                segmento AS cargo,
                NULL AS seccion,
                user_id,
                created_at,
                'externo' AS tipo
            FROM condoriri.clientes_externos
            WHERE deleted_at IS NULL
              AND estado = true
              AND (dip ILIKE ? OR nombre ILIKE ?)
            LIMIT 3
        ";
        $externos = $db->query($sqlExt, [$pattern, $pattern])->getResult();

        return $this->response->setJSON(array_values(array_slice(array_merge($uto, $externos), 0, 3)));
    }

    public function guardarClienteExterno()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'error' => 'Método no permitido.']);
        }

        $nombre   = strtoupper(trim($this->request->getPost('nombre') ?? ''));
        $dip      = trim($this->request->getPost('dip') ?? '');
        $segmento = trim($this->request->getPost('segmento') ?? '');

        if (empty($nombre) || empty($dip) || empty($segmento)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Nombre, DIP y segmento son obligatorios.']);
        }

        $existente = $this->clienteExternoModel
            ->where('dip', $dip)
            ->where('deleted_at IS NULL')
            ->first();
        if ($existente) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => "Ya existe un cliente externo con DIP {$dip}: {$existente->nombre} ({$existente->segmento}).",
            ]);
        }

        $id = $this->clienteExternoModel->insert([
            'nombre'   => $nombre,
            'dip'      => $dip,
            'segmento' => $segmento,
            'estado'   => true,
            'user_id'  => session()->get('id'),
        ]);

        if (!$id) {
            return $this->response->setJSON(['success' => false, 'error' => 'Error al guardar el cliente.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'cliente' => [
                'id'       => $id,
                'nombre'   => $nombre,
                'dip'      => $dip,
                'segmento' => $segmento,
                'tipo'     => 'externo',
            ],
        ]);
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

            $codigoVenta = $this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago);
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

        $receptorId   = (int)$this->request->getPost('cliente_id');
        $tipoReceptor = $this->request->getPost('tipo_receptor'); // 'uto' | 'externo'
        $tipoPago     = $this->request->getPost('tipo_pago');
        $productosJson = $this->request->getPost('productos');

        if ($receptorId <= 0) {
            return redirect()->back()->with('error', 'Debe seleccionar un receptor válido.');
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

        $sucursalId = session()->get('sucursal_id') ?? 1;
        $userId     = session()->get('id') ?? 1;

        $db = \Config\Database::connect();

        // Validar receptor según tipo
        if ($tipoReceptor === 'externo') {
            $receptor = $this->clienteExternoModel->find($receptorId);
            if (!$receptor || !$receptor->estado) {
                return redirect()->back()->with('error', 'El cliente externo seleccionado no es válido.');
            }
        } else {
            $receptor = $db->table('public.personas p')
                ->select('p.id_persona, p.nombre AS nombre')
                ->join('rrhh.empleados e', 'p.id_persona = e.id_persona AND e."id_estado" = true', 'inner')
                ->where('p.id_persona', $receptorId)
                ->where('p."id_estado"', true)
                ->get()->getRow();
            if (!$receptor) {
                return redirect()->back()->with('error', 'El personal seleccionado no es un empleado UTO activo.');
            }
        }

        $db->transBegin();

        try {
            $totalVenta  = 0;
            $itemsDetalle = [];
            $stockItems  = [];

            foreach ($productos as $item) {
                $stockId            = (int)($item['id'] ?? 0);
                $cantidad           = (int)($item['quantity'] ?? 0);
                $precioUnitario     = (float)($item['price'] ?? 0);
                $descuentoPorcentaje = (float)($item['discount'] ?? 0);

                if ($cantidad <= 0 || $precioUnitario <= 0 || $stockId <= 0) {
                    throw new \Exception("Datos inválidos en el carrito para el producto ID {$stockId}.");
                }

                if (!isset($stockItems[$stockId])) {
                    $stockItem = $this->stockSucursalModel->find($stockId);
                    if (!$stockItem) {
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
                if ((int)$stockItem['estado'] !== 0) {
                    throw new \Exception("Producto {$stockItem['producto']} está inactivo.");
                }
                if ($stockItem['stock'] < $cantidad) {
                    throw new \Exception("Stock insuficiente para: {$stockItem['producto']}. Disponible: {$stockItem['stock']}.");
                }

                $subtotalBruto = $precioUnitario * $cantidad;
                $subtotalFinal = $subtotalBruto - ($subtotalBruto * ($descuentoPorcentaje / 100));
                $totalVenta   += $subtotalFinal;

                $itemsDetalle[] = [
                    'stock_id'            => $stockId,
                    'cantidad'            => $cantidad,
                    'precio_unitario'     => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'subtotal'            => $subtotalFinal,
                    'stock_actual'        => $stockItem['stock'],
                    'producto_nombre'     => $stockItem['producto'],
                ];
            }

            $ventaData = [
                'code'               => 'TEMP',
                'cliente_id'         => null,
                'sucursal_id'        => $sucursalId,
                'tipo_pago'          => $tipoPago,
                'monto_total'        => $totalVenta,
                'estado'             => 1,
                'observaciones'      => 'Venta a crédito',
                'user_id'            => $userId,
                'personal_uto_id'    => $tipoReceptor === 'uto'      ? $receptorId : null,
                'cliente_externo_id' => $tipoReceptor === 'externo'  ? $receptorId : null,
            ];

            $ventaId = $this->ventaModel->insert($ventaData);
            if (!$ventaId) {
                throw new \Exception('Error al crear la cabecera de la venta.');
            }

            $codigoVenta = $this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago);
            $this->ventaModel->update($ventaId, ['code' => $codigoVenta]);

            foreach ($itemsDetalle as $item) {
                if (!$this->detalleModel->insert([
                    'venta_id'       => $ventaId,
                    'stock_id'       => $item['stock_id'],
                    'cantidad'       => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal'       => $item['subtotal'],
                    'observaciones'  => '',
                ])) {
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
            return redirect()->back()->withInput()->with('error', 'Error al procesar la venta: ' . $e->getMessage());
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
        $builder->where("{$detalleTableName}.deleted_at IS NULL");

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
        $cliente  = null;
        $personal = null;
        $clienteExterno = null;

        if (!empty($venta->personal_uto_id)) {
            $personal = $db->table('public.personas p')
                ->select('p.nombre, p.dip, p.telefono, p.celular, c.cargo, s.seccion')
                ->join('rrhh.empleados e', 'p.id_persona = e.id_persona', 'left')
                ->join('rrhh.cargos c', 'e.id_cargo = c.id_cargo', 'left')
                ->join('rrhh.secciones s', 'e.id_seccion = s.id_seccion', 'left')
                ->where('p.id_persona', $venta->personal_uto_id)
                ->get()->getRowArray();
        } elseif (!empty($venta->cliente_externo_id)) {
            $clienteExterno = $db->table('condoriri.clientes_externos')
                ->select('nombre, dip, segmento')
                ->where('id', $venta->cliente_externo_id)
                ->get()->getRowArray();
        } elseif (!empty($venta->cliente_id) && $venta->cliente_id != 0) {
            $cliente = $this->clienteModel->find($venta->cliente_id);
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
            'title'          => 'Recibo de Venta #' . $ventaId,
            'venta'          => $venta,
            'detalles'       => $detalles,
            'cliente'        => $cliente,
            'personal'       => $personal,
            'clienteExterno' => $clienteExterno,
            'sucursal'       => $sucursalInfo,
            'nombreUsuario'  => $nombreUsuario,
        ];

        return view('ventas/recibo_print', $data);
    }






    /**
     * Devuelve JSON con la última venta del usuario hoy (módulo lácteos tienda, sucursal_id=2).
     * Incluye detalles originales y lista de productos disponibles con stock.
     */
    public function ultimaVenta()
    {
        $userId     = (int)session()->get('id');
        $sucursalId = 2;
        $db         = \Config\Database::connect();

        // Última venta del usuario hoy en este módulo (excluye agro)
        $venta = $db->query("
            SELECT v.*
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL
              AND v.user_id = ?
              AND v.sucursal_id = ?
              AND DATE(v.created_at) = CURRENT_DATE
              AND NOT EXISTS (
                  SELECT 1 FROM condoriri.detalle_venta dv
                  WHERE dv.venta_id = v.id AND dv.producto_agro_id IS NOT NULL
              )
            ORDER BY v.id DESC
            LIMIT 1
        ", [$userId, $sucursalId])->getRow();

        if (!$venta) {
            return $this->response->setJSON(['venta' => null]);
        }

        // Detalles originales con nombre del producto
        $detalles = $db->query("
            SELECT dv.id, dv.stock_id, dv.cantidad, dv.precio_unitario, dv.subtotal, ss.producto
            FROM condoriri.detalle_venta dv
            JOIN condoriri.stock_sucursales ss ON ss.id = dv.stock_id
            WHERE dv.venta_id = ? AND dv.deleted_at IS NULL
        ", [$venta->id])->getResult();

        // Receptor actual
        $receptor = ['tipo' => 'cliente', 'id' => null, 'nombre' => 'Consumidor Final'];
        if (!empty($venta->personal_uto_id)) {
            $p = $db->query("
                SELECT p.id_persona AS id, p.nombre
                FROM public.personas p WHERE p.id_persona = ?
            ", [$venta->personal_uto_id])->getRow();
            if ($p) $receptor = ['tipo' => 'uto', 'id' => $p->id, 'nombre' => $p->nombre];
        } elseif (!empty($venta->cliente_externo_id)) {
            $ce = $db->query("
                SELECT id, nombre, dip, segmento
                FROM condoriri.clientes_externos WHERE id = ?
            ", [$venta->cliente_externo_id])->getRow();
            if ($ce) $receptor = ['tipo' => 'externo', 'id' => $ce->id, 'nombre' => $ce->nombre, 'dip' => $ce->dip, 'segmento' => $ce->segmento];
        } elseif (!empty($venta->cliente_id)) {
            $c = $db->query("
                SELECT id, nombre_completo AS nombre
                FROM condoriri.clientes WHERE id = ?
            ", [$venta->cliente_id])->getRow();
            if ($c) $receptor = ['tipo' => 'cliente', 'id' => $c->id, 'nombre' => $c->nombre];
        }

        // Productos disponibles con stock (para agregar al carrito)
        $productosDisponibles = $this->stockSucursalModel->where('stock >', 0)->findAll();

        return $this->response->setJSON([
            'venta'                => $venta,
            'detalles'             => $detalles,
            'receptor'             => $receptor,
            'productos_disponibles' => $productosDisponibles,
        ]);
    }

    /**
     * Actualiza la última venta del usuario hoy (módulo lácteos tienda).
     * Revierte stock original, valida nuevo carrito, reemplaza detalles y actualiza monto.
     */
    public function updateUltimaVenta()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'error' => 'Método no permitido.']);
        }

        $userId     = (int)session()->get('id');
        $sucursalId = 2;
        $db         = \Config\Database::connect();

        $ventaId      = (int)$this->request->getPost('venta_id');
        $receptorId   = (int)$this->request->getPost('receptor_id');
        $tipoReceptor = $this->request->getPost('tipo_receptor'); // 'cliente' | 'uto' | 'externo'
        $carritoJson  = $this->request->getPost('carrito');

        if ($ventaId <= 0 || empty($carritoJson)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Datos incompletos.']);
        }

        $carrito = json_decode($carritoJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($carrito)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Carrito inválido.']);
        }

        // Verificar que la venta pertenece al usuario, es de hoy y es de este módulo
        $venta = $db->query("
            SELECT v.*
            FROM condoriri.ventas v
            WHERE v.id = ?
              AND v.deleted_at IS NULL
              AND v.user_id = ?
              AND v.sucursal_id = ?
              AND DATE(v.created_at) = CURRENT_DATE
              AND NOT EXISTS (
                  SELECT 1 FROM condoriri.detalle_venta dv
                  WHERE dv.venta_id = v.id AND dv.producto_agro_id IS NOT NULL
              )
        ", [$ventaId, $userId, $sucursalId])->getRow();

        if (!$venta) {
            return $this->response->setJSON(['success' => false, 'error' => 'Venta no encontrada o no editable.']);
        }

        // Verificar que sea la última venta del usuario hoy
        $ultima = $db->query("
            SELECT id FROM condoriri.ventas
            WHERE deleted_at IS NULL AND user_id = ? AND sucursal_id = ?
              AND DATE(created_at) = CURRENT_DATE
              AND NOT EXISTS (
                  SELECT 1 FROM condoriri.detalle_venta dv
                  WHERE dv.venta_id = condoriri.ventas.id AND dv.producto_agro_id IS NOT NULL
              )
            ORDER BY id DESC LIMIT 1
        ", [$userId, $sucursalId])->getRow();

        if (!$ultima || (int)$ultima->id !== $ventaId) {
            return $this->response->setJSON(['success' => false, 'error' => 'Solo puede editar su última venta del día.']);
        }

        $db->transBegin();
        try {
            // 1. Leer detalles originales
            $detallesOriginales = $db->query("
                SELECT dv.id, dv.stock_id, dv.cantidad
                FROM condoriri.detalle_venta dv
                WHERE dv.venta_id = ? AND dv.deleted_at IS NULL
            ", [$ventaId])->getResult();

            // 2. Devolver stock original
            foreach ($detallesOriginales as $det) {
                $db->query("
                    UPDATE condoriri.stock_sucursales
                    SET stock = stock + ?
                    WHERE id = ?
                ", [$det->cantidad, $det->stock_id]);
            }

            // 3. Validar nuevo carrito (stock suficiente en todos antes de descontar)
            $itemsNuevos = [];
            $nuevoTotal  = 0;
            foreach ($carrito as $item) {
                $stockId        = (int)($item['id'] ?? 0);
                $cantidad       = (int)($item['cantidad'] ?? 0);
                $precioUnitario = (float)($item['precio_unitario'] ?? 0);

                if ($stockId <= 0 || $cantidad <= 0 || $precioUnitario <= 0) {
                    throw new \Exception('Datos inválidos en el carrito.');
                }

                $stockItem = $this->stockSucursalModel->find($stockId);
                if (!$stockItem) {
                    throw new \Exception("Producto ID {$stockId} no encontrado.");
                }
                if ($stockItem['stock'] < $cantidad) {
                    throw new \Exception("Stock insuficiente para: {$stockItem['producto']}. Disponible: {$stockItem['stock']}.");
                }

                $subtotal     = $precioUnitario * $cantidad;
                $nuevoTotal  += $subtotal;
                $itemsNuevos[] = [
                    'stock_id'        => $stockId,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => $subtotal,
                    'stock_actual'    => $stockItem['stock'],
                ];
            }

            // 4. Soft-delete de detalles originales
            foreach ($detallesOriginales as $det) {
                $this->detalleModel->delete($det->id);
            }

            // 5. Insertar nuevos detalles y descontar stock
            foreach ($itemsNuevos as $item) {
                $this->detalleModel->insert([
                    'venta_id'        => $ventaId,
                    'stock_id'        => $item['stock_id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal'        => $item['subtotal'],
                    'observaciones'   => '',
                ]);

                $db->query("
                    UPDATE condoriri.stock_sucursales
                    SET stock = stock - ?
                    WHERE id = ?
                ", [$item['cantidad'], $item['stock_id']]);
            }

            // 6. Actualizar receptor y monto en la venta
            $updateVenta = ['monto_total' => $nuevoTotal];
            if ($tipoReceptor === 'uto') {
                $updateVenta['personal_uto_id']    = $receptorId ?: null;
                $updateVenta['cliente_externo_id'] = null;
                $updateVenta['cliente_id']         = null;
            } elseif ($tipoReceptor === 'externo') {
                $updateVenta['cliente_externo_id'] = $receptorId ?: null;
                $updateVenta['personal_uto_id']    = null;
                $updateVenta['cliente_id']         = null;
            } else {
                $updateVenta['cliente_id']         = $receptorId ?: null;
                $updateVenta['personal_uto_id']    = null;
                $updateVenta['cliente_externo_id'] = null;
            }
            $this->ventaModel->update($ventaId, $updateVenta);

            $db->transCommit();

            return $this->response->setJSON([
                'success'     => true,
                'nuevo_monto' => $nuevoTotal,
                'recibo_url'  => base_url('ventas/recibo/' . $ventaId),
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
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

    public function exportarArqueoPdf()
    {
        $hoy          = date('Y-m-d');
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?: $hoy;
        $fecha_fin    = $this->request->getGet('fecha_fin')    ?: $hoy;

        $reportData = $this->ventaModel->getDailySalesReportData($fecha_inicio, $fecha_fin, null);

        $db = \Config\Database::connect();
        $usuarioGenerador = $db->table('condoriri.usuarios')
            ->select('nombre, apellidos')
            ->where('id', session()->get('id'))
            ->get()->getRowArray();
        $nombreUsuario = $usuarioGenerador
            ? ucwords(strtolower(trim(($usuarioGenerador['nombre'] ?? '') . ' ' . ($usuarioGenerador['apellidos'] ?? ''))))
            : 'Usuario';

        $pdf = new ArqueoVentasPdf();
        $pdf->generarArqueo($reportData, [
            'fecha_inicio'      => $fecha_inicio,
            'fecha_fin'         => $fecha_fin,
            'nombre_usuario'    => $nombreUsuario,
            'titulo_modulo'     => 'TIENDA CEAC (VENTAS GENERALES)',
            'responsable_cargo' => 'Responsable - Derivados Lacteos',
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
