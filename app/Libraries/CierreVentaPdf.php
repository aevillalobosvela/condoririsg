<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class CierreVentaPdf extends FPDF
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

    public function generarReporteVentas(array $reportData, array $filters)
    {
        $fecha_inicio = $filters['fecha_inicio'];
        $fecha_fin = $filters['fecha_fin'];
        // $tipo = $filters['tipo'] ?? 'general'; 

        $this->setReporteTitle('VENTAS AL CONTADO - TIENDA CEAC');
        $this->AddPage('P', 'A4');
        $this->SetMargins(10, 10, 10);
        $this->AliasNbPages();

        $productosUnicos = [];
        $ventasAgrupadas = [];
        
        $totalGeneralBs = 0;
        $totalGeneralCant = 0;

        foreach ($reportData as $item) {
            if ($item->estado_venta != 1) continue;
             $tipoPago = strtolower($item->tipo_pago);
             if ($tipoPago !== 'contado' && $tipoPago !== 'deposito_contado') continue; 

            $prodNombre = utf8_decode($item->producto_nombre);
            
            if (!isset($productosUnicos[$prodNombre])) {
                $productosUnicos[$prodNombre] = [
                    'precio' => $item->precio_unitario, 
                    'unidad' => 'UNIDAD', 
                    'total_cantidad' => 0
                ];
            }
            $productosUnicos[$prodNombre]['total_cantidad'] += $item->cantidad;

            $ventaId = $item->venta_id;
            if (!isset($ventasAgrupadas[$ventaId])) {
                $cliente = $item->cliente_nombre ?? 'Consumidor Final';
                if (stripos($cliente, 'Sin Nombre') !== false) {
                    $cliente = ''; 
                }

                $ventasAgrupadas[$ventaId] = [
                    'cliente' => utf8_decode($cliente),
                    'notas' => $ventaId, // Correct: venta_id
                    'total_venta' => 0, 
                    'items' => []
                ];
            }

            if (!isset($ventasAgrupadas[$ventaId]['items'][$prodNombre])) {
                $ventasAgrupadas[$ventaId]['items'][$prodNombre] = ['q' => 0, 'bs' => 0];
            }
            $ventasAgrupadas[$ventaId]['items'][$prodNombre]['q'] += $item->cantidad;
            $ventasAgrupadas[$ventaId]['items'][$prodNombre]['bs'] += $item->subtotal_item;
            
            $ventasAgrupadas[$ventaId]['total_venta'] += $item->subtotal_item;

            $totalGeneralBs += $item->subtotal_item;
            $totalGeneralCant += $item->cantidad;
        }

        ksort($productosUnicos);
        
        $this->SetFont('Arial', 'B', 9);
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $d = date('d', strtotime($fecha_inicio));
        $m = date('m', strtotime($fecha_inicio));
        $y = date('Y', strtotime($fecha_inicio));
        
        if ($fecha_inicio == $fecha_fin) {
             $fechaTexto = "Del: $d de {$meses[$m]} de $y Al: $d de {$meses[$m]} de $y";
        } else {
             $d2 = date('d', strtotime($fecha_fin));
             $m2 = date('m', strtotime($fecha_fin));
             $y2 = date('Y', strtotime($fecha_fin));
             $fechaTexto = "Del: $d de {$meses[$m]} de $y Al: $d2 de {$meses[$m2]} de $y2";
        }

        $this->Cell(0, 5, utf8_decode($fechaTexto), 0, 1, 'L');
        $this->SetX($this->GetPageWidth() - 60);
        $this->Cell(50, 5, "TOTAL: " . number_format($totalGeneralBs, 2, ',', '.') . " Bs.", 0, 1, 'R');
        $this->Ln(2);

        $colWidth = 25; 
        $labelWidth = 50;
        
        $numProds = count($productosUnicos);
        if ($numProds > 0) {
            $availableWidth = $this->GetPageWidth() - 20 - $labelWidth - 30; 
            $colWidth = min(25, $availableWidth / $numProds);
        }

        $this->SetFillColor(220, 220, 220);
        $this->SetFont('Arial', 'B', 8);

        $this->Cell($labelWidth, 5, 'TOTAL BOLIVIANOS:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
             $totalBsProd = $info['precio'] * $info['total_cantidad']; 
             $this->Cell($colWidth, 5, round($totalBsProd), 1, 0, 'C');
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);

        $this->Cell($labelWidth, 5, 'TOTAL CANTIDADES:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, $info['total_cantidad'], 1, 0, 'C');
        }
        $this->Cell(0, 5, $totalGeneralCant, 1, 1, 'R', true);

        $this->Cell($labelWidth, 8, 'PRODUCTOS:', 1, 0, 'R', true);
        $currX = $this->GetX();
        $currY = $this->GetY();
        foreach ($productosUnicos as $prod => $info) {
            $this->SetXY($currX, $currY);
            $this->SetFont('Arial', '', 6);
            $this->Cell($colWidth, 8, substr($prod, 0, 15), 1, 0, 'C'); 
            $currX += $colWidth;
        }
        $this->SetXY($currX, $currY + 8); 
        $this->SetFont('Arial', 'B', 8);
        $this->SetXY($this->GetPageWidth() - 10 - 25, $currY); 
        $this->Cell(25, 8, "TOTAL", 1, 1, 'C', true);

        $this->Cell($labelWidth, 5, 'UNIDADES DE MEDIDA:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, 'PIEZA', 1, 0, 'C'); 
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);

        $this->Cell($labelWidth, 5, 'PRECIOS:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, number_format($info['precio'], 0), 1, 0, 'C'); 
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);
        
        $this->Ln(5);

        $wNombre = 50; 
        $wNota = 25;
        $subColW = $colWidth / 2;
        
        $this->SetFillColor(200, 200, 200);
        $this->Cell($wNombre, 5, 'APELLIDOS Y NOMBRES:', 1, 0, 'L', true);
        
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($subColW, 5, 'Q', 1, 0, 'C', true);
            $this->Cell($subColW, 5, 'Bs', 1, 0, 'C', true);
        }
        $this->Cell($wNota, 5, 'Nro. Venta', 1, 0, 'C', true);
        $this->Cell(0, 5, 'TOTAL', 1, 1, 'C', true);

        $this->SetFont('Arial', '', 7);
        $fill = false;

        foreach ($ventasAgrupadas as $venta) {
            $this->SetFillColor(245, 245, 245);
            if ($this->GetY() > $this->GetPageHeight() - 20) {
                 $this->AddPage();
                 $this->SetFillColor(200, 200, 200);
                 $this->Cell($wNombre, 5, 'APELLIDOS Y NOMBRES:', 1, 0, 'L', true);
                 foreach ($productosUnicos as $prod => $info) {
                    $this->Cell($subColW, 5, 'Q', 1, 0, 'C', true);
                    $this->Cell($subColW, 5, 'Bs', 1, 0, 'C', true);
                 }
                 $this->Cell($wNota, 5, 'Nro. Venta', 1, 0, 'C', true);
                 $this->Cell(0, 5, 'TOTAL', 1, 1, 'C', true);
                 $this->SetFillColor(245, 245, 245);
            }
            
            $this->Cell($wNombre, 5, substr($venta['cliente'], 0, 30), 1, 0, 'L', $fill);
            
            foreach ($productosUnicos as $prod => $info) {
                if (isset($venta['items'][$prod])) {
                    $q = $venta['items'][$prod]['q'];
                    $bs = $venta['items'][$prod]['bs'];
                    
                    $qStr = $q > 0 ? $q : '';
                    $bsStr = $bs > 0 ? number_format($bs, 0) : ''; 
                    
                    $this->Cell($subColW, 5, $qStr, 1, 0, 'C', $fill);
                    $this->Cell($subColW, 5, $bsStr, 1, 0, 'R', $fill);
                } else {
                    $this->Cell($subColW, 5, '', 1, 0, 'C', $fill);
                    $this->Cell($subColW, 5, '', 1, 0, 'C', $fill);
                }
            }
            
            $this->Cell($wNota, 5, $venta['notas'], 1, 0, 'C', $fill);
            $this->Cell(0, 5, number_format($venta['total_venta'], 2), 1, 1, 'R', $fill);
            
            $fill = !$fill; 
        }

        $this->Ln(5);
        $this->SetFont('Arial', 'B', 10);
        $literal = $this->numeroALiteral($totalGeneralBs);
        $this->Cell(0, 6, "TOTAL VENTAS AL CONTADO: " . strtoupper($literal) . " BOLIVIANOS", 0, 1, 'L');
        
        $this->Ln(10);
        $this->Cell(0, 5, utf8_decode("NOTA.- El día " . strftime('%A, %d de %B de %Y', strtotime($fecha_inicio)) . ", No hubo ventas al contado"), 0, 1, 'L'); 

        $filename = 'reporte_contado_matrix_' . $fecha_inicio . '.pdf';
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

    private function tablaProductos(array $ventasPorProducto)
    {
        $w = [100, 30, 50];
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 230, 245);
        $this->Cell($w[0], 6, utf8_decode('Producto'), 1, 0, 'C', true);
        $this->Cell($w[1], 6, utf8_decode('Cant.'), 1, 0, 'C', true);
        $this->Cell($w[2], 6, utf8_decode('Monto (Bs.)'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 10);
        foreach ($ventasPorProducto as $producto => $data) {
            $this->Cell($w[0], 6, $producto, 1, 0, 'L');
            $this->Cell($w[1], 6, $data['cantidad_total'], 1, 0, 'C');
            $this->Cell($w[2], 6, number_format($data['monto_total'], 2, ',', '.'), 1, 1, 'R');
        }
    }

    private function tablaResumenFinanciero(array $resumen, float $total, string $tipo)
    {
        $wTipo = 90;
        $wMonto = 45;

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 230, 245);
        $this->Cell($wTipo, 6, utf8_decode('Tipo de Pago'), 1, 0, 'C', true);
        $this->Cell($wMonto, 6, utf8_decode('Monto (Bs.)'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 10);
        $tipos = match($tipo) {
            'contado' => ['Contado'],
            'credito' => ['Credito'],
            default => ['Contado', 'Credito'],
        };

        foreach ($tipos as $t) {
            if (isset($resumen[$t]) && $resumen[$t] > 0) {
                $this->Cell($wTipo, 6, $t, 1, 0, 'L');
                $this->Cell($wMonto, 6, number_format($resumen[$t], 2, ',', '.'), 1, 1, 'R');
            }
        }

        // Total
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(255, 240, 200);
        $this->Cell($wTipo, 7, utf8_decode('TOTAL GENERAL'), 1, 0, 'L', true);
        $this->Cell($wMonto, 7, number_format($total, 2, ',', '.'), 1, 1, 'R', true);
    }

    private function tablaAcumuladoClientes(array $acumuladoClientes)
    {
        $w = [80, 60, 40]; // Cliente, Nros Venta, Total
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 230, 245);
        $this->Cell($w[0], 6, utf8_decode('Cliente'), 1, 0, 'C', true);
        $this->Cell($w[1], 6, utf8_decode('Nros. Venta'), 1, 0, 'C', true);
        $this->Cell($w[2], 6, utf8_decode('Total Acumulado (Bs.)'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 9);
        
        foreach ($acumuladoClientes as $cliente => $data) {
            $idsStr = implode(', ', $data['ids']);
            
            // Calculate height
            $nbIds = $this->NbLines($w[1], $idsStr);
            $nbCliente = $this->NbLines($w[0], utf8_decode($cliente));
            $h = 6 * max($nbIds, $nbCliente);

            if ($this->GetY() + $h > $this->GetPageHeight() - 25) {
                $this->AddPage();
                $this->SetFont('Arial', 'B', 10);
                $this->SetFillColor(220, 230, 245);
                $this->Cell($w[0], 6, utf8_decode('Cliente'), 1, 0, 'C', true);
                $this->Cell($w[1], 6, utf8_decode('Nros. Venta'), 1, 0, 'C', true);
                $this->Cell($w[2], 6, utf8_decode('Total Acumulado (Bs.)'), 1, 1, 'C', true);
                $this->SetFont('Arial', '', 9);
            }

            $x = $this->GetX();
            $y = $this->GetY();

            $this->Rect($x, $y, $w[0], $h);
            $this->MultiCell($w[0], $h > 6 ? 6 : $h, utf8_decode($cliente), 0, 'L');
            
            $this->SetXY($x + $w[0], $y);
            $this->Rect($x + $w[0], $y, $w[1], $h);
            $this->MultiCell($w[1], $h > 6 ? 6 : $h, $idsStr, 0, 'C');

            $this->SetXY($x + $w[0] + $w[1], $y);
            $this->Rect($x + $w[0] + $w[1], $y, $w[2], $h);
            $this->MultiCell($w[2], $h, number_format($data['total'], 2, ',', '.'), 0, 'R');

            $this->SetY($y + $h);
        }
    }

    // Helper to calculate number of lines for MultiCell (copied from ReporteDeposito or FPDF tutorials)
    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }

    private function mostrarVentas(array $ventas, bool $esCredito)
    {
        $wVenta = [40, 70, 40, 30];
        $wItem = [15, 95, 35, 35];

        foreach ($ventas as $venta) {
            if ($this->GetY() > $this->GetPageHeight() - 50) {
                $this->AddPage();
            }

            // Cabecera de venta
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(230, 240, 255);
            $this->Cell($wVenta[0], 6, utf8_decode("Nro: {$venta['Nro']}"), 1, 0, 'L', true);
            $this->Cell($wVenta[1], 6, utf8_decode("Cliente: {$venta['cliente']}"), 1, 0, 'L', true);
            $this->Cell($wVenta[2], 6, utf8_decode("Tipo: {$venta['tipo_pago']}"), 1, 0, 'L', true);
            $this->Cell($wVenta[3], 6, utf8_decode("Bs. " . number_format($venta['monto_total'], 2, ',', '.')), 1, 1, 'R', true);

            // Fecha
            $this->SetFont('Arial', '', 9);
            $this->SetFillColor(245, 245, 245);
            $this->Cell(0, 5, utf8_decode("Fecha: " . date('d/m/Y H:i', strtotime($venta['fecha']))), 1, 1, 'L', true);

            // Datos del personal (solo crédito)
            if ($esCredito && !empty($venta['dip'])) {
                $this->SetFont('Arial', 'I', 9);
                $this->SetFillColor(250, 252, 240);
                $w = [25, 45, 50, 60];
                $this->Cell($w[0], 5, 'CI:', 1, 0, 'R', true);
                $this->Cell($w[1], 5, $venta['dip'], 1, 0, 'L', true);
                $this->Cell($w[2], 5, utf8_decode('Nombre:'), 1, 0, 'R', true);
                $this->Cell($w[3], 5, utf8_decode($venta['nombre_personal']), 1, 1, 'L', true);

                $this->Cell($w[0], 5, '', 1, 0, 'R', true);
                $this->Cell($w[1], 5, '', 1, 0, 'L', true);
                $this->Cell($w[2], 5, utf8_decode('Sección:'), 1, 0, 'R', true);
                $this->Cell($w[3], 5, utf8_decode($venta['seccion']), 1, 1, 'L', true);
            }

            // Ítems
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(220, 230, 245);
            $this->Cell($wItem[0], 6, utf8_decode('Cant.'), 1, 0, 'C', true);
            $this->Cell($wItem[1], 6, utf8_decode('Producto'), 1, 0, 'L', true);
            $this->Cell($wItem[2], 6, utf8_decode('Precio'), 1, 0, 'R', true);
            $this->Cell($wItem[3], 6, utf8_decode('Subtotal'), 1, 1, 'R', true);

            $this->SetFont('Arial', '', 9);
            $lineHeight = 5;

            if (!empty($venta['items'])) {
                foreach ($venta['items'] as $item) {
                    // CORRECCIÓN: Evitar el error si el elemento no es un array válido.
                    if (!is_array($item) || !isset($item['producto'])) {
                        continue; 
                    }

                    $x = $this->GetX();
                    $y = $this->GetY();

                    $this->SetX($x + $wItem[0]);
                    // Línea 318 Original
                    $this->MultiCell($wItem[1], $lineHeight, utf8_decode($item['producto']), 0, 'L');

                    $h = max($lineHeight, $this->GetY() - $y);
                    $this->SetXY($x, $y);
                    $this->Cell($wItem[0], $h, $item['cantidad'], 1, 0, 'C');
                    $this->Cell($wItem[1], $h, '', 1, 0, 'L');
                    $this->Cell($wItem[2], $h, number_format($item['precio'], 2, ',', '.'), 1, 0, 'R');
                    $this->Cell($wItem[3], $h, number_format($item['subtotal'], 2, ',', '.'), 1, 1, 'R');
                }
            } else {
                $this->Cell(array_sum($wItem), 5, utf8_decode('Sin ítems registrados.'), 1, 1, 'C');
            }

            $this->Ln(6);
        }
    }

    private function numeroALiteral($monto)
    {
        $monto = (float)$monto;
        $formatter = new \NumberFormatter("es", \NumberFormatter::SPELLOUT);
        return strtoupper($formatter->format($monto));
    }
}
