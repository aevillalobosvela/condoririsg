<?php

namespace App\Libraries;

// Cargar la librería FPDF desde la ruta de terceros
require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

/**
 * Generador de Reporte de Cierre de Ventas/Caja (Arqueo).
 */
class CierreVentaContadoPdf extends FPDF
{
    protected $reporteTitle = 'REPORTE DE CIERRE DE VENTAS Y ARQUEO';

    /**
     * Define el encabezado de la página.
     */
    public function Header()
    {
       // Ruta de los logos
        $logo_left = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

        // Agregar logo izquierdo
        if (file_exists($logo_left)) {
            $this->Image($logo_left, 15, 10, 25);
        }

        // Agregar logo derecho
        if (file_exists($logo_right)) {
            $this->Image($logo_right, $this->GetPageWidth() - 40, 10, 25);
        }

        // Título y subtítulos centrados
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 7, utf8_decode("UNIVERSIDAD TECNICA DE ORURO"), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, utf8_decode("CONDORIRI - AGRONOMIA"), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode("Telf.: 5281745 | Interno: 120;  FAX  5242215;  Casilla 49"), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode("Email: dpdi@uto.edu.bo; Internet: www.uto.edu.bo"), 0, 1, 'C');

        // Espacio para el título del reporte específico
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode($this->reporteTitle), 0, 1, 'C');
        $this->Ln(5);
    }

    /**
     * Define el pie de página.
     */
    public function Footer()
    {
        // Posición a 15 mm desde abajo
        $this->SetY(-15);
        // Configurar la fuente
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    /**
     * Genera el reporte de ventas diario (Arqueo).
     *
     * @param array $reportData Datos de ventas con detalles de productos.
     * @param array $filters Filtros usados (fecha_inicio, fecha_fin).
     */
    public function generarReporteVentas(array $reportData, array $filters)
    {
        $this->AddPage('P', 'A4');
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(true, 25);
        $this->AliasNbPages();

        $fecha_inicio = $filters['fecha_inicio'];
        $fecha_fin = $filters['fecha_fin'];

        // --- Resumen de Fechas ---
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 7, utf8_decode('Período del Reporte'), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        $periodo_texto = "Desde: " . date('d/m/Y', strtotime($fecha_inicio));
        if ($fecha_inicio !== $fecha_fin) {
            $periodo_texto .= " | Hasta: " . date('d/m/Y', strtotime($fecha_fin));
        } else {
            $periodo_texto = "Día: " . date('d/m/Y', strtotime($fecha_inicio));
        }
        $this->Cell(0, 6, utf8_decode($periodo_texto), 0, 1);
        $this->Ln(5);

      
        $ventasPorProducto = [];
        $totalVentasFinalizadas = 0.00;
        $processedVentas = []; 

        foreach ($reportData as $item) {
          
            $producto = utf8_decode($item->producto_nombre);
            $cantidad = $item->cantidad;
            $subtotal = $item->subtotal_item;

            if (!isset($ventasPorProducto[$producto]) && $item->tipo_pago == 'contado') {
                $ventasPorProducto[$producto] = [
                    'cantidad_total' => 0,
                    'monto_total' => 0.00
                ];
            }
            $ventasPorProducto[$producto]['cantidad_total'] += $cantidad;
            $ventasPorProducto[$producto]['monto_total'] += $subtotal;
        }
        
        // Reprocesar para asegurar el conteo correcto de pagos y monto total (solo ventas, no detalles)
        $resumenPagos = [];
        foreach ($reportData as $item) {
             if ($item->estado_venta == 1 && !isset($processedVentas[$item->venta_id])&& $item->tipo_pago == 'contado') {
                $totalVentasFinalizadas += $item->monto_total_venta;

                $tipo_pago = utf8_decode(ucfirst($item->tipo_pago));
                if (!isset($resumenPagos[$tipo_pago])) {
                    $resumenPagos[$tipo_pago] = 0.00;
                }
                $resumenPagos[$tipo_pago] += $item->monto_total_venta;
                $processedVentas[$item->venta_id] = true;
            }
        }


        // --- Bloque 1: Resumen de Productos Vendidos (Arqueo de Inventario) ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('1. Resumen de Productos Vendidos'), 0, 1, 'L');
        
        $this->SetFillColor(230, 230, 230);
        $columnWidths = [100, 30, 50]; // Producto, Cantidad, Monto
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($columnWidths[0], 7, utf8_decode('Producto'), 1, 0, 'C', true);
        $this->Cell($columnWidths[1], 7, utf8_decode('Cant.'), 1, 0, 'C', true);
        $this->Cell($columnWidths[2], 7, utf8_decode('Monto (Bs.)'), 1, 1, 'C', true);
        
        $this->SetFont('Arial', '', 10);
        foreach ($ventasPorProducto as $producto => $data) {
            $this->Cell($columnWidths[0], 6, $producto, 1, 0, 'L');
            $this->Cell($columnWidths[1], 6, $data['cantidad_total'], 1, 0, 'C');
            $this->Cell($columnWidths[2], 6, number_format($data['monto_total'], 2), 1, 1, 'R');
        }
        
        $this->Ln(5);

        // --- Bloque 2: Resumen Financiero y de Pagos ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('2. Resumen Financiero (Ventas Finalizadas)'), 0, 1, 'L');
        
        $yPagos = $this->GetY();

        // Resumen de Pagos (Columna Izquierda)
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 7, utf8_decode('Tipo de Pago'), 1, 0, 'C', true);
        $this->Cell(40, 7, utf8_decode('Monto (Bs.)'), 1, 1, 'C', true);
        
        $this->SetFont('Arial', '', 10);
        foreach ($resumenPagos as $tipo => $monto) {
            $this->Cell(80, 6, $tipo, 1, 0, 'L');
            $this->Cell(40, 6, number_format($monto, 2), 1, 1, 'R');
        }
        
        $yEndPagos = $this->GetY();

        // Total General (Columna Derecha)
        $this->SetXY(140, $yPagos);
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(255, 230, 150); // Color resaltado para el total
        $this->Cell(50, 7, utf8_decode('Total de Ingresos'), 1, 1, 'C', true);
        
        $this->SetX(140);
        $this->SetFont('Arial', 'B', 14);
        $this->SetFillColor(255, 255, 200);
        $this->Cell(50, 15, utf8_decode('Bs. ') . number_format($totalVentasFinalizadas, 2), 1, 1, 'C', true);
        
        $this->SetY(max($yEndPagos, $this->GetY() + 5));
        $this->Ln(15);


        // --- Bloque 3: Detalle Completo de Transacciones ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('3. Detalle de Transacciones (Ventas Finalizadas)'), 0, 1, 'L');
        
        // Encabezado de la tabla de detalles
        $colDetails = [25, 60, 30, 30, 45]; 
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        $this->Cell($colDetails[0], 7, utf8_decode('Nro'), 1, 0, 'C', true);
        $this->Cell($colDetails[1], 7, utf8_decode('Cliente'), 1, 0, 'C', true);
        $this->Cell($colDetails[2], 7, utf8_decode('Tipo Pago'), 1, 0, 'C', true);
        $this->Cell($colDetails[3], 7, utf8_decode('Monto (Bs.)'), 1, 0, 'C', true);
        $this->Cell($colDetails[4], 7, utf8_decode('Fecha/Hora'), 1, 1, 'C', true);
        
        $this->SetFont('Arial', '', 9);
        $processedVentas = []; // Resetear para el detalle
        $totalVentasImpresas = 0;
        
        foreach ($reportData as $item) {
          
            if ($item->estado_venta == 1 && !isset($processedVentas[$item->venta_id] ) && $item->tipo_pago == 'contado') {
                $totalVentasImpresas++;
                $this->Cell($colDetails[0], 6, utf8_decode($item->venta_id), 1, 0, 'C');
                $this->Cell($colDetails[1], 6, utf8_decode($item->cliente_nombre), 1, 0, 'L');
                $this->Cell($colDetails[2], 6, utf8_decode(ucfirst($item->tipo_pago)), 1, 0, 'C');
                $this->Cell($colDetails[3], 6, number_format($item->monto_total_venta, 2), 1, 0, 'R');
                $this->Cell($colDetails[4], 6, date('d/m/Y H:i', strtotime($item->fecha_venta)), 1, 1, 'C');
                $processedVentas[$item->venta_id] = true;
            }
        }

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(array_sum($colDetails), 7, utf8_decode('Total Transacciones Listadas: ') . $totalVentasImpresas, 1, 1, 'R');

        // Salida del PDF
        $filename = 'reporte_ventas_' . $fecha_inicio . '_' . $fecha_fin . '.pdf';
        $this->Output('D', $filename);
    }
}
