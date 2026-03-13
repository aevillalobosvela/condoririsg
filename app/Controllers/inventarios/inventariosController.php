<?php

namespace App\Controllers\Inventarios;

use App\Controllers\BaseController;
use App\Libraries\CierreVentaInve;
use App\Libraries\CierreVentaInve1;
use App\Libraries\CierreVentaPdf;
use App\Libraries\ReporteLacteos;
use App\Libraries\ReporteInventario;
use App\Libraries\ReporteCalidad;
use App\Libraries\ReporteControlCalidad;
use App\Models\Inventario\InventarioModel;
use App\Models\UsuarioModel;
use App\Models\Producto\ProductoModel;
use App\Models\Categoria\CategoriaModel;
use App\Models\Cliente\ClienteModel;
use App\Models\StockSucursal\StockSucursalModel;
use App\Models\Unidad\UnidadModel;
use App\Models\Venta\DetalleModel;
use App\Models\Venta\VentaModel;
use App\Models\MateriaPrima\MateriaPrimaModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;

class InventariosController extends BaseController
{
    protected $inventarioModel;
    protected $productoModel;
    protected $categoriaModel;
    protected $unidadModel;
    protected $clienteModel;
    protected $ventaModel;
    protected $detalleModel;
    protected $stockSucursalModel;
    protected $materiaPrimaModel;


    public function __construct()
    {
        $this->inventarioModel = new InventarioModel();
        $this->productoModel = new ProductoModel();
        $this->categoriaModel = new CategoriaModel();
        $this->unidadModel = new UnidadModel();
        $this->clienteModel = new ClienteModel();
        $this->ventaModel = new VentaModel();
        $this->detalleModel = new DetalleModel();
        $this->materiaPrimaModel = new MateriaPrimaModel();
        helper(['form', 'url']);
    }

    public function index(): string
    {
        $mostrarTodos = $this->request->getGet('mostrar_todos');
        $per_page = $this->request->getGet('per_page') ?? 20;
        $nombre = $this->request->getGet('nombre') ?? '';
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';

        // Si hay filtros personalizados, usar la lógica existente
        if (!empty($nombre) || (!empty($fecha_inicio) && !empty($fecha_fin))) {
            $inventarios = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);
            $pager = null;
        } else {
            // Sin filtros: mostrar solo hoy por defecto, todos si se especifica
            $builder = $this->inventarioModel->orderBy('created_at', 'DESC');

            if (!$mostrarTodos) {
                // Filtrar solo por el día de hoy (comportamiento por defecto)
                $hoy = date('Y-m-d');
                $builder->where("DATE(created_at)", $hoy);
            }

            // Aplicar paginación
            if ($per_page === 'all') {
                $inventarios = $builder->findAll();
                $pager = null;
            } else {
                $per_page = (int)$per_page;
                $inventarios = $builder->paginate($per_page, 'default');
                $pager = $this->inventarioModel->pager;
            }
        }

        // Obtener resúmenes
    $resumenInventario = $this->inventarioModel->getResumen($fecha_inicio, $fecha_fin);
    $resumenProducto = $this->productoModel->getResumen($fecha_inicio, $fecha_fin);
    $resumenPorNombre = $this->inventarioModel->getResumenPorNombre($fecha_inicio, $fecha_fin);

