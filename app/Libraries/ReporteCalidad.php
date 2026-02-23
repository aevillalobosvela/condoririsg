<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class ReporteCalidad extends FPDF
{
    protected $reporteTitle = 'REPORTE DE CONTROL DE CALIDAD';

    public function __construct()
    {
        parent::__construct('P', 'mm', 'Letter'); // Carta vertical
        $this->SetMargins(20, 20, 20);
        $this->SetAutoPageBreak(true, 20);
    }

    public function Header()
    {
        // Logos
        $logo_left = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

        // Logo izquierdo
        if (file_exists($logo_left)) {
            $this->Image($logo_left, 20, 10, 20);
        }

        // Logo derecho
        if (file_exists($logo_right)) {
            $this->Image($logo_right, $this->GetPageWidth() - 40, 10, 20);
        }

        // Títulos institucionales centrados
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, utf8_decode("UNIVERSIDAD TÉCNICA DE ORURO"), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, utf8_decode("FACULTAD DE CIENCIAS AGRARIAS Y NATURALES"), 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, utf8_decode("CONDORIRI - LABORATORIO DE INNOVACIÓN"), 0, 1, 'C');
        
        $this->Ln(10);
        
        // Título del reporte
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, utf8_decode($this->reporteTitle), 0, 1, 'C');
        $this->Ln(5);
        
        // Línea divisoria
        $this->SetDrawColor(0, 0, 0);
        $this->Line(20, $this->GetY(), $this->GetPageWidth() - 20, $this->GetY());
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->Cell(0, 10, utf8_decode('Generado el: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }

    public function generarReporte($inventario, $usuario)
    {
        $this->AliasNbPages();
        $this->AddPage();

        // --- Información del Inventario ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('Información del Inventario'), 0, 1, 'L');
        $this->Ln(2);

        $this->SetFont('Arial', '', 10);
        $this->Cell(40, 6, utf8_decode('Nombre:'), 0, 0);
        $this->Cell(0, 6, utf8_decode($inventario->nombre), 0, 1);
        
        $this->Cell(40, 6, utf8_decode('Código:'), 0, 0);
        $this->Cell(0, 6, utf8_decode($inventario->code), 0, 1);
        
        $this->Cell(40, 6, utf8_decode('Fecha Registro:'), 0, 0);
        $this->Cell(0, 6, utf8_decode(date('d/m/Y H:i', strtotime($inventario->fecha_calidad))), 0, 1);

        $this->Ln(8);

        // --- Datos de Calidad ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('Resultados del Análisis'), 0, 1, 'L');
        $this->Ln(2);

        // Tabla de valores
        $w = [80, 40]; // Anchos de columna
        
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(240, 240, 240);
        $this->Cell($w[0], 7, utf8_decode('Parámetro'), 1, 0, 'L', true);
        $this->Cell($w[1], 7, utf8_decode('Valor'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 10);
        
        $parametros = [
            'Grasa (%)' => $inventario->grasa,
            'SNG' => $inventario->sng,
            'Densidad' => $inventario->densidad,
            'Lactosa (%)' => $inventario->lactosa,
            'Sólidos Totales (%)' => $inventario->solidos,
            'Proteína (%)' => $inventario->proteina,
            'Agua Agregada (%)' => $inventario->agua,
            'Temperatura (°C)' => $inventario->temperatura,
            'Punto de Congelación' => $inventario->congelacion,
            'pH' => $inventario->ph,
        ];

        foreach ($parametros as $nombre => $valor) {
            $this->Cell($w[0], 7, utf8_decode($nombre), 1, 0, 'L');
            $this->Cell($w[1], 7, utf8_decode($valor ?? 'N/A'), 1, 1, 'C');
        }

        $this->Ln(15);

        // --- Responsable ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('Responsable del Registro'), 0, 1, 'L');
        $this->Ln(2);

        $this->SetFont('Arial', '', 10);
        
        $nombreCompleto = 'N/A';
        $ci = 'N/A';
        
        if ($usuario) {
            $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
            $ci = $usuario['ci'] ?? 'N/A';
        }

        $this->Cell(40, 6, utf8_decode('Nombre Completo:'), 0, 0);
        $this->Cell(0, 6, utf8_decode($nombreCompleto), 0, 1);
        
        $this->Cell(40, 6, utf8_decode('C.I.:'), 0, 0);
        $this->Cell(0, 6, utf8_decode($ci), 0, 1);

        // Espacio para firma
        $this->Ln(30);
        $this->Cell(0, 0, '', 'T', 1, 'C'); // Línea superior invisible para posicionar
        
        $this->SetX(60);
        $this->Cell(90, 0, '', 'T', 1, 'C'); // Línea de firma
        $this->Ln(2);
        $this->Cell(0, 5, utf8_decode('Firma del Responsable'), 0, 1, 'C');

        $this->Output('D', 'control_calidad_' . $inventario->code . '.pdf');
    }
}
