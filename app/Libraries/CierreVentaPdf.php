<?php

namespace App\Libraries;

class CierreVentaPdf extends CierreVentaBasePdf
{
    public function generarReporteVentas(array $reportData, array $filters): void
    {
        $fecha_inicio = $filters['fecha_inicio'];
        $fecha_fin    = $filters['fecha_fin'];

        $tipo = $filters['tipo'] ?? 'general';
        $tituloTipo = match($tipo) {
            'contado', 'deposito_contado' => 'VENTAS AL CONTADO',
            'credito'                     => 'VENTAS A CREDITO',
            default                       => 'VENTAS GENERALES',
        };
        $this->setReporteTitle($tituloTipo . ' - TIENDA CEAC');
        $this->AddPage('P', 'A4');
        $this->SetMargins(10, 10, 10);
        $this->AliasNbPages();

        $productosUnicos = [];
        $ventasAgrupadas = [];
        $totalGeneralBs   = 0;
        $totalGeneralCant = 0;

        foreach ($reportData as $item) {
            if ($item->estado_venta != 1) continue;
            $tipoPago = strtolower($item->tipo_pago);
            $esValida = match($tipo) {
                'contado', 'deposito_contado' => $tipoPago === 'contado' || $tipoPago === 'deposito_contado',
                'credito'                     => $tipoPago === 'credito',
                default                       => true,
            };
            if (!$esValida) continue;

            $prodNombre = utf8_decode($item->producto_nombre);
            if (!isset($productosUnicos[$prodNombre])) {
                $productosUnicos[$prodNombre] = ['precio' => $item->precio_unitario, 'unidad' => 'UNIDAD', 'total_cantidad' => 0];
            }
            $productosUnicos[$prodNombre]['total_cantidad'] += $item->cantidad;

            $ventaId = $item->venta_id;
            if (!isset($ventasAgrupadas[$ventaId])) {
                $cliente = $item->cliente_nombre ?? 'Consumidor Final';
                if (stripos($cliente, 'Sin Nombre') !== false) $cliente = '';
                $ventasAgrupadas[$ventaId] = ['cliente' => utf8_decode($cliente), 'notas' => $ventaId, 'total_venta' => 0, 'items' => []];
            }

            if (!isset($ventasAgrupadas[$ventaId]['items'][$prodNombre])) {
                $ventasAgrupadas[$ventaId]['items'][$prodNombre] = ['q' => 0, 'bs' => 0];
            }
            $ventasAgrupadas[$ventaId]['items'][$prodNombre]['q']  += $item->cantidad;
            $ventasAgrupadas[$ventaId]['items'][$prodNombre]['bs'] += $item->subtotal_item;
            $ventasAgrupadas[$ventaId]['total_venta'] += $item->subtotal_item;
            $totalGeneralBs   += $item->subtotal_item;
            $totalGeneralCant += $item->cantidad;
        }

        ksort($productosUnicos);

        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $d = date('d', strtotime($fecha_inicio));
        $m = date('m', strtotime($fecha_inicio));
        $y = date('Y', strtotime($fecha_inicio));

        $fechaTexto = ($fecha_inicio == $fecha_fin)
            ? "Del: $d de {$meses[$m]} de $y Al: $d de {$meses[$m]} de $y"
            : "Del: $d de {$meses[$m]} de $y Al: " . date('d', strtotime($fecha_fin)) . " de {$meses[date('m', strtotime($fecha_fin))]} de " . date('Y', strtotime($fecha_fin));

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, utf8_decode($fechaTexto), 0, 1, 'L');
        $this->SetX($this->GetPageWidth() - 60);
        $this->Cell(50, 5, 'TOTAL: ' . number_format($totalGeneralBs, 2, ',', '.') . ' Bs.', 0, 1, 'R');
        $this->Ln(2);

        $labelWidth = 50;
        $numProds   = count($productosUnicos);
        $colWidth   = $numProds > 0 ? min(25, ($this->GetPageWidth() - 20 - $labelWidth - 30) / $numProds) : 25;

        $this->SetFillColor(220, 220, 220);
        $this->SetFont('Arial', 'B', 8);

        $this->Cell($labelWidth, 5, 'TOTAL BOLIVIANOS:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, round($info['precio'] * $info['total_cantidad']), 1, 0, 'C');
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);

        $this->Cell($labelWidth, 5, 'TOTAL CANTIDADES:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, $info['total_cantidad'], 1, 0, 'C');
        }
        $this->Cell(0, 5, $totalGeneralCant, 1, 1, 'R', true);

        $this->Cell($labelWidth, 8, 'PRODUCTOS:', 1, 0, 'R', true);
        $currX = $this->GetX(); $currY = $this->GetY();
        foreach ($productosUnicos as $prod => $info) {
            $this->SetXY($currX, $currY);
            $this->SetFont('Arial', '', 6);
            $this->Cell($colWidth, 8, substr($prod, 0, 15), 1, 0, 'C');
            $currX += $colWidth;
        }
        $this->SetFont('Arial', 'B', 8);
        $this->SetXY($this->GetPageWidth() - 10 - 25, $currY);
        $this->Cell(25, 8, 'TOTAL', 1, 1, 'C', true);

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

        $wNombre = 50; $wNota = 25; $subColW = $colWidth / 2;
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
                    $q  = $venta['items'][$prod]['q'];
                    $bs = $venta['items'][$prod]['bs'];
                    $this->Cell($subColW, 5, $q > 0 ? $q : '', 1, 0, 'C', $fill);
                    $this->Cell($subColW, 5, $bs > 0 ? number_format($bs, 0) : '', 1, 0, 'R', $fill);
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
        $this->Cell(0, 6, 'TOTAL ' . $tituloTipo . ': ' . $this->numeroALiteral($totalGeneralBs) . ' BOLIVIANOS', 0, 1, 'L');

        $this->Output('D', 'reporte_' . $tipo . '_matrix_' . $fecha_inicio . '.pdf');
    }
}
