<?php

namespace App\Controllers\sucursales;

use App\Controllers\BaseController;
use App\Models\StockSucursal\StockSucursalModel;
use App\Models\Sucursal\SucursalModel;
use CodeIgniter\HTTP\RedirectResponse;

class stockSucursalesController extends BaseController
{
    /**
     * @var StockSucursalModel 
     */
    protected $stockSucursalModel;

    /**
     * @var SucursalModel 
     */
    protected $sucursalModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->stockSucursalModel = new StockSucursalModel();
        $this->sucursalModel = new SucursalModel();
    }

    /**
     * Muestra la lista de stock con estadísticas
     */
    public function index()
    {
        // Get filter parameters
        $filters = [
            'fecha_inicio' => $this->request->getGet('fecha_inicio') ?? '',
            'fecha_fin' => $this->request->getGet('fecha_fin') ?? '',
        ];

        // Get stock data with details
        $stockSucursales = $this->stockSucursalModel->getStockWithDetails();

        // Get statistics
        $resumenStock = $this->stockSucursalModel->getResumenStock(
            $filters['fecha_inicio'], 
            $filters['fecha_fin']
        );

        $resumenPorProducto = $this->stockSucursalModel->getResumenPorProducto(
            $filters['fecha_inicio'], 
            $filters['fecha_fin']
        );

        // Get all sucursales for display
        $sucursales = $this->sucursalModel->findAll();

        // Check which tab should be active
        $activeTab = $this->request->getGet('tab') ?? 'stock';

        $data = [
            'stockSucursales' => $stockSucursales,
            'sucursales' => $sucursales,
            'resumenStock' => $resumenStock,
            'resumenPorProducto' => $resumenPorProducto,
            'filters' => $filters,
            'activeTab' => $activeTab,
            'title' => 'Inventario de Sucursales',
        ];

        return view('stockSucursales/stockIndex', $data);
    }

    /**
     * Filtered stock data
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
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';

        // Get user data
        $userId = session()->get('id');
        $userModel = new \App\Models\UsuarioModel();
        $user = $userModel->find($userId);
        
        $nombreUser = $user['nombre'] ?? '';
        $apellidosUser = $user['apellidos'] ?? '';
        $ciUser = $user['ci'] ?? '';
        
        $datosUsuario = trim("$nombreUser $apellidosUser");
        if (!empty($ciUser)) {
            $datosUsuario .= " - CI: $ciUser";
        }
        if (empty($datosUsuario)) {
            $datosUsuario = 'Usuario Desconocido';
        }

        // Get stock data
        $resumenStock = $this->stockSucursalModel->getResumenStock($fecha_inicio, $fecha_fin);
        $resumenPorProducto = $this->stockSucursalModel->getResumenPorProducto($fecha_inicio, $fecha_fin);

        // Check if ReporteStock library exists, if not use simple PDF generation
        if (class_exists('\App\Libraries\ReporteStock')) {
            $pdfGenerator = new \App\Libraries\ReporteStock();
            $pdfGenerator->generarReporte($resumenStock, $resumenPorProducto, [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'usuario' => $datosUsuario
            ]);
        } else {
            // Fallback: Generate simple PDF
            $this->generarReporteSimple($resumenStock, $resumenPorProducto, [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'usuario' => $datosUsuario
            ]);
        }
    }

    /**
     * Simple PDF generation fallback
     */
    private function generarReporteSimple($resumenStock, $resumenPorProducto, $filters)
    {
        // Load TCPDF or FPDF library
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('Condoriri System');
        $pdf->SetAuthor($filters['usuario']);
        $pdf->SetTitle('Reporte de Stock de Sucursales');
        
        $pdf->AddPage();
        
        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Reporte de Stock de Sucursales', 0, 1, 'C');
        
        // Date range
        $pdf->SetFont('helvetica', '', 10);
        if ($filters['fecha_inicio'] && $filters['fecha_fin']) {
            $pdf->Cell(0, 5, 'Período: ' . $filters['fecha_inicio'] . ' - ' . $filters['fecha_fin'], 0, 1, 'C');
        }
        
        $pdf->Ln(5);
        
        // Summary
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Resumen General', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Total Productos: ' . ($resumenStock->total_productos ?? 0), 0, 1);
        $pdf->Cell(0, 5, 'Total Stock: ' . ($resumenStock->total_stock ?? 0), 0, 1);
        $pdf->Cell(0, 5, 'Valor Total (Contado): Bs. ' . number_format($resumenStock->total_valor_contado ?? 0, 2), 0, 1);
        $pdf->Cell(0, 5, 'Valor Total (Crédito): Bs. ' . number_format($resumenStock->total_valor_credito ?? 0, 2), 0, 1);
        
        $pdf->Output('reporte_stock_' . date('Y-m-d') . '.pdf', 'I');
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
