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
     * Export stock data to Excel
     */
    public function exportarExcelStock()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? '';
        $fecha_fin = $this->request->getGet('fecha_fin') ?? '';

        $resumenStock = $this->stockSucursalModel->getResumenStock($fecha_inicio, $fecha_fin);
        $resumenPorProducto = $this->stockSucursalModel->getResumenPorProducto($fecha_inicio, $fecha_fin);

        $filename = 'inventario_sucursales_' . date('Ymd_His') . '.xls';
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
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="1" ss:StyleID="header"><Data ss:Type="String">REPORTE DE INVENTARIO DE SUCURSALES</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Generado:</Data></Cell><Cell><Data ss:Type="String">' . date('d/m/Y H:i:s') . '</Data></Cell></Row>';
        if ($fecha_inicio && $fecha_fin) {
            echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Inicio:</Data></Cell><Cell><Data ss:Type="String">' . $fecha_inicio . '</Data></Cell></Row>';
            echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Fin:</Data></Cell><Cell><Data ss:Type="String">' . $fecha_fin . '</Data></Cell></Row>';
        }
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">Total Productos</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . ($resumenStock->total_productos ?? 0) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">Total Stock</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . ($resumenStock->total_stock ?? 0) . '</Data></Cell></Row>';
        echo '</Table></Worksheet>';

        echo '<Worksheet ss:Name="Detalle por Producto">';
        echo '<Table>';
        echo '<Column ss:Width="300"/><Column ss:Width="150"/>';
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Producto</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Inventario</Data></Cell>';
        echo '</Row>';

        if (!empty($resumenPorProducto['lista_productos'])) {
            foreach ($resumenPorProducto['lista_productos'] as $item) {
                echo '<Row>';
                echo '<Cell><Data ss:Type="String">' . htmlspecialchars($item->producto ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($item->total_stock ?? 0) . '</Data></Cell>';
                echo '</Row>';
            }
        }
        
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">TOTALES</Data></Cell><Cell ss:StyleID="total"><Data ss:Type="Number">' . ($resumenPorProducto['totales_generales']['total_stock'] ?? 0) . '</Data></Cell></Row>';
        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
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
