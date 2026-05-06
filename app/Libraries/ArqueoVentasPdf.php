<?php

namespace App\Libraries;

class ArqueoVentasPdf extends CierreVentaBasePdf
{
    // ── Versiones compactas de los métodos heredados ──────────────────────────
    // Se sobreescriben aquí para no afectar otros reportes que usan la clase base.

    protected function seccionTitulo(string $texto): void
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetDrawColor(0, 100, 200);
        $this->SetFillColor(240, 245, 255);
        $this->Cell(0, 5, utf8_decode($texto), 0, 1, 'L', true);
        $this->Ln(1);
    }

    protected function tablaProductos(array $ventasPorProducto): void
    {
        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // Anchos: Producto(35%) | Cant.Co(8%) | Monto Co(15%) | Cant.Cr(8%) | Monto Cr(15%) | Cant.Tot(8%) | Total(11%)
        $wProd  = (int)round($pageW * 0.35);
        $wCantC = (int)round($pageW * 0.08);
        $wMonC  = (int)round($pageW * 0.15);
        $wCantR = (int)round($pageW * 0.08);
        $wMonR  = (int)round($pageW * 0.15);
        $wCantT = (int)round($pageW * 0.08);
        $wMonT  = $pageW - $wProd - $wCantC - $wMonC - $wCantR - $wMonR - $wCantT; // resto

        // Fila 1 de encabezado: grupos
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(180, 200, 235);
        $this->Cell($wProd,           5, utf8_decode('PRODUCTO'),  1, 0, 'C', true);
        $this->Cell($wCantC + $wMonC, 5, utf8_decode('CONTADO'),   1, 0, 'C', true);
        $this->Cell($wCantR + $wMonR, 5, utf8_decode('CRÉDITO'),   1, 0, 'C', true);
        $this->Cell($wCantT + $wMonT, 5, utf8_decode('TOTAL'),     1, 1, 'C', true);

        // Fila 2 de encabezado: sub-columnas
        $this->SetFillColor(220, 230, 245);
        $this->Cell($wProd,  5, '',                        1, 0, 'C', true);
        $this->Cell($wCantC, 5, utf8_decode('Cant.'),      1, 0, 'C', true);
        $this->Cell($wMonC,  5, utf8_decode('Monto (Bs.)'),1, 0, 'C', true);
        $this->Cell($wCantR, 5, utf8_decode('Cant.'),      1, 0, 'C', true);
        $this->Cell($wMonR,  5, utf8_decode('Monto (Bs.)'),1, 0, 'C', true);
        $this->Cell($wCantT, 5, utf8_decode('Cant.'),      1, 0, 'C', true);
        $this->Cell($wMonT,  5, utf8_decode('Monto (Bs.)'),1, 1, 'C', true);

        // Filas de datos + acumuladores para totales
        $this->SetFont('Arial', '', 8);
        $totCantC = 0; $totMonC = 0.0;
        $totCantR = 0; $totMonR = 0.0;
        $totCantT = 0; $totMonT = 0.0;

        foreach ($ventasPorProducto as $producto => $data) {
            $cantC = $data['cantidad_contado'] ?? 0;
            $monC  = $data['monto_contado']    ?? 0.0;
            $cantR = $data['cantidad_credito']  ?? 0;
            $monR  = $data['monto_credito']     ?? 0.0;
            $cantT = $data['cantidad_total'];
            $monT  = $data['monto_total'];

            $this->Cell($wProd,  5, $producto,                                    1, 0, 'L');
            $this->Cell($wCantC, 5, $cantC > 0 ? $cantC : '-',                   1, 0, 'C');
            $this->Cell($wMonC,  5, $cantC > 0 ? number_format($monC, 2, ',', '.') : '-', 1, 0, 'R');
            $this->Cell($wCantR, 5, $cantR > 0 ? $cantR : '-',                   1, 0, 'C');
            $this->Cell($wMonR,  5, $cantR > 0 ? number_format($monR, 2, ',', '.') : '-', 1, 0, 'R');
            $this->Cell($wCantT, 5, $cantT,                                       1, 0, 'C');
            $this->Cell($wMonT,  5, number_format($monT, 2, ',', '.'),            1, 1, 'R');

            $totCantC += $cantC; $totMonC += $monC;
            $totCantR += $cantR; $totMonR += $monR;
            $totCantT += $cantT; $totMonT += $monT;
        }

        // Fila de totales
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(255, 240, 200);
        $this->Cell($wProd,  5, utf8_decode('TOTAL'),                              1, 0, 'L', true);
        $this->Cell($wCantC, 5, $totCantC,                                         1, 0, 'C', true);
        $this->Cell($wMonC,  5, number_format($totMonC, 2, ',', '.'),              1, 0, 'R', true);
        $this->Cell($wCantR, 5, $totCantR,                                         1, 0, 'C', true);
        $this->Cell($wMonR,  5, number_format($totMonR, 2, ',', '.'),              1, 0, 'R', true);
        $this->Cell($wCantT, 5, $totCantT,                                         1, 0, 'C', true);
        $this->Cell($wMonT,  5, number_format($totMonT, 2, ',', '.'),              1, 1, 'R', true);
    }

    protected function tablaResumenFinanciero(array $resumen, float $total, string $tipo): void
    {
        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // Anchos: Tipo(40%) | N° Ventas(20%) | Monto(40%)
        $wTipo  = (int)round($pageW * 0.40);
        $wN     = (int)round($pageW * 0.20);
        $wMonto = $pageW - $wTipo - $wN;

        // Encabezado
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(220, 230, 245);
        $this->Cell($wTipo,  5, utf8_decode('Tipo de Pago'),  1, 0, 'C', true);
        $this->Cell($wN,     5, utf8_decode('N° Ventas'),     1, 0, 'C', true);
        $this->Cell($wMonto, 5, utf8_decode('Monto (Bs.)'),   1, 1, 'C', true);

        $this->SetFont('Arial', '', 8);
        $tipos = match($tipo) {
            'contado' => ['Contado'],
            'credito' => ['Credito'],
            default   => ['Contado', 'Credito'],
        };

        $nMap = ['Contado' => $resumen['n_contado'] ?? 0, 'Credito' => $resumen['n_credito'] ?? 0];

        foreach ($tipos as $t) {
            if (isset($resumen[$t]) && $resumen[$t] > 0) {
                $this->Cell($wTipo,  5, $t,                                          1, 0, 'L');
                $this->Cell($wN,     5, $nMap[$t],                                   1, 0, 'C');
                $this->Cell($wMonto, 5, number_format($resumen[$t], 2, ',', '.'),    1, 1, 'R');
            }
        }

        // Fila total
        $nTotal = ($resumen['n_contado'] ?? 0) + ($resumen['n_credito'] ?? 0);
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(255, 240, 200);
        $this->Cell($wTipo,  6, utf8_decode('TOTAL GENERAL'),              1, 0, 'L', true);
        $this->Cell($wN,     6, $nTotal,                                   1, 0, 'C', true);
        $this->Cell($wMonto, 6, number_format($total, 2, ',', '.'),        1, 1, 'R', true);
    }

    protected function tablaAcumuladoClientes(array $acumuladoClientes): void
    {
        // Delegamos a la nueva implementación por grupo — este método ya no se usa directamente
        // pero se mantiene para compatibilidad con la clase base.
    }

    private function renderTablaAcumulado(string $titulo, array $grupo): void
    {
        $pageW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // Anchos: Cliente(50%) | N° Ventas(15%) | Total(35%)
        $wCli = (int)round($pageW * 0.50);
        $wN   = (int)round($pageW * 0.15);
        $wTot = $pageW - $wCli - $wN;

        // Sub-título de la tabla
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(200, 215, 240);
        $this->Cell($pageW, 5, utf8_decode($titulo), 1, 1, 'L', true);

        // Encabezado de columnas
        $this->SetFillColor(220, 230, 245);
        $this->Cell($wCli, 5, utf8_decode('CLIENTE'),      1, 0, 'C', true);
        $this->Cell($wN,   5, utf8_decode('N° VENTAS'),    1, 0, 'C', true);
        $this->Cell($wTot, 5, utf8_decode('TOTAL (Bs.)'),  1, 1, 'C', true);

        if (empty($grupo)) {
            $this->SetFont('Arial', 'I', 8);
            $this->Cell($pageW, 5, utf8_decode('  Sin registros.'), 1, 1, 'L');
            return;
        }

        $totN = 0; $totMonto = 0.0;

        foreach ($grupo as $clienteKey => $data) {
            $esSinCliente = ($clienteKey === '__SIN_CLIENTE__');
            $clienteLabel = $esSinCliente
                ? utf8_decode('Ventas sin cliente registrado')
                : utf8_decode($clienteKey);

            if ($this->GetY() + 9 > $this->GetPageHeight() - 25) {
                $this->AddPage();
                // Repetir encabezado
                $this->SetFont('Arial', 'B', 8);
                $this->SetFillColor(200, 215, 240);
                $this->Cell($pageW, 5, utf8_decode($titulo), 1, 1, 'L', true);
                $this->SetFillColor(220, 230, 245);
                $this->Cell($wCli, 5, utf8_decode('CLIENTE'),     1, 0, 'C', true);
                $this->Cell($wN,   5, utf8_decode('N° VENTAS'),   1, 0, 'C', true);
                $this->Cell($wTot, 5, utf8_decode('TOTAL (Bs.)'), 1, 1, 'C', true);
            }

            // Fila principal
            $fillColor = $esSinCliente ? [255, 248, 220] : [245, 248, 255];
            $this->SetFillColor(...$fillColor);
            $this->SetFont('Arial', $esSinCliente ? 'I' : '', 8);
            $this->Cell($wCli, 5, $clienteLabel,                              1, 0, 'L', true);
            $this->SetFont('Arial', '', 8);
            $this->Cell($wN,   5, $data['n'],                                 1, 0, 'C', true);
            $this->Cell($wTot, 5, number_format($data['total'], 2, ',', '.'), 1, 1, 'R', true);

            // Sub-fila de códigos
            $idsStr = implode('  |  ', $data['ids']);
            $this->SetFont('Arial', '', 6);
            $this->SetFillColor(235, 240, 250);
            $this->Cell($pageW, 4, utf8_decode('  Ventas: ' . $idsStr), 1, 1, 'L', true);

            $totN     += $data['n'];
            $totMonto += $data['total'];
        }

        // Fila de totales
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(255, 240, 200);
        $this->Cell($wCli, 5, utf8_decode('TOTAL'),                    1, 0, 'L', true);
        $this->Cell($wN,   5, $totN,                                   1, 0, 'C', true);
        $this->Cell($wTot, 5, number_format($totMonto, 2, ',', '.'),   1, 1, 'R', true);
    }

    protected function mostrarVentas(array $ventas, bool $esCredito): void
    {
        // Ancho útil real de la página (márgenes 10mm c/lado en A4 = 190mm)
        $pageW  = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // Encabezado de tarjeta: proporciones Nro(18%) | Cliente(38%) | Tipo(20%) | Monto(24%)
        $wVenta = [
            (int)round($pageW * 0.18),
            (int)round($pageW * 0.38),
            (int)round($pageW * 0.20),
            0, // última celda toma el resto para cerrar exacto
        ];
        $wVenta[3] = $pageW - $wVenta[0] - $wVenta[1] - $wVenta[2];

        // Ítems: Cant(7%) | Producto(52%) | Precio(20%) | Subtotal(21%)
        $wItem = [
            (int)round($pageW * 0.07),
            (int)round($pageW * 0.52),
            (int)round($pageW * 0.20),
            0,
        ];
        $wItem[3] = $pageW - $wItem[0] - $wItem[1] - $wItem[2];

        $wTotal = $pageW; // todas las filas usan el ancho completo disponible

        foreach ($ventas as $venta) {
            if ($this->GetY() > $this->GetPageHeight() - 40) {
                $this->AddPage();
            }

            // Encabezado de la tarjeta
            $this->SetFont('Arial', 'B', 8);
            $this->SetFillColor(230, 240, 255);
            $this->Cell($wVenta[0], 5, utf8_decode("Nro: {$venta['Nro']}"),                                              1, 0, 'L', true);
            $this->Cell($wVenta[1], 5, utf8_decode("Cliente: {$venta['cliente']}"),                                      1, 0, 'L', true);
            $this->Cell($wVenta[2], 5, utf8_decode("Tipo: {$venta['tipo_pago']}"),                                       1, 0, 'L', true);
            $this->Cell($wVenta[3], 5, utf8_decode('Bs. ' . number_format($venta['monto_total'], 2, ',', '.')),          1, 1, 'R', true);

            // Fila de fecha — mismo ancho que el encabezado
            $this->SetFont('Arial', '', 7);
            $this->SetFillColor(245, 245, 245);
            $this->Cell($wTotal, 4, utf8_decode('Fecha: ' . date('d/m/Y H:i', strtotime($venta['fecha']))), 1, 1, 'L', true);

            // Datos de crédito (CI, nombre, sección) — mismo ancho total
            if ($esCredito && !empty($venta['dip'])) {
                $this->SetFont('Arial', 'I', 7);
                $this->SetFillColor(250, 252, 240);
                // CI/nombre/sección: proporciones fijas, última celda cierra al ancho total
                $wCr = [20, 40, 45, $wTotal - 20 - 40 - 45];
                $this->Cell($wCr[0], 4, 'CI:',                                    1, 0, 'R', true);
                $this->Cell($wCr[1], 4, $venta['dip'],                            1, 0, 'L', true);
                $this->Cell($wCr[2], 4, utf8_decode('Nombre:'),                   1, 0, 'R', true);
                $this->Cell($wCr[3], 4, utf8_decode($venta['nombre_personal']),   1, 1, 'L', true);
                $this->Cell($wCr[0], 4, '',                                        1, 0, 'R', true);
                $this->Cell($wCr[1], 4, '',                                        1, 0, 'L', true);
                $this->Cell($wCr[2], 4, utf8_decode('Sección:'),                  1, 0, 'R', true);
                $this->Cell($wCr[3], 4, utf8_decode($venta['seccion']),           1, 1, 'L', true);
            }

            // Encabezado de ítems — mismo ancho total
            $this->SetFont('Arial', 'B', 7);
            $this->SetFillColor(220, 230, 245);
            $this->Cell($wItem[0], 4, utf8_decode('Cant.'),    1, 0, 'C', true);
            $this->Cell($wItem[1], 4, utf8_decode('Producto'), 1, 0, 'L', true);
            $this->Cell($wItem[2], 4, utf8_decode('Precio'),   1, 0, 'R', true);
            $this->Cell($wItem[3], 4, utf8_decode('Subtotal'), 1, 1, 'R', true);

            // Filas de ítems
            $this->SetFont('Arial', '', 7);
            $lineHeight = 4;
            if (!empty($venta['items'])) {
                foreach ($venta['items'] as $item) {
                    if (!is_array($item) || !isset($item['producto'])) continue;
                    $x = $this->GetX(); $y = $this->GetY();
                    $this->SetX($x + $wItem[0]);
                    $this->MultiCell($wItem[1], $lineHeight, utf8_decode($item['producto']), 0, 'L');
                    $h = max($lineHeight, $this->GetY() - $y);
                    $this->SetXY($x, $y);
                    $this->Cell($wItem[0], $h, $item['cantidad'],                              1, 0, 'C');
                    $this->Cell($wItem[1], $h, '',                                             1, 0, 'L');
                    $this->Cell($wItem[2], $h, number_format($item['precio'],   2, ',', '.'),  1, 0, 'R');
                    $this->Cell($wItem[3], $h, number_format($item['subtotal'], 2, ',', '.'),  1, 1, 'R');
                }
            } else {
                $this->Cell($wTotal, 4, utf8_decode('Sin ítems registrados.'), 1, 1, 'C');
            }

            $this->Ln(3);
        }
    }

    // ── Lógica del arqueo ─────────────────────────────────────────────────────
    /**
     * Genera el reporte de cierre y arqueo con 3 secciones:
     * General, Contado y Crédito.
     */
    public function generarArqueo(array $reportData, array $filters): void
    {
        $fecha_inicio   = $filters['fecha_inicio'];
        $fecha_fin      = $filters['fecha_fin'];
        $nombreUsuario  = utf8_decode($filters['nombre_usuario'] ?? 'Usuario');
        $tituloModulo   = $filters['titulo_modulo'] ?? 'VENTAS GENERALES';
        $cargo          = utf8_decode($filters['responsable_cargo'] ?? 'Responsable de Ventas');

        $this->setReporteTitle('REPORTE DE CIERRE DE VENTAS Y ARQUEO ' . $tituloModulo);
        $this->AddPage('P', 'A4');
        $this->SetMargins(10, 10, 10);
        $this->AliasNbPages();

        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
                  '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $fechaTexto = 'Desde: ' . $fecha_inicio . ' hasta: ' . $fecha_fin;
        if ($fecha_inicio !== $fecha_fin) {
            $di = date('d', strtotime($fecha_inicio)); $mi = date('m', strtotime($fecha_inicio)); $yi = date('Y', strtotime($fecha_inicio));
            $df = date('d', strtotime($fecha_fin));    $mf = date('m', strtotime($fecha_fin));    $yf = date('Y', strtotime($fecha_fin));
            $fechaTexto = "Desde: $di de {$meses[$mi]} de $yi hasta: $df de {$meses[$mf]} de $yf";
        } else {
            $di = date('d', strtotime($fecha_inicio)); $mi = date('m', strtotime($fecha_inicio)); $yi = date('Y', strtotime($fecha_inicio));
            $fechaTexto = "Desde: $di de {$meses[$mi]} de $yi hasta: $di de {$meses[$mi]} de $yi";
        }

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 5, utf8_decode('Período del Reporte'), 0, 1, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, utf8_decode($fechaTexto), 0, 1, 'L');
        $this->Ln(3);

        // Separar datos por tipo
        $itemsGeneral = $reportData;
        $itemsContado = array_filter($reportData, fn($i) => strtolower($i->tipo_pago) === 'contado');
        $itemsCredito = array_filter($reportData, fn($i) => strtolower($i->tipo_pago) === 'credito');

        // ── SECCIÓN GENERAL ──────────────────────────────────────────────────
        $this->seccionTitulo('1. Resumen General (Contado + Crédito)');
        [$productos, $ventas, $resumen, $total] = $this->procesarDatos($itemsGeneral);
        $this->tablaProductos($productos);
        $this->Ln(4);
        $this->tablaResumenFinanciero($resumen, $total, 'ambos');
        $this->Ln(6);

        // ── SECCIÓN CONTADO ──────────────────────────────────────────────────
        $this->seccionTitulo('2. Ventas al Contado');
        if (!empty($itemsContado)) {
            [$prodCo, $ventasCo, $resumenCo, $totalCo] = $this->procesarDatos($itemsContado);
            $this->mostrarVentas($ventasCo, false);
            $this->Ln(1);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(0, 4, utf8_decode('Total Contado: Bs. ' . number_format($totalCo, 2, ',', '.')), 0, 1, 'R');
        } else {
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 6, utf8_decode('Sin ventas al contado en el período.'), 0, 1, 'L');
        }
        $this->Ln(4);

        // ── SECCIÓN CRÉDITO ──────────────────────────────────────────────────
        $this->seccionTitulo('3. Ventas a Crédito');
        if (!empty($itemsCredito)) {
            [$prodCr, $ventasCr, $resumenCr, $totalCr] = $this->procesarDatos($itemsCredito);
            $this->mostrarVentas($ventasCr, true);
            $this->Ln(1);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(0, 4, utf8_decode('Total Crédito: Bs. ' . number_format($totalCr, 2, ',', '.')), 0, 1, 'R');
        } else {
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 6, utf8_decode('Sin ventas a crédito en el período.'), 0, 1, 'L');
        }
        $this->Ln(4);

        // ── ACUMULADO POR CLIENTE ────────────────────────────────────────────
        $this->seccionTitulo('4. Acumulado por Cliente');
        $acumulado = $this->calcularAcumuladoClientes($itemsGeneral);
        $this->renderTablaAcumulado('4a. Ventas al Contado por Cliente', $acumulado['contado']);
        $this->Ln(3);
        $this->renderTablaAcumulado('4b. Ventas a Crédito por Cliente',  $acumulado['credito']);
        $this->Ln(6);

        // ── FIRMA ────────────────────────────────────────────────────────────
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, utf8_decode('Total General: Bs. ' . number_format($total, 2, ',', '.') . ' - ' . $this->numeroALiteral($total) . ' BOLIVIANOS'), 0, 1, 'L');
        $this->Ln(12);
        $pageW  = $this->GetPageWidth();
        $firmaW = 70;
        $firmaX = ($pageW - $firmaW) / 2;
        $this->Line($firmaX, $this->GetY(), $firmaX + $firmaW, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, $nombreUsuario, 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 5, $cargo, 0, 1, 'C');

        $this->Output('D', 'arqueo_ventas_' . $fecha_inicio . '_' . $fecha_fin . '.pdf');
    }

    /**
     * Procesa un subconjunto de $reportData y devuelve
     * [productos, ventas formateadas para mostrarVentas, resumen financiero, total].
     */
    private function procesarDatos(array $items): array
    {
        $productos = [];
        $ventasMap = [];
        $resumen   = [
            'Contado' => 0, 'Credito' => 0,
            'n_contado' => 0, 'n_credito' => 0,  // contadores de ventas únicas
        ];
        $total     = 0;

        foreach ($items as $item) {
            if ($item->estado_venta != 1) continue;

            $prodNombre = trim(preg_replace('/\s+/', ' ', str_replace(['(', ')'], '', $item->producto_nombre ?? '')));
            if (empty($prodNombre)) continue;
            $prodNombreDecoded = utf8_decode($prodNombre);

            if (!isset($productos[$prodNombreDecoded])) {
                $productos[$prodNombreDecoded] = [
                    'cantidad_total'   => 0, 'monto_total'   => 0.0,
                    'cantidad_contado' => 0, 'monto_contado' => 0.0,
                    'cantidad_credito' => 0, 'monto_credito' => 0.0,
                ];
            }

            $esContado = strtolower($item->tipo_pago) === 'contado';
            $productos[$prodNombreDecoded]['cantidad_total'] += $item->cantidad;
            $productos[$prodNombreDecoded]['monto_total']    += $item->subtotal_item;
            if ($esContado) {
                $productos[$prodNombreDecoded]['cantidad_contado'] += $item->cantidad;
                $productos[$prodNombreDecoded]['monto_contado']    += $item->subtotal_item;
            } else {
                $productos[$prodNombreDecoded]['cantidad_credito'] += $item->cantidad;
                $productos[$prodNombreDecoded]['monto_credito']    += $item->subtotal_item;
            }

            $vid = $item->venta_id;
            if (!isset($ventasMap[$vid])) {
                $cliente = $item->cliente_nombre ?? 'Consumidor Final';
                if (stripos($cliente, 'Sin Nombre') !== false) $cliente = 'Consumidor Final';
                $tipoPago = ucfirst(strtolower($item->tipo_pago));
                $ventasMap[$vid] = [
                    'Nro'             => $item->codigo_venta ?? $vid,
                    'cliente'         => $cliente,
                    'tipo_pago'       => $tipoPago,
                    'monto_total'     => 0,
                    'fecha'           => $item->fecha_venta,
                    'dip'             => $item->personal_dip ?? '',
                    'nombre_personal' => $item->personal_nombre ?? '',
                    'seccion'         => $item->personal_seccion ?? '',
                    'items'           => [],
                ];
                // Contar venta única por tipo
                if (strtolower($item->tipo_pago) === 'contado') {
                    $resumen['n_contado']++;
                } else {
                    $resumen['n_credito']++;
                }
            }
            $ventasMap[$vid]['items'][] = [
                'producto' => $item->producto_nombre ?? '',
                'cantidad' => $item->cantidad,
                'precio'   => $item->precio_unitario,
                'subtotal' => $item->subtotal_item,
            ];
            $ventasMap[$vid]['monto_total'] += $item->subtotal_item;
            $total += $item->subtotal_item;

            $tipo = strtolower($item->tipo_pago);
            if ($tipo === 'contado') $resumen['Contado'] += $item->subtotal_item;
            else                     $resumen['Credito'] += $item->subtotal_item;
        }

        ksort($productos);
        return [$productos, array_values($ventasMap), $resumen, $total];
    }

    /**
     * Calcula el acumulado por cliente en el formato que espera tablaAcumuladoClientes.
     */
    private function calcularAcumuladoClientes(array $items): array
    {
        $ventaTotales  = [];
        $ventaCliente  = [];
        $ventaCodigo   = [];
        $ventaTipoPago = [];

        foreach ($items as $item) {
            if ($item->estado_venta != 1) continue;
            $vid = $item->venta_id;
            if (!isset($ventaTotales[$vid])) {
                $ventaTotales[$vid]  = 0;
                $cliente = $item->cliente_nombre ?? 'Consumidor Final';
                if (stripos($cliente, 'Sin Nombre') !== false || trim($cliente) === '') {
                    $cliente = '__SIN_CLIENTE__';
                }
                $ventaCliente[$vid]  = $cliente;
                $ventaCodigo[$vid]   = $item->codigo_venta ?? (string)$vid;
                $ventaTipoPago[$vid] = strtolower($item->tipo_pago);
            }
            $ventaTotales[$vid] += $item->subtotal_item;
        }

        $contado = [];
        $credito = [];

        foreach ($ventaTotales as $vid => $montoVenta) {
            $cliente  = $ventaCliente[$vid];
            $code     = $ventaCodigo[$vid];
            $tipoPago = $ventaTipoPago[$vid];

            if ($tipoPago === 'contado') {
                if (!isset($contado[$cliente])) {
                    $contado[$cliente] = ['ids' => [], 'total' => 0.0, 'n' => 0];
                }
                if (!in_array($code, $contado[$cliente]['ids'])) {
                    $contado[$cliente]['ids'][] = $code;
                }
                $contado[$cliente]['total'] += $montoVenta;
                $contado[$cliente]['n']++;
            } else {
                if (!isset($credito[$cliente])) {
                    $credito[$cliente] = ['ids' => [], 'total' => 0.0, 'n' => 0];
                }
                if (!in_array($code, $credito[$cliente]['ids'])) {
                    $credito[$cliente]['ids'][] = $code;
                }
                $credito[$cliente]['total'] += $montoVenta;
                $credito[$cliente]['n']++;
            }
        }

        $ordenar = function (array &$g): void {
            uksort($g, function ($a, $b) {
                if ($a === '__SIN_CLIENTE__') return 1;
                if ($b === '__SIN_CLIENTE__') return -1;
                return strcmp($a, $b);
            });
        };
        $ordenar($contado);
        $ordenar($credito);

        return ['contado' => $contado, 'credito' => $credito];
    }
}
