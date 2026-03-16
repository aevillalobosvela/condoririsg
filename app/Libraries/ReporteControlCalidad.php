<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class ReporteControlCalidad extends FPDF
{
    protected $reporteTitle = 'CONTROL DE CALIDAD';
    protected $filtros = [];

    public function __construct($filtros = [])
    {
        parent::__construct('L', 'mm', 'LEGAL');
        $this->filtros = $filtros;
        
        $this->SetAuthor('DTIC');
        $this->SetTitle('Reporte de Control de Calidad');
        $this->SetSubject('Control de Calidad de Inventarios');
        $this->SetMargins(15, 30, 15);
        $this->SetAutoPageBreak(true, 15);
        $this->SetFont('Arial', '', 10);
        $this->AliasNbPages();
    }

    public function Header()
    {
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
        $this->Cell(0, 4, utf8_decode('Sistema de Gestión de Inventarios CEAC-UTO'), 0, 1, 'L');
        $this->Ln(2);
        $this->SetTextColor(0);
        $this->Cell(0, 6, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    public function generarReporte(array $inventarios)
    {
        $this->AddPage();

        // Sección de filtros
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(240, 245, 255);
        $this->Cell(0, 7, utf8_decode('Filtros Aplicados'), 1, 1, 'L', true);
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(60, 6, utf8_decode('Nombre: ') . ($this->filtros['nombre'] ?: 'Todos'), 0, 0);
        $this->Cell(60, 6, utf8_decode('Fecha Inicio: ') . ($this->filtros['fecha_inicio'] ?: 'N/A'), 0, 0);
        $this->Cell(60, 6, utf8_decode('Fecha Fin: ') . ($this->filtros['fecha_fin'] ?: 'N/A'), 0, 1);
        $this->Ln(8);

        // Agrupar inventarios por mes
        $inventariosPorMes = [];
        foreach ($inventarios as $inv) {
            $mes = date('Y-m', strtotime($inv->created_at));
            $meses_es = [
                'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
                'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
                'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
                'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
            ];
            $mesIngles = date('F Y', strtotime($inv->created_at));
            $mesTexto = str_replace(array_keys($meses_es), array_values($meses_es), $mesIngles);
            
            if (!isset($inventariosPorMes[$mes])) {
                $inventariosPorMes[$mes] = [
                    'texto' => $mesTexto,
                    'registros' => []
                ];
            }
            $inventariosPorMes[$mes]['registros'][] = $inv;
        }

        krsort($inventariosPorMes);

        // Procesar cada mes
        foreach ($inventariosPorMes as $mes => $datos) {
            // Título del mes
            $this->SetFont('Arial', 'B', 12);
            $this->SetFillColor(139, 0, 0);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(0, 8, utf8_decode(strtoupper($datos['texto'])), 1, 1, 'C', true);
            $this->SetTextColor(0, 0, 0);
            $this->Ln(2);

            // Encabezados de tabla
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(220, 20, 60);
            $this->SetTextColor(255, 255, 255);
            
            $w = [20, 30, 15, 15, 15, 18, 18, 18, 18, 15, 18, 20, 12, 30];
            
            $this->Cell($w[0], 7, 'FECHA', 1, 0, 'C', true);
            $this->Cell($w[1], 7, 'NOMBRE', 1, 0, 'C', true);
            $this->Cell($w[2], 7, 'TURNO', 1, 0, 'C', true);
            $this->Cell($w[3], 7, 'GRASA', 1, 0, 'C', true);
            $this->Cell($w[4], 7, 'SNG', 1, 0, 'C', true);
            $this->Cell($w[5], 7, 'DENSIDAD', 1, 0, 'C', true);
            $this->Cell($w[6], 7, 'LACTOSA', 1, 0, 'C', true);
            $this->Cell($w[7], 7, 'SOLIDOS', 1, 0, 'C', true);
            $this->Cell($w[8], 7, 'PROTEINA', 1, 0, 'C', true);
            $this->Cell($w[9], 7, 'AGUA', 1, 0, 'C', true);
            $this->Cell($w[10], 7, 'TEMP', 1, 0, 'C', true);
            $this->Cell($w[11], 7, 'PUNTO CON', 1, 0, 'C', true);
            $this->Cell($w[12], 7, 'pH', 1, 0, 'C', true);
            $this->Cell($w[13], 7, 'OBSERVACION', 1, 1, 'C', true);
            
            $this->SetTextColor(0, 0, 0);

            // Agrupar por día
            $registrosPorDia = [];
            foreach ($datos['registros'] as $inv) {
                $dia = date('d-M-y', strtotime($inv->created_at));
                if (!isset($registrosPorDia[$dia])) {
                    $registrosPorDia[$dia] = [];
                }
                $registrosPorDia[$dia][] = $inv;
            }

            // Datos con colores alternados por día
            $this->SetFont('Arial', '', 8);
            $indiceDia = 0;
            
            foreach ($registrosPorDia as $fecha => $registrosDelDia) {
                $esDiaAmarillo = ($indiceDia % 2 === 0);
                $fillColor = $esDiaAmarillo ? [255, 249, 230] : [230, 242, 255];
                
                foreach ($registrosDelDia as $index => $inv) {
                    $esPrimeraFilaDelDia = ($index === 0);
                    
                    $this->SetFillColor(...$fillColor);
                    
                    // Fecha (solo en primera fila)
                    if ($esPrimeraFilaDelDia) {
                        $this->Cell($w[0], 6, utf8_decode($fecha), 1, 0, 'C', true);
                    } else {
                        $this->Cell($w[0], 6, '', 1, 0, 'C', true);
                    }
                    
                    $this->Cell($w[1], 6, utf8_decode($inv->nombre ?? ''), 1, 0, 'L', true);
                    $this->Cell($w[2], 6, utf8_decode($inv->turno ?? ''), 1, 0, 'C', true);
                    $this->Cell($w[3], 6, number_format($inv->grasa ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[4], 6, number_format($inv->sng ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[5], 6, number_format($inv->densidad ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[6], 6, number_format($inv->lactosa ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[7], 6, number_format($inv->solidos ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[8], 6, number_format($inv->proteina ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[9], 6, number_format($inv->agua ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[10], 6, number_format($inv->temperatura ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[11], 6, number_format($inv->congelacion ?? 0, 3), 1, 0, 'C', true);
                    $this->Cell($w[12], 6, number_format($inv->ph ?? 0, 2), 1, 0, 'C', true);
                    $this->Cell($w[13], 6, '', 1, 1, 'L', true);
                }
                
                $indiceDia++;
            }
            
            $this->Ln(5);
        }

        $this->Output('D', 'control_calidad_' . date('Ymd') . '.pdf');
    }
}
