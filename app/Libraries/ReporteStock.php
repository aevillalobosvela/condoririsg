<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class ReporteStock extends FPDF
{
    protected $reporteTitle = 'REPORTE DE STOCK DE SUCURSALES';

    public function setReporteTitle(string $title)
    {
        $this->reporteTitle = $title;
    }

    public function Header()
    {
        // Logos
        $logo_left = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

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

    public function generarReporte($resumenStock, $resumenPorProducto, array $filters)
    {
        $fecha_inicio = $filters['fecha_inicio'];
        $fecha_fin = $filters['fecha_fin'];
        $usuario = $filters['usuario'] ?? 'N/A';

        $this->setReporteTitle('REPORTE DE STOCK VALORADO');

        // ✅ TAMANO CARTA EN HORIZONTAL (landscape)
        // Carta horizontal = 11" × 8.5" → 279.4 × 215.9 mm
        $this->AddPage('L', [279.4, 215.9]); // ← Esto es Carta en landscape con FPDF
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(true, 25);
        $this->AliasNbPages();

        // --- Filtros ---
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, utf8_decode('Información del Reporte'), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);

        $periodo = "Todo el historial";
        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $periodo = ($fecha_inicio === $fecha_fin)
                ? "Fecha: " . date('d/m/Y', strtotime($fecha_inicio))
                : "Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
        } elseif (!empty($fecha_inicio)) {
            $periodo = "Desde: " . date('d/m/Y', strtotime($fecha_inicio));
        } elseif (!empty($fecha_fin)) {
            $periodo = "Hasta: " . date('d/m/Y', strtotime($fecha_fin));
        }

        $this->Cell(0, 5, utf8_decode("Período: $periodo"), 0, 1);
        $this->Cell(0, 5, utf8_decode("Generado por: $usuario"), 0, 1);
        $this->Ln(5);

        // --- Resumen General ---
        $this->seccionTitulo('Resumen General');
        $this->SetFont('Arial', '', 10);

        $this->SetFillColor(245, 245, 245);
        $this->Cell(60, 7, utf8_decode('Total Productos:'), 1, 0, 'L', true);
        $this->Cell(45, 7, $resumenStock->total_productos ?? 0, 1, 1, 'L');

        $this->Cell(60, 7, utf8_decode('Total Stock (Unidades):'), 1, 0, 'L', true);
        $this->Cell(45, 7, $resumenStock->total_stock ?? 0, 1, 1, 'L');

        $this->Cell(60, 7, utf8_decode('Valor Total (Contado):'), 1, 0, 'L', true);
        $this->Cell(45, 7, 'Bs. ' . number_format($resumenStock->total_valor_contado ?? 0, 2), 1, 1, 'L');

        $this->Ln(8);

        // --- Tabla Detallada (SIN Categoría ni Unidad) ---
        $this->seccionTitulo('Detalle de Stock por Producto');

        // ✅ Anchos optimizados para Carta horizontal (ancho útil ≈ 250 mm)
        // Columnas: Producto(110), Stock(30), P.Contado(35), P.Crédito(35), Valor(45)
        $w = [110, 30, 35, 35, 45]; // Total = 255 mm (cabe perfecto con márgenes 15+15)

        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220, 230, 245);
        $this->Cell($w[0], 8, utf8_decode('Producto'), 1, 0, 'C', true);
        $this->Cell($w[1], 8, utf8_decode('Stock'), 1, 0, 'C', true);
        $this->Cell($w[2], 8, utf8_decode('P. Contado'), 1, 0, 'C', true);
        $this->Cell($w[3], 8, utf8_decode('P. Crédito'), 1, 0, 'C', true);
        $this->Cell($w[4], 8, utf8_decode('Valor Total'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 9);
        $lista = $resumenPorProducto['lista_productos'] ?? [];

        foreach ($lista as $item) {
            if ($this->GetY() > $this->GetPageHeight() - 30) {
                $this->AddPage('L', [279.4, 215.9]); // ← también landscape en nuevas páginas
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(220, 230, 245);
                $this->Cell($w[0], 8, utf8_decode('Producto'), 1, 0, 'C', true);
                $this->Cell($w[1], 8, utf8_decode('Stock'), 1, 0, 'C', true);
                $this->Cell($w[2], 8, utf8_decode('P. Contado'), 1, 0, 'C', true);
                $this->Cell($w[3], 8, utf8_decode('P. Crédito'), 1, 0, 'C', true);
                $this->Cell($w[4], 8, utf8_decode('Valor Total'), 1, 1, 'C', true);
                $this->SetFont('Arial', '', 9);
            }

            // ✅ Sin Categoría ni Unidad
            $this->Cell($w[0], 7, utf8_decode(substr($item->producto, 0, 65)), 1, 0, 'L'); // más espacio para nombre
            $this->Cell($w[1], 7, $item->total_stock, 1, 0, 'R');
            $this->Cell($w[2], 7, number_format($item->precio_contado, 2), 1, 0, 'R');
            $this->Cell($w[3], 7, number_format($item->precio_credito, 2), 1, 0, 'R');
            $this->Cell($w[4], 7, number_format($item->valor_contado, 2), 1, 1, 'R');
        }

        // --- Totales ---
        $totales = $resumenPorProducto['totales_generales'] ?? [];
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(255, 240, 200);

        $this->Cell($w[0], 8, utf8_decode('TOTALES GENERALES'), 1, 0, 'R', true);
        $this->Cell($w[1], 8, $totales['total_stock'] ?? 0, 1, 0, 'R', true);
        $this->Cell($w[2] + $w[3], 8, '', 1, 0, 'C', true);
        $this->Cell($w[4], 8, number_format($totales['total_valor_contado'] ?? 0, 2), 1, 1, 'R', true);

        $filename = 'reporte_stock_' . date('Ymd_His') . '.pdf';
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