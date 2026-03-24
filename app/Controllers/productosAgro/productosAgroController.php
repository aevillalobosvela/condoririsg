<?php

namespace App\Controllers\productosAgro;

use App\Controllers\BaseController;
use App\Models\ProductoAgro\ProductoAgroModel;
use App\Models\Unidad\UnidadModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RedirectResponse;

class ProductosAgroController extends BaseController
{
    use ResponseTrait;

    protected $productoModel;
    protected $unidadModel;

    public function __construct()
    {
        $this->productoModel = new ProductoAgroModel();
        $this->unidadModel = new UnidadModel();
    }


    public function index()
    {

        $sucursal_id = (int) session()->get('sucursal_id');
        $builder = $this->productoModel->select('productos_agro.*, u.nombre as unidad_nombre, s.nombre as sucursal_nombre')
            ->join('condoriri.unidades u', 'u.id = productos_agro.unidad_id', 'left')
            ->join('condoriri.sucursales s', 's.id = productos_agro.sucursal_id', 'left')
            ->where('productos_agro.fecha_delete', null)
            ->where('productos_agro.estado', true);

        if ($sucursal_id) {
            $builder->where('productos_agro.sucursal_id', $sucursal_id);
        }

        $data['productos'] = $builder->orderBy('productos_agro.id', 'DESC')->findAll();
        $data['title'] = 'Productos Agropecuarios';

        return view('productosAgro/productosAgroIndex', $data);
    }


    public function create()
    {



        $data = [
            'title' => 'Nuevo Producto Agropecuario',
            'unidades' => $this->unidadModel->findAll(),
        ];

        return view('productosAgro/productosAgroFrom', $data);
    }

    public function store(): RedirectResponse
    {

        $sucursal = (int) session()->get('sucursal_id');
        $userId = (int) session()->get('id');
        // 1. Verificación del método de solicitud
        if (!$this->request->is('post')) {
            return redirect()->back()->withInput()->with('error', 'Método de solicitud no permitido.');
        }

        // 2. Iniciar la Transacción para asegurar la atomicidad (generación de código + inserción)
        $this->productoModel->db->transStart();

        try {
            // Generar código único AGRO-YYYYMMDD-NNN (Asumiendo PostgreSQL por el uso de SUBSTRING/regex)
            $hoy = date('Ymd');
            $codigo_prefijo = 'AGRO-' . $hoy;

            // Usamos el builder del modelo para ejecutar la consulta de manera limpia.
            // La consulta busca el número secuencial más alto del día.
            $query = $this->productoModel->db->table($this->productoModel->table)
                // Utilizamos una expresión regular específica de PostgreSQL para extraer el número secuencial.
                ->select("COALESCE(MAX(CAST(SUBSTRING(code FROM '{$codigo_prefijo}-(\\d+)$') AS INTEGER)), 0) AS max_num")
                // Filtramos solo por códigos que sigan el patrón del día actual
                ->where("code ~ '^{$codigo_prefijo}-\\d+$'")
                ->get()->getRow(); // Obtenemos el resultado como objeto

            $siguiente = ($query->max_num ?? 0) + 1;
            $code = $codigo_prefijo . '-' . str_pad($siguiente, 3, '0', STR_PAD_LEFT);

            // 3. Preparar y sanear datos
            $data = [
                'code' => $code,
                'producto' => strtoupper(trim($this->request->getPost('producto'))),
                'descripcion' => trim($this->request->getPost('descripcion')),
                'categoria' => strtoupper(trim($this->request->getPost('categoria'))),

                // Conversión explícita de tipos y uso de coalescing para seguridad
                'unidad_id' => (int) $this->request->getPost('unidad_id'),
                'precio_contado' => (float) $this->request->getPost('precio_contado'),
                'precio_credito' => (float) $this->request->getPost('precio_credito'),
                'cantidad' => (int) ($this->request->getPost('cantidad') ?? 0),
                'cantidad_inve' => (int) ($this->request->getPost('cantidad') ?? 0),

                // Obtener datos de sesión con valor predeterminado seguro
                'sucursal_id' => $sucursal,
                'user_id' => $userId,
                'estado' => true,
            ];

            // 4. Usar ORM: save() inserta + valida + maneja timestamps
            if (!$this->productoModel->save($data)) {
                // Si save() devuelve false, es un error de VALIDACIÓN
                // Forzamos un throw para que caiga en el catch y haga el rollback
                throw new \Exception('Validation Failed');
            }

            // 5. Si todo fue exitoso, completar la transacción (commit)
            $this->productoModel->db->transComplete();

            return redirect()->to('/productosagro')->with('success', "✅ Producto registrado. Código: <strong>{$code}</strong>");
        } catch (\Throwable $e) {
            // Rollback de la transacción en caso de cualquier error
            $this->productoModel->db->transRollback();

            // Manejar errores de validación (si save falló)
            if ($e->getMessage() === 'Validation Failed') {
                $errors = $this->productoModel->errors();
                $msg = 'Errores de Validación: ' . implode(', ', $errors);
            } else {
                // Manejar otros errores (BD, sintaxis, etc.)
                $msg = 'Error del Sistema/BD: ' . $e->getMessage();
            }

            // Redireccionar con los datos de entrada para que el usuario no pierda lo escrito
            return redirect()->back()->withInput()->with('error', $msg);
        }
    }


