<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;
use App\Models\UsuarioModel;

class ReporteInventario extends FPDF
{
    protected $reporteTitle = 'REPORTE DE CIERRE DE VENTAS Y ARQUEO';

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

    public function generarReporte(array $inventarios, array $filters)
    {
        $nombre = $filters['nombre'] ?? '';
        $fecha_inicio = $filters['fecha_inicio'];
        $fecha_fin = $filters['fecha_fin'];
        $usuario = $filters['usuario'] ?? 'N/A';

        $this->setReporteTitle('REPORTE DE INVENTARIO POR PRODUCTO');
        $this->AddPage('P', 'A4');
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(true, 25);
        $this->AliasNbPages();

        // --- Filtros ---
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, utf8_decode('Filtros Aplicados'), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);

        $filtrosTexto = [];
        if (!empty($nombre)) {
            $filtrosTexto[] = "Producto: $nombre";
        }
        
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
        $filtrosTexto[] = "Período: $periodo";
        $filtrosTexto[] = "Generado por: $usuario"; // Mostrar usuario

        foreach ($filtrosTexto as $txt) {
            $this->Cell(0, 5, utf8_decode($txt), 0, 1);
        }
        $this->Ln(8);

        // --- Tabla ---
        $this->seccionTitulo('Resumen Detallado por Producto');

        $w = [90, 50, 50]; // 190 ancho total (ajustado)
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 230, 245);
        
        // Cabecera Tabla
        $this->Cell($w[0], 7, utf8_decode('Producto'), 1, 0, 'C', true);
        $this->Cell($w[1], 7, utf8_decode('Total Producido'), 1, 0, 'C', true);
        $this->Cell($w[2], 7, utf8_decode('Stock Actual'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 10);
        $lista = $inventarios['lista_productos'] ?? [];

        foreach ($lista as $item) {
            // Verificar salto de página
            if ($this->GetY() > $this->GetPageHeight() - 30) {
                $this->AddPage();
                $this->SetFont('Arial', 'B', 10);
                $this->SetFillColor(220, 230, 245);
                $this->Cell($w[0], 7, utf8_decode('Producto'), 1, 0, 'C', true);
                $this->Cell($w[1], 7, utf8_decode('Total Producido'), 1, 0, 'C', true);
                $this->Cell($w[2], 7, utf8_decode('Stock Actual'), 1, 1, 'C', true);
                $this->SetFont('Arial', '', 10);
            }

            $this->Cell($w[0], 6, utf8_decode($item->nombre), 1, 0, 'L');
            // Cambiado a 0 decimales
            $this->Cell($w[1], 6, number_format($item->total_producido, 0, ',', '.'), 1, 0, 'R');
            $this->Cell($w[2], 6, number_format($item->total_stock_actual, 0, ',', '.'), 1, 1, 'R');
        }

        // --- Totales ---
        $totales = $inventarios['totales_generales'] ?? [];
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(255, 240, 200); // Color diferente para totales
        
        $this->Cell($w[0], 8, utf8_decode('TOTALES GENERALES'), 1, 0, 'R', true);
        // Cambiado a 0 decimales
        $this->Cell($w[1], 8, number_format($totales['gran_total_producido'] ?? 0, 0, ',', '.'), 1, 0, 'R', true);
        $this->Cell($w[2], 8, number_format($totales['gran_total_stock'] ?? 0, 0, ',', '.'), 1, 1, 'R', true);

        $filename = 'reporte_inventario_' . date('Ymd_His') . '.pdf';
        $this->Output('D', $filename);
    }

    private function seccionTitulo(string $texto)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetDrawColor(0, 100, 200);
        $this->SetFillColor(240, 245, 255);
        $this->Cell(0, 7, utf8_decode($texto), 0, 1, 'L', true);
        $this->Ln(2);
    }
}