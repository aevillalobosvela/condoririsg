<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class ReporteRecepcion extends FPDF
{
    protected $reporteTitle = 'REPORTE DE RECEPCIONES';

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

    // Reporte General: Lista de envíos con filtros
    public function generarReporteGeneral(array $envios, array $sucursalesMap, array $filters)
    {
        $this->setReporteTitle('HISTORIAL DE RECEPCIONES');
        $this->AddPage('L', 'A4');
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(true, 25);
        $this->AliasNbPages();

        // --- Filtros ---
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, utf8_decode('Filtros Aplicados'), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);

        $estadoMap = [
            1 => 'Pendiente',
            2 => 'Enviado',
            3 => 'Entregado',
            9 => 'Aceptado'
        ];

        $estadoTxt = !empty($filters['estado']) ? ($estadoMap[$filters['estado']] ?? 'Desconocido') : 'Todos';
        
        $periodo = "Todo el historial";
        if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
             $periodo = ($filters['fecha_inicio'] === $filters['fecha_fin'])
                ? "Fecha: " . date('d/m/Y', strtotime($filters['fecha_inicio']))
                : "Del " . date('d/m/Y', strtotime($filters['fecha_inicio'])) . " al " . date('d/m/Y', strtotime($filters['fecha_fin']));
        } elseif (!empty($filters['fecha_inicio'])) {
            $periodo = "Desde: " . date('d/m/Y', strtotime($filters['fecha_inicio']));
        } elseif (!empty($filters['fecha_fin'])) {
             $periodo = "Hasta: " . date('d/m/Y', strtotime($filters['fecha_fin']));
        }

        $this->Cell(0, 5, utf8_decode("Estado: $estadoTxt"), 0, 1);
        $this->Cell(0, 5, utf8_decode("Período: $periodo"), 0, 1);
        $this->Cell(0, 5, utf8_decode("Generado por: " . ($filters['usuario'] ?? 'N/A')), 0, 1);
        $this->Ln(5);

        // --- Tabla ---
        $this->seccionTitulo('Listado de Envíos Recibidos');

        // Columnas: Código(30), Origen(60), Fecha Envío(30), Fecha Recepción(30), Estado(30), Obs(80)
        $w = [30, 60, 30, 30, 30, 87]; 
        
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220, 230, 245);
        
        $this->Cell($w[0], 8, utf8_decode('Código'), 1, 0, 'C', true);
        $this->Cell($w[1], 8, utf8_decode('Sucursal Origen'), 1, 0, 'C', true);
        $this->Cell($w[2], 8, utf8_decode('F. Envío'), 1, 0, 'C', true);
        $this->Cell($w[3], 8, utf8_decode('F. Recepción'), 1, 0, 'C', true);
        $this->Cell($w[4], 8, utf8_decode('Estado'), 1, 0, 'C', true);
        $this->Cell($w[5], 8, utf8_decode('Observación Destino'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 9);

        foreach ($envios as $envio) {
            $estado = $estadoMap[$envio['estado_id']] ?? 'Otro';
            $origen = $sucursalesMap[$envio['sucursal_origen_id']] ?? 'N/A';
            $fEnvio = $envio['fecha_envio'] ? date('d/m/Y', strtotime($envio['fecha_envio'])) : '-';
            $fRecep = $envio['fecha_recepcion'] ? date('d/m/Y', strtotime($envio['fecha_recepcion'])) : '-';

            $this->Cell($w[0], 7, utf8_decode($envio['code']), 1, 0, 'L');
            $this->Cell($w[1], 7, utf8_decode(substr($origen, 0, 35)), 1, 0, 'L');
            $this->Cell($w[2], 7, $fEnvio, 1, 0, 'C');
            $this->Cell($w[3], 7, $fRecep, 1, 0, 'C');
            $this->Cell($w[4], 7, utf8_decode($estado), 1, 0, 'C');
            $this->Cell($w[5], 7, utf8_decode(substr($envio['observacion_destino'] ?? '', 0, 50)), 1, 1, 'L');
        }

        $filename = 'reporte_recepciones_' . date('Ymd_His') . '.pdf';
        $this->Output('D', $filename);
    }

    // Reporte Detallado: Un solo envío con sus productos
    public function generarReporteDetallado($envio, array $transferencias, array $info)
    {
        $this->setReporteTitle('DETALLE DE RECEPCIÓN DE ENVÍO');
        $this->AddPage('P', 'A4');
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(true, 25);
        $this->AliasNbPages();

        // --- Información del Envío ---
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, utf8_decode('Información General'), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);

        $this->Cell(40, 6, utf8_decode('Código de Envío:'), 0, 0, 'L');
        $this->Cell(0, 6, utf8_decode($envio['code']), 0, 1, 'L');

        $this->Cell(40, 6, utf8_decode('Origen:'), 0, 0, 'L');
        $this->Cell(0, 6, utf8_decode($info['sucursal_origen']), 0, 1, 'L');

        $this->Cell(40, 6, utf8_decode('Destino:'), 0, 0, 'L');
        $this->Cell(0, 6, utf8_decode($info['sucursal_destino']), 0, 1, 'L');

        $this->Cell(40, 6, utf8_decode('Enviado por:'), 0, 0, 'L');
        $this->Cell(0, 6, utf8_decode($info['creador_nombre'] . ' (CI: ' . $info['creador_ci'] . ')'), 0, 1, 'L');

        $this->Cell(40, 6, utf8_decode('Transportista:'), 0, 0, 'L');
        $this->Cell(0, 6, utf8_decode($info['transporte_nombre'] . ' (CI: ' . $info['transporte_ci'] . ')'), 0, 1, 'L');

        $this->Cell(40, 6, utf8_decode('Fecha Envío:'), 0, 0, 'L');
        $this->Cell(0, 6, utf8_decode($envio['fecha_envio'] ? date('d/m/Y H:i', strtotime($envio['fecha_envio'])) : '-'), 0, 1, 'L');

        $this->Cell(40, 6, utf8_decode('Fecha Recepción:'), 0, 0, 'L');
        $this->Cell(0, 6, utf8_decode($envio['fecha_recepcion'] ? date('d/m/Y H:i', strtotime($envio['fecha_recepcion'])) : 'Pendiente'), 0, 1, 'L');

        if (!empty($info['recepcion_nombre']) && $info['recepcion_nombre'] !== 'N/A') {
            $this->Cell(40, 6, utf8_decode('Recepcionado por:'), 0, 0, 'L');
            $this->Cell(0, 6, utf8_decode($info['recepcion_nombre'] . ' (CI: ' . $info['recepcion_ci'] . ')'), 0, 1, 'L');
        }

        $this->Ln(5);

        // --- Tabla de Productos ---
        $this->seccionTitulo('Productos Transferidos');

        // Columnas: Producto(60), Enviado(25), Aceptado(25), Diferencia(25), Obs(55)
        $w = [60, 25, 25, 25, 55]; 
        
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(220, 230, 245);
        
        $this->Cell($w[0], 8, utf8_decode('Producto'), 1, 0, 'C', true);
        $this->Cell($w[1], 8, utf8_decode('C. Env'), 1, 0, 'C', true);
        $this->Cell($w[2], 8, utf8_decode('C. Acep'), 1, 0, 'C', true);
        $this->Cell($w[3], 8, utf8_decode('Dif'), 1, 0, 'C', true);
        $this->Cell($w[4], 8, utf8_decode('Observación'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 8);

        $totalEnviado = 0;
        $totalAceptado = 0;

        foreach ($transferencias as $t) {
            // Acceder como objeto o array según corresponda (el controlador los pasa como objetos ahora)
            // Pero en el array original eran arrays. El usuario cambió a objetos en el controlador.
            // Verificaremos si es objeto o array para ser seguros.
            $cantEnviada = is_object($t) ? $t->cantidad : $t['cantidad'];
            $cantAceptada = is_object($t) ? ($t->cantidad_acep ?? 0) : ($t['cantidad_acep'] ?? 0);
            $prodNombre = is_object($t) ? $t->producto_nombre : $t['producto_nombre'];
            $obs = is_object($t) ? ($t->observacion_destino ?? '') : ($t['observacion_destino'] ?? '');
            
            $diferencia = $cantEnviada - $cantAceptada;

            $totalEnviado += $cantEnviada;
            $totalAceptado += $cantAceptada;

            // MultiCell para nombre producto si es largo? No, Cell simple recortada para mantener alineación fácil
            $this->Cell($w[0], 7, utf8_decode(substr($prodNombre, 0, 35)), 1, 0, 'L');
            $this->Cell($w[1], 7, $cantEnviada, 1, 0, 'C');
            $this->Cell($w[2], 7, $cantAceptada, 1, 0, 'C');
            
            // Resaltar diferencia si existe
            if ($diferencia > 0) {
                $this->SetTextColor(200, 0, 0); // Rojo
                $this->Cell($w[3], 7, "-$diferencia", 1, 0, 'C');
                $this->SetTextColor(0);
            } else {
                $this->Cell($w[3], 7, '0', 1, 0, 'C');
            }
            
            $this->Cell($w[4], 7, utf8_decode(substr($obs, 0, 35)), 1, 1, 'L');
        }

        // Totales
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(255, 240, 200);
        $this->Cell($w[0], 8, utf8_decode('TOTALES'), 1, 0, 'R', true);
        $this->Cell($w[1], 8, $totalEnviado, 1, 0, 'C', true);
        $this->Cell($w[2], 8, $totalAceptado, 1, 0, 'C', true);
        $this->Cell($w[3], 8, '', 1, 1, 'C', true);

        $filename = 'detalle_recepcion_' . $envio['code'] . '.pdf';
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
