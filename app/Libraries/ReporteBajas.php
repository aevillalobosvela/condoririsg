<?php
namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class ReporteBajas extends FPDF
{
    public function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode('REPORTE DE BAJAS DE PRODUCTOS'), 0, 1, 'C');
        $this->Ln(4);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, utf8_decode('Sistema Condoriri - CEAC-UTO'), 0, 1, 'C');
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . ' / {nb}', 0, 0, 'C');
    }

    public function generar($bajas, $filters, $usuario)
    {
        $this->AliasNbPages();
        $this->AddPage('P', 'LETTER');
        $this->SetMargins(15, 15, 15);

        // Filtros
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, utf8_decode('Filtros Aplicados'), 0, 1);
        $this->SetFont('Arial', '', 10);
        
        $periodo = "Todo el historial";
        if (!empty($filters['fecha_desde']) || !empty($filters['fecha_hasta'])) {
            $inicio = $filters['fecha_desde'] ?? '—';
            $fin = $filters['fecha_hasta'] ?? '—';
            $periodo = "Del {$inicio} al {$fin}";
        }
        $this->Cell(0, 5, utf8_decode("Período: $periodo"), 0, 1);
        $this->Cell(0, 5, utf8_decode("Generado por: $usuario"), 0, 1);
        $this->Ln(5);

        // Tabla
        $this->SetFont('Arial', 'B', 9);
        $w = [30, 60, 25, 40, 55];
        $this->Cell($w[0], 7, utf8_decode('Fecha'), 1, 0, 'C');
        $this->Cell($w[1], 7, utf8_decode('Producto'), 1, 0, 'C');
        $this->Cell($w[2], 7, utf8_decode('Cantidad'), 1, 0, 'C');
        $this->Cell($w[3], 7, utf8_decode('Usuario'), 1, 0, 'C');
        $this->Cell($w[4], 7, utf8_decode('Observación'), 1, 1, 'C');

        $this->SetFont('Arial', '', 9);
        if (empty($bajas)) {
            $this->Cell(array_sum($w), 7, utf8_decode('No hay bajas registradas.'), 1, 1, 'C');
        } else {
            foreach ($bajas as $baja) {
                $this->Cell($w[0], 6, date('d/m/Y', strtotime($baja->created_at)), 1, 0);
                $this->Cell($w[1], 6, utf8_decode(substr($baja->producto_nombre, 0, 30)), 1, 0);
                $this->Cell($w[2], 6, $baja->cantidad, 1, 0, 'C');
                $this->Cell($w[3], 6, utf8_decode(substr($baja->usuario_nombre . ' ' . $baja->usuario_apellidos, 0, 25)), 1, 0);
                $this->Cell($w[4], 6, utf8_decode(substr($baja->observacion ?? '—', 0, 40)), 1, 1);
            }
        }

        $filename = 'reporte_bajas_' . date('Ymd') . '.pdf';
        $this->Output('D', $filename);
    }
}