    public function edit($id = null)
    {
        $producto = $this->productoModel->find($id);
        if (!$producto) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $unidadModel = model('App\Models\Unidad\UnidadModel');
        $sucursalModel = model('App\Models\Sucursal\SucursalModel');

        $data = [
            'title' => 'Editar Producto',
            'producto' => $producto,
            'unidades' => $unidadModel->findAll(),
            'sucursales' => $sucursalModel->findAll(),
        ];

        return view('productosAgro/productosAgroFrom', $data);
    }


    public function update($id = null)
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método no permitido.');
        }

        if (!$id) {
            return redirect()->back()->with('error', 'ID de producto no especificado.');
        }

        // ✅ Obtener producto existente (para preservar campos no editables)
        $producto = $this->productoModel->find($id);
        if (!$producto) {
            return redirect()->to('/productosagro')->with('error', 'Producto no encontrado.');
        }

        $data = [
            'producto'       => strtoupper(trim($this->request->getPost('producto'))),
            'descripcion'    => trim($this->request->getPost('descripcion')),
            'categoria'      => strtoupper(trim($this->request->getPost('categoria'))),
            'unidad_id'      => (int) $this->request->getPost('unidad_id'),
            'precio_contado' => (float) $this->request->getPost('precio_contado'),
            'precio_credito' => (float) $this->request->getPost('precio_credito'),
            'cantidad'       => (int) $this->request->getPost('cantidad'),
            'cantidad_inve'  => (int) $producto->cantidad_inve,
            'sucursal_id'    => (int) $producto->sucursal_id,
            'user_id'        => (int) $producto->user_id,
            'estado'         => true,
        ];

        if ($this->productoModel->skipValidation(true)->update($id, $data)) {
            return redirect()->to('/productosagro')->with('success', "✅ Producto actualizado: {$producto->code}");
        }

        log_message('error', "[update] ID={$id} → " . json_encode($this->productoModel->errors()));
        return redirect()->back()->withInput()->with('error', '❌ Error al actualizar el producto.');
    }


    public function delete1($id = null)
    {
        $producto = $this->productoModel->find($id);
        if (!$producto) {
            return $this->failNotFound('Producto no encontrado.');
        }

        if ($this->productoModel->delete($id)) {
            return $this->respond(['message' => 'Producto eliminado correctamente.'], 200);
        }

        return $this->failServerError('No se pudo eliminar el producto.');
    }


    public function delete($id = null)
    {
        $producto = $this->productoModel->find($id);
        if (!$producto) {
            return $this->failNotFound();
        }

        $nuevoEstado = !$producto->estado;
        if ($this->productoModel->update($id, ['estado' => $nuevoEstado])) {
            // return $this->respond([
            //     'success' => true,
            //     'estado' => $nuevoEstado,
            //     'mensaje' => $nuevoEstado ? 'Activado' : 'Desactivado'
            // ]);
            return redirect()->to('/productosagro')->with('success', "✅ Producto elminado correctamente.");
        }

        return $this->failServerError();
    }
}
