<?php

namespace App\Controllers\transferencias;

use App\Controllers\BaseController;
use App\Libraries\FacturaPdf;
use App\Models\Envios\EnviosModel;
use App\Models\Estado\EstadoModel;
use App\Models\Producto\ProductoModel;
use App\Models\TransferirProducto\TransferirProductosModel;
use App\Models\UsuarioModel;
use App\Models\Inventario\InventarioModel;
use App\Models\StockSucursal\StockSucursalModel;
use CodeIgniter\API\ResponseTrait;

class transferenciasController extends BaseController
{
    use ResponseTrait;

    protected $transferenciasModel;
    protected $envioModel;
    protected $productosModel;
    protected $estadoModel;
    protected $usuarioModel;
    protected $inventariosModel;
    protected $stockSucursalModel;

    public function __construct()
    {
        $this->envioModel = new EnviosModel();
        $this->productosModel = new ProductoModel();
        $this->estadoModel = new EstadoModel();
        $this->usuarioModel = new UsuarioModel();
        $this->transferenciasModel = new TransferirProductosModel();
        $this->inventariosModel = new InventarioModel();
        $this->stockSucursalModel = new StockSucursalModel();
    }

    /*** Muestra los detalles de un envío y sus transferencias.
     
     *
     * @param int $id
     * @return string
     */
    public function index($id)
    {



        $envio = $this->envioModel->getEnvioWithRelations($id);

        if (!$envio) {
            session()->setFlashdata('error', 'El envío solicitado no existe.');
            return redirect()->to(base_url('envios'));
        }

        $sucursalId = session()->get('sucursal_id');
        $searchQuery = $this->request->getGet('q');

        $productos = $this->productosModel->getProductosBySucursalAndSearch($sucursalId, $searchQuery);


        $transferenciasReales = [];

        if ($envio['estado_id'] == 2) {

            $transferenciasReales = $this->transferenciasModel->getTransferenciasWithProductData($id);
        }
        $envioID = $id;

        $data = [
            'envio'               => $envio,
            'productos'           => $productos,
            'transferenciasReales' => $transferenciasReales,
            'title'               => 'Detalles de Envío',
            'searchQuery'         => $searchQuery,
            'sucursalId'          => $sucursalId,
            'envioID'             => $envioID,
        ];



        return view('envios/enviosShow', $data);
    }


