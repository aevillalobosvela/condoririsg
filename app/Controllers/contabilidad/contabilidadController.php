<?php



namespace App\Controllers\contabilidad;

use App\Controllers\BaseController;
use App\Libraries\CierreVentaAdmin;
use App\Libraries\CierreVentaPdf;
use App\Models\Sucursal\SucursalModel;
use App\Models\Venta\VentaModel;
use CodeIgniter\HTTP\RedirectResponse;

class contabilidadController extends BaseController

{
    /**
     * @var SucursalModel 
     */
    protected $sucursalModel;
    protected $ventaModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->sucursalModel = new SucursalModel();
        $this->ventaModel = new VentaModel();
    }

    /**
     * Muestra la lista de todas las unidades (operación READ - todas).
     *
     * @return string
     */
    public function index()
    {
        $data = [
            'sucursales' => $this->sucursalModel->findAll(),
            'title'    => 'Listado de sucursales',
        ];


        // echo view('sucursales/sucursalesIndex', $data);
        echo view('panel/contabilidad', $data);
    }


    public function show(int $id)
    {
        $sucursal = $this->sucursalModel->find($id);

        if (!$sucursal) {
            return redirect()->to('/sucursales')->with('error', 'Sucursal no encontrada.');
        }

        $data = [
            'sucursal' => $sucursal,
            'title'    => 'Detalle de Sucursal: ' . esc($sucursal['nombre']),
        ];
        echo view('contabilidad/lacteosIndex', $data);
    }



    public function grafico()
    {

        $fecha_inicio = $this->request->getGet('fecha_inicio') ?: date('Y-m-d');
        $fecha_fin    = $this->request->getGet('fecha_fin') ?: $fecha_inicio;
        $sucursal_id  = (int) $this->request->getGet('sucursal_id');


        if ($sucursal_id <= 0) {
            throw new \InvalidArgumentException('ID de sucursal requerido');
        }


        if ($sucursal_id == 5) {

            $data = $this->ventaModel->getSalesDataForGraphAdmin(
                $fecha_inicio,
                $fecha_fin,
                $sucursal_id
            );
        } else {

            $data = $this->ventaModel->getSalesDataForGraph(
                $fecha_inicio,
                $fecha_fin,
                $sucursal_id
            );
        }

        return $this->response->setJSON($data);
    }


    public function exportarPdfVentas()
    {
        $sucursal_id  = (int) $this->request->getGet('sucursal_id');
        $fecha_inicio = $this->request->getGet('fecha_inicio');
        $fecha_fin    = $this->request->getGet('fecha_fin');
        $tipo         = $this->request->getGet('tipo'); 

        $hoy = date('Y-m-d');
        $fecha_inicio = $fecha_inicio ?: $hoy;
        $fecha_fin    = $fecha_fin ?: $hoy;

        if (!in_array($tipo, ['contado', 'credito', 'general'])) {
            $tipo = 'general';
        }
        if ($sucursal_id <= 0) {
            throw new \InvalidArgumentException('ID de sucursal requerido');
        }

        if ($sucursal_id === 5) {
            $reportData = $this->ventaModel->getDailySalesReportDataAdmin(
                $fecha_inicio,
                $fecha_fin,
                $sucursal_id,
                $tipo
            );
        } elseif ($sucursal_id === 10 || $sucursal_id === 11) {
            $reportData = $this->ventaModel->getDailySalesReportData2(
                $fecha_inicio,
                $fecha_fin,
                $sucursal_id,
                $tipo
            );
        } elseif ($sucursal_id === 2) {
            $reportData = $this->ventaModel->getDailySalesReportDataVenta(
                $fecha_inicio,
                $fecha_fin, 
                $sucursal_id,
                $tipo
            );
        } elseif ($sucursal_id === 4) {
            $reportData = $this->ventaModel->getDailySalesReportData3(
                $fecha_inicio,
                $fecha_fin,
                $sucursal_id,
                $tipo
            );
        } else {
            // Para otras sucursales, usar método genérico
            $reportData = $this->ventaModel->getDailySalesReportDataVenta(
                $fecha_inicio,
                $fecha_fin,
                $sucursal_id,
                $tipo
            );
        }

        $sucursal = $this->sucursalModel->find($sucursal_id);

        if ($sucursal_id === 5) {
            $pdfGenerator = new CierreVentaAdmin();
            $pdfGenerator->generarReporteVentas($reportData, [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin'    => $fecha_fin,
                'tipo'         => $tipo,
                'sucursal_nombre' => $sucursal ? $sucursal['nombre'] : 'Desconocida',
            ]);
        } else {
            $pdfGenerator = new CierreVentaPdf();
            $pdfGenerator->generarReporteVentas($reportData, [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin'    => $fecha_fin,
                'tipo'         => $tipo,
                'sucursal_nombre' => $sucursal ? $sucursal['nombre'] : 'Desconocida',
            ]);
        }
    }














      public function indexLactos()
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
    public function exportarExcelVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? date('Y-m-d');
        $fecha_fin = $this->request->getGet('fecha_fin') ?? date('Y-m-d');
        $tipo = $this->request->getGet('tipo') ?? 'general';
        $sucursal_id = (int)$this->request->getGet('sucursal_id');

        if ($sucursal_id <= 0) {
            throw new \InvalidArgumentException('ID de sucursal requerido');
        }

        $db = \Config\Database::connect();
        $inicio = $fecha_inicio . ' 00:00:00';
        $fin = $fecha_fin . ' 23:59:59';

        $sql = "SELECT v.*, COALESCE(c.nombre_completo, 'Consumidor Final') AS cliente_nombre, p.nombre AS nombre_personal, p.dip
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

        $sucursal = $this->sucursalModel->find($sucursal_id);
        $sucursalNombre = $sucursal ? $sucursal['nombre'] : 'Desconocida';

        $filename = 'ventas_' . $sucursalNombre . '_' . $tipo . '_' . date('Ymd_His') . '.xls';
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
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">CONDORIRI - LABORATORIO DE INNOVACIÓN</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Telf.: 5281745 – Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Email: dpdi@uto.edu.bo | www.uto.edu.bo</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="1" ss:StyleID="header"><Data ss:Type="String">REPORTE DE VENTAS - ' . strtoupper($sucursalNombre) . ' - ' . strtoupper($tipo) . '</Data></Cell></Row>';
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