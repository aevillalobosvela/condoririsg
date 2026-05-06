<?php

namespace App\Controllers\productosAgro;

use App\Controllers\BaseController;

use App\Libraries\Agro\CierreVentaAgroPdf;
use App\Libraries\ArqueoVentasPdf;


use App\Models\Categoria\CategoriaModel;
use App\Models\Cliente\ClienteModel;
use App\Models\ClienteExterno\ClienteExternoModel;
use App\Models\ProductoAgro\ProductoAgroModel;
use App\Models\Venta\DetalleModel;
use App\Models\Venta\VentaModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Database\Exceptions\DatabaseException;

class ventasAgroController extends BaseController
{
    protected $categoriaModel;
    protected $clienteModel;
    protected $clienteExternoModel;
    protected $ventaModel;
    protected $detalleModel;
    protected $productoAgroModel;

    public function __construct()
    {
        $this->categoriaModel      = new CategoriaModel();
        $this->clienteModel        = new ClienteModel();
        $this->clienteExternoModel = new ClienteExternoModel();
        $this->ventaModel          = new VentaModel();
        $this->detalleModel        = new DetalleModel();
        $this->productoAgroModel   = new ProductoAgroModel();
    }

    /**
     * Muestra la lista de ventas (Operación READ - todas).
     *
     * @return string
     */
    public function index()
    {
        $userId = session()->get('id');
        $sucursalId = (int) session()->get('sucursal_id');

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
        $fin    = $fecha_hasta . ' 23:59:59';
        $offset = ($page - 1) * $per_page;

        $db = \Config\Database::connect();

        // Filtro base: sucursal + rango de fechas + solo ventas agro
        $baseParams = [$sucursalId, $inicio, $fin];
        $filtroAgro = "
            AND EXISTS (
                SELECT 1 FROM condoriri.detalle_venta dv
                WHERE dv.venta_id = v.id AND dv.producto_agro_id IS NOT NULL
            )
        ";

        $countQuery = $db->query("
            SELECT COUNT(*) as total
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL
              AND v.sucursal_id = ?
              AND v.created_at >= ?
              AND v.created_at <= ?
              {$filtroAgro}
        ", $baseParams);
        $total = (int)($countQuery->getRow()->total ?? 0);

        $totalVentasQuery = $db->query("
            SELECT SUM(v.monto_total) AS total_monto
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL
              AND v.sucursal_id = ?
              AND v.created_at >= ?
              AND v.created_at <= ?
              AND v.estado = '1'
              {$filtroAgro}
        ", $baseParams);
        $totalVentas = (float)($totalVentasQuery->getRow()->total_monto ?? 0);

        $totalContadoQuery = $db->query("
            SELECT SUM(v.monto_total) AS total_contado
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL
              AND v.sucursal_id = ?
              AND v.created_at >= ?
              AND v.created_at <= ?
              AND v.estado = '1'
              AND v.tipo_pago = 'contado'
              {$filtroAgro}
        ", $baseParams);
        $totalContado = (float)($totalContadoQuery->getRow()->total_contado ?? 0);

        $totalCreditoQuery = $db->query("
            SELECT SUM(v.monto_total) AS total_credito
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL
              AND v.sucursal_id = ?
              AND v.created_at >= ?
              AND v.created_at <= ?
              AND v.estado = '1'
              AND v.tipo_pago = 'credito'
              {$filtroAgro}
        ", $baseParams);
        $totalCredito = (float)($totalCreditoQuery->getRow()->total_credito ?? 0);

        $ventasQuery = $db->query("
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
              AND v.sucursal_id = ?
              AND v.created_at >= ?
              AND v.created_at <= ?
              {$filtroAgro}
            ORDER BY v.created_at DESC
            LIMIT ? OFFSET ?
        ", array_merge($baseParams, [$per_page, $offset]));
        $ventas = $ventasQuery->getResult();

        $totalPages = ceil($total / $per_page);
        $hasPrev   = $page > 1;
        $hasNext   = $page < $totalPages;

        $pagerLinks = '';
        if ($totalPages > 1) {
            $baseUrl  = base_url('productosagro/ventas');
            $queryArr = [
                'fecha_desde' => $fecha_desde,
                'fecha_hasta' => $fecha_hasta,
                'per_page'    => $per_page,
            ];
            $pagerLinks = '<nav aria-label="Paginación"><ul class="pagination justify-content-center mb-0">';

            $prevUrl = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $page - 1]));
            $pagerLinks .= '<li class="page-item' . (!$hasPrev ? ' disabled' : '') . '"><a class="page-link" href="' . ($hasPrev ? $prevUrl : '#') . '">Anterior</a></li>';

            $start = max(1, $page - 2);
            $end   = min($totalPages, $start + 4);
            if ($end - $start < 4 && $totalPages > 5) {
                $start = max(1, $end - 4);
            }
            for ($i = $start; $i <= $end; $i++) {
                $isActive   = $i === $page ? ' active' : '';
                $pageUrl    = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $i]));
                $pagerLinks .= '<li class="page-item' . $isActive . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
            }

            $nextUrl = $baseUrl . '?' . http_build_query(array_merge($queryArr, ['page' => $page + 1]));
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
        $db = \Config\Database::connect();
        $q = $db->query(
            'SELECT pa.*, u.nombre AS unidad_nombre
             FROM condoriri.productos_agro pa
             LEFT JOIN condoriri.unidades u ON u.id = pa.unidad_id
             WHERE pa.fecha_delete IS NULL
               AND pa.estado = true
               AND pa.cantidad_inve > 0
               AND pa.sucursal_id = ?',
            [$sucursal]
        );
        $productos = $q ? $q->getResult() : [];

        return view('productosAgro/ventasCredito', [
            'title'     => 'Registrar Venta Crédito',
            'productos' => $productos,
        ]);
    }


