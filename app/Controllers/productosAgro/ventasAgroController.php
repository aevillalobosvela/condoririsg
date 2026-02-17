<?php

namespace App\Controllers\productosAgro;

use App\Controllers\BaseController;

use App\Libraries\CierreVentaPdf;


use App\Models\Categoria\CategoriaModel;
use App\Models\Cliente\ClienteModel;
use App\Models\ProductoAgro\ProductoAgroModel;
use App\Models\Venta\DetalleModel;
use App\Models\Venta\VentaModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Database\Exceptions\DatabaseException;

class ventasAgroController extends BaseController
{
    /**
     * @var CategoriaModel 
     */
    protected $categoriaModel;
    protected $clienteModel;

    protected $ventaModel;
    protected $detalleModel;

    protected $productoAgroModel;
    /**
     * Constructor del controlador.
     */
    public function __construct()
    {

        $this->categoriaModel = new CategoriaModel();
        $this->clienteModel = new ClienteModel();

        $this->ventaModel = new VentaModel();
        $this->detalleModel = new DetalleModel();

        $this->productoAgroModel = new ProductoAgroModel();
    }

    /**
     * Muestra la lista de ventas (Operación READ - todas).
     *
     * @return string
     */
    public function index()
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


        return view('productosAgro/index', [
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

        $sucursal = (int) session()->get('sucursal_id');

        $productos = $this->productoAgroModel
            ->where('sucursal_id', $sucursal)
            ->where('estado', true)
            ->findAll();
        




        $data = [
            'title'     => 'Registrar Venta Crédito',
            'productos' => $productos,

        ];


        return view('productosAgro/ventasCredito', $data);
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
        $sucursal = (int) session()->get('sucursal_id');

        $productos = $this->productoAgroModel
            ->where('sucursal_id', $sucursal)
            ->where('estado', true)
            ->findAll();
        $clientes = $this->clienteModel->findAll();

        $data = [
            'title'     => 'Registrar Venta',
            'productos' => $productos,
            'clientes'  => $clientes,
        ];


        return view('productosAgro/ventasIndex', $data);
    }











    /**
     * Guarda la venta y actualiza el stock.
     *
     * @return RedirectResponse
     */
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
                $producto = $this->productoAgroModel->find($productoId);
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

          
            if ($producto->cantidad_inve < $cantidad) {
                $nombreProd = $producto->nombre ?? 'Sin nombre';
                throw new \Exception("Stock insuficiente para: {$nombreProd}. Disponible: {$producto->stock_inve}.");
            }

            $subtotalBruto = $precioUnitario * $cantidad;
            $descuentoMonto = $subtotalBruto * ($descuentoPorcentaje / 100);
            $subtotalFinal = $subtotalBruto - $descuentoMonto;
            $totalVenta += $subtotalFinal;

            $itemsDetalle[] = [
                'producto_agro_id' => $productoId,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'descuento_porcentaje' => $descuentoPorcentaje,
                'subtotal' => $subtotalFinal,
                'stock_actual' => $producto->cantidad_inve, 
                'producto_nombre' => $producto->nombre ?? 'Sin nombre',
            ];
        }

