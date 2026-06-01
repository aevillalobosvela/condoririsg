<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use FPDF;

class CierreVentaBasePdf extends FPDF
{
    protected string $reporteTitle = 'REPORTE DE CIERRE DE VENTAS Y ARQUEO';

    public function setReporteTitle(string $title): void
    {
        $this->reporteTitle = $title;
    }

    public function Header()
    {
        $logo_left  = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

        $this->SetY(10);

        if (file_exists($logo_left)) {
            $this->Image($logo_left, 15, 10, 22);
        }
        if (file_exists($logo_right)) {
            $this->Image($logo_right, $this->GetPageWidth() - 37, 10, 22);
        }

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 6, utf8_decode('UNIVERSIDAD TÉCNICA DE ORURO'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 5, utf8_decode('FACULTAD DE CIENCIAS AGRARIAS Y NATURALES'), 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, utf8_decode('CONDORIRI - LABORATORIO DE INNOVACIÓN'), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode('Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49'), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode('Email: dpdi@uto.edu.bo | www.uto.edu.bo'), 0, 1, 'C');

        $this->Ln(4);
        $this->SetDrawColor(0, 0, 0);
        $this->Line(15, $this->GetY(), $this->GetPageWidth() - 15, $this->GetY());
        $this->Ln(6);

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

    protected function seccionTitulo(string $texto): void
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetDrawColor(0, 100, 200);
        $this->SetFillColor(240, 245, 255);
        $this->Cell(0, 7, utf8_decode($texto), 0, 1, 'L', true);
        $this->Ln(2);
    }

    protected function tablaProductos(array $ventasPorProducto): void
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

    protected function tablaResumenFinanciero(array $resumen, float $total, string $tipo): void
    {
        $wTipo  = 90;
        $wMonto = 45;

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 230, 245);
        $this->Cell($wTipo,  6, utf8_decode('Tipo de Pago'), 1, 0, 'C', true);
        $this->Cell($wMonto, 6, utf8_decode('Monto (Bs.)'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 10);
        $tipos = match($tipo) {
            'contado' => ['Contado'],
            'credito' => ['Credito'],
            default   => ['Contado', 'Credito'],
        };

        foreach ($tipos as $t) {
            if (isset($resumen[$t]) && $resumen[$t] > 0) {
                $this->Cell($wTipo,  6, $t, 1, 0, 'L');
                $this->Cell($wMonto, 6, number_format($resumen[$t], 2, ',', '.'), 1, 1, 'R');
            }
        }

        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(255, 240, 200);
        $this->Cell($wTipo,  7, utf8_decode('TOTAL GENERAL'), 1, 0, 'L', true);
        $this->Cell($wMonto, 7, number_format($total, 2, ',', '.'), 1, 1, 'R', true);
    }

