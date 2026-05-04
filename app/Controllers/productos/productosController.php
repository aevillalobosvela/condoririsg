<?php

namespace App\Controllers\productos;

use App\Controllers\BaseController;
use App\Models\Producto\ProductoModel;
use App\Models\Categoria\CategoriaModel;
use App\Models\Unidad\UnidadModel;
use App\Models\Inventario\InventarioModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\API\ResponseTrait;

class ProductosController extends BaseController
{
    use ResponseTrait;

    /**
     * @var ProductoModel
     */
    protected $productoModel;

    /**
     * @var CategoriaModel
     */
    protected $categoriaModel;

    /**
     * @var UnidadModel
     */
    protected $unidadModel;

    /**
     * @var InventarioModel
     */
    protected $inventarioModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->productoModel = new ProductoModel();
        $this->categoriaModel = new CategoriaModel();
        $this->unidadModel = new UnidadModel();
        $this->inventarioModel = new InventarioModel();


        helper(['form', 'url']);
    }

    /**
     * Muestra la lista de todos los productos.
     */
    public function index(): string
    {
        $data = [
            'productos' => $this->productoModel->getAllProductosWithRelations(),
            'title'     => 'Lista de Productos',
        ];
        return view('productos/productosIndex', $data);
    }

    /**
     * Muestra el formulario para crear un nuevo producto.
     *
     * @param int|null $inventarioId ID del inventario opcional.
     */
    public function register($inventarioId = null): string
    {
        // Obtener el inventario específico si existe
        $inventarioSeleccionado = null;
        if ($inventarioId) {
            $inventarioSeleccionado = $this->inventarioModel->find($inventarioId);
        }

        $data = [
            'title'      => 'Crear Nuevo Producto',
            'producto'   => (object)['inventario_id' => $inventarioId],
            'categorias' => $this->categoriaModel->findAll(),
            'unidades'   => $this->unidadModel->where('tipo', 'lacteo')->where('estado', true)->findAll(),
            'inventarios' => $this->inventarioModel->findAll(),
            'inventario_seleccionado' => $inventarioSeleccionado, // Nuevo dato
            'validation' => service('validation'),
            'userId'     => session()->get('id'),
        ];
        return view('productos/productosform', $data);
    }

    /**
     * Procesa la creación de un nuevo producto.
     *
     * @return RedirectResponse
     */
    public function create(): RedirectResponse
    {
        $data = $this->request->getPost();

        // Validar usando las reglas de creación
        $validation = \Config\Services::validation();
        $validation->setRules($this->productoModel->getValidationRulesForCreate());
        
        if (!$validation->run($data)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Convertir campos de texto a MAYÚSCULAS
        $textFields = ['nombre', 'descripcion', 'marca', 'codigo'];
        foreach ($textFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strtoupper(trim($data[$field]));
            }
        }

        // Asegurar que 'stock' sea un entero válido
        $data['stock'] = !empty($data['stock']) ? (int) $data['stock'] : 0;
        $data['stock_inve'] = $data['stock'];
        $data['estado'] = true; // Boolean para PostgreSQL

        // Convertir la fecha de vencimiento a null si está vacía
        if (empty($data['fecha_vencimiento'])) {
            $data['fecha_vencimiento'] = null;
        }

        // Manejar la subida de la imagen
        $imagen = $this->request->getFile('imagen');
        if ($imagen && $imagen->isValid() && !$imagen->hasMoved()) {
            // Validar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($imagen->getMimeType(), $allowedTypes)) {
                return redirect()->back()->withInput()->with('error', 'El archivo debe ser una imagen válida (JPG, PNG, GIF).');
            }
            
            // Validar tamaño (máximo 2MB)
            if ($imagen->getSize() > 2048000) {
                return redirect()->back()->withInput()->with('error', 'La imagen no puede superar los 2MB.');
            }
            
            $newName = $imagen->getRandomName();
            if (!$imagen->move(ROOTPATH . 'public/uploads', $newName)) {
                return redirect()->back()->withInput()->with('error', 'Error al subir la imagen.');
            }
            $data['imagen'] = 'uploads/' . $newName;
        } else {
            $data['imagen'] = 'jpg'; // Imagen por defecto
        }
        
        if ($this->productoModel->insert($data)) {
            $inventarioId = $data['inventario_id'];
            $nuevaReserva = $data['reserva'] ?? 0;

            if (!$this->inventarioModel->update($inventarioId, ['reserva' => $nuevaReserva])) {
                log_message('error', "No se pudo actualizar la reserva del inventario ID {$inventarioId}");
            }

            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('message', 'Producto creado exitosamente.');
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el producto.');
        }
    }
    /**
     * Muestra los detalles de un solo producto.
     */
    /**
     * Muestra los detalles de un solo producto.
     */
    public function show($id = null): string|RedirectResponse
    {
        // Validar que el ID sea numérico
        if (!is_numeric($id) || $id <= 0) {
            return redirect()->to('/productos')->with('error', 'ID de producto inválido.');
        }

        try {
            // Obtener el producto con las relaciones
            $producto = $this->productoModel->getProductoWithRelations1($id);

            // Verificar si el producto existe
            if (!$producto) {
                return redirect()->to('/productos')->with('error', "No se encontró el producto con ID: {$id}");
            }

            $data = [
                'producto' => $producto,
                'title'    => 'Detalles del Producto - ' . $producto->nombre,
            ];

            return view('productos/productosShow', $data);
        } catch (\Exception $e) {
            log_message('error', 'Error en ProductosController::show - ' . $e->getMessage());
            return redirect()->to('/productos')->with('error', 'Error al cargar los detalles del producto.');
        }
    }

    /**
     * Muestra el formulario para editar un producto existente.
     */
    public function edit(int $id): string|RedirectResponse
    {
        $producto = $this->productoModel->find($id);

        if (empty($producto)) {
            return redirect()->to('/productos')->with('error', 'No se encontró el producto.');
        }

        // Obtener el inventario específico si el producto ya tiene uno asignado
        $inventarioSeleccionado = null;
        if (!empty($producto->inventario_id)) {
            $inventarioSeleccionado = $this->inventarioModel->find($producto->inventario_id);
        }

        $data = [
            'producto'   => $producto,
            'title'      => 'Editar Producto',
            'categorias' => $this->categoriaModel->findAll(),
            'unidades'   => $this->unidadModel->where('tipo', 'lacteo')->where('estado', true)->findAll(),
            'inventarios' => $this->inventarioModel->findAll(),
            'inventario_seleccionado' => $inventarioSeleccionado, // Pasar el inventario seleccionado
            'validation' => service('validation'),
            'userId'     => session()->get('id'),
        ];
        return view('productos/productosform', $data);
    }

    public function update(int $id): RedirectResponse
    {
        // Verificar que el producto existe antes de procesar
        $productoExistente = $this->productoModel->find($id);
        if (!$productoExistente) {
            return redirect()->to('/productos')->with('error', 'Producto no encontrado.');
        }

        $data = $this->request->getPost();

        // Campos seguros que se pueden actualizar en productos existentes
        $camposSegurosPorActualizar = [
            'nombre',
            'descripcion', 
            'precio_credito',
            'precio_contado',
            'categoria_id',
            'unidad_id',
            'fecha_vencimiento',
            'porocidad',
            'ph',
            'acides', 
            'consistencia',
            'color',
            'olor',
            'textura',
            'observaciones'
        ];

        // Filtrar solo los campos seguros
        $datosSegurosPorActualizar = [];
        foreach ($camposSegurosPorActualizar as $campo) {
            if (isset($data[$campo])) {
                $datosSegurosPorActualizar[$campo] = $data[$campo];
            }
        }

        // Usar validación específica para actualización
        $validation = \Config\Services::validation();
        $validation->setRules($this->productoModel->getValidationRulesForUpdate());
        
        if (!$validation->run($datosSegurosPorActualizar)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Convertir la fecha de vencimiento a null si está vacía
        if (empty($datosSegurosPorActualizar['fecha_vencimiento'])) {
            $datosSegurosPorActualizar['fecha_vencimiento'] = null;
        }

        // Manejar la actualización de la imagen
        $imagen = $this->request->getFile('imagen');
        if ($imagen && $imagen->isValid() && !$imagen->hasMoved()) {
            // Validar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($imagen->getMimeType(), $allowedTypes)) {
                return redirect()->back()->withInput()->with('error', 'El archivo debe ser una imagen válida (JPG, PNG, GIF).');
            }
            
            // Validar tamaño (máximo 2MB)
            if ($imagen->getSize() > 2048000) {
                return redirect()->back()->withInput()->with('error', 'La imagen no puede superar los 2MB.');
            }
            
            // Eliminar imagen anterior si existe y no es la por defecto
            if (!empty($productoExistente->imagen) && $productoExistente->imagen !== 'jpg' && file_exists(ROOTPATH . 'public/' . $productoExistente->imagen)) {
                @unlink(ROOTPATH . 'public/' . $productoExistente->imagen);
            }
            
            $newName = $imagen->getRandomName();
            if (!$imagen->move(ROOTPATH . 'public/uploads', $newName)) {
                return redirect()->back()->withInput()->with('error', 'Error al subir la imagen.');
            }
            $datosSegurosPorActualizar['imagen'] = 'uploads/' . $newName;
        }
        // Si no se subió nueva imagen, mantener la existente (no agregar al array)

        if ($this->productoModel->update($id, $datosSegurosPorActualizar)) {
            $inventarioId = (int)($productoExistente->inventario_id ?? 0);
            if ($inventarioId) {
                return redirect()->to('/inventarios/show/' . $inventarioId)
                    ->with('message', 'Producto actualizado con éxito.');
            }
            return redirect()->to('/productos')->with('message', 'Producto actualizado con éxito.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->productoModel->errors());
        }
    }

    /**
     * Elimina (soft-delete) el último producto registrado por el usuario hoy
     * en un inventario dado, siempre que no tenga subproductos ni ventas asociadas.
     * Restaura la cantidad_unidad del producto a la reserva del inventario.
     */
    public function deleteUltimo(): RedirectResponse
    {
        $userId    = session()->get('id');
        $productoId = (int)$this->request->getPost('producto_id');

        if (!$productoId) {
            return redirect()->back()->with('error', 'Solicitud inválida.');
        }

        $producto = $this->productoModel->find($productoId);
        if (!$producto) {
            return redirect()->back()->with('error', 'Producto no encontrado.');
        }

        $inventarioId = (int)$producto->inventario_id;

        // Verificar que sea el último producto del usuario en este inventario
        $ultimo = $this->productoModel
            ->where('inventario_id', $inventarioId)
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$ultimo || (int)$ultimo->id !== $productoId) {
            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('error', 'Solo puede eliminar el último producto que usted registró en este inventario.');
        }

        if (date('Y-m-d', strtotime($ultimo->created_at)) !== date('Y-m-d')) {
            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('error', 'Solo puede eliminar productos registrados el día de hoy.');
        }

        // Sin subproductos
        $tieneSubproductos = $this->productoModel
            ->where('parent_id', $productoId)
            ->where('deleted_at', null)
            ->countAllResults() > 0;

        if ($tieneSubproductos) {
            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('error', 'No se puede eliminar: este producto tiene subproductos asociados.');
        }

        // Sin ventas (no existe detalle_venta activo con este producto_id)
        $db = \Config\Database::connect();
        $tieneVentas = $db->query(
            "SELECT 1 FROM condoriri.detalle_venta WHERE producto_id = ? AND deleted_at IS NULL LIMIT 1",
            [$productoId]
        )->getRow() !== null;

        if ($tieneVentas) {
            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('error', 'No se puede eliminar: este producto ya tiene ventas registradas.');
        }

        $db->transBegin();
        try {
            // Restaurar cantidad_unidad a la reserva del inventario
            $cantidadUnidad = (float)($producto->cantidad_unidad ?? 0);
            if ($cantidadUnidad > 0) {
                $inventario = $this->inventarioModel->find($inventarioId);
                if ($inventario) {
                    $nuevaReserva = (float)$inventario->reserva + $cantidadUnidad;
                    $this->inventarioModel->update($inventarioId, ['reserva' => $nuevaReserva]);
                }
            }

            // Soft-delete del producto
            $this->productoModel->delete($productoId);

            $db->transCommit();
            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('message', 'Producto eliminado correctamente.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
    }

    /**
     * Elimina un producto.
     *
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        if ($this->productoModel->update($id, ['estado' => 0])) {

            // El estado 0 indica "Eliminado Lógicamente" o "Inactivo"
            return redirect()->to('/productos')->with('message', 'Producto eliminado (lógicamente) exitosamente.');
        }

        return redirect()->to('/productos')->with('error', 'No se pudo eliminar (inactivar) el producto.');
    }

    /**
     * Registra una merma para un producto específico.
     */
    public function merma(int $id): RedirectResponse
    {
        $producto = $this->productoModel->find($id);
        if (!$producto) {
            return redirect()->back()->with('error', 'Producto no encontrado.');
        }

        $cantidad = (int) $this->request->getPost('cantidad');
        $observacion = trim($this->request->getPost('observacion') ?? '');

        if ($cantidad <= 0) {
            return redirect()->back()->with('error', 'La cantidad de merma debe ser mayor a 0.');
        }

        // Asegurar que stock_inve no sea null
        $stockActual = (int)($producto->stock_inve ?? 0);

        if ($cantidad > $stockActual) {
            return redirect()->back()->with('error', "No se puede registrar merma. Stock insuficiente (disponible: {$stockActual}).");
        }

        $nuevoStock = $stockActual - $cantidad;
        $mermaActual = (int)($producto->merma ?? 0);
        $nuevaMerma = $mermaActual + $cantidad;

        $data = [
            'stock_inve'  => $nuevoStock,
            'merma'       => $nuevaMerma,
            'observacion' => $observacion,
        ];

        // Depuración: ver qué se intenta guardar
        log_message('debug', 'Merma - ID: ' . $id . ', Datos: ' . json_encode($data));

        if (!$this->productoModel->update($id, $data)) {
            // Obtener error real de la base de datos
            $db = \Config\Database::connect();
            $error = $db->error();
            log_message('error', 'Error DB en merma(): ' . print_r($error, true));

            return redirect()->back()->with('error', 'Error al registrar la merma. Verifica los datos e inténtalo nuevamente.');
        }

        return redirect()->back()->with('message', "Merma registrada exitosamente. {$cantidad} unidades eliminadas.");
    }
    public function agregar(int $id): RedirectResponse
    {
        $producto = $this->productoModel->find($id);
        if (!$producto) {
            return redirect()->back()->with('error', 'Producto no encontrado.');
        }

        $cantidad = (int) $this->request->getPost('cantidad');
        $observacion = trim($this->request->getPost('observacion') ?? '');

        if ($cantidad <= 0) {
            return redirect()->back()->with('error', 'La cantidad a agregar debe ser mayor a 0.');
        }

        // Asegurar valores numéricos (evitar NULL o strings)
        $stockActual = (int)($producto->stock_inve ?? 0);
        $agregaActual = (int)($producto->agrega ?? 0);

        $nuevoStock = $stockActual + $cantidad;
        $nuevoAgrega = $agregaActual + $cantidad;

        $data = [
            'stock_inve'  => $nuevoStock,
            'agrega'      => $nuevoAgrega,
            'observacion' => $observacion,
        ];

        // Depuración: registrar lo que se intenta guardar
        log_message('debug', 'Agregar - ID: ' . $id . ', Datos: ' . json_encode($data));

        if (!$this->productoModel->update($id, $data)) {
            // Obtener error real de la base de datos
            $db = \Config\Database::connect();
            $error = $db->error();
            log_message('error', 'Error DB en agregar(): ' . print_r($error, true));

            return redirect()->back()->with('error', 'Error al agregar stock. Verifica los datos e inténtalo nuevamente.');
        }

        return redirect()->back()->with('message', "Stock actualizado exitosamente. {$cantidad} unidades agregadas.");
    }


    public function subproducto(): RedirectResponse
    {
        $data = $this->request->getPost();
        if (empty($data['inventario_id'])) {
            return redirect()->back()->with('error', 'Inventario no especificado.');
        }
        $inventarioId = (int) $data['inventario_id'];
        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $textFields = ['nombre', 'descripcion'];

        foreach ($textFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strtoupper(trim($data[$field]));
            }
        }

        $data['parent_id'] = $parentId;
        $data['inventario_id'] =  $inventarioId;
        // $data['user_id'] = session()->get('user_id');


        $data['stock'] = !empty($data['cantidad_unidad']) ? (int) $data['cantidad_unidad'] : 0;
        $data['stock_inve'] = $data['stock'];


        if ($parentId) {
            $parentProducto = $this->productoModel->find($parentId);
            if ($parentProducto) {
                $nuevoStockInve = (int)($parentProducto->stock_inve ?? 0) - $data['stock'];
                if ($nuevoStockInve < 0) {
                    return redirect()->back()->with('error', 'No hay suficiente stock en el producto padre.');
                }
                $this->productoModel->update($parentId, ['stock_inve' => $nuevoStockInve]);
            } else {
                return redirect()->back()->with('error', 'Producto padre no encontrado.');
            }
        }

        if ($this->productoModel->insert($data)) {
            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('message', 'Subproducto creado exitosamente.');
        }  else {
    // 👇 Reemplaza esto para ver los errores REALES
    $errors = $this->productoModel->errors();
    log_message('error', 'Errores al crear subproducto: ' . print_r($errors, true));
    return redirect()->back()
        ->withInput()
        ->with('error', 'Errores de validación: ' . json_encode($errors, JSON_PRETTY_PRINT));
}
    }

    /**
     * Crea un producto para inventarios de SUERO, deduciendo de la reserva.
     */
    public function createSuero(): RedirectResponse
    {
        $data = $this->request->getPost();
        
        // Validaciones básicas
        if (empty($data['inventario_id'])) {
            return redirect()->back()->with('error', 'ID de inventario no proporcionado.');
        }

        $inventarioId = (int) $data['inventario_id'];
        $cantidadDeducir = (float) ($data['cantidad_unidad'] ?? 0); // Cantidad a restar de reserva
        $stockNuevo = (int) ($data['stock'] ?? 0); // Total Producto (Stock resultante)

        if ($cantidadDeducir <= 0) {
            return redirect()->back()->withInput()->with('error', 'La cantidad por unidad (a deducir) debe ser mayor a 0.');
        }
        
        if ($stockNuevo < 0) {
            return redirect()->back()->withInput()->with('error', 'El total de producto no puede ser negativo.');
        }

        $db = \Config\Database::connect();
        $db->transStart(); // Iniciar transacción

        try {
            // 1. Obtener datos actuales del inventario
            $inventario = $this->inventarioModel->find($inventarioId);

            if (!$inventario) {
                throw new \Exception('Inventario no encontrado.');
            }

            // Validar que sea un inventario de tipo SUERO (Opcional)
            $nombreInve = strtoupper($inventario->nombre);
            if (strpos($nombreInve, 'SUERO LECHE') === false && strpos($nombreInve, 'SUERO QUESERIA') === false) {
                 log_message('warning', "Intento de createSuero en inventario: $nombreInve");
            }

            // 2. Verificar Reserva
            $reservaActual = (float) ($inventario->reserva ?? 0);
            if ($cantidadDeducir > $reservaActual) {
                throw new \Exception("La reserva insuficiente ($reservaActual) para la cantidad solicitada ($cantidadDeducir).");
            }

            // 3. Actualizar Reserva Inventario
            $nuevaReserva = $reservaActual - $cantidadDeducir;
            if (!$this->inventarioModel->update($inventarioId, ['reserva' => $nuevaReserva])) {
                throw new \Exception('Error al actualizar la reserva del inventario.');
            }

            // 4. Preparar datos del Producto
            $productoData = [
                'inventario_id'  => $inventarioId,
                'nombre'         => strtoupper(trim($data['nombre'])),
                'descripcion'    => strtoupper(trim($data['descripcion'] ?? '')),
                'precio_credito' => $data['precio_credito'],
                'precio_contado' => $data['precio_contado'],
                'stock'          => $stockNuevo, // Total Producto
                'stock_inve'     => $stockNuevo, // Se sincroniza inicial
                'categoria_id'   => $data['categoria_id'],
                'unidad_id'      => $data['unidad_id'],
                'user_id'        => session()->get('id'),
                'estado'         => true, // Send boolean for Postgres
                'imagen'         => 'jpg', // Default string
                'fecha_vencimiento' => null
            ];

            // Pasamos cantidad_unidad si el modelo lo permite (aunque no es stock, es dato histórico)
            if (isset($data['cantidad_unidad'])) {
                 $productoData['cantidad_unidad'] = $data['cantidad_unidad'];
            }

            // Relax validation for manual insert
            $this->productoModel->setValidationRule('estado', 'permit_empty');
            $this->productoModel->setValidationRule('imagen', 'permit_empty');

            if (!$this->productoModel->insert($productoData)) {
                 $errores = $this->productoModel->errors();
                 $dbError = $this->productoModel->db->error(); // Capturar error de BD
                 $msg = 'Error al crear el producto: ' . json_encode($errores);
                 if (!empty($dbError['message'])) {
                     $msg .= ' | DB Error: ' . $dbError['message'];
                 }
                 throw new \Exception($msg);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error en la transacción de base de datos.');
            }

            return redirect()->to('/inventarios/show/' . $inventarioId)
                ->with('message', "Producto creado exitosamente. Se dedujeron $cantidadDeducir de la reserva.");

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
