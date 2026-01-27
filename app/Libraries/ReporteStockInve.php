<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class ReporteStockInve extends FPDF
{
    protected $reporteTitle = 'REPORTE DE INVENTARIO LACTEOS';

    public function setReporteTitle(string $title)
    {
        $this->reporteTitle = $title;
    }

    public function Header()
    {
        // Logos
        $logo_left = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

        // Altura del encabezado: 30 mm
        $this->SetY(10);

        // Logo izquierdo
        if (file_exists($logo_left)) {
            $this->Image($logo_left, 15, 10, 22);
        }

        // Logo derecho
        if (file_exists($logo_right)) {
            $this->Image($logo_right, $this->GetPageWidth() - 37, 10, 22);
        }

        // Títulos institucionales centrados
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 6, utf8_decode("UNIVERSIDAD TÉCNICA DE ORURO"), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 5, utf8_decode("FACULTAD DE CIENCIAS AGRONÓMICAS Y MEDIO AMBIENTE"), 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, utf8_decode("CONDORIRI - LABORATORIO DE INNOVACIÓN"), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode("Telf.: 5281745 – Interno: 120 | FAX: 5242215 | Casilla 49"), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode("Email: dpdi@uto.edu.bo | www.uto.edu.bo"), 0, 1, 'C');

        // Línea divisoria
        $this->Ln(4);
        $this->SetDrawColor(0, 0, 0);
        $this->Line(15, $this->GetY(), $this->GetPageWidth() - 15, $this->GetY());
        $this->Ln(6);

        // Título del reporte
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, utf8_decode($this->reporteTitle), 0, 1, 'C');
        $this->Ln(4);
    }

    public function Footer()
    {
        $this->SetY(-18);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100);
        $this->Cell(0, 4, utf8_decode('Generado el: ' . date('d/m/Y H:i:s')), 0, 1, 'L');
        $this->Cell(0, 4, utf8_decode('Sistema de Gestión de Ventas CEAC-UTO'), 0, 1, 'L');
        $this->Ln(2);
        $this->SetTextColor(0);
        $this->Cell(0, 6, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

 public function generarReporte($resumenGeneral, $stockAgrupado, array $filters)
{
    $fecha_inicio = $filters['fecha_inicio'] ?? '';
    $fecha_fin = $filters['fecha_fin'] ?? '';
    $usuario = $filters['usuario'] ?? 'Usuario Desconocido';

    // ✅ TÍTULO ACTUALIZADO
    $this->setReporteTitle('Resumen de Inventario (Agrupado por Producto)');

    // ✅ TAMAÑO CARTA (8.5 x 11 pulgadas = 215.9 x 279.4 mm) en VERTICAL
    $this->AddPage('P', [215.9, 279.4]);
    $this->SetMargins(15, 15, 15);
    $this->SetAutoPageBreak(true, 25);
    $this->AliasNbPages();

    // --- Información del Reporte ---
    $this->SetFont('Arial', 'B', 11);
    $this->Cell(0, 6, utf8_decode('Información del Reporte'), 0, 1, 'L');
    $this->SetFont('Arial', '', 10);

    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $periodo = "Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
    } else {
        $periodo = "Período: General (todo el tiempo)";
    }

    $this->Cell(0, 5, utf8_decode("Período: $periodo"), 0, 1);
    $this->Cell(0, 5, utf8_decode("Generado por: $usuario"), 0, 1);
    $this->Ln(8);

    // --- Resumen General (2 bloques como en la vista) ---
    $this->seccionTitulo('Resumen General');

    $totalRegistros = $resumenGeneral->total_productos ?? 0;
    $totalStock = $resumenGeneral->total_stock_actual ?? 0;

    // Usamos una tabla de 2 columnas para simular las tarjetas
    $this->SetFont('Arial', 'B', 10);
    $this->SetFillColor(220, 240, 220); // verde claro
    $this->Cell(95, 7, utf8_decode('Total de Registros de Lotes'), 1, 0, 'C', true);
    $this->Cell(95, 7, utf8_decode('Suma Total de Stock Actual'), 1, 1, 'C', true);

    $this->SetFont('Arial', 'B', 12);
    $this->SetFillColor(255);
    $this->Cell(95, 8, number_format($totalRegistros, 0, ',', '.'), 1, 0, 'C');
    $this->Cell(95, 8, number_format($totalStock, 0, ',', '.') . ' Unidades', 1, 1, 'C');

    $this->SetFont('Arial', '', 9);
    $this->SetTextColor(100);
    $this->Cell(95, 5, utf8_decode('Lotes de productos registrados'), 1, 0, 'C');
    $this->Cell(95, 5, utf8_decode('Stock físico de todos los productos'), 1, 1, 'C');
    $this->SetTextColor(0);

    $this->Ln(10);

    // --- Tabla Detallada (igual que la vista) ---
    $this->seccionTitulo('Detalle Agrupado por Producto');

    // Anchos: Producto (100), Cant. Lotes (45), Stock (45) → total 190 dentro de 185 útil (215.9 - 30)
    $w = [100, 45, 45];

    $this->SetFont('Arial', 'B', 9);
    $this->SetFillColor(240, 240, 240);
    $this->Cell($w[0], 7, utf8_decode('Producto'), 1, 0, 'C', true);
    $this->Cell($w[1], 7, utf8_decode('Cant. Lotes'), 1, 0, 'C', true);
    $this->Cell($w[2], 7, utf8_decode('Stock Total'), 1, 1, 'C', true);

    $this->SetFont('Arial', '', 9);

    if (empty($stockAgrupado)) {
        $this->Cell(190, 8, utf8_decode('No hay stock agrupado disponible para el período.'), 1, 1, 'C');
    } else {
        foreach ($stockAgrupado as $item) {
            $nombre = substr($item->nombre ?? '', 0, 50);
            $cantRegistros = $item->cantidad_registros ?? 0;
            $stockTotal = $item->suma_stock_inve ?? 0;

            // ✅ Color según stock (como en la vista con badge)
            if ($stockTotal > 0) {
                $this->SetFillColor(223, 240, 216); // bg-success
                $this->SetTextColor(46, 103, 44);   // verde oscuro
            } else {
                $this->SetFillColor(242, 222, 222); // bg-danger
                $this->SetTextColor(169, 68, 66);   // rojo oscuro
            }

            $this->Cell($w[0], 8, utf8_decode($nombre), 1, 0, 'L', true);
            $this->Cell($w[1], 8, number_format($cantRegistros, 0, ',', '.'), 1, 0, 'C', true);
            $this->Cell($w[2], 8, number_format($stockTotal, 0, ',', '.'), 1, 1, 'C', true);

            // Reset
            $this->SetTextColor(0);
            $this->SetFillColor(255);
        }
    }

    // --- Pie final y salida ---
    $this->Ln(10);
    $this->SetFont('Arial', 'I', 8);
    $this->SetTextColor(120);
    $this->Cell(0, 6, utf8_decode('Reporte generado el ' . date('d/m/Y \a \l\a\s H:i:s')), 0, 1, 'C');
    $this->SetTextColor(0);

    $filename = 'reporte_stock_agrupado_' . date('Ymd_His') . '.pdf';
    $this->Output('D', $filename); // 'D' = descarga directa, 'I' = inline
}

    private function seccionTitulo(string $texto)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetDrawColor(0, 100, 200);
        $this->SetFillColor(240, 245, 255);
        $this->Cell(0, 8, utf8_decode($texto), 0, 1, 'L', true);
        $this->Ln(2);
    }
}