    public function buscarPersonalUto()
    {
        $db      = db_connect();
        $termino = $this->request->getGet('dip');
        $pattern = '%' . $termino . '%';

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
        $sucursal = (int) session()->get('sucursal_id');
        $db = \Config\Database::connect();
        $q = $db->query(
            'SELECT pa.*, u.nombre AS unidad_nombre
             FROM condoriri.productos_agro pa
             LEFT JOIN condoriri.unidades u ON u.id = pa.unidad_id
             WHERE pa.fecha_delete IS NULL
               AND pa.estado = true
               AND pa.cantidad_inve > 0
               AND pa.sucursal_id = ?',
            [$sucursal]
        );
        $productos = $q ? $q->getResult() : [];
        $clientes  = $this->clienteModel->findAll();

        return view('productosAgro/ventasIndex', [
            'title'     => 'Registrar Venta',
            'productos' => $productos,
            'clientes'  => $clientes,
        ]);
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
                $nombreProd = $producto->producto ?? 'Sin nombre';
                throw new \Exception("Stock insuficiente para: {$nombreProd}. Disponible: {$producto->cantidad_inve}.");
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

        $codigoVenta = $this->ventaModel->generarCodigoAgroVenta($sucursalId, $tipoPago);
        $this->ventaModel->update($ventaId, ['code' => $codigoVenta]);

        foreach ($itemsDetalle as $item) {
            $detalleData = [
                'venta_id'         => $ventaId,
                'producto_agro_id' => $item['producto_agro_id'],
                'cantidad'         => $item['cantidad'],
                'precio_unitario'  => $item['precio_unitario'],
                'subtotal'         => $item['subtotal'],
                'observaciones'    => '',
            ];

            if (!$this->detalleModel->insert($detalleData)) {
                throw new \Exception('Error al registrar el detalle para: ' . $item['producto_nombre']);
            }

            $newStock = $item['stock_actual'] - $item['cantidad'];
            if (!$this->productoAgroModel->update($item['producto_agro_id'], ['cantidad_inve' => $newStock])) {
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

        $receptorId   = (int)$this->request->getPost('cliente_id');
        $tipoReceptor = $this->request->getPost('tipo_receptor');
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

        if ($tipoReceptor === 'externo') {
            $receptor = $this->clienteExternoModel->find($receptorId);
            if (!$receptor || !$receptor->estado) {
                return redirect()->back()->with('error', 'El cliente externo seleccionado no es válido.');
            }
        } else {
            $receptor = $db->table('public.personas p')
                ->select('p.id_persona, p.nombre AS nombre')
                ->join('rrhh.empleados e', 'p.id_persona = e.id_persona', 'inner')
                ->where('p.id_persona', $receptorId)
                ->where('p.id_estado', true)
                ->where('e.id_estado', true)
                ->get()->getRow();
            if (!$receptor) {
                return redirect()->back()->with('error', 'El personal seleccionado no es un empleado UTO activo.');
            }
        }

        $db->transBegin();

        try {
            $totalVenta   = 0;
            $itemsDetalle = [];
            $productosCache = [];

            foreach ($productos as $item) {
                $productoId          = (int)($item['id'] ?? 0);
                $cantidad            = (int)($item['quantity'] ?? 0);
                $precioUnitario      = (float)($item['price'] ?? 0);
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

                if ($producto->cantidad_inve < $cantidad) {
                    throw new \Exception("Stock insuficiente para: {$producto->producto}. Disponible: {$producto->cantidad_inve}.");
                }

                $subtotalBruto = $precioUnitario * $cantidad;
                $subtotalFinal = $subtotalBruto - ($subtotalBruto * ($descuentoPorcentaje / 100));
                $totalVenta   += $subtotalFinal;

                $itemsDetalle[] = [
                    'producto_id'         => $productoId,
                    'cantidad'            => $cantidad,
                    'precio_unitario'     => $precioUnitario,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'subtotal'            => $subtotalFinal,
                    'stock_actual'        => $producto->cantidad_inve,
                    'producto_nombre'     => $producto->producto,
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
                'personal_uto_id'    => $tipoReceptor === 'uto'     ? $receptorId : null,
                'cliente_externo_id' => $tipoReceptor === 'externo' ? $receptorId : null,
            ];

            $ventaId = $this->ventaModel->insert($ventaData);
            if (!$ventaId) {
                throw new \Exception('Error al crear la cabecera de la venta.');
            }

            $codigoVenta = $this->ventaModel->generarCodigoAgroVenta($sucursalId, $tipoPago);
            $this->ventaModel->update($ventaId, ['code' => $codigoVenta]);

            foreach ($itemsDetalle as $item) {
                if (!$this->detalleModel->insert([
                    'venta_id'         => $ventaId,
                    'producto_agro_id' => $item['producto_id'],
                    'cantidad'         => $item['cantidad'],
                    'precio_unitario'  => $item['precio_unitario'],
                    'subtotal'         => $item['subtotal'],
                    'observaciones'    => '',
                ])) {
                    throw new \Exception('Error al registrar el detalle para: ' . $item['producto_nombre']);
                }

                $newStock = $item['stock_actual'] - $item['cantidad'];
                if (!$this->productoAgroModel->update($item['producto_id'], ['cantidad_inve' => $newStock])) {
                    throw new \Exception('Error al actualizar el stock de: ' . $item['producto_nombre']);
                }
            }

            $db->transCommit();
            return redirect()->to('/productosagro/recibo/' . $ventaId)->with('success', 'Venta a crédito registrada exitosamente.');
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

     
        $db = \Config\Database::connect();
        $detalleTableName = 'condoriri.detalle_venta';
        $stockTableName = 'condoriri.productos_agro';
        $stockAlias = 'SS';

        $builder = $db->table($detalleTableName);
        $builder->select("{$detalleTableName}.*, {$stockAlias}.producto, u.nombre AS unidad_nombre");
        $builder->join("{$stockTableName} AS {$stockAlias}", "{$stockAlias}.id = {$detalleTableName}.producto_agro_id");
        $builder->join('condoriri.unidades u', "u.id = {$stockAlias}.unidad_id", 'left');
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

        $data = [
            'title'          => 'Recibo de Venta #' . $ventaId,
            'venta'          => $venta,
            'detalles'       => $detalles,
            'cliente'        => $cliente,
            'personal'       => $personal,
            'clienteExterno' => $clienteExterno,
        ];

        return view('productosAgro/recibo_print', $data);
    }





    /**
     * Devuelve JSON con la última venta del usuario hoy (módulo agropecuario).
     */
    public function ultimaVenta()
    {
        $userId     = (int)session()->get('id');
        $sucursalId = (int)session()->get('sucursal_id');
        $db         = \Config\Database::connect();

        $venta = $db->query("
            SELECT v.*
            FROM condoriri.ventas v
            WHERE v.deleted_at IS NULL
              AND v.user_id = ?
              AND v.sucursal_id = ?
              AND DATE(v.created_at) = CURRENT_DATE
              AND EXISTS (
                  SELECT 1 FROM condoriri.detalle_venta dv
                  WHERE dv.venta_id = v.id AND dv.producto_agro_id IS NOT NULL
              )
            ORDER BY v.id DESC
            LIMIT 1
        ", [$userId, $sucursalId])->getRow();

        if (!$venta) {
            return $this->response->setJSON(['venta' => null]);
        }

        $detalles = $db->query("
            SELECT dv.id, dv.producto_agro_id, dv.cantidad, dv.precio_unitario, dv.subtotal, pa.producto
            FROM condoriri.detalle_venta dv
            JOIN condoriri.productos_agro pa ON pa.id = dv.producto_agro_id
            WHERE dv.venta_id = ? AND dv.deleted_at IS NULL
        ", [$venta->id])->getResult();

        $receptor = ['tipo' => 'cliente', 'id' => null, 'nombre' => 'Consumidor Final'];
        if (!empty($venta->personal_uto_id)) {
            $p = $db->query("
                SELECT id_persona AS id, nombre FROM public.personas WHERE id_persona = ?
            ", [$venta->personal_uto_id])->getRow();
            if ($p) $receptor = ['tipo' => 'uto', 'id' => $p->id, 'nombre' => $p->nombre];
        } elseif (!empty($venta->cliente_externo_id)) {
            $ce = $db->query("
                SELECT id, nombre, dip, segmento FROM condoriri.clientes_externos WHERE id = ?
            ", [$venta->cliente_externo_id])->getRow();
            if ($ce) $receptor = ['tipo' => 'externo', 'id' => $ce->id, 'nombre' => $ce->nombre, 'dip' => $ce->dip, 'segmento' => $ce->segmento];
        } elseif (!empty($venta->cliente_id)) {
            $c = $db->query("
                SELECT id, nombre_completo AS nombre FROM condoriri.clientes WHERE id = ?
            ", [$venta->cliente_id])->getRow();
            if ($c) $receptor = ['tipo' => 'cliente', 'id' => $c->id, 'nombre' => $c->nombre];
        }

        $productosDisponibles = $this->productoAgroModel
            ->where('cantidad_inve >', 0)
            ->where('sucursal_id', $sucursalId)
            ->findAll();

        return $this->response->setJSON([
            'venta'                => $venta,
            'detalles'             => $detalles,
            'receptor'             => $receptor,
            'productos_disponibles' => $productosDisponibles,
            'clientes'             => $this->clienteModel->findAll(),
        ]);
    }

    /**
     * Actualiza la última venta del usuario hoy (módulo agropecuario).
     */
    public function updateUltimaVenta()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'error' => 'Método no permitido.']);
        }

        $userId     = (int)session()->get('id');
        $sucursalId = (int)session()->get('sucursal_id');
        $db         = \Config\Database::connect();

        $ventaId      = (int)$this->request->getPost('venta_id');
        $receptorId   = (int)$this->request->getPost('receptor_id');
        $tipoReceptor = $this->request->getPost('tipo_receptor');
        $carritoJson  = $this->request->getPost('carrito');

        if ($ventaId <= 0 || empty($carritoJson)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Datos incompletos.']);
        }

        $carrito = json_decode($carritoJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($carrito)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Carrito inválido.']);
        }

        $venta = $db->query("
            SELECT v.*
            FROM condoriri.ventas v
            WHERE v.id = ?
              AND v.deleted_at IS NULL
              AND v.user_id = ?
              AND v.sucursal_id = ?
              AND DATE(v.created_at) = CURRENT_DATE
              AND EXISTS (
                  SELECT 1 FROM condoriri.detalle_venta dv
                  WHERE dv.venta_id = v.id AND dv.producto_agro_id IS NOT NULL
              )
        ", [$ventaId, $userId, $sucursalId])->getRow();

        if (!$venta) {
            return $this->response->setJSON(['success' => false, 'error' => 'Venta no encontrada o no editable.']);
        }

        $ultima = $db->query("
            SELECT id FROM condoriri.ventas
            WHERE deleted_at IS NULL AND user_id = ? AND sucursal_id = ?
              AND DATE(created_at) = CURRENT_DATE
              AND EXISTS (
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
                SELECT dv.id, dv.producto_agro_id, dv.cantidad
                FROM condoriri.detalle_venta dv
                WHERE dv.venta_id = ? AND dv.deleted_at IS NULL
            ", [$ventaId])->getResult();

            // 2. Devolver cantidad_inve original
            foreach ($detallesOriginales as $det) {
                $db->query("
                    UPDATE condoriri.productos_agro SET cantidad_inve = cantidad_inve + ? WHERE id = ?
                ", [$det->cantidad, $det->producto_agro_id]);
            }

            // 3. Validar nuevo carrito
            $itemsNuevos = [];
            $nuevoTotal  = 0;
            foreach ($carrito as $item) {
                $productoId     = (int)($item['id'] ?? 0);
                $cantidad       = (int)($item['cantidad'] ?? 0);
                $precioUnitario = (float)($item['precio_unitario'] ?? 0);

                if ($productoId <= 0 || $cantidad <= 0 || $precioUnitario <= 0) {
                    throw new \Exception('Datos inválidos en el carrito.');
                }

                $producto = $this->productoAgroModel->find($productoId);
                if (!$producto) {
                    throw new \Exception("Producto agro ID {$productoId} no encontrado.");
                }
                if ($producto->cantidad_inve < $cantidad) {
                    throw new \Exception("Stock insuficiente para: {$producto->producto}. Disponible: {$producto->cantidad_inve}.");
                }

                $subtotal    = $precioUnitario * $cantidad;
                $nuevoTotal += $subtotal;
                $itemsNuevos[] = [
                    'producto_agro_id' => $productoId,
                    'cantidad'         => $cantidad,
                    'precio_unitario'  => $precioUnitario,
                    'subtotal'         => $subtotal,
                ];
            }

            // 4. Soft-delete detalles originales
            foreach ($detallesOriginales as $det) {
                $this->detalleModel->delete($det->id);
            }

            // 5. Insertar nuevos detalles y descontar cantidad_inve
            foreach ($itemsNuevos as $item) {
                $this->detalleModel->insert([
                    'venta_id'         => $ventaId,
                    'producto_agro_id' => $item['producto_agro_id'],
                    'cantidad'         => $item['cantidad'],
                    'precio_unitario'  => $item['precio_unitario'],
                    'subtotal'         => $item['subtotal'],
                    'observaciones'    => '',
                ]);

                $db->query("
                    UPDATE condoriri.productos_agro SET cantidad_inve = cantidad_inve - ? WHERE id = ?
                ", [$item['cantidad'], $item['producto_agro_id']]);
            }

            // 6. Actualizar receptor y monto
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
                'recibo_url'  => base_url('productosagro/recibo/' . $ventaId),
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Elimina la última venta del usuario hoy (módulo agropecuario).
     * Devuelve cantidad_inve y hace soft-delete de detalles + soft-delete de la venta.
     */
    public function deleteUltimaVenta()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'error' => 'Método no permitido.']);
        }

        $userId     = (int)session()->get('id');
        $sucursalId = (int)session()->get('sucursal_id');
        $db         = \Config\Database::connect();

        $ventaId = (int)$this->request->getPost('venta_id');
        if ($ventaId <= 0) {
            return $this->response->setJSON(['success' => false, 'error' => 'ID de venta inválido.']);
        }

        $venta = $db->query("
            SELECT v.*
            FROM condoriri.ventas v
            WHERE v.id = ?
              AND v.deleted_at IS NULL
              AND v.user_id = ?
              AND v.sucursal_id = ?
              AND DATE(v.created_at) = CURRENT_DATE
              AND EXISTS (
                  SELECT 1 FROM condoriri.detalle_venta dv
                  WHERE dv.venta_id = v.id AND dv.producto_agro_id IS NOT NULL
              )
        ", [$ventaId, $userId, $sucursalId])->getRow();

        if (!$venta) {
            return $this->response->setJSON(['success' => false, 'error' => 'Venta no encontrada o no eliminable.']);
        }

        $ultima = $db->query("
            SELECT id FROM condoriri.ventas
            WHERE deleted_at IS NULL AND user_id = ? AND sucursal_id = ?
              AND DATE(created_at) = CURRENT_DATE
              AND EXISTS (
                  SELECT 1 FROM condoriri.detalle_venta dv
                  WHERE dv.venta_id = condoriri.ventas.id AND dv.producto_agro_id IS NOT NULL
              )
            ORDER BY id DESC LIMIT 1
        ", [$userId, $sucursalId])->getRow();

        if (!$ultima || (int)$ultima->id !== $ventaId) {
            return $this->response->setJSON(['success' => false, 'error' => 'Solo puede eliminar su última venta del día.']);
        }

        $db->transBegin();
        try {
            // 1. Leer detalles originales
            $detalles = $db->query("
                SELECT dv.id, dv.producto_agro_id, dv.cantidad
                FROM condoriri.detalle_venta dv
                WHERE dv.venta_id = ? AND dv.deleted_at IS NULL
            ", [$ventaId])->getResult();

            // 2. Devolver cantidad_inve
            foreach ($detalles as $det) {
                $db->query("
                    UPDATE condoriri.productos_agro SET cantidad_inve = cantidad_inve + ? WHERE id = ?
                ", [$det->cantidad, $det->producto_agro_id]);
            }

            // 3. Soft-delete de detalles
            foreach ($detalles as $det) {
                $this->detalleModel->delete($det->id);
            }

            // 4. Soft-delete de la venta
            $db->query("UPDATE condoriri.ventas SET deleted_at = NOW() WHERE id = ?", [$ventaId]);

            $db->transCommit();
            return $this->response->setJSON(['success' => true]);
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

        if (!in_array($tipo, ['contado', 'credito', 'general'])) {
            $tipo = 'general';
        }

        $ventaModel = new \App\Models\Venta\VentaModel();
        $reportData = $ventaModel->getDailySalesReportData($fecha_inicio, $fecha_fin, $tipo, true);

        $db = \Config\Database::connect();
        $usuarioGenerador = $db->table('condoriri.usuarios')
            ->select('nombre, apellidos')
            ->where('id', session()->get('id'))
            ->get()->getRowArray();
        $nombreUsuario = $usuarioGenerador
            ? ucwords(strtolower(trim(($usuarioGenerador['nombre'] ?? '') . ' ' . ($usuarioGenerador['apellidos'] ?? ''))))
            : 'Usuario';

        $pdfGenerator = new CierreVentaAgroPdf();
        $pdfGenerator->generarReporteVentas($reportData, [
            'fecha_inicio'   => $fecha_inicio,
            'fecha_fin'      => $fecha_fin,
            'tipo'           => $tipo,
            'nombre_usuario' => $nombreUsuario,
        ]);
    }

    public function exportarExcelVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? date('Y-m-d');
        $fecha_fin    = $this->request->getGet('fecha_fin')    ?? date('Y-m-d');
        $tipo         = $this->request->getGet('tipo')         ?? 'general';

        $service = new \App\Services\Agro\ExcelVentasAgroService();
        $service->exportar($fecha_inicio, $fecha_fin, $tipo);
    }

    public function exportarArqueoPdf()
    {
        $hoy          = date('Y-m-d');
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?: $hoy;
        $fecha_fin    = $this->request->getGet('fecha_fin')    ?: $hoy;

        $ventaModel = new \App\Models\Venta\VentaModel();
        $reportData = $ventaModel->getDailySalesReportData($fecha_inicio, $fecha_fin, null, true);

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
            'titulo_modulo'     => 'CONDORIRI AGROPECUARIO',
            'responsable_cargo' => 'Responsable - Productos Agropecuarios',
        ]);
    }
}
