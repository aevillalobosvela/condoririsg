<?php

namespace App\Controllers\inventarios;

use App\Controllers\BaseController;
use App\Models\Producto\ProductoModel;
use App\Models\Sucursal\SucursalModel;
use CodeIgniter\HTTP\RedirectResponse;

class stockinventarioController extends BaseController
{
    /**
     * @var ProductoModel 
     */
    protected $productoModel;

    /**
     * @var SucursalModel 
     */
    protected $sucursalModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->productoModel = new ProductoModel();
        $this->sucursalModel = new SucursalModel();
    }

    /**
     * Muestra el resumen de stock agrupado por nombre y con opción de filtrar por fechas.
     */
    public function index()
    {
        // Obtener parámetros de filtro
        $filters = [
            'fecha_inicio'       => $this->request->getGet('fecha_inicio') ?? '',
            'fecha_fin'          => $this->request->getGet('fecha_fin') ?? '',
            'sucursal_filter_id' => $this->request->getGet('sucursal_filter_id') ?? '',
        ];

        $fechaInicio = empty($filters['fecha_inicio']) ? null : $filters['fecha_inicio'];
        $fechaFin    = empty($filters['fecha_fin'])    ? null : $filters['fecha_fin'];

        // Obtener stock agrupado (con o sin filtro de fecha)
        if ($fechaInicio && $fechaFin) {
            $stockAgrupado = $this->productoModel->getStockTotalFiltradoPorFecha($fechaInicio, $fechaFin);
        } else {
            $stockAgrupado = $this->productoModel->getStockTotalAgrupadoPorNombre();
        }

        // Resumen general
        $resumenGeneral = $this->productoModel->getResumen($fechaInicio, $fechaFin);

        // Vista completa (incluye productos con stock = 0) o solo disponibles
        $vistaCompleta = $this->request->getGet('vista') === 'completa';
        if (!$vistaCompleta) {
            $stockAgrupado = array_values(array_filter(
                $stockAgrupado,
                fn($i) => ($i->suma_stock_inve ?? 0) > 0
            ));
        }

        $sucursales = $this->sucursalModel->findAll();
        $activeTab  = $this->request->getGet('tab') ?? 'stock';

        $data = [
            'stockAgrupado'      => $stockAgrupado,
            'sucursales'         => $sucursales,
            'resumenGeneral'     => $resumenGeneral,
            'filters'            => $filters,
            'activeTab'          => $activeTab,
            'vistaCompleta'      => $vistaCompleta,
            'title'              => 'Resumen de Inventario (Agrupado por Producto)',
            'stockSucursales'    => [],
            'resumenPorProducto' => [],
        ];

        return view('inventarios/stockIndex', $data);
    }

    /**
     * Filtered stock data - Llama a index para aplicar filtros
     */
    public function filtered()
    {
        return $this->index();
    }

    /**
     * Generate PDF report for stock
     */
    public function reporteStock()
    {
        $fecha_inicio    = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin       = $this->request->getGet('fecha_fin') ?? '';
        $soloDisponibles = $this->request->getGet('solo_disponibles') === '1';

        $userId = session()->get('id');
        $userModel = new \App\Models\UsuarioModel();
        $user = $userModel->find($userId);

        $nombreUser   = $user['nombre'] ?? '';
        $apellidosUser = $user['apellidos'] ?? '';
        $ciUser       = $user['ci'] ?? '';

        $datosUsuario = trim("$nombreUser $apellidosUser");
        if (!empty($ciUser)) {
            $datosUsuario .= " - CI: $ciUser";
        }
        if (empty($datosUsuario)) {
            $datosUsuario = 'Usuario Desconocido';
        }

        $fechaInicio = empty($fecha_inicio) ? null : $fecha_inicio;
        $fechaFin    = empty($fecha_fin)    ? null : $fecha_fin;

        $resumenGeneral = $this->productoModel->getResumen($fechaInicio, $fechaFin);

        if ($fechaInicio && $fechaFin) {
            $stockAgrupado = $this->productoModel->getStockTotalFiltradoPorFecha($fechaInicio, $fechaFin);
        } else {
            $stockAgrupado = $this->productoModel->getStockTotalAgrupadoPorNombre();
        }

        // Filtrar solo disponibles si se solicitó
        if ($soloDisponibles) {
            $stockAgrupado = array_values(array_filter(
                $stockAgrupado,
                fn($i) => ($i->suma_stock_inve ?? 0) > 0
            ));
        }

        $filters = [
            'fecha_inicio'    => $fecha_inicio,
            'fecha_fin'       => $fecha_fin,
            'usuario'         => $datosUsuario,
            'solo_disponibles' => $soloDisponibles,
        ];

        if (class_exists('\App\Libraries\ReporteStockInve')) {
            $pdfGenerator = new \App\Libraries\ReporteStockInve();
            $pdfGenerator->generarReporte($resumenGeneral, $stockAgrupado, $filters);
        } else {
            $this->generarReporteSimple($resumenGeneral, $stockAgrupado, $filters);
        }
    }

    /**
     * Simple PDF generation fallback
     */
