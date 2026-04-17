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


    public function storeUnidadRapida()
    {
        $nombre = trim($this->request->getPost('nombre'));
        if (empty($nombre)) {
            return $this->response->setJSON(['success' => false, 'message' => 'El nombre es requerido.']);
        }

        $unidadModel = new \App\Models\Unidad\UnidadModel();
        $id = $unidadModel->insert([
            'nombre'  => strtoupper($nombre),
            'tipo'    => 'agro',
            'estado'  => true,
            'user_id' => session()->get('id'),
        ]);

        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error al guardar la unidad.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'unidad'  => ['id' => $id, 'nombre' => strtoupper($nombre)],
        ]);
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
        $sucursal_id = (int) session()->get('sucursal_id');

        // Últimos 5 productos registrados en esta sucursal para plantillas rápidas
        $recientes = $this->productoModel
            ->where('sucursal_id', $sucursal_id)
            ->where('estado', true)
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->findAll();

        // Si viene del botón Duplicar, pre-cargar ese producto como base
        $desde = (int) $this->request->getGet('from');
        $base  = $desde ? $this->productoModel->find($desde) : null;

        $data = [
            'title'     => 'Nuevo Producto Agropecuario',
            'unidades'  => $this->unidadModel->where('tipo', 'agro')->where('estado', true)->findAll(),
            'recientes' => $recientes,
            'base'      => $base,
        ];

        return view('productosAgro/productosAgroFrom', $data);
    }

    public function store(): RedirectResponse
    {
        $sucursal = (int) session()->get('sucursal_id');
        $userId   = (int) session()->get('id');

        if (!$this->request->is('post')) {
            return redirect()->back()->withInput()->with('error', 'Método de solicitud no permitido.');
        }

        $rules = [
            'producto'       => 'required|min_length[2]|max_length[255]',
            'categoria'      => 'required',
            'unidad_id'      => 'required|is_natural_no_zero',
            'cantidad'       => 'required|is_natural',
            'precio_contado' => 'required|decimal|greater_than[0]',
            'precio_credito' => 'required|decimal|greater_than[0]',
        ];

        $messages = [
            'producto'       => [
                'required'   => 'El nombre del producto es obligatorio.',
                'min_length' => 'El nombre debe tener al menos 2 caracteres.',
            ],
            'categoria'      => ['required' => 'Seleccione una categoría.'],
            'unidad_id'      => [
                'required'            => 'Seleccione una unidad de medida.',
                'is_natural_no_zero'  => 'Seleccione una unidad válida.',
            ],
            'cantidad'       => [
                'required'   => 'La cantidad inicial es obligatoria.',
                'is_natural' => 'La cantidad debe ser un número entero mayor o igual a 0.',
            ],
            'precio_contado' => [
                'required'     => 'El precio de contado es obligatorio.',
                'decimal'      => 'El precio de contado debe ser un número válido.',
                'greater_than' => 'El precio de contado debe ser mayor a 0.',
            ],
            'precio_credito' => [
                'required'     => 'El precio de crédito es obligatorio.',
                'decimal'      => 'El precio de crédito debe ser un número válido.',
                'greater_than' => 'El precio de crédito debe ser mayor a 0.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->productoModel->db->transStart();

        try {
            $hoy           = date('Ymd');
            $codigo_prefijo = 'AGRO-' . $hoy;

            $query = $this->productoModel->db->table($this->productoModel->table)
                ->select("COALESCE(MAX(CAST(SUBSTRING(code FROM '{$codigo_prefijo}-(\\d+)$') AS INTEGER)), 0) AS max_num")
                ->where("code ~ '^{$codigo_prefijo}-\\d+$'")
                ->get()->getRow();

            $siguiente = ($query->max_num ?? 0) + 1;
            $code      = $codigo_prefijo . '-' . str_pad($siguiente, 3, '0', STR_PAD_LEFT);

            $data = [
                'code'          => $code,
                'producto'      => strtoupper(trim($this->request->getPost('producto'))),
                'descripcion'   => trim($this->request->getPost('descripcion')),
                'categoria'     => strtoupper(trim($this->request->getPost('categoria'))),
                'unidad_id'     => (int) $this->request->getPost('unidad_id'),
                'precio_contado'=> (float) $this->request->getPost('precio_contado'),
                'precio_credito'=> (float) $this->request->getPost('precio_credito'),
                'cantidad'      => (int) $this->request->getPost('cantidad'),
                'cantidad_inve' => (int) $this->request->getPost('cantidad'),
                'sucursal_id'   => $sucursal,
                'user_id'       => $userId,
                'estado'        => true,
            ];

            if (!$this->productoModel->save($data)) {
                throw new \Exception('Validation Failed');
            }

            $this->productoModel->db->transComplete();

            return redirect()->to('/productosagro')->with('success', "✅ Producto registrado. Código: <strong>{$code}</strong>");

        } catch (\Throwable $e) {
            $this->productoModel->db->transRollback();
            $msg = $e->getMessage() === 'Validation Failed'
                ? 'Errores de Validación: ' . implode(', ', $this->productoModel->errors())
                : 'Error del Sistema/BD: ' . $e->getMessage();
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
            'title'     => 'Editar Producto',
            'producto'  => $producto,
            'unidades'  => $unidadModel->where('tipo', 'agro')->where('estado', true)->findAll(),
            'sucursales'=> $sucursalModel->findAll(),
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

        $producto = $this->productoModel->find($id);
        if (!$producto) {
            return redirect()->to('/productosagro')->with('error', 'Producto no encontrado.');
        }

        $rules = [
            'producto'       => 'required|min_length[2]|max_length[255]',
            'categoria'      => 'required',
            'unidad_id'      => 'required|is_natural_no_zero',
            'precio_contado' => 'required|decimal|greater_than[0]',
            'precio_credito' => 'required|decimal|greater_than[0]',
        ];

        $messages = [
            'producto'       => [
                'required'   => 'El nombre del producto es obligatorio.',
                'min_length' => 'El nombre debe tener al menos 2 caracteres.',
            ],
            'categoria'      => ['required' => 'Seleccione una categoría.'],
            'unidad_id'      => [
                'required'           => 'Seleccione una unidad de medida.',
                'is_natural_no_zero' => 'Seleccione una unidad válida.',
            ],
            'precio_contado' => [
                'required'     => 'El precio de contado es obligatorio.',
                'decimal'      => 'El precio de contado debe ser un número válido.',
                'greater_than' => 'El precio de contado debe ser mayor a 0.',
            ],
            'precio_credito' => [
                'required'     => 'El precio de crédito es obligatorio.',
                'decimal'      => 'El precio de crédito debe ser un número válido.',
                'greater_than' => 'El precio de crédito debe ser mayor a 0.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'producto'       => strtoupper(trim($this->request->getPost('producto'))),
            'descripcion'    => trim($this->request->getPost('descripcion')),
            'categoria'      => strtoupper(trim($this->request->getPost('categoria'))),
            'unidad_id'      => (int) $this->request->getPost('unidad_id'),
            'precio_contado' => (float) $this->request->getPost('precio_contado'),
            'precio_credito' => (float) $this->request->getPost('precio_credito'),
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
