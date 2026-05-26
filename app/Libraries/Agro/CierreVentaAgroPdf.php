<?php

namespace App\Libraries\Agro;

use App\Libraries\CierreVentaBasePdf;

class CierreVentaAgroPdf extends CierreVentaBasePdf
{
    public function generarReporteVentas(array $reportData, array $filters): void
    {
        $fecha_inicio = $filters['fecha_inicio'];
        $fecha_fin    = $filters['fecha_fin'];
        $tipo         = $filters['tipo'] ?? 'general';
        $nombreUsuario = $filters['nombre_usuario'] ?? 'Usuario';

        $tituloTipo = match($tipo) {
            'contado' => 'VENTAS AL CONTADO',
            'credito' => 'VENTAS A CREDITO',
            default   => 'VENTAS GENERALES',
        };

        $this->setReporteTitle($tituloTipo . ' - CONDORIRI AGROPECUARIO');
        $this->AddPage('L', 'A4');
        $this->SetMargins(10, 10, 10);
        $this->AliasNbPages();

        $productosUnicos  = [];
        $ventasAgrupadas  = [];
        $totalGeneralBs   = 0;
        $totalGeneralCant = 0;

        foreach ($reportData as $item) {
            if ($item->estado_venta != 1) continue;
            $tipoPago = strtolower($item->tipo_pago);
            $esValida = match($tipo) {
                'contado' => $tipoPago === 'contado',
                'credito' => $tipoPago === 'credito',
                default   => true,
            };
            if (!$esValida) continue;

            $prodNombre = utf8_decode(trim(preg_replace('/\s+/', ' ', str_replace(['(', ')'], '', $item->producto_nombre ?? ''))));
            if (empty($prodNombre)) continue;

            if (!isset($productosUnicos[$prodNombre])) {
                $productosUnicos[$prodNombre] = ['precio' => $item->precio_unitario, 'total_cantidad' => 0];
            }
            $productosUnicos[$prodNombre]['total_cantidad'] += $item->cantidad;

            $ventaId = $item->venta_id;
            $codigoVenta = $item->codigo_venta ?? (string)$ventaId;
            if (!isset($ventasAgrupadas[$ventaId])) {
                $cliente = $item->cliente_nombre ?? 'Consumidor Final';
                if (stripos($cliente, 'Sin Nombre') !== false) $cliente = '';
                $fechaVenta = !empty($item->fecha_venta)
                    ? date('d/m/Y', strtotime($item->fecha_venta))
                    : '';
                $origenMap = [
                    'ORURO-VENTAS' => 'SUC. CENTRO',
                    'LACTEOS'      => 'PLANTA PROD.',
                ];
                $origen = $origenMap[$item->sucursal_nombre ?? ''] ?? 'OTRO';
                $ventasAgrupadas[$ventaId] = [
                    'cliente'            => utf8_decode($cliente),
                    'code'               => $codigoVenta,
                    'fecha_venta'        => $fechaVenta,
                    'origen'             => $origen,
                    'total_venta'        => 0,
                    'receptor_categoria' => $item->receptor_categoria ?? null,
                    'items'              => [],
                ];
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

        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
                  '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $d  = date('d', strtotime($fecha_inicio));
        $m  = date('m', strtotime($fecha_inicio));
        $y  = date('Y', strtotime($fecha_inicio));

        $fechaTexto = ($fecha_inicio === $fecha_fin)
            ? "Del: $d de {$meses[$m]} de $y Al: $d de {$meses[$m]} de $y"
            : "Del: $d de {$meses[$m]} de $y Al: " . date('d', strtotime($fecha_fin)) . " de {$meses[date('m', strtotime($fecha_fin))]} de " . date('Y', strtotime($fecha_fin));

        $ventaIds    = array_keys($ventasAgrupadas);
        $codigos     = array_column($ventasAgrupadas, 'code');
        $numeros     = array_map(fn($c) => (int) substr($c, strrpos($c, '-') + 1), $codigos);
        $nroVentaMin = !empty($numeros) ? min($numeros) : 0;
        $nroVentaMax = !empty($numeros) ? max($numeros) : 0;

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(0, 70, 180);
        $this->Cell(0, 5, 'Nro. Venta: ' . $nroVentaMin . ' al ' . $nroVentaMax, 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 5, utf8_decode($fechaTexto), 0, 1, 'L');
        $this->SetX($this->GetPageWidth() - 60);
        $this->Cell(50, 5, 'TOTAL: ' . number_format($totalGeneralBs, 2, ',', '.') . ' Bs.', 0, 1, 'R');
        $this->Ln(2);

        $wNro       = 8;
        $numProds   = count($productosUnicos);
        $esCredito  = ($tipo === 'credito');
        $wCategoria = $esCredito ? 22 : 0;

        // labelWidth dinámico: ancho justo para el nombre más largo del reporte
        $this->SetFont('Arial', '', 7);
        $maxNombreW = 40;
        foreach ($ventasAgrupadas as $venta) {
            $w = $this->GetStringWidth($venta['cliente']) + 4;
            if ($w > $maxNombreW) $maxNombreW = $w;
        }
        $labelWidth = min(70, max(40, $maxNombreW));

        // Columnas fijas: wNro + wNota + wFecha + wOrigen + wCategoria + wTotal
        $wFijos   = $wNro + 22 + 18 + 22 + $wCategoria + 10;
        $colWidth = $numProds > 0
            ? ($this->GetPageWidth() - 20 - $labelWidth - $wFijos) / $numProds
            : 20;

        $this->SetFillColor(220, 220, 220);
        $this->SetFont('Arial', 'B', 8);

        $this->Cell($wNro, 5, '', 1, 0, 'C', true);
        $this->Cell($labelWidth, 5, 'TOTAL BOLIVIANOS:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, round($info['precio'] * $info['total_cantidad']), 1, 0, 'C');
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);

        $this->Cell($wNro, 5, '', 1, 0, 'C', true);
        $this->Cell($labelWidth, 5, 'TOTAL CANTIDADES:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, $info['total_cantidad'], 1, 0, 'C');
        }
        $this->Cell(0, 5, $totalGeneralCant, 1, 1, 'R', true);

        $this->SetFont('Arial', '', 6);
        $maxLines = 1;
        foreach ($productosUnicos as $prod => $info) {
            $lines = $this->NbLines($colWidth, $prod);
            if ($lines > $maxLines) $maxLines = $lines;
        }
        $rowH = $maxLines * 5;
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($wNro, $rowH, '', 1, 0, 'C', true);
        $this->Cell($labelWidth, $rowH, 'PRODUCTOS:', 1, 0, 'R', true);
        $currX = $this->GetX();
        $currY = $this->GetY();
        foreach ($productosUnicos as $prod => $info) {
            $this->SetXY($currX, $currY);
            $this->SetFont('Arial', '', 6);
            $this->Rect($currX, $currY, $colWidth, $rowH);
            $this->MultiCell($colWidth, 5, $prod, 0, 'C');
            $currX += $colWidth;
        }
        $this->SetFont('Arial', 'B', 8);
        $this->SetXY($currX, $currY);
        $this->Cell(0, $rowH, 'TOTAL', 1, 1, 'C', true);

        // Unidades genéricas para agro
        $this->Cell($wNro, 5, '', 1, 0, 'C', true);
        $this->Cell($labelWidth, 5, 'UNIDADES DE MEDIDA:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, 'UND', 1, 0, 'C');
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);

        $this->Cell($wNro, 5, '', 1, 0, 'C', true);
        $this->Cell($labelWidth, 5, 'PRECIOS:', 1, 0, 'R', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($colWidth, 5, number_format($info['precio'], 2, ',', '.'), 1, 0, 'C');
        }
        $this->Cell(0, 5, number_format($totalGeneralBs, 2, ',', '.'), 1, 1, 'R', true);

        $this->Ln(5);

        $wNota  = 22;
        $wFecha = 18;
        $wOrigen = 22;
        $wTotal = 10;
        $subColW = $colWidth / 2;
        $this->SetFillColor(200, 200, 200);
        $this->Cell($wNro, 5, 'N°', 1, 0, 'C', true);
        $this->Cell($labelWidth, 5, 'APELLIDOS Y NOMBRES:', 1, 0, 'L', true);
        foreach ($productosUnicos as $prod => $info) {
            $this->Cell($subColW, 5, 'Q', 1, 0, 'C', true);
            $this->Cell($subColW, 5, 'Bs', 1, 0, 'C', true);
        }
        $this->Cell($wNota,      5, 'Nro. Venta', 1, 0, 'C', true);
        $this->Cell($wFecha,     5, 'Fecha',       1, 0, 'C', true);
        $this->Cell($wOrigen,    5, 'Origen',      1, 0, 'C', true);
        if ($esCredito) {
            $this->Cell($wCategoria, 5, utf8_decode('Categoría'), 1, 0, 'C', true);
        }
        $this->Cell($wTotal,     5, 'T',           1, 1, 'C', true);

        $this->SetFont('Arial', '', 6);
        $fill = false;
        $nro  = 1;
        foreach ($ventasAgrupadas as $venta) {
            $this->SetFillColor(245, 245, 245);
            if ($this->GetY() > $this->GetPageHeight() - 20) {
                $this->AddPage('L', 'A4');
                $this->SetFillColor(200, 200, 200);
                $this->Cell($wNro, 5, 'N°', 1, 0, 'C', true);
                $this->Cell($labelWidth, 5, 'APELLIDOS Y NOMBRES:', 1, 0, 'L', true);
                foreach ($productosUnicos as $prod => $info) {
                    $this->Cell($subColW, 5, 'Q', 1, 0, 'C', true);
                    $this->Cell($subColW, 5, 'Bs', 1, 0, 'C', true);
                }
                $this->Cell($wNota,      5, 'Nro. Venta', 1, 0, 'C', true);
                $this->Cell($wFecha,     5, 'Fecha',       1, 0, 'C', true);
                $this->Cell($wOrigen,    5, 'Origen',      1, 0, 'C', true);
                if ($esCredito) {
                    $this->Cell($wCategoria, 5, utf8_decode('Categoría'), 1, 0, 'C', true);
                }
                $this->Cell($wTotal,     5, 'T',           1, 1, 'C', true);
                $this->SetFillColor(245, 245, 245);
            }

            $this->Cell($wNro, 5, $nro++, 1, 0, 'C', $fill);
            $this->Cell($labelWidth, 5, $venta['cliente'], 1, 0, 'L', $fill);
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
            $this->Cell($wNota,   5, $venta['code'],        1, 0, 'C', $fill);
            $this->Cell($wFecha,  5, $venta['fecha_venta'], 1, 0, 'C', $fill);
            $this->Cell($wOrigen, 5, $venta['origen'],      1, 0, 'C', $fill);
            if ($esCredito) {
                $cat = utf8_decode($venta['receptor_categoria'] ?? '');
                $this->Cell($wCategoria, 5, $cat, 1, 0, 'C', $fill);
            }
            $this->Cell($wTotal,  5, number_format($venta['total_venta'], 2, ',', '.'), 1, 1, 'R', $fill);
            $fill = !$fill;
        }

        $this->Ln(5);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, 'TOTAL ' . $tituloTipo . ': ' . $this->numeroALiteral($totalGeneralBs) . ' BOLIVIANOS', 0, 1, 'L');

        $nombreUsuario = utf8_decode($nombreUsuario);
        $this->Ln(15);
        $pageW  = $this->GetPageWidth();
        $firmaW = 70;
        $firmaX = ($pageW - $firmaW) / 2;
        $this->SetDrawColor(0, 0, 0);
        $this->Line($firmaX, $this->GetY(), $firmaX + $firmaW, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, $nombreUsuario, 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 5, utf8_decode('Responsable - Productos Agropecuarios'), 0, 1, 'C');

        $this->Output('D', 'reporte_agro_' . $tipo . '_' . $fecha_inicio . '.pdf');
    }
}