    $data = [
        'inventarios' => $inventarios,
        'title' => 'Listado de Inventarios',
        'filters' => [
            'nombre' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
        ],
        'per_page' => $per_page,
        'pager' => $pager ?? null,
        'resumenInventario' => $resumenInventario,
        'resumenProducto' => $resumenProducto,
        'resumenPorNombre' => $resumenPorNombre,
        'mostrar_todos' => $mostrarTodos,
    ];
        return view('inventarios/inventariosIndex', $data);
    }

    public function show(int $id)
    {
        $inventario = $this->inventarioModel->find($id);

        if (!$inventario) {
            return redirect()->to('/inventarios')->with('error', 'Inventario no encontrado.');
        }

        $productosPlanos = $this->productoModel
            ->where('inventario_id', $id)
            ->orderBy('nombre', 'ASC')
            ->findAll();

        $productosArbol = $this->productoModel->buildTree($productosPlanos);

        // Ahora estos NO son null
        $categorias = $this->categoriaModel->findAll();
        $unidades   = $this->unidadModel->findAll();

        $categoriasSelect = [];
        foreach ($categorias as $cat) {
            $categoriasSelect[$cat['id']] = $cat['nombre'];
        }

        $unidadesSelect = [];
        foreach ($unidades as $uni) {
            $unidadesSelect[$uni['id']] = $uni['nombre'];
        }

        $data = [
            'inventario' => $inventario,
            'productos' => $productosArbol,
            'categoriasSelect' => $categoriasSelect,
            'unidadesSelect' => $unidadesSelect,
            'title' => 'Detalles del Inventario',
        ];

        return view('inventarios/inventariosShow', $data);
    }

    public function register(): string
    {
        $userId = session()->get('id');
        $sucursalId = session()->get('sucursal_id');

        $data = [
            'title' => 'Crear Nuevo Inventario',
            'inventario' => (object) [
                'id' => null,
                'nombre' => '',
                'code' => 'Auto-generado',
                'descripcion' => '',
                'stock' => '',
                'turno' => '',
                'estado' => true,
                'sucursal_id' => $sucursalId,
                'user_id' => $userId
            ],
            'auto_generate_code' => true,
            'materiasPrimas' => $this->materiaPrimaModel->findAll()
        ];

        return view('inventarios/inventariosForm', $data);
    }

    public function create(): RedirectResponse
    {
        $userId = session()->get('id');
        $sucursalId = session()->get('sucursal_id');

        if (empty($userId) || empty($sucursalId)) {
            return redirect()->back()->with('error', 'Error: La sesión de usuario o sucursal no es válida. Por favor, inicie sesión nuevamente.');
        }

        // Generar código único automático
        $code = $this->generateUniqueCode();

        // Validar los datos (remover validación de code ya que se genera automáticamente)
        $validation = $this->validate([
            'nombre' => 'required|max_length[100]',
            'stock' => 'required|greater_than_equal_to[0]',
            'turno' => 'required|in_list[AM,PM]',
            'estado' => 'required|in_list[0,1]',
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $nombre = $this->request->getPost('nombre');
        $currentStock = $this->request->getPost('stock');

        // Lógica de reserva acumulativa SOLO para 'LECHE'
        if ($nombre === 'LECHE') {
            $lastInventory = $this->inventarioModel
                ->where('sucursal_id', $sucursalId)
                ->where('nombre', 'LECHE')
                ->orderBy('id', 'DESC')
                ->first();

            $previousReserva = $lastInventory ? $lastInventory->reserva : 0;
            $newReserva = $previousReserva + $currentStock;
        } else {
            // Para otros productos, la reserva inicial es igual al stock
            $newReserva = $currentStock;
        }

        $data = [
            'nombre' => $nombre,
            'code' => $code,
            'descripcion' => $this->request->getPost('descripcion'),
            'stock' => $currentStock,
            'turno' => $this->request->getPost('turno'),
            'estado' => $this->request->getPost('estado'),
            'sucursal_id' => $sucursalId,

            'user_id' => $userId,
            'reserva' => $newReserva,

        ];

        try {
            if ($this->inventarioModel->insert($data)) {
                return redirect()->to('/inventarios')->with('success', 'Inventario creado exitosamente.');
            } else {
                return redirect()->back()->withInput()->with('error', 'No se pudo crear el inventario.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al crear el inventario: ' . $e->getMessage());
        }
    }

    /**
     * Genera un código único con formato INV-DIA-MES-AÑO-HORA
     * Si ya existe un código para la misma hora, agrega un contador
     */
    private function generateUniqueCode(): string
    {
        $fechaActual = new \DateTime();
        $baseCode = 'INV-' .
            $fechaActual->format('d') . '-' .
            $fechaActual->format('m') . '-' .
            $fechaActual->format('Y') . '-' .
            $fechaActual->format('H'); // Solo la hora, sin minutos

        // Verificar si ya existe un código con esta base
        $existingCodes = $this->inventarioModel
            ->like('code', $baseCode, 'after')
            ->orderBy('code', 'DESC')
            ->findAll();

        if (empty($existingCodes)) {
            // Si no existe, usar el código base sin sufijo
            return $baseCode;
        }

        // Obtener el último código existente para esta base
        $lastCode = $existingCodes[0]->code;

        // Si el último código es exactamente la base, agregar -1
        if ($lastCode === $baseCode) {
            return $baseCode . '-1';
        }

        // Si el último código tiene sufijo, incrementarlo
        if (preg_match('/^' . preg_quote($baseCode, '/') . '-(\d+)$/', $lastCode, $matches)) {
            $counter = (int)$matches[1] + 1;
            return $baseCode . '-' . $counter;
        }

        // Si no coincide el patrón, usar la base con -1
        return $baseCode . '-1';
    }

    public function edit(int $id)
    {
        $inventario = $this->inventarioModel->find($id);

        if (!$inventario) {
            return redirect()->to('/inventarios')->with('error', 'Inventario no encontrado.');
        }

        $data = [
            'inventario' => $inventario,
            'title' => 'Editar Inventario',
            'auto_generate_code' => false,
            'materiasPrimas' => $this->materiaPrimaModel->findAll()
        ];

        return view('inventarios/inventariosForm', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $userId = session()->get('id');
        $sucursalId = session()->get('sucursal_id');

        if (empty($userId) || empty($sucursalId)) {
            return redirect()->back()->with('error', 'Error: La sesión de usuario o sucursal no es válida. Por favor, inicie sesión nuevamente.');
        }

        // Verificar que el inventario existe
        $inventario = $this->inventarioModel->find($id);
        if (!$inventario) {
            return redirect()->to('/inventarios')->with('error', 'Inventario no encontrado.');
        }

        // Validar los datos (remover validación de code ya que no se puede modificar)
        $validation = $this->validate([
            'nombre' => 'required|max_length[100]',
            'stock' => 'required|integer|greater_than_equal_to[0]',
            'turno' => 'required|in_list[AM,PM]',
            'estado' => 'required|in_list[0,1]',
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nombre' => $this->request->getPost('nombre'),

            'descripcion' => $this->request->getPost('descripcion'),
            'stock' => $this->request->getPost('stock'),
            'turno' => $this->request->getPost('turno'),
            'estado' => $this->request->getPost('estado'),
            'sucursal_id' => $sucursalId,
            'user_id' => $userId,
        ];

        try {
            if ($this->inventarioModel->update($id, $data)) {
                return redirect()->to('/inventarios')->with('success', 'Inventario actualizado exitosamente.');
            } else {
                return redirect()->back()->withInput()->with('error', 'No se pudo actualizar el inventario.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar el inventario: ' . $e->getMessage());
        }
    }

    public function delete(int $id): RedirectResponse
    {
        if ($this->inventarioModel->delete($id)) {
            return redirect()->to('/inventarios')->with('success', 'Inventario eliminado exitosamente (movido a la papelera).');
        } else {
            return redirect()->to('/inventarios')->with('error', 'No se pudo eliminar el inventario.');
        }
    }

    public function filtered()
    {
        // Obtener los datos de los filtros de la URL (si existen)
        $nombre = $this->request->getGet('nombre') ?? '';
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';


        $inventarios = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);

        // Obtener resúmenes
        $resumenInventario = $this->inventarioModel->getResumen($fecha_inicio, $fecha_fin);
        $resumenProducto = $this->productoModel->getResumen($fecha_inicio, $fecha_fin);
        $resumenPorNombre = $this->inventarioModel->getResumenPorNombre($fecha_inicio, $fecha_fin);

        $data = [
            'inventarios' => $inventarios,
            'title'       => 'Inventarios Filtrados',
            'filters'     => [
                'nombre'      => $nombre,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin'   => $fecha_fin,
            ],
            'per_page' => 20,
            'pager' => null,
            'resumenInventario' => $resumenInventario,
            'resumenProducto' => $resumenProducto,
            'resumenPorNombre' => $resumenPorNombre,
            'mostrar_todos' => false,
        ];


        echo view('inventarios/inventariosIndex', $data);
    }



    public function indexVenta()
    {
        // 1. CONFIGURACIÓN DE LA SUCURSAL
        // La restricción de ventas será por sucursal_id = 4.
        $sucursalId = 4;

        // Se quita la verificación de user_id ya que la restricción es por sucursal.
        // Si necesitas verificar que el usuario esté logueado, usa session()->get('id').
        // $userId = session()->get('id');
        // if (empty($userId)) {
        //     return redirect()->to(base_url('login'))->with('error', 'Debe iniciar sesión para ver las ventas.');
        // }

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

        // Los parámetros base ahora incluyen solo el sucursalId, inicio y fin.
        $baseParams = [$sucursalId, $inicio, $fin];

        // ---------------------------------------------------------
        // 2. CONTEO TOTAL DE REGISTROS (Ajustado a sucursal_id)
        // ---------------------------------------------------------
        $countSql = "
        SELECT COUNT(*) as total
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- RESTRICCIÓN POR SUCURSAL
          AND v.created_at >= ?
          AND v.created_at <= ?
    ";

        $countQuery = $db->query($countSql, $baseParams);
        if ($countQuery === false) {
            throw new \RuntimeException('Error en la consulta de Conteo de Ventas: ' . $db->error()['message']);
        }

        $countResult = $countQuery->getRow();
        $total = (int)($countResult->total ?? 0);

        // ---------------------------------------------------------
        // 3. Monto total de ventas (Ajustado a sucursal_id)
        // ---------------------------------------------------------
        $totalVentasSql = "
        SELECT SUM(v.monto_total) AS total_monto
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- RESTRICCIÓN POR SUCURSAL
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

        // ---------------------------------------------------------
        // 4. Monto total de ventas AL CONTADO (Ajustado a sucursal_id)
        // ---------------------------------------------------------
        $totalContadoSql = "
        SELECT SUM(v.monto_total) AS total_contado
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- RESTRICCIÓN POR SUCURSAL
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


        // ---------------------------------------------------------
        // 5. Monto total de ventas A CRÉDITO (Ajustado a sucursal_id)
        // ---------------------------------------------------------
        $totalCreditoSql = "
        SELECT SUM(v.monto_total) AS total_credito
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- RESTRICCIÓN POR SUCURSAL
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


        // ---------------------------------------------------------
        // 6. OBTENER REGISTROS (Listado) (Ajustado a sucursal_id)
        // ---------------------------------------------------------
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
        -- JOIN con personal UTO
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.\"id_estado\" = true
        LEFT JOIN rrhh.cargos cargos ON cargos.id_cargo = e.id_cargo
        LEFT JOIN rrhh.secciones secciones ON secciones.id_seccion = e.id_seccion
        WHERE v.deleted_at IS NULL 
          AND v.sucursal_id = ?  -- RESTRICCIÓN POR SUCURSAL
          AND v.created_at >= ?
          AND v.created_at <= ?
        ORDER BY v.created_at DESC
        LIMIT ? OFFSET ?
    ";

        $ventasParams = array_merge($baseParams, [$per_page, $offset]);

        $ventasQuery = $db->query($sql, $ventasParams);
        if ($ventasQuery === false) {
            throw new \RuntimeException('Error en la consulta de Listado de Ventas: ' . $db->error()['message']);
        }

        $ventas = $ventasQuery->getResult();

        // ---------------------------------------------------------
        // 7. Paginación (Sin cambios)
        // ---------------------------------------------------------
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


        return view('inventarios/index', [
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

    public function indexVenta1()
    {
        $userId = session()->get('id');

        if (empty($userId)) {
            return redirect()->to(base_url('login'))->with('error', 'Debe iniciar sesión para ver las ventas.');
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

        $baseParams = [$userId, $inicio, $fin];

        //---------------------------------------------------------

        $countSql = "
        SELECT COUNT(*) as total
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.user_id = ?
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

        $totalVentasSql = "
        SELECT SUM(v.monto_total) AS total_monto
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.user_id = ?
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


        $totalContadoSql = "
        SELECT SUM(v.monto_total) AS total_contado
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.user_id = ?
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


        // =========================================================
        // 4. Monto total de ventas A CRÉDITO (NUEVO KPI)
        // =========================================================
        $totalCreditoSql = "
        SELECT SUM(v.monto_total) AS total_credito
        FROM condoriri.ventas v
        WHERE v.deleted_at IS NULL 
          AND v.user_id = ?
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
        // =========================================================


        //---------------------------------------------------------
        // 5. OBTENER REGISTROS CON DATOS DEL PERSONAL UTO (Listado)
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
        -- JOIN con personal UTO
        LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
        LEFT JOIN rrhh.empleados e ON e.id_persona = p.id_persona AND e.\"id_estado\" = true
        LEFT JOIN rrhh.cargos cargos ON cargos.id_cargo = e.id_cargo
        LEFT JOIN rrhh.secciones secciones ON secciones.id_seccion = e.id_seccion
        WHERE v.deleted_at IS NULL 
          AND v.user_id = ? 
          AND v.created_at >= ?
          AND v.created_at <= ?
        ORDER BY v.created_at DESC
        LIMIT ? OFFSET ?
    ";

        $ventasParams = array_merge($baseParams, [$per_page, $offset]);

        $ventasQuery = $db->query($sql, $ventasParams);
        if ($ventasQuery === false) {
            throw new \RuntimeException('Error en la consulta de Listado de Ventas: ' . $db->error()['message']);
        }

        $ventas = $ventasQuery->getResult();

        //---------------------------------------------------------
        // 6. Paginación (Sin cambios)
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


        return view('inventarios/index', [
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

        $productos = $this->productoModel
            ->where('stock_inve >', 0)
            ->findAll();



        $data = [
            'title'     => 'Registrar Venta Crédito',
            'productos' => $productos,

        ];


        return view('inventarios/ventasCredito', $data);
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


    public function registerVenta()
    {


        $productos = $this->productoModel
            ->where('stock_inve >', 0)
            ->findAll();

        $clientes = $this->clienteModel->findAll();

        $data = [
            'title'     => 'Registrar Venta',
            'productos' => $productos,
            'clientes'  => $clientes,
        ];


        return view('inventarios/ventasIndex', $data);
    }

    public function guardarVenta(): RedirectResponse
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método no permitido.');
        }

        $clienteId = (int)$this->request->getPost('cliente_id');
        $tipoPago = $this->request->getPost('tipo_pago');
        $productosJson = $this->request->getPost('productos');

        if ($this->request->getPost('cliente_id') === null) {
            return redirect()->back()->with('error', 'Faltan datos obligatorios (ID de Cliente no enviado).');
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
        $userId = session()->get('id') ?? 1;

        if ($clienteId !== 0) {
            $cliente = $this->clienteModel->find($clienteId);
            if (!$cliente || (int)$cliente['estado'] === 1) {
                return redirect()->back()->with('error', 'Cliente no válido o inactivo.');
            }
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $totalVenta = 0;
            $itemsDetalle = [];
            $productosCache = [];

            foreach ($productos as $item) {
                if (!isset($item['id'])) {
                    throw new \Exception("Error: un producto no tiene ID.");
                }

                $productoId = (int)$item['id'];
                $cantidad = (int)$item['quantity'];
                $precioUnitario = (float)$item['price'];
                $descuentoPorcentaje = (float)$item['discount'];

                if ($cantidad <= 0 || $precioUnitario <= 0) {
                    throw new \Exception("Cantidad o precio inválido para el producto ID {$productoId}.");
                }

                if (!isset($productosCache[$productoId])) {
                    $producto = $this->productoModel->find($productoId);
                    if (!$producto) {
                        throw new \Exception("Producto con ID {$productoId} no encontrado en la base de datos.");
                    }
                    $productosCache[$productoId] = $producto;
                }

                $producto = $productosCache[$productoId];

                if ((int)$producto->estado === 1) {
                    $nombreProd = $producto->nombre ?? 'Sin nombre';
                    throw new \Exception("Producto '{$nombreProd}' (ID {$productoId}) está inactivo.");
                }

                // ✅ VALIDAR stock_inve (no stock)
                if ($producto->stock_inve < $cantidad) {
                    $nombreProd = $producto->nombre ?? 'Sin nombre';
                    throw new \Exception("Stock insuficiente para: {$nombreProd}. Disponible: {$producto->stock_inve}.");
                }

                $subtotalBruto = $precioUnitario * $cantidad;
                $descuentoMonto = $subtotalBruto * ($descuentoPorcentaje / 100);
                $subtotalFinal = $subtotalBruto - $descuentoMonto;
                $totalVenta += $subtotalFinal;

                $itemsDetalle[] = [
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'subtotal' => $subtotalFinal,
                    'stock_actual' => $producto->stock_inve, // ✅ stock_inve
                    'producto_nombre' => $producto->nombre ?? 'Sin nombre',
                ];
            }

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

            $codigoVenta = 'VENTA-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT);
            $this->ventaModel->update($ventaId, ['code' => $codigoVenta]);

            foreach ($itemsDetalle as $item) {
                $detalleData = [
                    'venta_id' => $ventaId,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                    'observaciones' => '',
                ];

                if (!$this->detalleModel->insert($detalleData)) {
                    throw new \Exception('Error al registrar el detalle para: ' . $item['producto_nombre']);
                }

                // ✅ ACTUALIZAR stock_inve
                $newStock = $item['stock_actual'] - $item['cantidad'];
                if (!$this->productoModel->update($item['producto_id'], ['stock_inve' => $newStock])) {
                    throw new \Exception('Error al actualizar el stock del producto: ' . $item['producto_nombre']);
                }
            }

            $db->transCommit();
            return redirect()->to('/inventarios/recibo/' . $ventaId)->with('success', 'Venta registrada exitosamente.');
        } catch (\Exception $e) {
            $db->transRollback();
            $error = $e->getMessage();
            if ($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException) {
                $error .= " (Error DB: " . $db->error()['message'] . ")";
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

        $sucursalId = session()->get('sucursal_id') ?? 1;
        $userId = session()->get('id') ?? 1;

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

        $db->transBegin();

        try {
            $totalVenta = 0;
            $itemsDetalle = [];
            $productosCache = [];

            foreach ($productos as $item) {
                $productoId = (int)($item['id'] ?? 0);
                $cantidad = (int)($item['quantity'] ?? 0);
                $precioUnitario = (float)($item['price'] ?? 0);
                $descuentoPorcentaje = (float)($item['discount'] ?? 0);

                if ($cantidad <= 0 || $precioUnitario <= 0 || $productoId <= 0) {
                    throw new \Exception("Datos inválidos en el carrito para el producto ID {$productoId}.");
                }

                // ✅ Usar productoModel en lugar de stockSucursalModel
                if (!isset($productosCache[$productoId])) {
                    $producto = $this->productoModel->find($productoId);
                    if (!$producto) {
                        throw new \Exception("Producto con ID {$productoId} no encontrado.");
                    }
                    $productosCache[$productoId] = $producto;
                }

                $producto = $productosCache[$productoId];

                // ✅ Validar estado: asumimos que estado = 1 → activo (ajusta si es distinto)
                if ((int)$producto->estado === 1) {
                    $nombreProd = $producto->nombre ?? 'Sin nombre';
                    throw new \Exception("Producto '{$nombreProd}' está inactivo.");
                }

                // ✅ Validar stock_inve (no stock)
                if ($producto->stock_inve < $cantidad) {
                    $nombreProd = $producto->nombre ?? 'Sin nombre';
                    throw new \Exception("Stock insuficiente para: {$nombreProd}. Disponible: {$producto->stock_inve}.");
                }

                $subtotalBruto = $precioUnitario * $cantidad;
                $descuentoMonto = $subtotalBruto * ($descuentoPorcentaje / 100);
                $subtotalFinal = $subtotalBruto - $descuentoMonto;
                $totalVenta += $subtotalFinal;

                $itemsDetalle[] = [
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'subtotal' => $subtotalFinal,
                    'stock_actual' => $producto->stock_inve, // ✅ stock_inve
                    'producto_nombre' => $producto->nombre ?? 'Sin nombre',
                ];
            }

            // ✅ Insertar venta
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

            // ✅ Corregir obtención del ID de inserción
            if (!$this->ventaModel->insert($ventaData)) {
                throw new \Exception('Error al crear la cabecera de la venta.');
            }
            $ventaId = $this->ventaModel->getInsertID();
            if (!$ventaId) {
                throw new \Exception('No se pudo obtener el ID de la venta.');
            }

            $codigoVenta = 'VENTA-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT);
            $this->ventaModel->update($ventaId, ['code' => $codigoVenta]);

            // ✅ Insertar detalles y actualizar stock_inve
            foreach ($itemsDetalle as $item) {
                $detalleData = [
                    'venta_id' => $ventaId,
                    'stock_id' => null, // Ajustar si es necesario

                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                    'observaciones' => '',
                    'producto_id' => $item['producto_id'],
                ];

                if (!$this->detalleModel->insert($detalleData)) {
                    throw new \Exception('Error al registrar el detalle de venta para: ' . $item['producto_nombre']);
                }

                // ✅ Actualizar stock_inve en productoModel
                $newStock = $item['stock_actual'] - $item['cantidad'];
                if (!$this->productoModel->update($item['producto_id'], ['stock_inve' => $newStock])) {
                    $errors = $this->productoModel->errors();
                    $errorMsg = !empty($errors) ? 'Errores: ' . json_encode($errors) : 'Falló la actualización del stock.';
                    throw new \Exception('Error al actualizar stock_inve del producto ' . $item['producto_nombre'] . '. ' . $errorMsg);
                }
            }

            $db->transCommit();
            return redirect()->to('/inventarios/recibo/' . $ventaId)->with('success', 'Venta a crédito registrada exitosamente.');
        } catch (\Exception $e) {
            $db->transRollback();
            $error = $e->getMessage();
            if ($db->error()['code'] ?? false) {
                $error .= " (DB: {$db->error()['message']})";
            }
            log_message('error', 'Error en guardarCreditoVenta: ' . $error);
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
        $stockTableName = 'condoriri.productos';
        $stockAlias = 'SS';

        $builder = $db->table($detalleTableName);
        $builder->select("{$detalleTableName}.*, {$stockAlias}.nombre");
        $builder->join("{$stockTableName} AS {$stockAlias}", "{$stockAlias}.id = {$detalleTableName}.producto_id");
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

        return view('inventarios/recibo_print', $data);
    }






    public function exportarPdfVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio');
        $fecha_fin    = $this->request->getGet('fecha_fin');
        $tipo         = $this->request->getGet('tipo'); // 'contado', 'credito', o 'general'

        $hoy = date('Y-m-d');
        $fecha_inicio = $fecha_inicio ?: $hoy;
        $fecha_fin    = $fecha_fin ?: $hoy;

        // Validar tipo
        if (!in_array($tipo, ['contado', 'credito', 'general', 'deposito_contado'])) {
            $tipo = 'general';
        }

        // Si es deposito_contado, para el modelo es 'contado', pero el tipo original se pasa al PDF
        $tipoModelo = ($tipo === 'deposito_contado') ? 'contado' : $tipo;

        $reportData = $this->ventaModel->getDailySalesReportDataInve($fecha_inicio, $fecha_fin, $tipoModelo);

        // Generar PDF 
        if ($tipo === 'deposito_contado') {
            $pdfGenerator = new CierreVentaInve1();
        } else {
            $pdfGenerator = new CierreVentaInve();
        }

        $pdfGenerator->generarReporteVentas($reportData, [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin'    => $fecha_fin,
            'tipo'         => $tipo,
        ]);
    }






    public function exportarPdf()
    {

        $nombre = $this->request->getGet('nombre') ?? '';
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';


        $inventarios = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);

        $pdfGenerator = new ReporteLacteos();


        $pdfGenerator->generarReporte($inventarios, [
            'nombre' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);
    }

    public function exportarExcel()
    {
        $nombre = $this->request->getGet('nombre') ?? '';
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';

        $inventarios = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);

        // Cargar sucursales y usuarios
        $db = \Config\Database::connect();
        $sucursales = $db->table('condoriri.sucursales')->select('id, nombre')->get()->getResultArray();
        $usuarios = $db->table('condoriri.usuarios')->select('id, nombre, apellidos')->get()->getResultArray();
        
        $sucursalesMap = [];
        foreach ($sucursales as $s) {
            $sucursalesMap[$s['id']] = $s['nombre'];
        }
        
        $usuariosMap = [];
        foreach ($usuarios as $u) {
            $usuariosMap[$u['id']] = trim($u['nombre'] . ' ' . $u['apellidos']);
        }

        $filename = 'inventarios_' . date('Ymd_His') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        
        // Estilos mejorados
        echo '<Styles>' . "\n";
        
        // Título principal UTO
        echo '<Style ss:ID="titulo_uto">';
        echo '<Font ss:Bold="1" ss:Size="14" ss:Color="#1F4E78" ss:FontName="Calibri"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Subtítulo UTO
        echo '<Style ss:ID="subtitulo_uto">';
        echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#1F4E78" ss:FontName="Calibri"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Info UTO
        echo '<Style ss:ID="info_uto">';
        echo '<Font ss:Size="9" ss:Color="#404040" ss:FontName="Calibri"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Encabezado de tabla
        echo '<Style ss:ID="header">';
        echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF" ss:FontName="Calibri"/>';
        echo '<Interior ss:Color="#2E5090" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";
        
        // Subencabezado
        echo '<Style ss:ID="subheader">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#000000" ss:FontName="Calibri"/>';
        echo '<Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Números decimales
        echo '<Style ss:ID="number">';
        echo '<NumberFormat ss:Format="#,##0.00"/>';
        echo '<Alignment ss:Horizontal="Right" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Números enteros
        echo '<Style ss:ID="integer">';
        echo '<NumberFormat ss:Format="#,##0"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Fechas
        echo '<Style ss:ID="date">';
        echo '<NumberFormat ss:Format="dd/mm/yyyy hh:mm"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Estado Activo
        echo '<Style ss:ID="activo">';
        echo '<Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Calibri"/>';
        echo '<Interior ss:Color="#28A745" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Estado Inactivo
        echo '<Style ss:ID="inactivo">';
        echo '<Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Calibri"/>';
        echo '<Interior ss:Color="#DC3545" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Totales
        echo '<Style ss:ID="total">';
        echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#000000" ss:FontName="Calibri"/>';
        echo '<Interior ss:Color="#FFF2CC" ss:Pattern="Solid"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Top" ss:LineStyle="Double" ss:Weight="3" ss:Color="#000000"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";
        
        // Centrado
        echo '<Style ss:ID="center">';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";
        
        // Filas alternas
        echo '<Style ss:ID="row_even">';
        echo '<Interior ss:Color="#F8F9FA" ss:Pattern="Solid"/>';
        echo '</Style>' . "\n";
        
        echo '</Styles>' . "\n";

        // Calcular totales
        $totalInventarios = count($inventarios);
        $totalStock = 0;
        foreach ($inventarios as $inv) {
            $totalStock += $inv->stock ?? 0;
        }

        // Hoja 1: Resumen con encabezado UTO
        echo '<Worksheet ss:Name="Resumen">' . "\n";
        echo '<Table>' . "\n";
        echo '<Column ss:Width="500"/>' . "\n";
        echo '<Column ss:Width="150"/>' . "\n";
        
        // Encabezado institucional UTO
        echo '<Row ss:Height="20">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="titulo_uto"><Data ss:Type="String">UNIVERSIDAD TÉCNICA DE ORURO</Data></Cell>';
        echo '</Row>' . "\n";
        
        echo '<Row ss:Height="18">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="subtitulo_uto"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRARIAS Y NATURALES</Data></Cell>';
        echo '</Row>' . "\n";
        
        echo '<Row ss:Height="16">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">CONDORIRI - LABORATORIO DE INNOVACIÓN</Data></Cell>';
        echo '</Row>' . "\n";
        
        echo '<Row ss:Height="14">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell>';
        echo '</Row>' . "\n";
        
        echo '<Row ss:Height="14">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Email: dpdi@uto.edu.bo | www.uto.edu.bo</Data></Cell>';
        echo '</Row>' . "\n";
        
        echo '<Row></Row>' . "\n";
        
        // Título del reporte
        echo '<Row ss:Height="22">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="header"><Data ss:Type="String">REPORTE DE INVENTARIOS - CONDORIRI</Data></Cell>';
        echo '</Row>' . "\n";
        
        echo '<Row></Row>' . "\n";
        
        // Información del reporte
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Generado:</Data></Cell><Cell><Data ss:Type="String">' . date('d/m/Y H:i:s') . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Filtro Nombre:</Data></Cell><Cell><Data ss:Type="String">' . ($nombre ?: 'Todos') . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Inicio:</Data></Cell><Cell><Data ss:Type="String">' . ($fecha_inicio ?: 'N/A') . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Fin:</Data></Cell><Cell><Data ss:Type="String">' . ($fecha_fin ?: 'N/A') . '</Data></Cell></Row>' . "\n";
        
        echo '<Row></Row>' . "\n";
        
        // Totales
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">Total Inventarios</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . $totalInventarios . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">Stock Total (Litros)</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . number_format($totalStock, 2, '.', '') . '</Data></Cell></Row>' . "\n";
        
        echo '</Table></Worksheet>' . "\n";

        // Hoja 2: Detalle Inventarios
        echo '<Worksheet ss:Name="Detalle Inventarios">' . "\n";
        echo '<Table>' . "\n";
        echo '<Column ss:Width="100"/>' . "\n";
        echo '<Column ss:Width="120"/>' . "\n";
        echo '<Column ss:Width="200"/>' . "\n";
        echo '<Column ss:Width="80"/>' . "\n";
        echo '<Column ss:Width="80"/>' . "\n";
        echo '<Column ss:Width="60"/>' . "\n";
        echo '<Column ss:Width="80"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="150"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="150"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="70"/>' . "\n";
        echo '<Column ss:Width="90"/>' . "\n";
        echo '<Column ss:Width="90"/>' . "\n";
        echo '<Column ss:Width="60"/>' . "\n";
        echo '<Column ss:Width="130"/>' . "\n";
        echo '<Column ss:Width="130"/>' . "\n";
        echo '<Column ss:Width="130"/>' . "\n";
        
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Nombre</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Descripción</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock (L)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Reserva</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Turno</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Estado</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Sucursal ID</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Sucursal</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Usuario ID</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Usuario</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Grasa (%)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">SNG (%)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Densidad</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Lactosa (%)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Sólidos (%)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Proteína (%)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Agua (%)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Temperatura (°C)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Congelación (°C)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">pH</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha Creación</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha Actualización</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha Calidad</Data></Cell>';
        echo '</Row>' . "\n";

        $fill = false;
        foreach ($inventarios as $inv) {
            $rowStyle = $fill ? 'row_even' : '';
            $sucursalNombre = $sucursalesMap[$inv->sucursal_id] ?? 'N/A';
            $usuarioNombre = $usuariosMap[$inv->user_id] ?? 'N/A';
            
            echo '<Row>';
            echo '<Cell' . ($rowStyle ? ' ss:StyleID="' . $rowStyle . '"' : '') . '><Data ss:Type="String">' . htmlspecialchars($inv->code ?? '', ENT_XML1) . '</Data></Cell>';
            echo '<Cell' . ($rowStyle ? ' ss:StyleID="' . $rowStyle . '"' : '') . '><Data ss:Type="String">' . htmlspecialchars($inv->nombre ?? '', ENT_XML1) . '</Data></Cell>';
            echo '<Cell' . ($rowStyle ? ' ss:StyleID="' . $rowStyle . '"' : '') . '><Data ss:Type="String">' . htmlspecialchars($inv->descripcion ?? '', ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->stock ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->reserva ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . htmlspecialchars($inv->turno ?? '', ENT_XML1) . '</Data></Cell>';
            $estadoStyle = $inv->estado ? 'activo' : 'inactivo';
            $estadoTexto = $inv->estado ? 'Activo' : 'Inactivo';
            echo '<Cell ss:StyleID="' . $estadoStyle . '"><Data ss:Type="String">' . $estadoTexto . '</Data></Cell>';
            echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($inv->sucursal_id ?? 0) . '</Data></Cell>';
            echo '<Cell' . ($rowStyle ? ' ss:StyleID="' . $rowStyle . '"' : '') . '><Data ss:Type="String">' . htmlspecialchars($sucursalNombre, ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($inv->user_id ?? 0) . '</Data></Cell>';
            echo '<Cell' . ($rowStyle ? ' ss:StyleID="' . $rowStyle . '"' : '') . '><Data ss:Type="String">' . htmlspecialchars($usuarioNombre, ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->grasa ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->sng ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->densidad ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->lactosa ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->solidos ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->proteina ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->agua ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->temperatura ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->congelacion ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inv->ph ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="date"><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($inv->created_at)) . '</Data></Cell>';
            echo '<Cell ss:StyleID="date"><Data ss:Type="String">' . ($inv->updated_at ? date('d/m/Y H:i', strtotime($inv->updated_at)) : 'N/A') . '</Data></Cell>';
            echo '<Cell ss:StyleID="date"><Data ss:Type="String">' . ($inv->fecha_calidad ? date('d/m/Y H:i', strtotime($inv->fecha_calidad)) : 'N/A') . '</Data></Cell>';
            echo '</Row>' . "\n";
            $fill = !$fill;
        }
        
        echo '</Table></Worksheet>' . "\n";

        // Hoja 3: Productos por Inventario
        echo '<Worksheet ss:Name="Productos">' . "\n";
        echo '<Table>' . "\n";
        echo '<Column ss:Width="50"/>' . "\n";  // ID
        echo '<Column ss:Width="70"/>' . "\n";  // Inventario ID
        echo '<Column ss:Width="120"/>' . "\n"; // Código Inventario
        echo '<Column ss:Width="120"/>' . "\n"; // Inventario
        echo '<Column ss:Width="150"/>' . "\n"; // Producto
        echo '<Column ss:Width="200"/>' . "\n"; // Descripción
        echo '<Column ss:Width="90"/>' . "\n";  // Precio Crédito
        echo '<Column ss:Width="90"/>' . "\n";  // Precio Contado
        echo '<Column ss:Width="70"/>' . "\n";  // Stock
        echo '<Column ss:Width="70"/>' . "\n";  // Stock Inve
        echo '<Column ss:Width="70"/>' . "\n";  // Reserva
        echo '<Column ss:Width="80"/>' . "\n";  // Estado
        echo '<Column ss:Width="120"/>' . "\n"; // Categoría
        echo '<Column ss:Width="100"/>' . "\n"; // Unidad
        echo '<Column ss:Width="100"/>' . "\n"; // Fecha Venc.
        echo '<Column ss:Width="90"/>' . "\n";  // Cant. Producción
        echo '<Column ss:Width="80"/>' . "\n";  // Cant. Unidad
        echo '<Column ss:Width="80"/>' . "\n";  // Porosidad
        echo '<Column ss:Width="60"/>' . "\n";  // pH
        echo '<Column ss:Width="80"/>' . "\n";  // Acidez
        echo '<Column ss:Width="100"/>' . "\n"; // Consistencia
        echo '<Column ss:Width="80"/>' . "\n";  // Color
        echo '<Column ss:Width="80"/>' . "\n";  // Olor
        echo '<Column ss:Width="80"/>' . "\n";  // Textura
        echo '<Column ss:Width="70"/>' . "\n";  // Merma
        echo '<Column ss:Width="70"/>' . "\n";  // Agrega
        echo '<Column ss:Width="70"/>' . "\n";  // Litros
        echo '<Column ss:Width="80"/>' . "\n";  // Materia Sub
        echo '<Column ss:Width="90"/>' . "\n";  // Suero Lácteo
        echo '<Column ss:Width="90"/>' . "\n";  // Suero Quesería
        echo '<Column ss:Width="200"/>' . "\n"; // Observaciones
        echo '<Column ss:Width="130"/>' . "\n"; // Fecha Creación
        echo '<Column ss:Width="130"/>' . "\n"; // Fecha Actualización
        
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">ID</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Inventario ID</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código Inventario</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Inventario</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Producto</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Descripción</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Precio Crédito</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Precio Contado</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Stock Inve</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Reserva</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Estado</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Categoría</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Unidad</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha Vencimiento</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cant. Producción</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cant. Unidad</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Porosidad</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">pH</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Acidez</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Consistencia</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Color</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Olor</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Textura</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Merma</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Agrega</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Litros</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Materia Sub</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Suero Lácteo</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Suero Quesería</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Observaciones</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha Creación</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha Actualización</Data></Cell>';
        echo '</Row>' . "\n";

        // Cargar categorías y unidades
        $categorias = $db->table('condoriri.categorias')->select('id, nombre')->get()->getResultArray();
        $unidades = $db->table('condoriri.unidades')->select('id, nombre')->get()->getResultArray();
        
        $categoriasMap = [];
        foreach ($categorias as $c) {
            $categoriasMap[$c['id']] = $c['nombre'];
        }
        
        $unidadesMap = [];
        foreach ($unidades as $u) {
            $unidadesMap[$u['id']] = $u['nombre'];
        }

        foreach ($inventarios as $inv) {
            $productos = $this->productoModel
                ->where('inventario_id', $inv->id)
                ->orderBy('nombre', 'ASC')
                ->findAll();
            
            foreach ($productos as $prod) {
                $categoriaNombre = $categoriasMap[$prod->categoria_id] ?? 'N/A';
                $unidadNombre = $unidadesMap[$prod->unidad_id] ?? 'N/A';
                $estadoTexto = $prod->estado ? 'Activo' : 'Inactivo';
                $estadoStyle = $prod->estado ? 'activo' : 'inactivo';
                
                echo '<Row>';
                echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($prod->id ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($inv->id ?? 0) . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . htmlspecialchars($inv->code ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . htmlspecialchars($inv->nombre ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . htmlspecialchars($prod->nombre ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . htmlspecialchars($prod->descripcion ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($prod->precio_credito ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($prod->precio_contado ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($prod->stock ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($prod->stock_inve ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($prod->reserva ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $estadoStyle . '"><Data ss:Type="String">' . $estadoTexto . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . htmlspecialchars($categoriaNombre, ENT_XML1) . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . htmlspecialchars($unidadNombre, ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . ($prod->fecha_vencimiento ? date('d/m/Y', strtotime($prod->fecha_vencimiento)) : 'N/A') . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($prod->cantidad_produccion ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($prod->cantidad_unidad ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . htmlspecialchars($prod->porocidad ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . htmlspecialchars($prod->ph ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . htmlspecialchars($prod->acides ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . htmlspecialchars($prod->consistencia ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . htmlspecialchars($prod->color ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . htmlspecialchars($prod->olor ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . htmlspecialchars($prod->textura ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($prod->merma ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($prod->agrega ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($prod->litros ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($prod->materia_sub ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($prod->suero_lacteo ?? 0) . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($prod->suero_queseria ?? 0) . '</Data></Cell>';
                echo '<Cell><Data ss:Type="String">' . htmlspecialchars($prod->observaciones ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="date"><Data ss:Type="String">' . ($prod->created_at ? date('d/m/Y H:i', strtotime($prod->created_at)) : 'N/A') . '</Data></Cell>';
                echo '<Cell ss:StyleID="date"><Data ss:Type="String">' . ($prod->updated_at ? date('d/m/Y H:i', strtotime($prod->updated_at)) : 'N/A') . '</Data></Cell>';
                echo '</Row>' . "\n";
            }
        }
        
        echo '</Table></Worksheet>' . "\n";
        echo '</Workbook>';
        exit;
    }





    public function guardarCalidad(int $id): RedirectResponse
    {
        $userId = session()->get('id');
        $sucursalId = session()->get('sucursal_id');

        if (empty($userId) || empty($sucursalId)) {
            return redirect()->back()->with('error', 'Error: La sesión de usuario o sucursal no es válida. Por favor, inicie sesión nuevamente.');
        }

        
        $inventario = $this->inventarioModel->find($id);
        if (!$inventario) {
            return redirect()->to('/inventarios')->with('error', 'Inventario no encontrado.');
        }

    
        $rules = [
            'grasa'         => 'required|numeric', 
            'sng'           => 'required|numeric', 
            'densidad'      => 'required|numeric', 
            'lactosa'       => 'required|numeric', 
            'solidos'       => 'required|numeric', 
            'proteina'      => 'required|numeric', 
            'agua'          => 'required|numeric', 
            'temperatura'   => 'required|numeric', 
            'congelacion'   => 'required|numeric', 
            'ph'            => 'required|numeric|less_than_equal_to[14]|greater_than_equal_to[0]', 
        ];
        
        if (!$this->validate($rules)) {
        
            return redirect()->back()->with('error', 'Error de validación. Revise los campos numéricos.')
                ->with('errors', $this->validator->getErrors());
        }

    

        $dataCalidad = [
        
            'grasa'         => $this->request->getPost('grasa'),
            'sng'           => $this->request->getPost('sng'),
            'densidad'      => $this->request->getPost('densidad'),
            'lactosa'       => $this->request->getPost('lactosa'),
            'solidos'       => $this->request->getPost('solidos'),
            'proteina'      => $this->request->getPost('proteina'),
            'agua'          => $this->request->getPost('agua'),
            'temperatura'   => $this->request->getPost('temperatura'),
            'congelacion'   => $this->request->getPost('congelacion'),
            'ph'            => $this->request->getPost('ph'),
            
        
            'fecha_calidad' => date('Y-m-d H:i:s'), 
            'user_cali'     => $userId,
            
        
        ];

        try {
            if ($this->inventarioModel->update($id, $dataCalidad)) {
                return redirect()->to('/inventarios')->with('success', '✅ Control de calidad registrado exitosamente.');
            } else {
            
                return redirect()->back()->with('error', '❌ No se pudo registrar el control de calidad. Error desconocido en el modelo.');
            }
        } catch (\Exception $e) {
        
            return redirect()->back()->with('error', '❌ Error fatal al registrar el control de calidad: ' . $e->getMessage());
        }
    }



    public function getResumenPorNombre()
    {
        $resumen = $this->inventarioModel->getResumenPorNombre();
        return $this->response->setJSON(
            [
                'resumen' => $resumen,
                'totales' => $resumen['totales_generales']
            ]
        );
    }

    public function updateMateriaPrima()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud inválida']);
        }

        $id = $this->request->getPost('id');
        $nombre = trim($this->request->getPost('nombre'));

        if (empty($id) || empty($nombre)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Datos incompletos']);
        }

        try {
            if ($this->materiaPrimaModel->update($id, ['nombre' => $nombre])) {
                return $this->response->setJSON(['success' => true, 'message' => 'Materia prima actualizada']);
            }
            return $this->response->setJSON(['success' => false, 'message' => 'Error al actualizar']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function reporteInventario()
    {
        $nombre = $this->request->getGet('nombre') ?? '';
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';

        // Obtener datos del usuario
        $userId = session()->get('id');
        $userModel = new \App\Models\UsuarioModel();
        $user = $userModel->find($userId);
        
        $nombreUser = $user['nombre'] ?? '';
        $apellidosUser = $user['apellidos'] ?? '';
        $ciUser = $user['ci'] ?? '';
        
        $datosUsuario = trim("$nombreUser $apellidosUser");
        if (!empty($ciUser)) {
            $datosUsuario .= " - CI: $ciUser";
        }
        if (empty($datosUsuario)) {
            $datosUsuario = 'Usuario Desconocido';
        }

        // Asegúrate de que el método getResumenPorNombre en tu modelo (inventarioModel)
        // acepte y utilice los parámetros $nombre, $fecha_inicio y $fecha_fin para filtrar los resultados.
        $inventarios = $this->inventarioModel->getResumenPorNombre($nombre, $fecha_inicio, $fecha_fin);

        $pdfGenerator = new ReporteInventario();

        $pdfGenerator->generarReporte($inventarios, [
            'nombre' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'usuario' => $datosUsuario // Pasar datos completos del usuario
        ]);
    }
    

    public function controlCalidadPdf($id)
    {
        $inventario = $this->inventarioModel->find($id);

        if (!$inventario) {
            return redirect()->back()->with('error', 'Inventario no encontrado.');
        }

        if (empty($inventario->fecha_calidad)) {
             return redirect()->back()->with('error', 'Este inventario no tiene registro de control de calidad.');
        }

        $usuarioModel = new UsuarioModel();
        // Asumimos que user_cali es el ID del usuario
        $usuario = $usuarioModel->get($inventario->user_cali);

        $pdf = new ReporteCalidad();
        $pdf->generarReporte($inventario, $usuario);
    }

    public function controlCalidadExcel($id)
    {
        $inventario = $this->inventarioModel->find($id);

        if (!$inventario) {
            return redirect()->back()->with('error', 'Inventario no encontrado.');
        }

        if (empty($inventario->fecha_calidad)) {
            return redirect()->back()->with('error', 'Este inventario no tiene registro de control de calidad.');
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('condoriri.usuarios')->where('id', $inventario->user_cali)->get()->getRowArray();
        $nombreUsuario = $usuario ? trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')) : 'N/A';

        $filename = 'control_calidad_' . $inventario->code . '_' . date('Ymd_His') . '.xls';
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
        echo '<Style ss:ID="center"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '</Styles>';

        echo '<Worksheet ss:Name="Control de Calidad">';
        echo '<Table>';
        echo '<Column ss:Width="250"/><Column ss:Width="150"/>';
        
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="1" ss:StyleID="titulo_uto"><Data ss:Type="String">UNIVERSIDAD TÉCNICA DE ORURO</Data></Cell></Row>';
        echo '<Row ss:Height="18"><Cell ss:MergeAcross="1" ss:StyleID="subtitulo_uto"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRARIAS Y NATURALES</Data></Cell></Row>';
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">CONDORIRI - CONTROL DE CALIDAD</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="1" ss:StyleID="header"><Data ss:Type="String">CONTROL DE CALIDAD - ' . htmlspecialchars($inventario->nombre, ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row></Row>';
        
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Código:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($inventario->code, ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Registro:</Data></Cell><Cell><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($inventario->fecha_calidad)) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Registrado por:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($nombreUsuario, ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row></Row>';
        
        echo '<Row><Cell ss:StyleID="header"><Data ss:Type="String">Parámetro</Data></Cell><Cell ss:StyleID="header"><Data ss:Type="String">Valor</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Grasa (%)</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->grasa ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">SNG</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->sng ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Densidad</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->densidad ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Lactosa (%)</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->lactosa ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Sólidos Totales (%)</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->solidos ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Proteína (%)</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->proteina ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Agua Agregada (%)</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->agua ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Temperatura (°C)</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->temperatura ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Punto de Congelación</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->congelacion ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">pH</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="Number">' . ($inventario->ph ?? 0) . '</Data></Cell></Row>';
        
        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
    }

    public function exportarCalidadExcel()
    {
        $nombre = $this->request->getGet('nombre') ?? '';
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';

        $inventarios = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);

        // Agrupar inventarios por mes
        $inventariosPorMes = [];
        foreach ($inventarios as $inv) {
            $mes = date('Y-m', strtotime($inv->created_at)); // Formato: 2023-11 para ordenar
            // Nombres de meses en español
            $meses_es = [
                'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
                'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
                'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
                'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
            ];
            $mesIngles = date('F Y', strtotime($inv->created_at));
            $mesTexto = str_replace(array_keys($meses_es), array_values($meses_es), $mesIngles);
            
            if (!isset($inventariosPorMes[$mes])) {
                $inventariosPorMes[$mes] = [
                    'texto' => $mesTexto,
                    'registros' => []
                ];
            }
            $inventariosPorMes[$mes]['registros'][] = $inv;
        }

        // Ordenar por mes descendente (más reciente primero)
        krsort($inventariosPorMes);

        $filename = 'control_calidad_' . date('Ymd_His') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        
        echo '<Styles>';
        // Título principal
        echo '<Style ss:ID="titulo"><Font ss:Bold="1" ss:Size="16" ss:Color="#FFFFFF" ss:FontName="Arial"/><Interior ss:Color="#DC143C" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        
        // Título de mes
        echo '<Style ss:ID="titulo_mes"><Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF" ss:FontName="Arial"/><Interior ss:Color="#8B0000" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        
        // Encabezados con fondo rojo
        echo '<Style ss:ID="header"><Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/><Interior ss:Color="#DC143C" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Celdas de datos con bordes rojos - TURNO AM (fondo amarillo suave)
        echo '<Style ss:ID="celda_am"><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Celdas de datos con bordes rojos - TURNO PM (fondo azul suave)
        echo '<Style ss:ID="celda_pm"><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Números con 2 decimales - TURNO AM
        echo '<Style ss:ID="numero_am"><NumberFormat ss:Format="0.00"/><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Números con 2 decimales - TURNO PM
        echo '<Style ss:ID="numero_pm"><NumberFormat ss:Format="0.00"/><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Números con 2 decimales - TURNO AM (borde superior más grueso para separación de día)
        echo '<Style ss:ID="numero_am_dia"><NumberFormat ss:Format="0.00"/><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Celdas de datos - TURNO AM (borde superior más grueso para separación de día)
        echo '<Style ss:ID="celda_am_dia"><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Números negativos - TURNO AM (borde superior más grueso para separación de día)
        echo '<Style ss:ID="numero_neg_am_dia"><NumberFormat ss:Format="-0.000"/><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Números negativos (punto de congelación) - TURNO AM
        echo '<Style ss:ID="numero_neg_am"><NumberFormat ss:Format="-0.000"/><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        // Números negativos (punto de congelación) - TURNO PM
        echo '<Style ss:ID="numero_neg_pm"><NumberFormat ss:Format="-0.000"/><Font ss:Size="10" ss:FontName="Arial"/><Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/></Borders></Style>';
        
        echo '</Styles>';

        echo '<Worksheet ss:Name="Control de Calidad">';
        echo '<Table>';
        
        // Anchos de columna
        echo '<Column ss:Width="80"/>';  // FECHA
        echo '<Column ss:Width="120"/>'; // NOMBRE (nueva columna)
        echo '<Column ss:Width="50"/>';  // TURNO
        echo '<Column ss:Width="50"/>';  // GRASA
        echo '<Column ss:Width="50"/>';  // SNG
        echo '<Column ss:Width="60"/>';  // DENSIDAD
        echo '<Column ss:Width="60"/>';  // LACTOSA
        echo '<Column ss:Width="60"/>';  // SOLIDOS
        echo '<Column ss:Width="60"/>';  // PROTEINA
        echo '<Column ss:Width="50"/>';  // AGUA
        echo '<Column ss:Width="60"/>';  // TEMP
        echo '<Column ss:Width="70"/>';  // PUNTO CON
        echo '<Column ss:Width="40"/>';  // pH
        echo '<Column ss:Width="100"/>'; // OBSERVACION
        
        // Título principal
        echo '<Row ss:Height="25">';
        echo '<Cell ss:MergeAcross="13" ss:StyleID="titulo"><Data ss:Type="String">CONTROL DE CALIDAD</Data></Cell>';
        echo '</Row>';
        echo '<Row></Row>'; // Fila vacía
        
        // Iterar por cada mes
        $primerMes = true;
        foreach ($inventariosPorMes as $mes => $datos) {
            // Agregar fila vacía entre meses (excepto antes del primero)
            if (!$primerMes) {
                echo '<Row></Row>';
            }
            $primerMes = false;
            
            // Título del mes
            echo '<Row ss:Height="22">';
            echo '<Cell ss:MergeAcross="13" ss:StyleID="titulo_mes"><Data ss:Type="String">' . strtoupper($datos['texto']) . '</Data></Cell>';
            echo '</Row>';
            
            // Encabezados de columnas para este mes
            echo '<Row ss:Height="30">';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">FECHA</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">NOMBRE</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">TURNO</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">GRASA (%)</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">SNG (%)</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">DENSIDAD</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">LACTOSA (%)</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">SOLIDOS (%)</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">PROTEINA (%)</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">AGUA (%)</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">TEMP (°C)</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">PUNTO CON</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">pH</Data></Cell>';
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">OBSERVACION</Data></Cell>';
            echo '</Row>';
            
            // Datos del mes
            $registrosPorDia = [];
            
            // Agrupar registros por día
            foreach ($datos['registros'] as $inv) {
                $dia = date('d-M-y', strtotime($inv->created_at));
                if (!isset($registrosPorDia[$dia])) {
                    $registrosPorDia[$dia] = [];
                }
                $registrosPorDia[$dia][] = $inv;
            }
            
            // Procesar cada día con colores alternados
            $esPrimerDia = true;
            $indiceDia = 0;
            foreach ($registrosPorDia as $fecha => $registrosDelDia) {
                // Alternar color por día (no por turno)
                $esDiaAmarillo = ($indiceDia % 2 === 0);
                
                foreach ($registrosDelDia as $index => $inv) {
                    $turno = strtoupper($inv->turno ?? 'AM');
                    $esPrimeraFilaDelDia = ($index === 0);
                    
                    // Determinar estilos según el día (no el turno)
                    if ($esPrimeraFilaDelDia && !$esPrimerDia) {
                        // Aplicar borde superior grueso para separar días (excepto el primer día)
                        $estiloCelda = $esDiaAmarillo ? 'celda_am_dia' : 'celda_pm';
                        $estiloNumero = $esDiaAmarillo ? 'numero_am_dia' : 'numero_pm';
                        $estiloNumeroNeg = $esDiaAmarillo ? 'numero_neg_am_dia' : 'numero_neg_pm';
                    } else {
                        $estiloCelda = $esDiaAmarillo ? 'celda_am' : 'celda_pm';
                        $estiloNumero = $esDiaAmarillo ? 'numero_am' : 'numero_pm';
                        $estiloNumeroNeg = $esDiaAmarillo ? 'numero_neg_am' : 'numero_neg_pm';
                    }
                    
                    echo '<Row ss:Height="20">';
                    
                    // Celda de FECHA: solo mostrar en la primera fila del día
                    if ($esPrimeraFilaDelDia) {
                        echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String">' . htmlspecialchars($fecha, ENT_XML1) . '</Data></Cell>';
                    } else {
                        // Celda vacía para las siguientes filas del mismo día
                        echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String"></Data></Cell>';
                    }
                    
                    // Nueva columna NOMBRE
                    echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String">' . htmlspecialchars($inv->nombre ?? '', ENT_XML1) . '</Data></Cell>';
                    
                    echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String">' . htmlspecialchars($turno, ENT_XML1) . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->grasa ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->sng ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->densidad ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->lactosa ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->solidos ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->proteina ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->agua ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->temperatura ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumeroNeg . '"><Data ss:Type="Number">' . number_format($inv->congelacion ?? 0, 3, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->ph ?? 0, 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String"></Data></Cell>'; // OBSERVACION vacía
                    echo '</Row>' . "\n";
                }
                
                $esPrimerDia = false;
                $indiceDia++;
            }
        }
        
        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
    }

    public function exportarCalidadPdf()
    {
        $nombre = $this->request->getGet('nombre') ?? '';
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';

        $inventarios = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);

        $pdfGenerator = new ReporteControlCalidad([
            'nombre' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);

        $pdfGenerator->generarReporte($inventarios);
    }

    public function exportarExcelVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? date('Y-m-d');
        $fecha_fin = $this->request->getGet('fecha_fin') ?? date('Y-m-d');
        $tipo = $this->request->getGet('tipo') ?? 'general';
        $sucursal_id = $this->request->getGet('sucursal_id') ?? 4;

        $db = \Config\Database::connect();
        $inicio = $fecha_inicio . ' 00:00:00';
        $fin = $fecha_fin . ' 23:59:59';

        $sql = "SELECT v.*, COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre, p.nombre_completo AS nombre_personal, p.dip
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
            $sqlDetalle = "SELECT dv.*, pr.nombre AS producto, pr.unidad_id
                          FROM condoriri.detalle_venta dv
                          LEFT JOIN condoriri.productos pr ON pr.id = dv.producto_id
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
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell></Row>';
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
                    echo '<Cell ss:StyleID="detalle_producto"><Data ss:Type="String">und</Data></Cell>';
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