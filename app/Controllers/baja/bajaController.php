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

    public function exportarExcel()
    {
        $filters = [
            'fecha_desde' => $this->request->getGet('fecha_desde') ?? '',
            'fecha_hasta' => $this->request->getGet('fecha_hasta') ?? '',
            'producto'    => $this->request->getGet('producto') ?? '',
        ];

        $bajas = $this->bajaModel
            ->select('condoriri.bajas.*, p.nombre as producto_nombre, u.nombre as usuario_nombre, u.apellidos as usuario_apellidos')
            ->join('condoriri.productos p', 'p.id = condoriri.bajas.producto_id', 'left')
            ->join('condoriri.usuarios u', 'u.id = condoriri.bajas.user_id', 'left')
            ->orderBy('condoriri.bajas.created_at', 'DESC');

        if ($filters['fecha_desde']) $bajas = $bajas->where('condoriri.bajas.created_at >=', $filters['fecha_desde']);
        if ($filters['fecha_hasta']) $bajas = $bajas->where('condoriri.bajas.created_at <=', $filters['fecha_hasta'] . ' 23:59:59');
        if ($filters['producto']) $bajas = $bajas->like('p.nombre', $filters['producto']);

        $bajas = $bajas->findAll();
        $totalBajas = count($bajas);
        $totalCantidad = array_sum(array_column($bajas, 'cantidad'));

        $filename = 'bajas_' . date('Ymd_His') . '.xls';
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
        echo '<Row ss:Height="18"><Cell ss:MergeAcross="1" ss:StyleID="subtitulo_uto"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRARIAS Y NATURALES</Data></Cell></Row>';
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">CONDORIRI - LABORATORIO DE INNOVACIÓN</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Email: dpdi@uto.edu.bo | www.uto.edu.bo</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="1" ss:StyleID="header"><Data ss:Type="String">REPORTE DE BAJAS - CONDORIRI</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Generado:</Data></Cell><Cell><Data ss:Type="String">' . date('d/m/Y H:i:s') . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Desde:</Data></Cell><Cell><Data ss:Type="String">' . ($filters['fecha_desde'] ?: 'N/A') . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Hasta:</Data></Cell><Cell><Data ss:Type="String">' . ($filters['fecha_hasta'] ?: 'N/A') . '</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">Total Bajas</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . $totalBajas . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">Cantidad Total</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . $totalCantidad . '</Data></Cell></Row>';
        echo '</Table></Worksheet>';

        echo '<Worksheet ss:Name="Detalle Bajas">';
        echo '<Table>';
        echo '<Column ss:Width="50"/><Column ss:Width="130"/><Column ss:Width="200"/><Column ss:Width="80"/><Column ss:Width="150"/><Column ss:Width="250"/>';
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">ID</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Fecha</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Producto</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cantidad</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Usuario</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Observación</Data></Cell>';
        echo '</Row>';

        foreach ($bajas as $baja) {
            echo '<Row>';
            echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($baja->id ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($baja->created_at)) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($baja->producto_nombre ?? 'N/A', ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($baja->cantidad ?? 0) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars(trim(($baja->usuario_nombre ?? '') . ' ' . ($baja->usuario_apellidos ?? '')), ENT_XML1) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($baja->observacion ?? '', ENT_XML1) . '</Data></Cell>';
            echo '</Row>';
        }
        
        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
    }
}