        $ventaData = [
            'code' => 'VENTA-' . strtoupper(bin2hex(random_bytes(4))),
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

        foreach ($itemsDetalle as $item) {
            $detalleData = [
                'venta_id' => $ventaId,

                'producto_agro_id' => $item['producto_id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'subtotal' => $item['subtotal'],
                'observaciones' => '',
            ];

            if (!$this->detalleModel->insert($detalleData)) {
                throw new \Exception('Error al registrar el detalle para: ' . $item['producto_nombre']);
            }

            
            $newStock = $item['stock_actual'] - $item['cantidad'];
            if (!$this->productoAgroModel->update($item['producto_id'], ['cantidad_inve' => $newStock])) {
                throw new \Exception('Error al actualizar el stock del producto: ' . $item['producto_nombre']);
            }
        }

        $db->transCommit();
        return redirect()->to('/productosagro/recibo/' . $ventaId)->with('success', 'Venta registrada exitosamente.');

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
        ->join('rrhh.empleados e', 'p.id_persona = e.id_persona', 'inner')
        ->where('p.id_persona', $personalUtoId)
        ->where('p.id_estado', true)
        ->where('e.id_estado', true)
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
                throw new \Exception("Datos inválidos para el producto ID {$productoId}.");
            }

       
            if (!isset($productosCache[$productoId])) {
                $producto = $this->productoAgroModel->find($productoId);
                if (!$producto) {
                    throw new \Exception("Producto con ID {$productoId} no encontrado.");
                }
                $productosCache[$productoId] = $producto;
            }

            $producto = $productosCache[$productoId];

            // ✅ Validar estado (0 = activo, según tu lógica anterior)
            if ((int)$producto->estado !== 0) { 
                // NOTA: En tu guardarVenta usas: if ((int)$producto->estado === 1) → inactivo
                // Ajusta según tu lógica real: 
                // - Si `estado = 1` → activo, usa `!== 1`
                // - Si `estado = 0` → activo, usa `!== 0`
                // Aquí asumo que `1 = activo` (como en formularios)
            }

            // ✅ CORREGIDO: cantidad_inve (no catidad_inve, ni stock_inve)
            if ($producto->cantidad_inve < $cantidad) {
                $nombreProd = $producto->producto ?? 'Sin nombre';
                throw new \Exception("Stock insuficiente para: {$nombreProd}. Disponible: {$producto->cantidad_inve}.");
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
                'stock_actual' => $producto->cantidad_inve, 
                'producto_nombre' => $producto->producto ?? 'Sin nombre', 
            ];
        }

        
        $ventaData = [
            'code' => 'VENTA-' . strtoupper(bin2hex(random_bytes(4))),
            'cliente_id' => null,
            'sucursal_id' => $sucursalId,
            'tipo_pago' => $tipoPago,
            'monto_total' => $totalVenta,
            'estado' => 1, // 1 = activa
            'observaciones' => 'Venta a crédito para personal UTO',
            'user_id' => $userId,
            'personal_uto_id' => $personalUtoId,
        ];

        $ventaId = $this->ventaModel->insert($ventaData);
        if (!$ventaId) {
            throw new \Exception('Error al crear la cabecera de la venta.');
        }

        // ✅ Procesar detalles y actualizar stock
        foreach ($itemsDetalle as $item) {
            $detalleData = [
                'venta_id' => $ventaId,
                'producto_agro_id' => $item['producto_id'], // ✅ producto_id, no stock_id
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'subtotal' => $item['subtotal'],
                'observaciones' => '',
            ];

            if (!$this->detalleModel->insert($detalleData)) {
                throw new \Exception('Error al registrar el detalle para: ' . $item['producto_nombre']);
            }

            // ✅ Actualizar stock
            $newStock = $item['stock_actual'] - $item['cantidad'];
            if (!$this->productoAgroModel->update($item['producto_id'], ['cantidad_inve' => $newStock])) {
                throw new \Exception('Error al actualizar el stock de: ' . $item['producto_nombre']);
            }
        }

