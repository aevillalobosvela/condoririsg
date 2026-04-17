<?php

namespace App\Libraries;

class ArqueoVentasPdf extends CierreVentaBasePdf
{
    /**
     * Genera el reporte de cierre y arqueo con 3 secciones:
     * General, Contado y Crédito.
     *
     * @param array  $reportData  Resultado de getDailySalesReportData (todos los tipos)
     * @param array  $filters     fecha_inicio, fecha_fin, nombre_usuario, titulo_modulo, responsable_cargo
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

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, utf8_decode('Período del Reporte'), 0, 1, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, utf8_decode($fechaTexto), 0, 1, 'L');
        $this->Ln(4);

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
            $this->Ln(2);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(0, 5, utf8_decode('Total Contado: Bs. ' . number_format($totalCo, 2, ',', '.')), 0, 1, 'R');
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
            $this->Ln(2);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(0, 5, utf8_decode('Total Crédito: Bs. ' . number_format($totalCr, 2, ',', '.')), 0, 1, 'R');
        } else {
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 6, utf8_decode('Sin ventas a crédito en el período.'), 0, 1, 'L');
        }
        $this->Ln(4);

        // ── ACUMULADO POR CLIENTE ────────────────────────────────────────────
        $this->seccionTitulo('4. Acumulado por Cliente');
        $acumulado = $this->calcularAcumuladoClientes($itemsGeneral);
        if (!empty($acumulado)) {
            $this->tablaAcumuladoClientes($acumulado);
        } else {
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 6, utf8_decode('Sin datos.'), 0, 1, 'L');
        }
        $this->Ln(6);

        // ── FIRMA ────────────────────────────────────────────────────────────
        $this->SetFont('Arial', 'B', 10);
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
        $resumen   = ['Contado' => 0, 'Credito' => 0];
        $total     = 0;

        foreach ($items as $item) {
            if ($item->estado_venta != 1) continue;

            $prodNombre = trim(preg_replace('/\s+/', ' ', str_replace(['(', ')'], '', $item->producto_nombre ?? '')));
            if (empty($prodNombre)) continue;
            $prodNombreDecoded = utf8_decode($prodNombre);

            if (!isset($productos[$prodNombreDecoded])) {
                $productos[$prodNombreDecoded] = ['cantidad_total' => 0, 'monto_total' => 0];
            }
            $productos[$prodNombreDecoded]['cantidad_total'] += $item->cantidad;
            $productos[$prodNombreDecoded]['monto_total']    += $item->subtotal_item;

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
            }
            $ventasMap[$vid]['items'][] = [
                'producto'  => $item->producto_nombre ?? '',
                'cantidad'  => $item->cantidad,
                'precio'    => $item->precio_unitario,
                'subtotal'  => $item->subtotal_item,
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
        // Agrupar por venta primero para evitar doble conteo de subtotales
        $ventaTotales = [];
        $ventaCliente = [];
        $ventaCodigo  = [];
        foreach ($items as $item) {
            if ($item->estado_venta != 1) continue;
            $vid = $item->venta_id;
            if (!isset($ventaTotales[$vid])) {
                $ventaTotales[$vid] = 0;
                $cliente = $item->cliente_nombre ?? 'Consumidor Final';
                if (stripos($cliente, 'Sin Nombre') !== false) $cliente = 'Consumidor Final';
                $ventaCliente[$vid] = $cliente; // mantener UTF-8, tablaAcumuladoClientes hace utf8_decode
                $ventaCodigo[$vid]  = $item->codigo_venta ?? (string)$vid;
            }
            $ventaTotales[$vid] += $item->subtotal_item;
        }
        $acumulado = [];
        foreach ($ventaTotales as $vid => $montoVenta) {
            $cliente = $ventaCliente[$vid];
            $code    = $ventaCodigo[$vid];
            if (!isset($acumulado[$cliente])) {
                $acumulado[$cliente] = ['ids' => [], 'total' => 0];
            }
            if (!in_array($code, $acumulado[$cliente]['ids'])) {
                $acumulado[$cliente]['ids'][] = $code;
            }
            $acumulado[$cliente]['total'] += $montoVenta;
        }
        return $acumulado;
    }
}
