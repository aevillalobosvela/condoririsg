<?php

namespace App\Controllers\baja;

use App\Controllers\BaseController;
use App\Models\Baja\BajaModel;
use App\Models\Producto\ProductoModel;
use App\Models\UsuarioModel;

class bajaController extends BaseController
{
    protected $bajaModel;
    protected $productoModel;
    protected $usuarioModel;

    public function __construct()
    {
        $this->bajaModel = new BajaModel();
        $this->productoModel = new ProductoModel();
        $this->usuarioModel = new UsuarioModel();
    }

   public function index()
{
    $filters = [
        'producto'   => $this->request->getGet('producto') ?? '',
        'usuario'    => $this->request->getGet('usuario') ?? '',
        'fecha_desde' => $this->request->getGet('fecha_desde') ?? '',
        'fecha_hasta' => $this->request->getGet('fecha_hasta') ?? '',
    ];

    // ✅ Productos con stock > 0
    $productos = $this->productoModel
        ->select('id, nombre, stock_inve')
        ->where('estado', true)
        ->where('stock_inve >', 0)
        ->orderBy('nombre', 'ASC')
        ->findAll();

    // ✅ Bajas con JOINs para producto_nombre y usuario
    $builder = $this->bajaModel
        ->select('
            bajas.*,
            p.nombre as producto_nombre,
            u.nombre as usuario_nombre,
            u.apellidos as usuario_apellidos
        ')
        ->join('condoriri.productos p', 'p.id = bajas.producto_id', 'left')
        ->join('condoriri.usuarios u', 'u.id = bajas.user_id', 'left');

    // Aplicar filtros
    if ($filters['fecha_desde']) {
        $builder->where('bajas.created_at >=', $filters['fecha_desde']);
    }
    if ($filters['fecha_hasta']) {
        $builder->where('bajas.created_at <=', $filters['fecha_hasta'] . ' 23:59:59');
    }

    $bajas = $builder->orderBy('bajas.created_at', 'DESC')->paginate(10, 'bajas');
    $pager = $this->bajaModel->pager;

    $data = [
        'bajas'     => $bajas,
        'pager'     => $pager,
        'productos' => $productos,
        'filters'   => $filters,
        'title'     => 'Gestión de Bajas'
    ];

    return view('baja/index', $data);
}

    public function store()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Método no permitido.'
            ])->setStatusCode(405);
        }

        $productoId = (int) $this->request->getPost('producto_id');
        $cantidad   = (int) $this->request->getPost('cantidad');
        $observacion = trim($this->request->getPost('observacion'));
        $noRestarStock = (bool) $this->request->getPost('no_restar_stock');
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Sesión expirada. Inicie sesión nuevamente.'
            ])->setStatusCode(401);
        }

        $data = [
            'producto_id' => $productoId,
            'cantidad'    => $cantidad,
            'observacion' => $observacion,
            'user_id'     => $userId,
        ];

        if (!$this->bajaModel->validate($data)) {
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $this->bajaModel->errors()
            ])->setStatusCode(400);
        }

        $resultado = $this->bajaModel->registrarConStock(
            $productoId,
            $cantidad,
            $observacion,
            $userId,
            actualizarStock: !$noRestarStock
        );

        if ($resultado['success']) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Baja registrada exitosamente.',
                'redirect' => base_url('bajas')
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => $resultado['error']
        ])->setStatusCode(500);
    }

    public function reportePDF()
    {
        $filters = [
            'fecha_desde' => $this->request->getGet('fecha_desde'),
            'fecha_hasta' => $this->request->getGet('fecha_hasta'),
            'producto'    => $this->request->getGet('producto'),
        ];

        $bajas = $this->bajaModel
            ->select('
                condoriri.bajas.*,
                p.nombre as producto_nombre,
                COALESCE(p.code, \'SIN-COD\') as producto_code,
                u.nombre as usuario_nombre,
                u.apellidos as usuario_apellidos
            ')
            ->join('condoriri.productos p', 'p.id = condoriri.bajas.producto_id', 'left')
            ->join('condoriri.usuarios u', 'u.id = condoriri.bajas.user_id', 'left')
            ->orderBy('condoriri.bajas.created_at', 'DESC');

        if ($filters['fecha_desde']) {
            $bajas = $bajas->where('condoriri.bajas.created_at >=', $filters['fecha_desde']);
        }
        if ($filters['fecha_hasta']) {
            $bajas = $bajas->where('condoriri.bajas.created_at <=', $filters['fecha_hasta'] . ' 23:59:59');
        }
        if ($filters['producto']) {
            $bajas = $bajas->like('p.nombre', $filters['producto']);
        }

        $bajas = $bajas->findAll();

        $user = $this->usuarioModel->find(session()->get('id'));
        $usuario = $user ? trim($user->nombre . ' ' . $user->apellidos) : 'Usuario';

        $pdf = new \App\Libraries\ReporteBajas();
        $pdf->generar($bajas, $filters, $usuario);
    }
}