    /**
     * Confirma un envío y registra las transferencias.
     *
     * @return RedirectResponse
     */
  public function confirmarEnvio()
{
    if (!$this->request->is('post')) {
        return redirect()->back()->with('error', 'Método no permitido.');
    }

    $envioId = (int) $this->request->getPost('envio_id');
    $productos = $this->request->getPost('productos');

    if (empty($envioId) || empty($productos) || !is_array($productos)) {
        return redirect()->back()->with('error', 'Datos incompletos.');
    }

    $envio = $this->envioModel->find($envioId);
    if (!$envio) {
        return redirect()->back()->with('error', 'Envío no encontrado.');
    }

    // ✅ Determinar si es devolución (origen = sucursal 2)
    $esDevolucion = ($envio['sucursal_origen_id'] == 2);

    $db = \Config\Database::connect();
    $db->transStart();

 try {
    // ✅ Validar usuario
    $userId = session()->get('id');
    if (!$userId || !is_numeric($userId) || $userId <= 0) {
        throw new \Exception('Usuario no autenticado.');
    }
    $userId = (int) $userId;

    foreach ($productos as $key => $data) {
        $productoId = (int) ($data['producto_id'] ?? 0);
        $inventarioId = (int) ($data['inventario_id'] ?? 0);
        $cantidad = (int) ($data['cantidad'] ?? 0);
        $observacion = trim($data['observacion_origen'] ?? '');

        if ($productoId <= 0 || $inventarioId <= 0 || $cantidad <= 0) {
            throw new \Exception("Datos inválidos en producto #{$key}.");
        }

        $productoDb = $this->productosModel->find($productoId);
        if (!$productoDb) {
            throw new \Exception("Producto ID {$productoId} no encontrado.");
        }

        // ✅ Manejo de stock (devolución vs transferencia)
        if ($esDevolucion) {
            $stockItem = $this->stockSucursalModel
                ->where('id', $inventarioId)
                ->where('producto_id', $productoId)
                ->where('sucursal_id', $envio['sucursal_origen_id'])
                ->first();

            if (!$stockItem) {
                throw new \Exception("Lote {$inventarioId} no válido para este producto y sucursal.");
            }

            $stockActual = (int) ($stockItem['stock'] ?? 0);
            if ($stockActual < $cantidad) {
                throw new \Exception("Stock insuficiente en lote {$inventarioId}. Disponible: {$stockActual}.");
            }

            if (!$this->stockSucursalModel->update($inventarioId, ['stock' => $stockActual - $cantidad])) {
                throw new \Exception("Error al actualizar stock del lote {$inventarioId}.");
            }
        } else {
            $stockActual = (int) ($productoDb->stock_inve ?? 0);
            if ($stockActual < $cantidad) {
                throw new \Exception("Stock insuficiente para producto {$productoId}.");
            }
            if (!$this->productosModel->update($productoId, ['stock_inve' => $stockActual - $cantidad])) {
                throw new \Exception("Error al actualizar stock del producto {$productoId}.");
            }
        }

        // ✅ ✅ ✅ Transferencia — datos 100% compatibles con validación
        $transferData = [
            'envio_id'           => $envioId,
            'producto_id'        => $productoId,
            'inventario_id'      => $inventarioId,
            'cantidad'           => $cantidad,
            'cantidad_acep'      => 0,
            'precio_contado'     => (float) ($productoDb->precio_contado ?? 0),
            'precio_credito'     => (float) ($productoDb->precio_credito ?? 0),
            'observacion_origen' => !empty($observacion) ? $observacion : null,
            'estado_id'          => 2,
            'user_id'            => $userId, // ← entero > 0
            'user_recepcion_id'  => 0,       // ← 0 permitido
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ];

        if (!$this->transferenciasModel->insert($transferData)) {
            $errors = $this->transferenciasModel->errors();
            throw new \Exception("Error al guardar transferencia: " . implode(', ', $errors));
        }
    }

    // ✅ Actualizar envío
    $this->envioModel->update($envioId, [
        'estado_id'   => 2,
        'fecha_envio' => date('Y-m-d H:i:s'),
    ]);

    $db->transComplete();
    if ($db->transStatus() === false) {
        throw new \Exception('Transacción fallida.');
    }

    $mensaje = $esDevolucion ? 'Devolución confirmada.' : 'Envío confirmado.';
    return redirect()->to("/envios/show/{$envioId}")->with('message', $mensaje);

} catch (\Exception $e) {
    $db->transRollback();
    log_message('error', 'confirmarEnvio: ' . $e->getMessage());
    return redirect()->back()->with('error', $e->getMessage());
}
}

    /**
     * Genera una factura PDF para un envío.
     *
     * @param int $envioId
     * @return void
     */
    public function generarFactura($envioId)
    {
        $db = \Config\Database::connect();

        // Obtener los datos del envío con JOINs
        $query = $db->query("
            SELECT 
                e.*,
                so.nombre as sucursal_origen_nombre,
                sd.nombre as sucursal_destino_nombre,
                uc.nombre as creador_nombre,
                uc.apellidos as creador_apellido,
                uc.ci as creador_ci,
                ut.nombre as transporte_nombre,
                ut.apellidos as transporte_apellido,
                ut.ci as transporte_ci
            FROM condoriri.envios e
            JOIN condoriri.sucursales so ON so.id = e.sucursal_origen_id
            JOIN condoriri.sucursales sd ON sd.id = e.sucursal_destino_id
            LEFT JOIN condoriri.usuarios uc ON uc.id = e.user_id
            LEFT JOIN condoriri.usuarios ut ON ut.id = e.user_transporte_id
            WHERE e.id = ?
        ", [$envioId]);
        
        $envio = $query->getRowArray();

        // Obtener los productos asociados a la transferencia
        $queryProductos = $db->query("
            SELECT 
                tp.*,
                p.nombre as producto_nombre,
                p.precio_contado as precio_unitario
            FROM condoriri.transferencias_productos tp
            JOIN condoriri.productos p ON p.id = tp.producto_id
            WHERE tp.envio_id = ?
        ", [$envioId]);
        
        $productos = $queryProductos->getResult();

        if (empty($envio) || empty($productos)) {
            return $this->response->setStatusCode(404)->setBody('Envío no encontrado.');
        }

        $pdf = new FacturaPdf();
        $pdf->generarReporteEnvio($envio, $productos);
    }
}
