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
        $this->Cell(0, 5, utf8_decode("FACULTAD DE CIENCIAS AGRARIAS Y NATURALES"), 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, utf8_decode("CONDORIRI - LABORATORIO DE INNOVACIÓN"), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode("Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49"), 0, 1, 'C');
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
    $fecha_inicio    = $filters['fecha_inicio'] ?? '';
    $fecha_fin       = $filters['fecha_fin'] ?? '';
    $usuario         = $filters['usuario'] ?? 'Usuario Desconocido';
    $soloDisponibles = $filters['solo_disponibles'] ?? false;

    $titulo = $soloDisponibles
        ? 'Inventario Disponible (Productos con Stock)'
        : 'Resumen de Inventario (Agrupado por Producto)';

    $this->setReporteTitle($titulo);

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
    if ($soloDisponibles) {
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(22, 101, 52);
        $this->Cell(0, 5, utf8_decode("Filtro: solo productos con stock disponible (stock > 0)"), 0, 1);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);
    }
    $this->Ln(6);

    // --- Resumen General ---
    $this->seccionTitulo('Resumen General');

    $totalRegistros = $resumenGeneral->total_productos ?? 0;
    $totalStock     = $resumenGeneral->total_stock_actual ?? 0;

    $this->SetFont('Arial', 'B', 10);
    $this->SetFillColor(220, 240, 220);
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

    $this->Ln(8);

    // --- Tabla Detallada ---
    $this->seccionTitulo('Detalle Agrupado por Producto');

    // Anchos: Producto(70) Precio(22) TotalLotes(22) LotesActivos(22) StockTotal(22) → 158mm
    $w = [70, 22, 22, 22, 22];

    $this->SetFont('Arial', 'B', 8);
    $this->SetFillColor(240, 240, 240);
    $this->Cell($w[0], 7, utf8_decode('Producto'),      1, 0, 'C', true);
    $this->Cell($w[1], 7, utf8_decode('Precio'),        1, 0, 'C', true);
    $this->Cell($w[2], 7, utf8_decode('Total lotes'),   1, 0, 'C', true);
    $this->Cell($w[3], 7, utf8_decode('Lotes activos'), 1, 0, 'C', true);
    $this->Cell($w[4], 7, utf8_decode('Stock total'),   1, 1, 'C', true);

    $this->SetFont('Arial', '', 9);

    if (empty($stockAgrupado)) {
        $this->Cell(158, 8, utf8_decode('No hay stock disponible para el período.'), 1, 1, 'C');
    } else {
        foreach ($stockAgrupado as $item) {
            $nombre       = substr($item->nombre ?? '', 0, 45);
            $precio       = 'Bs ' . number_format((float)($item->precio_contado ?? 0), 2, '.', '');
            $cantLotes    = $item->cantidad_registros ?? 0;
            $lotesActivos = $item->lotes_con_stock ?? 0;
            $stockTotal   = (int)($item->suma_stock_inve ?? 0);

            if ($stockTotal > 20) {
                $this->SetFillColor(209, 250, 229); // verde suave
                $this->SetTextColor(6, 95, 70);
            } elseif ($stockTotal > 0) {
                $this->SetFillColor(254, 243, 199); // amarillo suave
                $this->SetTextColor(146, 64, 14);
            } else {
                $this->SetFillColor(254, 226, 226); // rojo suave
                $this->SetTextColor(153, 27, 27);
            }

            $this->Cell($w[0], 8, utf8_decode($nombre),                          1, 0, 'L', true);
            $this->Cell($w[1], 8, utf8_decode($precio),                           1, 0, 'C', true);
            $this->Cell($w[2], 8, number_format($cantLotes, 0, ',', '.'),         1, 0, 'C', true);
            $this->Cell($w[3], 8, number_format($lotesActivos, 0, ',', '.'),      1, 0, 'C', true);
            $this->Cell($w[4], 8, number_format($stockTotal, 0, ',', '.'),        1, 1, 'C', true);

            $this->SetTextColor(0);
            $this->SetFillColor(255);
        }
    }

    $this->Ln(8);
    $this->SetFont('Arial', 'I', 8);
    $this->SetTextColor(120);
    $this->Cell(0, 6, utf8_decode('Reporte generado el ' . date('d/m/Y \a \l\a\s H:i:s')), 0, 1, 'C');
    $this->SetTextColor(0);

    $prefijo   = $soloDisponibles ? 'stock_disponible' : 'stock_completo';
    $filename  = $prefijo . '_' . date('Ymd_His') . '.pdf';
    $this->Output('D', $filename);
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