        $db->transCommit();
        return redirect()->to('/productosagro/recibo/' . $ventaId) // ✅ Ruta corregida: productosagro (no produtosagro)
            ->with('success', 'Venta a crédito registrada exitosamente.');

    } catch (\Exception $e) {
        $db->transRollback();
        $error = $e->getMessage();
        if ($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException) {
            $error .= " (DB: " . $db->error()['message'] . ")";
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

     
        $db = \Config\Database::connect();
        $detalleTableName = 'condoriri.detalle_venta';
        $stockTableName = 'condoriri.productos_agro';
        $stockAlias = 'SS';

        $builder = $db->table($detalleTableName);
        $builder->select("{$detalleTableName}.*, {$stockAlias}.producto");
        $builder->join("{$stockTableName} AS {$stockAlias}", "{$stockAlias}.id = {$detalleTableName}.producto_agro_id");
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

        
        $cliente = null;
        $personal = null;

        
        if (!empty($venta->cliente_id) && $venta->cliente_id != 0) {
            $cliente = $this->clienteModel->find($venta->cliente_id);
        }
      
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

       
        $sucursalInfo = [
            'nombre' => 'Mi Tienda POS',
            'direccion' => 'Av. Principal #123',
            'telefono' => '+591 555-1234',
            'nit' => '123456789-0'
        ];
        
      
        $data = [
            'title' => 'Recibo de Venta #' . $ventaId,
            'venta' => $venta,
            'detalles' => $detalles,
            'cliente' => $cliente,
            'personal' => $personal, 
            'sucursal' => $sucursalInfo,
        ];

        return view('productosAgro/recibo_print', $data);
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
        if (!in_array($tipo, ['contado', 'credito', 'general'])) {
            $tipo = 'general';
        }


        $reportData = $this->ventaModel->getDailySalesReportData($fecha_inicio, $fecha_fin, $tipo);

        // Generar PDF
        $pdfGenerator = new CierreVentaPdf();
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
        $userId = session()->get('id');

        $db = \Config\Database::connect();
        $inicio = $fecha_inicio . ' 00:00:00';
        $fin = $fecha_fin . ' 23:59:59';

        $sql = "SELECT v.*, COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre, p.nombre AS nombre_personal, p.dip
                FROM condoriri.ventas v
                LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
                LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
                WHERE v.deleted_at IS NULL AND v.user_id = ? AND v.created_at >= ? AND v.created_at <= ?";
        
        $params = [$userId, $inicio, $fin];
        if ($tipo === 'contado') {
            $sql .= " AND v.tipo_pago = 'contado'";
        } elseif ($tipo === 'credito') {
            $sql .= " AND v.tipo_pago = 'credito'";
        }
        $sql .= " ORDER BY v.created_at DESC";

        $ventas = $db->query($sql, $params)->getResult();
        $totalVentas = array_sum(array_column($ventas, 'monto_total'));
        $totalRegistros = count($ventas);

        $filename = 'ventas_agro_' . $tipo . '_' . date('Ymd_His') . '.xls';
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
        echo '</Styles>';

        echo '<Worksheet ss:Name="Resumen">';
        echo '<Table>';
        echo '<Column ss:Width="500"/><Column ss:Width="150"/>';
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="1" ss:StyleID="titulo_uto"><Data ss:Type="String">UNIVERSIDAD TÉCNICA DE ORURO</Data></Cell></Row>';
        echo '<Row ss:Height="18"><Cell ss:MergeAcross="1" ss:StyleID="subtitulo_uto"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRONÓMICAS Y MEDIO AMBIENTE</Data></Cell></Row>';
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">CONDORIRI - PRODUCTOS AGROPECUARIOS</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Telf.: 5281745 – Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Email: dpdi@uto.edu.bo | www.uto.edu.bo</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="1" ss:StyleID="header"><Data ss:Type="String">REPORTE DE VENTAS AGROPECUARIAS - ' . strtoupper($tipo) . '</Data></Cell></Row>';
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
        echo '<Column ss:Width="50"/><Column ss:Width="120"/><Column ss:Width="200"/><Column ss:Width="100"/><Column ss:Width="100"/><Column ss:Width="130"/><Column ss:Width="80"/>';
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">ID</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cliente/Personal</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Monto Total</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Tipo Pago</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Estado</Data></Cell>';
        echo '</Row>';

        foreach ($ventas as $venta) {
            $cliente = !empty($venta->personal_uto_id) ? ($venta->nombre_personal . ' - CI: ' . $venta->dip) : $venta->cliente_nombre;
            echo '<Row>';
            echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($venta->id ?? 0) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($venta->code ?? '', ENT_XML1) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($cliente, ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($venta->monto_total ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . ucfirst($venta->tipo_pago ?? '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($venta->created_at)) . '</Data></Cell>';
            echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . ($venta->estado == 1 ? 'Finalizada' : 'Cancelada') . '</Data></Cell>';
            echo '</Row>';
        }
        
        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
    }
}