    protected function tablaAcumuladoClientes(array $acumuladoClientes): void
    {
        $w = [80, 60, 40];
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 230, 245);
        $this->Cell($w[0], 6, utf8_decode('Cliente'), 1, 0, 'C', true);
        $this->Cell($w[1], 6, utf8_decode('Nros. Venta'), 1, 0, 'C', true);
        $this->Cell($w[2], 6, utf8_decode('Total Acumulado (Bs.)'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 9);
        foreach ($acumuladoClientes as $cliente => $data) {
            $idsStr    = implode(', ', $data['ids']);
            $nbIds     = $this->NbLines($w[1], $idsStr);
            $nbCliente = $this->NbLines($w[0], utf8_decode($cliente));
            $h         = 6 * max($nbIds, $nbCliente);

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

    protected function mostrarVentas(array $ventas, bool $esCredito): void
    {
        $wVenta = [40, 70, 40, 30];
        $wItem  = [15, 95, 35, 35];

        foreach ($ventas as $venta) {
            if ($this->GetY() > $this->GetPageHeight() - 50) {
                $this->AddPage();
            }

            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(230, 240, 255);
            $this->Cell($wVenta[0], 6, utf8_decode("Nro: {$venta['Nro']}"), 1, 0, 'L', true);
            $this->Cell($wVenta[1], 6, utf8_decode("Cliente: {$venta['cliente']}"), 1, 0, 'L', true);
            $this->Cell($wVenta[2], 6, utf8_decode("Tipo: {$venta['tipo_pago']}"), 1, 0, 'L', true);
            $this->Cell($wVenta[3], 6, utf8_decode('Bs. ' . number_format($venta['monto_total'], 2, ',', '.')), 1, 1, 'R', true);

            $this->SetFont('Arial', '', 9);
            $this->SetFillColor(245, 245, 245);
            $this->Cell(0, 5, utf8_decode('Fecha: ' . date('d/m/Y H:i', strtotime($venta['fecha']))), 1, 1, 'L', true);

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
                if (!empty($venta['receptor_categoria'])) {
                    $this->Cell($w[0], 5, '', 1, 0, 'R', true);
                    $this->Cell($w[1], 5, '', 1, 0, 'L', true);
                    $this->Cell($w[2], 5, utf8_decode('Categoría:'), 1, 0, 'R', true);
                    $this->Cell($w[3], 5, utf8_decode($venta['receptor_categoria']), 1, 1, 'L', true);
                }
            }

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
                    if (!is_array($item) || !isset($item['producto'])) continue;

                    $x = $this->GetX();
                    $y = $this->GetY();

                    $this->SetX($x + $wItem[0]);
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

    /**
     * Renderiza el resumen financiero al final del reporte.
     * Solo debe llamarse para tipo 'contado' o 'credito', nunca para 'general'.
     *
     * @param array $ventasAgrupadas  Array de ventas ya procesadas (con 'cliente', 'total_venta', 'items')
     * @param array $productosUnicos  Array de productos únicos (con 'total_cantidad')
     * @param float $totalGeneralBs   Monto total del reporte
     */
    protected function renderResumenFinanciero(
        array $ventasAgrupadas,
        array $productosUnicos,
        float $totalGeneralBs
    ): void {
        if (empty($ventasAgrupadas)) return;

        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // ── Calcular indicadores desde datos en memoria ───────────────────────
        $nVentas    = count($ventasAgrupadas);
        $ticketProm = $nVentas > 0 ? $totalGeneralBs / $nVentas : 0;
        $montos     = array_column($ventasAgrupadas, 'total_venta');
        $ventaMin   = !empty($montos) ? min($montos) : 0;
        $ventaMax   = !empty($montos) ? max($montos) : 0;

        // Producto más vendido por cantidad
        $prodMaxCant  = '';
        $prodMaxQ     = 0;
        foreach ($productosUnicos as $nombre => $info) {
            if ($info['total_cantidad'] > $prodMaxQ) {
                $prodMaxQ    = $info['total_cantidad'];
                $prodMaxCant = $nombre;
            }
        }

        // Top 5 clientes por monto acumulado
        $topClientes = [];
        foreach ($ventasAgrupadas as $venta) {
            $nombre = $venta['cliente'] ?: 'Consumidor Final';
            if (!isset($topClientes[$nombre])) {
                $topClientes[$nombre] = ['n' => 0, 'monto' => 0.0];
            }
            $topClientes[$nombre]['n']++;
            $topClientes[$nombre]['monto'] += $venta['total_venta'];
        }
        uasort($topClientes, fn($a, $b) => $b['monto'] <=> $a['monto']);
        $topClientes = array_slice($topClientes, 0, 5, true);

        // ── Salto de página si no hay espacio suficiente ──────────────────────
        $alturaEstimada = 10 + 8 * 6 + 8 + (count($topClientes) + 1) * 6;
        if ($this->GetY() + $alturaEstimada > $this->GetPageHeight() - 20) {
            $this->AddPage($this->CurOrientation);
        }

        $this->Ln(6);

        // ── BLOQUE 1 — Indicadores de ventas ─────────────────────────────────
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(46, 80, 144); // azul institucional
        $this->SetTextColor(255, 255, 255);
        $this->Cell($pageW, 6, utf8_decode('  RESUMEN FINANCIERO'), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);

        $wLabel = (int)round($pageW * 0.45);
        $wVal   = $pageW - $wLabel;

        $indicadores = [
            [utf8_decode('N° de ventas'),                    (string)$nVentas],
            [utf8_decode('Monto total'),                     'Bs. ' . number_format($totalGeneralBs, 2, ',', '.')],
            [utf8_decode('Ticket promedio'),                 'Bs. ' . number_format($ticketProm, 2, ',', '.')],
            [utf8_decode('Venta mínima'),                    'Bs. ' . number_format($ventaMin, 2, ',', '.')],
            [utf8_decode('Venta máxima'),                    'Bs. ' . number_format($ventaMax, 2, ',', '.')],
            [utf8_decode('Producto más vendido (cantidad)'), utf8_decode($prodMaxCant) . ' (' . $prodMaxQ . ' uds)'],
        ];

        $par = false;
        foreach ($indicadores as [$label, $valor]) {
            $this->SetFillColor($par ? 245 : 255, $par ? 245 : 255, $par ? 245 : 255);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell($wLabel, 5, $label, 1, 0, 'L', true);
            $this->SetFont('Arial', '', 8);
            $this->Cell($wVal,   5, $valor, 1, 1, 'R', true);
            $par = !$par;
        }

        $this->Ln(4);

        // ── BLOQUE 2 — Top 5 clientes ─────────────────────────────────────────
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(46, 80, 144);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($pageW, 6, utf8_decode('  TOP 5 CLIENTES POR MONTO'), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        $wNombre  = (int)round($pageW * 0.60);
        $wNVentas = (int)round($pageW * 0.15);
        $wMonto   = $pageW - $wNombre - $wNVentas;

        // Encabezado
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(220, 220, 220);
        $this->Cell($wNombre,  5, utf8_decode('CLIENTE'),    1, 0, 'C', true);
        $this->Cell($wNVentas, 5, utf8_decode('N° VENTAS'),  1, 0, 'C', true);
        $this->Cell($wMonto,   5, utf8_decode('MONTO (Bs.)'),1, 1, 'C', true);

        // Filas
        $this->SetFont('Arial', '', 8);
        $par = false;
        foreach ($topClientes as $nombre => $data) {
            $this->SetFillColor($par ? 245 : 255, $par ? 245 : 255, $par ? 245 : 255);
            $this->Cell($wNombre,  5, utf8_decode(substr($nombre, 0, 45)), 1, 0, 'L', true);
            $this->Cell($wNVentas, 5, (string)$data['n'],                  1, 0, 'C', true);
            $this->Cell($wMonto,   5, number_format($data['monto'], 2, ',', '.'), 1, 1, 'R', true);
            $par = !$par;
        }
    }

    protected function numeroALiteral(float $monto): string
    {
        if (class_exists('NumberFormatter')) {
            $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
            return strtoupper($formatter->format($monto));
        }
        return number_format($monto, 2) . ' (Literal no disponible)';
    }

    protected function NbLines($w, $txt)
    {
        $cw   = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s    = str_replace("\r", '', $txt);
        $nb   = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) { if ($i == $j) $i++; } else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}
