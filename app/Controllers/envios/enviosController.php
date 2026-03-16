<?php

namespace App\Controllers\Envios;

use App\Controllers\BaseController;
use App\Models\Envios\EnviosModel;
use App\Models\Sucursal\SucursalModel;
use App\Models\UsuarioModel;
use App\Models\Rol\RolModel;
use App\Models\Estado\EstadoModel;
use App\Models\Producto\ProductoModel;
use App\Models\TransferirProducto\TransferirProductosModel;
use App\Models\StockSucursal\StockSucursalModel;
use App\Models\Devoluciones\DevolucionesModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;

class EnviosController extends BaseController
{
    protected $envioModel;
    protected $sucursalModel;
    protected $transferenciasModel;
    protected $userModel;
    protected $rolesModel;
    protected $detallesEnvioModel;
    protected $estadoModel;
    protected $productosModel;
    protected $stockSucursalModel;
    protected $devolucionesModel;

    public function __construct()
    {
        $this->envioModel = new EnviosModel();
        $this->sucursalModel = new SucursalModel();
        $this->userModel = new UsuarioModel();
        $this->rolesModel = new RolModel();
        $this->estadoModel = new EstadoModel();
        $this->productosModel = new ProductoModel();
        $this->transferenciasModel = new TransferirProductosModel();
        $this->stockSucursalModel = new StockSucursalModel();
        $this->devolucionesModel = new DevolucionesModel();
    }

    public function index()
    {
        // Obtener el tipo de la URL o por defecto 'envio'
        $tipo = $this->request->getGet('tipo') ?? 'envio';
        
        // Validar que el tipo sea válido
        if (!in_array($tipo, ['envio', 'devolucion'])) {
            $tipo = 'envio';
        }

        // Obtener la sucursal del usuario en sesión
        $userSucursalId = session()->get('sucursal_id');
        
        // Filtrar envíos solo de la sucursal del usuario y por tipo
        $this->envioModel->where('tipo', $tipo);
        
        if ($userSucursalId) {
            $this->envioModel->where('sucursal_origen_id', $userSucursalId);
        }
        
        $envios = $this->envioModel->findAll();
        
        $data = [
            'envios' => $envios,
            'title'  => ucfirst($tipo) . 's', // 'Envios' o 'Devolucions' (ajustaremos el título en la vista si es necesario)
            'tipo'   => $tipo,
            'productos' => $this->productosModel->getAllProductosWithRelations(),
            'sucursales' => $this->sucursalModel->findAll(),
        ];

        echo view('envios/enviosIndex', $data);
    }

    public function register()
    {
        $tipo = $this->request->getGet('tipo') ?? 'envio';
        if (!in_array($tipo, ['envio', 'devolucion'])) {
            $tipo = 'envio';
        }

        $sucursal_origen_id = session()->get('sucursal_id');
        $userId = session()->get('id');

        $sucursales = $this->sucursalModel->findAll();
        $rol = $this->rolesModel->where('nombre', 'envios')->first();

        $users = [];
        if ($rol) {
            $users = $this->userModel->where('rol_id', $rol->id)->findAll();
        }

        $data = [
            'title'       => $tipo === 'devolucion' ? 'Registrar Devolución' : 'Crear Nuevo Envío',
            'errors'      => [],
            'sucursales'  => $sucursales,
            'users'       => $users,
            'tipo'        => $tipo,
            'envio'       => [
                'id' => null,
                'code' => '',
                'sucursal_origen_id' => $sucursal_origen_id,
                'sucursal_destino_id' => '',
                'observacion_origen' => '',
                'observacion_destino' => '',
                'estado_id' => '',
                'fecha_envio' => '',
                'fecha_recepcion' => '',
                'user_transporte_id' => '',
                'user_id' => '',
                'user_recepcion_id' => '',
                'tipo' => $tipo,
            ],
            'sucursalId' => $sucursal_origen_id,
            'userId'     => $userId,
        ];

        echo view('envios/enviosForm', $data);
    }