private function generarReporteSimple($resumenGeneral, $stockAgrupado, $filters)
{
    try {
        // ✅ USAR TAMAÑO CARTA (LETTER) EN VERTICAL
        $pdf = new \TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
    } catch (\Throwable $th) {
        log_message('error', 'Error al instanciar TCPDF: ' . $th->getMessage());
        return redirect()->back()->with('error', 'No se pudo generar el PDF. Verifica la librería TCPDF.');
    }

    // Configuración básica
    $pdf->SetCreator('Condoriri System');
    $pdf->SetAuthor($filters['usuario'] ?? 'Usuario');
    $pdf->SetTitle('Reporte de Stock Agrupado por Producto');
    $pdf->SetSubject('Reporte de Inventario');
    $pdf->SetKeywords('Stock, Inventario, Reporte, PDF');

    // Margenes
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Añadir página
    $pdf->AddPage();

    // ==== ENCABEZADO ====
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Resumen de Inventario (Agrupado por Producto)', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 10);
    $pdf->Ln(3);
    
    // Usuario
    $pdf->Cell(0, 6, 'Generado por: ' . ($filters['usuario'] ?? '—'), 0, 1, 'L');
    
    // Fechas
    if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
        $periodo = 'Período: ' . date('d/m/Y', strtotime($filters['fecha_inicio'])) . ' — ' . date('d/m/Y', strtotime($filters['fecha_fin']));
    } else {
        $periodo = 'Período: General (todo el tiempo)';
    }
    $pdf->Cell(0, 6, $periodo, 0, 1, 'L');
    
    $pdf->Ln(5);

    // ==== RESUMEN GENERAL (como las tarjetas) ====
    $pdf->SetFillColor(40, 167, 69); // bg-primary ≈ verde éxito
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(90, 8, 'Total de Registros de Lotes', 0, 0, 'C', true);
    $pdf->Cell(90, 8, 'Suma Total de Stock Actual', 0, 1, 'C', true);

    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', 'B', 12);
    $total_registros = $resumenGeneral->total_productos ?? 0;
    $total_stock = $resumenGeneral->total_stock_actual ?? 0;

    $pdf->Cell(90, 10, number_format($total_registros, 0, ',', '.'), 0, 0, 'C');
    $pdf->Cell(90, 10, number_format($total_stock, 0, ',', '.') . ' Unidades', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(108, 117, 125); // text-muted
    $pdf->Cell(90, 6, 'Lotes de productos registrados', 0, 0, 'C');
    $pdf->Cell(90, 6, 'Stock físico de todos los productos', 0, 1, 'C');

    $pdf->Ln(8);

    // ==== TABLA DETALLADA ====
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 7, 'Detalle Agrupado por Producto', 0, 1);
    $pdf->Ln(2);

    // Encabezados
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(248, 249, 250); // table-light ≈ very light gray
    $pdf->SetTextColor(52, 58, 64);
    $pdf->Cell(90, 7, 'Producto', 1, 0, 'L', true);
    $pdf->Cell(45, 7, 'Cant. Lotes', 1, 0, 'C', true);
    $pdf->Cell(45, 7, 'Stock Total', 1, 1, 'C', true);

    // Contenido
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0);

    if (empty($stockAgrupado)) {
        $pdf->Cell(180, 7, 'No hay stock agrupado disponible para el período.', 1, 1, 'C');
    } else {
        foreach ($stockAgrupado as $item) {
            $nombre = $this->limitText($item->nombre ?? '', 40);
            $cantRegistros = $item->cantidad_registros ?? 0;
            $stockTotal = $item->suma_stock_inve ?? 0;

            // Color según stock
            if ($stockTotal > 0) {
                $pdf->SetFillColor(220, 240, 220); // verde claro
                $pdf->SetTextColor(27, 80, 40);
            } else {
                $pdf->SetFillColor(250, 230, 230); // rojo claro
                $pdf->SetTextColor(114, 28, 36);
            }
            $pdf->Cell(90, 7, $nombre, 1, 0, 'L', true);
            $pdf->Cell(45, 7, number_format($cantRegistros, 0, ',', '.'), 1, 0, 'C', true);
            $pdf->Cell(45, 7, number_format($stockTotal, 0, ',', '.'), 1, 1, 'C', true);

            // Reset color para siguiente fila
            $pdf->SetTextColor(0);
            $pdf->SetFillColor(255);
        }
    }

    // ==== PIE DE PÁGINA ====
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->SetTextColor(150);
    $pdf->Cell(0, 6, 'Reporte generado el ' . date('d/m/Y \a \l\a\s H:i:s') . ' — Sistema Condoriri', 0, 1, 'C');

    // Salida
    $filename = 'reporte_stock_agrupado_' . date('Y-m-d_H-i') . '.pdf';
    $pdf->Output($filename, 'I'); // 'I' = inline browser, 'D' = download
}

    /**
     * Show sucursal details
     */
    public function show(int $id)
    {
        $sucursal = $this->sucursalModel->find($id);

        if (!$sucursal) {
            return redirect()->to('/inventariosucursales')->with('error', 'Sucursal no encontrada.');
        }

        $data = [
            'sucursal' => $sucursal,
            'title' => 'Detalle de Sucursal: ' . esc($sucursal['nombre']),
        ];
        
        return view('stockSucursales/show', $data);
    }
}