    public function create(): RedirectResponse
    {
        $sucursal_origen_id = (int) session()->get('sucursal_id');
        $userId = (int) session()->get('id');

        if (empty($sucursal_origen_id) || empty($userId)) {
            session()->setFlashdata('error', 'No se pudo obtener la información del usuario o la sucursal.');
            return redirect()->back()->withInput();
        }

        $rules = $this->envioModel->getValidationRules();
        unset($rules['code']);

        if (!$this->validate($rules, [
            'sucursal_destino_id' => [
                'required' => 'La sucursal de destino es obligatoria.',
            ],
            'user_transporte_id' => [
                'required' => 'El usuario de transporte es obligatorio.',
            ],
            'fecha_envio' => [
                'required' => 'La fecha de envío es obligatoria.',
            ],
        ])) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $newCode = $this->_generarNuevoCodigo($this->request->getPost('tipo'));
        $user_recepcion_id = $this->request->getPost('user_recepcion_id') ?: null;

        $data = [
            'code'                => $newCode,
            'sucursal_origen_id'  => (int)$sucursal_origen_id,
            'sucursal_destino_id' => (int)$this->request->getPost('sucursal_destino_id'),
            'observacion_origen'  => $this->request->getPost('observacion_origen'),
            'observacion_destino' => $this->request->getPost('observacion_destino'),
            'estado_id'           => 1, 
            'fecha_envio'         => $this->request->getPost('fecha_envio'),
            'fecha_recepcion'     => $this->request->getPost('fecha_recepcion'),
            'user_transporte_id'  => (int)$this->request->getPost('user_transporte_id'),
            'user_id'             => (int)$userId,
            'user_recepcion_id'   => $user_recepcion_id ? (int)$user_recepcion_id : null,
            'tipo'                => $this->request->getPost('tipo') ?: 'envio',
        ];

        if ($this->envioModel->save($data)) {
            $tipo = $data['tipo'];
            return redirect()->to("/envios?tipo={$tipo}")->with('message', ucfirst($tipo) . ' creado exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al crear el envío.');
        }
    }

   public function show(int $id)
{
    $envio = $this->envioModel->find($id);

    if (!$envio) {
        return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
    }

    // Cargar relaciones
    $sucursalOrigen  = $this->sucursalModel->find($envio['sucursal_origen_id']);
    $sucursalDestino = $this->sucursalModel->find($envio['sucursal_destino_id']);
    $userTransporte  = $this->userModel->find($envio['user_transporte_id']);
    $userCreador     = $this->userModel->find($envio['user_id']);
    $userRecepcion   = $envio['user_recepcion_id']
        ? $this->userModel->find($envio['user_recepcion_id'])
        : null;
    $estado          = $this->estadoModel->find($envio['estado_id']);

    // Obtener todas las transferencias (luego se filtran en la vista por envio_id)
    $transferencias = $this->transferenciasModel->getTransfersWithDetails();

    // Etiquetas dinámicas
    $tipoLabel  = ucfirst($envio['tipo'] ?? 'envio'); // "Envio", "Transferencia", etc.
    $tipoAccion = ($envio['sucursal_origen_id'] == 2) ? 'Devolución' : 'Envío';

    // Cargar productos disponibles según la sucursal de origen
    if ($envio['sucursal_origen_id'] == 2) {
        $productos = $this->stockSucursalModel->getProductosConStockPorSucursal(2);
    } else {
        $productos = $this->productosModel->getAllProductosWithRelations();
    }
 
    $data = [
        'envio'           => (object) $envio,
        'productos'       => $productos,
        'transferencias'  => $transferencias,
        'sucursalOrigen'  => (object) $sucursalOrigen,
        'sucursalDestino' => (object) $sucursalDestino,
        'userTransporte'  => (object) $userTransporte,
        'userCreador'     => (object) $userCreador,
        'userRecepcion'   => $userRecepcion ? (object) $userRecepcion : null,
        'estado'          => (object) $estado,
        'title'           => "Detalle del $tipoLabel",
        'envioID'         => $id,
        'tipoLabel'       => $tipoLabel,
        'tipoAccion'      => $tipoAccion, // ✅ imprescindible para la vista
    ];

    return view('envios/enviosShow', $data);
}

    public function generarReporte()
    {
        $tipo = $this->request->getGet('tipo') ?? 'envio';
        $sucursal_origen_id = $this->request->getGet('sucursal_origen_id');
        $sucursal_destino_id = $this->request->getGet('sucursal_destino_id');
        $estado_id = $this->request->getGet('estado_id');
        $fecha_inicio = $this->request->getGet('fecha_inicio');
        $fecha_fin = $this->request->getGet('fecha_fin');

        // Builder para envios con relaciones
        $builder = $this->envioModel->builder();
        $builder->select('
            condoriri.envios.*,
            so.nombre as sucursal_origen_nombre,
            sd.nombre as sucursal_destino_nombre,
            u.usuario as creador_nombre,
            ut.usuario as transporte_nombre,
            e.nombre as estado_nombre
        ');
        $builder->join('condoriri.sucursales as so', 'so.id = condoriri.envios.sucursal_origen_id', 'left');
        $builder->join('condoriri.sucursales as sd', 'sd.id = condoriri.envios.sucursal_destino_id', 'left');
        $builder->join('condoriri.usuarios as u', 'u.id = condoriri.envios.user_id', 'left');
        $builder->join('condoriri.usuarios as ut', 'ut.id = condoriri.envios.user_transporte_id', 'left');
        $builder->join('condoriri.estados as e', 'e.id = condoriri.envios.estado_id', 'left');

        $builder->where('condoriri.envios.tipo', $tipo);

        if ($sucursal_origen_id) {
            $builder->where('condoriri.envios.sucursal_origen_id', $sucursal_origen_id);
        }
        if ($sucursal_destino_id) {
            $builder->where('condoriri.envios.sucursal_destino_id', $sucursal_destino_id);
        }
        if ($estado_id) {
            $builder->where('condoriri.envios.estado_id', $estado_id);
        }
        if ($fecha_inicio) {
            $builder->where('condoriri.envios.fecha_envio >=', $fecha_inicio . ' 00:00:00');
        }
        if ($fecha_fin) {
            $builder->where('condoriri.envios.fecha_envio <=', $fecha_fin . ' 23:59:59');
        }

        $envios = $builder->get()->getResultArray();

        // Obtener productos para cada envío
        foreach ($envios as &$envio) {
            $envio['productos'] = $this->transferenciasModel->builder()
                ->select('
                    condoriri.transferencias_productos.cantidad,
                    condoriri.productos.nombre as producto_nombre,
                    condoriri.productos.code as producto_codigo
                ')
                ->where('condoriri.transferencias_productos.envio_id', $envio['id'])
                ->join('condoriri.productos', 'condoriri.productos.id = condoriri.transferencias_productos.producto_id')
                ->get()
                ->getResultArray();
        }

        $data = [
            'envios' => $envios,
            'tipo' => $tipo,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'title' => 'Reporte Detallado de ' . ucfirst($tipo) . 's',
        ];

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_top' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('envios/reporteEnvios', $data);
        $mpdf->WriteHTML($html);
        
        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output('Reporte_' . ucfirst($tipo) . 's_' . date('YmdHis') . '.pdf', 'I');
    }

    public function edit(int $id)
    {
        $envio = $this->envioModel->find($id);

        if (!$envio) {
            return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
        }

        $sucursales = $this->sucursalModel->findAll();
        $rol = $this->rolesModel->where('nombre', 'envios')->first();
        $users = $rol ? $this->userModel->where('rol_id', $rol->id)->findAll() : [];

        $data = [
            'envio'      => $envio,
            'title'      => 'Editar Envío',
            'sucursales' => $sucursales,
            'users'      => $users,
        ];

        echo view('envios/enviosForm', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $rules = $this->envioModel->getValidationRules();

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $envio = $this->envioModel->find($id);

        if (!$envio) {
            return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
        }

        $user_recepcion_id = $this->request->getPost('user_recepcion_id') ?: null;

        $data = [
            'id'                  => $id,
            'code'                => $this->request->getPost('code'),
            'sucursal_origen_id'  => $envio['sucursal_origen_id'],
            'sucursal_destino_id' => $this->request->getPost('sucursal_destino_id'),
            'observacion_origen'  => $this->request->getPost('observacion_origen'),
            'observacion_destino' => $this->request->getPost('observacion_destino'),
            'estado_id'           => $this->request->getPost('estado_id'),
            'fecha_envio'         => $this->request->getPost('fecha_envio'),
            'fecha_recepcion'     => $this->request->getPost('fecha_recepcion'),
            'user_transporte_id'  => $this->request->getPost('user_transporte_id'),
            'user_id'             => $envio['user_id'],
            'user_recepcion_id'   => $user_recepcion_id,
        ];

        if ($this->envioModel->save($data)) {
            return redirect()->to('/envios')->with('message', 'Envío actualizado exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar el envío.');
        }
    }

    public function delete(int $id): RedirectResponse
    {
        if ($this->envioModel->delete($id)) {
            session()->setFlashdata('success', 'Envío eliminado exitosamente.');
        } else {
            session()->setFlashdata('error', 'No se pudo eliminar el envío.');
        }

        return redirect()->to('/envios');
    }

    /**
     * Confirmar envío - Actualizar estado_id a 2 (Enviado)
     */
public function confirmarEnvio(): RedirectResponse
{
    // Nota: el ID viene como hidden input, no por URL
    $id = (int) $this->request->getPost('envio_id');
    if (!$id) {
        return redirect()->to('/envios')->with('error', 'ID de envío no proporcionado.');
    }

    $envio = $this->envioModel->withDeleted()->find($id);
    if (!$envio) {
        return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
    }

    // Verificar que no esté ya confirmado
    if ($envio['estado_id'] == 2) {
        return redirect()->to("/envios/show/{$id}")
            ->with('error', 'El envío ya ha sido confirmado anteriormente.');
    }

    $productosInput = $this->request->getPost('productos') ?? [];

    if (empty($productosInput) || !is_array($productosInput)) {
        return redirect()->back()->with('error', 'Debe seleccionar al menos un producto.');
    }

    // Validar y sanitizar productos
    $productosValidos = [];
    foreach ($productosInput as $key => $data) {
        $productoId = (int)($data['producto_id'] ?? 0);
        $inventarioId = (int)($data['inventario_id'] ?? 0);
        $cantidad = (int)($data['cantidad'] ?? 0);
        $observacionOrigen = trim($data['observacion_origen'] ?? '');

        if ($productoId <= 0 || $inventarioId <= 0 || $cantidad <= 0) {
            continue;
        }

        // ✅ Verificar que el lote exista y tenga stock suficiente
        $stockItem = $this->stockSucursalModel
            ->where('id', $inventarioId)
            ->where('producto_id', $productoId)
            ->where('sucursal_id', $envio['sucursal_origen_id'])
            ->first();

        if (!$stockItem) {
            return redirect()->back()->with('error', "Lote inválido para el producto ID {$productoId}.");
        }

        if ($stockItem['stock'] < $cantidad) {
            return redirect()->back()->with('error', 
                "Stock insuficiente para '{$stockItem['nombre']}'. Disponible: {$stockItem['stock']}, solicitado: {$cantidad}.");
        }

        $productosValidos[] = [
            'producto_id' => $productoId,
            'inventario_id' => $inventarioId,
            'cantidad' => $cantidad,
            'observacion_origen' => $observacionOrigen,
            'stock_item' => $stockItem, // para reducir stock después
        ];
    }

    if (empty($productosValidos)) {
        return redirect()->back()->with('error', 'No se encontraron productos válidos para procesar.');
    }

    // ✅ Transacción para garantizar atomicidad
    $db = \Config\Database::connect();
    $db->transStart();

    try {
        // 1. Insertar transferencias
        $transferenciasData = [];
        $now = date('Y-m-d H:i:s');

        foreach ($productosValidos as $item) {
            $transferenciasData[] = [
                'envio_id' => $id,
                'producto_id' => $item['producto_id'],
                'inventario_id' => $item['inventario_id'],
                'cantidad' => $item['cantidad'],
                'cantidad_acep' => 0, // pendiente de recepción
                'precio_contado' => $item['stock_item']['precio_contado'] ?? 0,
                'precio_credito' => $item['stock_item']['precio_credito'] ?? 0,
                'observacion_origen' => $item['observacionOrigen'],
                'estado_id' => 2, // Enviado
                'user_id' => auth()->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($transferenciasData)) {
            $this->transferenciasModel->insertBatch($transferenciasData);
        }

        // 2. Reducir stock en sucursal origen
        foreach ($productosValidos as $item) {
            $stockId = $item['stock_item']['id'];
            $nuevoStock = $item['stock_item']['stock'] - $item['cantidad'];

            $this->stockSucursalModel->update($stockId, [
                'stock' => $nuevoStock,
                'updated_at' => $now,
            ]);
        }

        // 3. Actualizar envío
        $this->envioModel->update($id, [
            'estado_id' => 2,
            'fecha_envio' => $now,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \Exception('Error en la transacción.');
        }

        return redirect()->to("/envios/show/{$id}")
            ->with('message', $envio['sucursal_origen_id'] == 2 
                ? 'Devolución confirmada exitosamente. Estado actualizado a "Enviado".'
                : 'Envío confirmado exitosamente. Estado actualizado a "Enviado".');

    } catch (\Exception $e) {
        $db->transRollback();
        log_message('error', 'Error confirmando envío ID ' . $id . ': ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error interno al procesar la confirmación.');
    }
}

    private function _generarNuevoCodigo($tipo = 'envio'): string
    {
        $fecha = date('d-m-y');
        $prefijo = ($tipo === 'devolucion' ? "DEV-" : "ENV-") . "{$fecha}-";

        $ultimoEnvio = $this->envioModel
            ->like('code', $prefijo, 'after')
            ->orderBy('code', 'DESC')
            ->first();

        $incremento = 1;
        if ($ultimoEnvio) {
            $partes = explode('-', $ultimoEnvio['code']);
            $ultimoIncremento = end($partes);
            $incremento = (int)$ultimoIncremento + 1;
        }

        return $prefijo . $incremento;
    }

    public function show1(int $id)
    {
        $envio = $this->envioModel->find($id);

        if (!$envio) {
            return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
        }

        $sucursalOrigen = $this->sucursalModel->find($envio['sucursal_origen_id']);
        $sucursalDestino = $this->sucursalModel->find($envio['sucursal_destino_id']);
        $userTransporte = $this->userModel->find($envio['user_transporte_id']);
        $userCreador = $this->userModel->find($envio['user_id']);
        $userRecepcion = $envio['user_recepcion_id'] ? $this->userModel->find($envio['user_recepcion_id']) : null;
        $estado = $this->estadoModel->find($envio['estado_id']);

        // ✅ Obtener productos de la sucursal de origen con stock > 0
        $productos = $this->productosModel->getAllProductosWithRelations($sucursalOrigen ? $sucursalOrigen['id'] : null);

        $transferencias = $this->transferenciasModel->getTransfersWithDetails();

        $data = [
            'envio'           => (object) $envio,
            'productos'       => $productos,
            'transferencias'  => $transferencias,
            'sucursalOrigen'  => (object) $sucursalOrigen,
            'sucursalDestino' => (object) $sucursalDestino,
            'userTransporte'  => (object) $userTransporte,
            'userCreador'     => (object) $userCreador,
            'userRecepcion'   => $userRecepcion ? (object) $userRecepcion : null,
            'estado'          => (object) $estado,
            'title'           => 'Detalle del Envío',
            'envioID'         => $id,
        ];

        echo view('envios/enviosShow', $data);
    }

    /**
     * Confirmar envío Y procesar productos (actualizar stock)
     */
    public function confirmarConProductos(int $envioId): RedirectResponse
    {
        $envio = $this->envioModel->find($envioId);
        if (!$envio) {
            return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
        }
        if ($envio['estado_id'] == 2) {
            return redirect()->to("/envios/show/{$envioId}")->with('error', 'El envío ya fue confirmado.');
        }

        $productosPost = $this->request->getPost('productos');

        if (empty($productosPost) || !is_array($productosPost)) {
            return redirect()->to("/envios/show/{$envioId}")->with('error', 'Debe agregar al menos un producto.');
        }

        
        $db = db_connect();
        $db->transStart();

        try {
          
            foreach ($productosPost as $productoData) {
                $nombreProducto = $productoData['producto_id'] ?? null;
                $cantidad = (int)($productoData['cantidad'] ?? 0);
                $observacion = $productoData['observacion_origen'] ?? '';

                if ($cantidad <= 0 || empty($nombreProducto)) continue;

               
                $producto = $this->productosModel
                    ->like('nombre', $nombreProducto, 'after')
                    ->first();

                if (!$producto) {
                    throw new \Exception("Producto no encontrado: {$nombreProducto}");
                }

                if ($producto->stock < $cantidad) {
                    throw new \Exception("Stock insuficiente para: {$producto->nombre}");
                }

               
                $nuevoStock = $producto->stock - $cantidad;
                $this->productosModel->update($producto->id, ['stock' => $nuevoStock]);

             
                $this->transferenciasModel->insert([
                    'envio_id' => $envioId,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'observacion_origen' => $observacion,
                    'estado_id' => 2, // Enviado
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

           
            $this->envioModel->update($envioId, [
                'estado_id' => 2,
                'fecha_envio' => date('Y-m-d H:i:s')
            ]);
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error en la transacción.');
            }

            return redirect()->to("/envios/show/{$envioId}")
                ->with('message', 'Envío confirmado y stock actualizado exitosamente.');
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error confirmando envío: ' . $e->getMessage());
            return redirect()->to("/envios/show/{$envioId}")
                ->with('error', 'Error al confirmar el envío: ' . $e->getMessage());
        }
    }


    public function devIndex()
    {
       
        $enviosFiltrados = $this->envioModel->where('sucursal_origen_id', 2)->findAll();
        
        $data = [
            'envios' => $enviosFiltrados,
            'title'  => 'Listado de Devoluciones',
            // 'productos' => $this->productosModel->getAllProductosWithRelations(),
            'sucursales' => $this->sucursalModel->findAll(),
        ];

        echo view('envios/devolucionesIndex', $data);
    }



    public function exportarExcelEnvio(int $id)
    {
        $envio = $this->envioModel->find($id);
        if (!$envio) {
            return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
        }

        $sucursalDestino = $this->sucursalModel->find($envio['sucursal_destino_id']);
        $userTransporte = $this->userModel->find($envio['user_transporte_id']);

        try {
            $transferencias = $this->transferenciasModel->where('envio_id', $id)->orderBy('id')->findAll();
            foreach ($transferencias as $t) {
                $producto = $this->productosModel->find($t->producto_id);
                $t->producto_nombre = $producto->nombre ?? '';
                $t->unidad = $producto->unidad ?? '';
            }
        } catch (\Exception $e) {
            log_message('error', 'Error en exportarExcelEnvio: ' . $e->getMessage());
            $transferencias = [];
        }

        $totalImporte = 0;
        foreach ($transferencias as $t) {
            $totalImporte += ($t->cantidad ?? 0) * ($t->precio_contado ?? 0);
        }

        $filename = 'envio_' . ($envio['code'] ?? $id) . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        
        echo '<Styles>';
        echo '<Style ss:ID="header"><Font ss:Bold="1" ss:Size="10"/><Alignment ss:Horizontal="Left" ss:Vertical="Top"/></Style>';
        echo '<Style ss:ID="titulo"><Font ss:Bold="1" ss:Size="16"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="info"><Font ss:Size="10"/><Alignment ss:Horizontal="Left"/></Style>';
        echo '<Style ss:ID="tableHeader"><Font ss:Bold="1" ss:Size="10"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="cell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="cellLeft"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders><Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="number"><NumberFormat ss:Format="#,##0"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="total"><Font ss:Bold="1" ss:Size="11"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/></Style>';
        echo '</Styles>';

        echo '<Worksheet ss:Name="Nota de Entrega">';
        echo '<Table>';
        echo '<Column ss:Width="80"/>';
        echo '<Column ss:Width="80"/>';
        echo '<Column ss:Width="250"/>';
        echo '<Column ss:Width="80"/>';
        echo '<Column ss:Width="80"/>';
        echo '<Column ss:Width="100"/>';

        // Encabezado institucional
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="2" ss:StyleID="header"><Data ss:Type="String">UNIVERSIDAD TÉCNICA DE ORURO</Data></Cell><Cell ss:MergeAcross="2" ss:StyleID="info"><Data ss:Type="String">N° ' . htmlspecialchars($envio['code'] ?? $id) . '</Data></Cell></Row>';
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="2" ss:StyleID="header"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRARIAS Y NATURALES</Data></Cell><Cell ss:MergeAcross="2" ss:StyleID="info"><Data ss:Type="String">ENVÍO DE PRODUCTOS</Data></Cell></Row>';
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="2" ss:StyleID="header"><Data ss:Type="String">CENTRO EXPERIMENTAL AGROPECUARIO CONDORIRI</Data></Cell><Cell ss:MergeAcross="2" ss:StyleID="info"><Data ss:Type="String">ENTREGADO A: ' . htmlspecialchars($userTransporte->usuario ?? '') . '</Data></Cell></Row>';
        echo '<Row ss:Height="15"><Cell ss:MergeAcross="5"></Cell></Row>';
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="2"></Cell><Cell ss:MergeAcross="2" ss:StyleID="info"><Data ss:Type="String">CONDORIRI</Data></Cell></Row>';
        echo '<Row ss:Height="25"><Cell ss:MergeAcross="5" ss:StyleID="titulo"><Data ss:Type="String">NOTA DE ENTREGA</Data></Cell></Row>';
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="2"></Cell><Cell ss:MergeAcross="2" ss:StyleID="info"><Data ss:Type="String">DESTINO: ' . htmlspecialchars($sucursalDestino['nombre'] ?? '') . '</Data></Cell></Row>';
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="2"></Cell><Cell ss:MergeAcross="2" ss:StyleID="info"><Data ss:Type="String">FECHA: ' . date('d/m/Y', strtotime($envio['fecha_envio'] ?? 'now')) . '</Data></Cell></Row>';
        echo '<Row ss:Height="15"><Cell ss:MergeAcross="5"></Cell></Row>';

        // Encabezados de tabla
        echo '<Row>';
        echo '<Cell ss:StyleID="tableHeader"><Data ss:Type="String">CANT.</Data></Cell>';
        echo '<Cell ss:StyleID="tableHeader"><Data ss:Type="String">UNIDAD</Data></Cell>';
        echo '<Cell ss:StyleID="tableHeader"><Data ss:Type="String">DETALLE</Data></Cell>';
        echo '<Cell ss:StyleID="tableHeader"><Data ss:Type="String">s/g Dcto</Data></Cell>';
        echo '<Cell ss:StyleID="tableHeader"><Data ss:Type="String">UNIT.</Data></Cell>';
        echo '<Cell ss:StyleID="tableHeader"><Data ss:Type="String">TOTAL</Data></Cell>';
        echo '</Row>';

        if (empty($transferencias)) {
            echo '<Row><Cell ss:MergeAcross="5" ss:StyleID="cell"><Data ss:Type="String">No hay productos registrados</Data></Cell></Row>';
        } else {
            foreach ($transferencias as $t) {
                $precioUnit = $t->precio_contado ?? 0;
                $cantidad = $t->cantidad ?? 0;
                $total = $cantidad * $precioUnit;
                
                echo '<Row>';
                echo '<Cell ss:StyleID="cell"><Data ss:Type="Number">' . $cantidad . '</Data></Cell>';
                echo '<Cell ss:StyleID="cell"><Data ss:Type="String">' . htmlspecialchars($t->unidad ?? '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="cellLeft"><Data ss:Type="String">' . htmlspecialchars($t->producto_nombre ?? '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="cell"></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $precioUnit . '</Data></Cell>';
                echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . $total . '</Data></Cell>';
                echo '</Row>';
            }
            
            echo '<Row>';
            echo '<Cell ss:MergeAcross="4" ss:StyleID="total"><Data ss:Type="String">TOTAL (Bs)</Data></Cell>';
            echo '<Cell ss:StyleID="total"><Data ss:Type="Number">' . $totalImporte . '</Data></Cell>';
            echo '</Row>';
        }

        echo '<Row ss:Height="15"><Cell ss:MergeAcross="5"></Cell></Row>';
        echo '<Row><Cell ss:MergeAcross="5" ss:StyleID="info"><Data ss:Type="String">OBSERVACIONES</Data></Cell></Row>';

        echo '</Table>';
        echo '</Worksheet>';
        echo '</Workbook>';
        exit;
    }

    public function devShow(int $id)
{
    $envio = $this->envioModel->find($id);
    if (!$envio) {
        return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
    }

    $envio = (object) $envio;

    $sucursalOrigen = $envio->sucursal_origen_id 
        ? (object) $this->sucursalModel->find($envio->sucursal_origen_id) 
        : (object) ['nombre' => '—'];
    
    $sucursalDestino = $envio->sucursal_destino_id 
        ? (object) $this->sucursalModel->find($envio->sucursal_destino_id) 
        : (object) ['nombre' => '—'];

    $userCreador = $envio->user_id 
        ? (object) $this->userModel->find($envio->user_id) 
        : (object) ['usuario' => '—'];

    $userTransporte = $envio->user_transporte_id 
        ? (object) $this->userModel->find($envio->user_transporte_id) 
        : (object) ['usuario' => '—'];

    $userRecepcion = $envio->user_recepcion_id 
        ? (object) $this->userModel->find($envio->user_recepcion_id) 
        : null;

    $estado = $envio->estado_id 
        ? (object) $this->estadoModel->find($envio->estado_id) 
        : (object) ['nombre' => 'Desconocido'];

    // Cargar productos disponibles
    $productos = [];
    if ($envio->sucursal_origen_id) {
        try {
            $builder = $this->stockSucursalModel->builder()
                ->select([
                    'condoriri.stock_sucursales.*',
                    'condoriri.productos.nombre as producto_nombre',
                    'condoriri.categorias.nombre as categoria_nombre',
                    'condoriri.unidades.nombre as unidad_nombre'
                ])
                ->join('condoriri.productos', 'condoriri.productos.id = condoriri.stock_sucursales.producto_id', 'left')
                ->join('condoriri.categorias', 'condoriri.categorias.id = condoriri.productos.categoria_id', 'left')
                ->join('condoriri.unidades', 'condoriri.unidades.id = condoriri.productos.unidad_id', 'left')
                ->where('condoriri.stock_sucursales.sucursal_id', $envio->sucursal_origen_id)
                ->where('condoriri.stock_sucursales.stock >', 0)
                ->orderBy('condoriri.productos.nombre', 'ASC');

            $productosRaw = $builder->get()->getResultArray();
            $productos = array_map(fn($item) => (object) $item, $productosRaw);
        } catch (\Throwable $e) {
            log_message('error', '[devShow] Error cargando productos: ' . $e->getMessage());
            session()->setFlashdata('warning', '⚠️ No se pudieron cargar los productos disponibles.');
        }
    }

    // Cargar historial de transferencias/devoluciones desde transferencias_productos
    $transferencias = [];
    try {
        $db = \Config\Database::connect();
        
        // Primero obtener las transferencias relacionadas con este envío
        $builderTransferencias = $db->table('condoriri.transferencias');
        $transferenciasIds = $builderTransferencias
            ->select('id')
            ->where('envio_id', $id)
            ->get()
            ->getResultArray();
        
        if (!empty($transferenciasIds)) {
            $idsArray = array_column($transferenciasIds, 'id');
            
            // Ahora obtener los productos de esas transferencias
            $builderProductos = $db->table('condoriri.transferencias_productos');
            $transferencias = $builderProductos
                ->select([
                    'condoriri.transferencias_productos.id',
                    'condoriri.transferencias_productos.transferencia_id',
                    'condoriri.transferencias_productos.producto_id',
                    'condoriri.transferencias_productos.cantidad',
                    'condoriri.transferencias_productos.created_at',
                    'condoriri.productos.nombre as producto_nombre',
                    'condoriri.transferencias.estado_id',
                    'condoriri.estados.nombre as estado_nombre'
                ])
                ->join('condoriri.transferencias', 'condoriri.transferencias.id = condoriri.transferencias_productos.transferencia_id', 'left')
                ->join('condoriri.productos', 'condoriri.productos.id = condoriri.transferencias_productos.producto_id', 'left')
                ->join('condoriri.estados', 'condoriri.estados.id = condoriri.transferencias.estado_id', 'left')
                ->whereIn('condoriri.transferencias_productos.transferencia_id', $idsArray)
                ->orderBy('condoriri.transferencias_productos.created_at', 'DESC')
                ->get()
                ->getResultArray();
            
            $transferencias = array_map(fn($item) => (object) $item, $transferencias);
        }
        
        log_message('info', '[devShow] Transferencias cargadas: ' . count($transferencias) . ' para envío ID: ' . $id);
        log_message('debug', '[devShow] Datos transferencias: ' . json_encode($transferencias));
        
    } catch (\Throwable $e) {
        log_message('error', '[devShow] Error cargando devoluciones: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        session()->setFlashdata('warning', '⚠️ No se pudo cargar el historial de devoluciones.');
    }

    $data = [
        'envio'           => $envio,
        'productos'       => $productos,
        'transferencias'  => $transferencias,
        'sucursalOrigen'  => $sucursalOrigen,
        'sucursalDestino' => $sucursalDestino,
        'userTransporte'  => $userTransporte,
        'userCreador'     => $userCreador,
        'userRecepcion'   => $userRecepcion,
        'estado'          => $estado,
        'title'           => 'Detalle de Devolución #' . ($envio->code ?? 'ENV-' . $envio->id),
        'envioID'         => $id,
    ];

    return view('devoluciones/devolucionesShow', $data);
}

}
