<?php

namespace App\Services\Shared;

/**
 * ResumenGlobalService
 * Genera un Excel con 4 hojas: Producción, Ventas, Envíos y Balance.
 * Cada hoja muestra datos agrupados por mes para el rango indicado.
 */
class ResumenGlobalService
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // ── Punto de entrada ──────────────────────────────────────────────────────

    public function exportar(string $fecha_inicio, string $fecha_fin): void
    {
        $produccion = $this->queryProduccion($fecha_inicio, $fecha_fin);
        $ventas     = $this->queryVentas($fecha_inicio, $fecha_fin);
        $envios     = $this->queryEnvios($fecha_inicio, $fecha_fin);
        $balance    = $this->calcularBalance($produccion, $ventas, $envios);

        $filename = 'resumen_global_' . str_replace('-', '', $fecha_inicio)
                  . '_' . str_replace('-', '', $fecha_fin) . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
           . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        $this->definirEstilos();

        // Una sola hoja — todos los bloques apilados verticalmente
        echo '<Worksheet ss:Name="Resumen Global">' . "\n";
        echo '<Table>' . "\n";
        // 8 columnas (el bloque Ventas es el más ancho)
        foreach ([130, 80, 90, 90, 110, 90, 110, 110] as $w) {
            echo '<Column ss:Width="' . $w . '"/>' . "\n";
        }

        $periodo = $this->periodoTexto($fecha_inicio, $fecha_fin);
        $merge   = 7; // MergeAcross = cols - 1

        // Encabezado global
        echo '<Row ss:Height="26"><Cell ss:MergeAcross="' . $merge . '" ss:StyleID="rg_titulo"><Data ss:Type="String">RESUMEN GLOBAL CONDORIRI</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="' . $merge . '" ss:StyleID="rg_subtitulo"><Data ss:Type="String">' . htmlspecialchars($periodo, ENT_XML1) . '</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="' . $merge . '" ss:StyleID="rg_subtitulo"><Data ss:Type="String">Generado: ' . date('d/m/Y H:i') . '</Data></Cell></Row>' . "\n";
        echo '<Row></Row>' . "\n";

        $this->bloqueProduccion($produccion);
        echo '<Row></Row>' . "\n";
        $this->indicadoresProduccion($produccion, $fecha_inicio, $fecha_fin);
        echo '<Row></Row>' . "\n";
        $this->bloqueVentas($ventas);
        echo '<Row></Row>' . "\n";
        $this->indicadoresVentas($ventas, $fecha_inicio, $fecha_fin);
        echo '<Row></Row>' . "\n";
        $this->bloqueEnvios($envios);
        echo '<Row></Row>' . "\n";
        $this->indicadoresEnvios($envios, $fecha_inicio, $fecha_fin);
        echo '<Row></Row>' . "\n";
        $this->bloqueBalance($balance);

        echo '</Table></Worksheet>' . "\n";
        echo '</Workbook>';
        exit;
    }

    // ── Queries ───────────────────────────────────────────────────────────────

    private function queryProduccion(string $fi, string $ff): array
    {
        return $this->db->query("
            SELECT
                TO_CHAR(i.created_at, 'YYYY-MM')    AS mes,
                SUM(i.stock)                         AS litros_recibidos,
                COUNT(DISTINCT DATE(i.created_at))   AS dias_activos,
                SUM(p.cantidad_produccion)           AS unidades_producidas,
                COALESCE(SUM(p.merma), 0)            AS merma_total,
                COALESCE(SUM(p.agrega), 0)           AS agrega_total,
                COUNT(DISTINCT p.id)                 AS lotes_produccion
            FROM condoriri.inventarios i
            LEFT JOIN condoriri.productos p
                ON p.inventario_id = i.id AND p.deleted_at IS NULL
            WHERE i.deleted_at IS NULL
              AND i.nombre = 'LECHE'
              AND i.created_at >= ? AND i.created_at <= ?
            GROUP BY TO_CHAR(i.created_at, 'YYYY-MM')
            ORDER BY mes
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getResultArray();
    }

    private function queryVentas(string $fi, string $ff): array
    {
        // Módulo: ORURO-VENTAS=tienda, LACTEOS=planta, resto=agro (por producto_agro_id)
        return $this->db->query("
            SELECT
                TO_CHAR(v.created_at, 'YYYY-MM')    AS mes,
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM condoriri.detalle_venta dv2
                        WHERE dv2.venta_id = v.id AND dv2.producto_agro_id IS NOT NULL
                    ) THEN 'Agro'
                    WHEN s.nombre = 'ORURO-VENTAS' THEN 'Tienda'
                    ELSE 'Planta'
                END                                  AS modulo,
                COUNT(DISTINCT v.id)                 AS num_ventas,
                COUNT(DISTINCT CASE WHEN v.tipo_pago = 'contado' THEN v.id END) AS ventas_contado,
                COUNT(DISTINCT CASE WHEN v.tipo_pago = 'credito' THEN v.id END) AS ventas_credito,
                SUM(CASE WHEN v.tipo_pago = 'contado' THEN v.monto_total ELSE 0 END) AS ingresos_contado,
                SUM(CASE WHEN v.tipo_pago = 'credito' THEN v.monto_total ELSE 0 END) AS ingresos_credito,
                SUM(v.monto_total)                   AS ingresos_total
            FROM condoriri.ventas v
            JOIN condoriri.sucursales s ON s.id = v.sucursal_id
            WHERE v.deleted_at IS NULL
              AND v.created_at >= ? AND v.created_at <= ?
            GROUP BY TO_CHAR(v.created_at, 'YYYY-MM'), modulo
            ORDER BY mes, modulo
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getResultArray();
    }

    private function queryEnvios(string $fi, string $ff): array
    {
        return $this->db->query("
            SELECT
                TO_CHAR(e.created_at, 'YYYY-MM')    AS mes,
                COUNT(DISTINCT e.id)                 AS num_envios,
                COUNT(DISTINCT CASE WHEN e.estado_id = 9 THEN e.id END) AS entregados,
                COUNT(DISTINCT CASE WHEN e.estado_id = 2 THEN e.id END) AS en_transito,
                COUNT(DISTINCT CASE WHEN e.estado_id = 1 THEN e.id END) AS pendientes,
                COALESCE(SUM(tp.cantidad), 0)        AS unidades_enviadas
            FROM condoriri.envios e
            LEFT JOIN condoriri.transferencias_productos tp
                ON tp.envio_id = e.id AND tp.deleted_at IS NULL
            WHERE e.deleted_at IS NULL
              AND e.created_at >= ? AND e.created_at <= ?
            GROUP BY TO_CHAR(e.created_at, 'YYYY-MM')
            ORDER BY mes
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getResultArray();
    }

    /**
     * Cruza producción, ventas y envíos por mes.
     * Balance = producido + agrega - merma - vendido_total - enviado
     */
    private function calcularBalance(array $produccion, array $ventas, array $envios): array
    {
        // Indexar por mes
        $prod  = array_column($produccion, null, 'mes');
        $envio = array_column($envios,     null, 'mes');

        // Sumar ventas de todos los módulos por mes
        $ventasPorMes = [];
        foreach ($ventas as $v) {
            $mes = $v['mes'];
            if (!isset($ventasPorMes[$mes])) {
                $ventasPorMes[$mes] = ['num_ventas' => 0, 'ingresos_total' => 0.0];
            }
            $ventasPorMes[$mes]['num_ventas']     += (int)$v['num_ventas'];
            $ventasPorMes[$mes]['ingresos_total'] += (float)$v['ingresos_total'];
        }

        // Unir todos los meses conocidos
        $meses = array_unique(array_merge(
            array_keys($prod),
            array_keys($ventasPorMes),
            array_keys($envio)
        ));
        sort($meses);

        $balance = [];
        foreach ($meses as $mes) {
            $producido = (float)($prod[$mes]['unidades_producidas'] ?? 0);
            $merma     = (float)($prod[$mes]['merma_total']         ?? 0);
            $agrega    = (float)($prod[$mes]['agrega_total']        ?? 0);
            $vendido   = (float)($ventasPorMes[$mes]['ingresos_total'] ?? 0); // en Bs
            $enviado   = (int)($envio[$mes]['unidades_enviadas']    ?? 0);
            $nVentas   = (int)($ventasPorMes[$mes]['num_ventas']    ?? 0);

            $balance[] = [
                'mes'        => $mes,
                'producido'  => $producido,
                'merma'      => $merma,
                'agrega'     => $agrega,
                'n_ventas'   => $nVentas,
                'ingresos'   => $vendido,
                'enviado'    => $enviado,
                'balance_u'  => $producido + $agrega - $merma - $enviado, // unidades
            ];
        }

        return $balance;
    }

    // ── Estilos compartidos ───────────────────────────────────────────────────

    private function definirEstilos(): void
    {
        echo '<Styles>';
        // Encabezado institucional
        echo '<Style ss:ID="rg_titulo"><Font ss:Bold="1" ss:Size="14" ss:Color="#FFFFFF" ss:FontName="Calibri"/><Interior ss:Color="#1F4E79" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="rg_subtitulo"><Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Calibri"/><Interior ss:Color="#2E75B6" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        // Encabezado de grupo (banda de color por hoja)
        echo '<Style ss:ID="rg_grupo_prod"><Font ss:Bold="1" ss:Size="9" ss:Color="#FFFFFF"/><Interior ss:Color="#375623" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="rg_grupo_venta"><Font ss:Bold="1" ss:Size="9" ss:Color="#FFFFFF"/><Interior ss:Color="#1F4E79" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="rg_grupo_envio"><Font ss:Bold="1" ss:Size="9" ss:Color="#FFFFFF"/><Interior ss:Color="#7B3F00" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="rg_grupo_balance"><Font ss:Bold="1" ss:Size="9" ss:Color="#FFFFFF"/><Interior ss:Color="#4A235A" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        // Encabezado de columnas
        echo '<Style ss:ID="rg_th"><Font ss:Bold="1" ss:Size="8" ss:Color="#FFFFFF"/><Interior ss:Color="#5B9BD5" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/></Borders></Style>';
        // Filas de datos
        echo '<Style ss:ID="rg_mes"><Font ss:Bold="1" ss:Size="8"/><Interior ss:Color="#DEEAF1" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#AAAAAA"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#AAAAAA"/></Borders></Style>';
        echo '<Style ss:ID="rg_par"><Font ss:Size="8"/><Interior ss:Color="#EBF3FB" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/></Borders></Style>';
        echo '<Style ss:ID="rg_impar"><Font ss:Size="8"/><Interior ss:Color="#F5F9FE" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/></Borders></Style>';
        echo '<Style ss:ID="rg_num_par"><NumberFormat ss:Format="#,##0.00"/><Font ss:Size="8"/><Interior ss:Color="#EBF3FB" ss:Pattern="Solid"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/></Borders></Style>';
        echo '<Style ss:ID="rg_num_impar"><NumberFormat ss:Format="#,##0.00"/><Font ss:Size="8"/><Interior ss:Color="#F5F9FE" ss:Pattern="Solid"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/></Borders></Style>';
        echo '<Style ss:ID="rg_int_par"><NumberFormat ss:Format="#,##0"/><Font ss:Size="8"/><Interior ss:Color="#EBF3FB" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/></Borders></Style>';
        echo '<Style ss:ID="rg_int_impar"><NumberFormat ss:Format="#,##0"/><Font ss:Size="8"/><Interior ss:Color="#F5F9FE" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/></Borders></Style>';
        // Fila total
        echo '<Style ss:ID="rg_total_label"><Font ss:Bold="1" ss:Size="8" ss:Color="#FFFFFF"/><Interior ss:Color="#1F4E79" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="rg_total_num"><NumberFormat ss:Format="#,##0.00"/><Font ss:Bold="1" ss:Size="8" ss:Color="#FFFFFF"/><Interior ss:Color="#1F4E79" ss:Pattern="Solid"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="rg_total_int"><NumberFormat ss:Format="#,##0"/><Font ss:Bold="1" ss:Size="8" ss:Color="#FFFFFF"/><Interior ss:Color="#1F4E79" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        // Indicadores
        echo '<Style ss:ID="rg_ind_titulo"><Font ss:Bold="1" ss:Size="8" ss:Color="#FFFFFF"/><Interior ss:Color="#404040" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="rg_ind_label"><Font ss:Bold="1" ss:Size="8" ss:Color="#404040"/><Interior ss:Color="#F2F2F2" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/></Borders></Style>';
        echo '<Style ss:ID="rg_ind_val"><Font ss:Size="8" ss:Color="#1F4E79"/><Interior ss:Color="#F2F2F2" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/></Borders></Style>';
        echo '</Styles>' . "\n";
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function textoMes(string $mes): string
    {
        $meses = [
            '01' => 'Enero',    '02' => 'Febrero',   '03' => 'Marzo',
            '04' => 'Abril',    '05' => 'Mayo',       '06' => 'Junio',
            '07' => 'Julio',    '08' => 'Agosto',     '09' => 'Septiembre',
            '10' => 'Octubre',  '11' => 'Noviembre',  '12' => 'Diciembre',
        ];
        [$anio, $numMes] = explode('-', $mes);
        return ($meses[$numMes] ?? $numMes) . ' ' . $anio;
    }


    private function periodoTexto(string $fi, string $ff): string
    {
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
                  '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $di = date('d', strtotime($fi)); $mi = date('m', strtotime($fi)); $yi = date('Y', strtotime($fi));
        $df = date('d', strtotime($ff)); $mf = date('m', strtotime($ff)); $yf = date('Y', strtotime($ff));
        if ($fi === $ff) return "$di de {$meses[$mi]} de $yi";
        return "$di de {$meses[$mi]} de $yi al $df de {$meses[$mf]} de $yf";
    }

    private function th(string $label): void
    {
        echo '<Cell ss:StyleID="rg_th"><Data ss:Type="String">' . htmlspecialchars($label, ENT_XML1) . '</Data></Cell>';
    }

    private function td(string $val, bool $par, string $tipo = 'str'): void
    {
        $style = match($tipo) {
            'num' => $par ? 'rg_num_par' : 'rg_num_impar',
            'int' => $par ? 'rg_int_par' : 'rg_int_impar',
            default => $par ? 'rg_par'   : 'rg_impar',
        };
        $type = ($tipo === 'str') ? 'String' : 'Number';
        echo '<Cell ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . htmlspecialchars($val, ENT_XML1) . '</Data></Cell>';
    }

    // ── Bloque 1: Producción ─────────────────────────────────────────────────

    private function bloqueProduccion(array $data): void
    {
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="7" ss:StyleID="rg_grupo_prod"><Data ss:Type="String">1. PRODUCCIÓN MENSUAL</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="28">';
        foreach (['MES', 'LITROS RECIBIDOS', 'DÍAS ACTIVOS', 'PROM. DIARIO (L)', 'UNIDADES PRODUCIDAS', 'MERMA', 'AGREGA', 'LOTES'] as $h) { $this->th($h); }
        echo '</Row>' . "\n";

        $par = true;
        $totLitros = $totDias = $totUnid = $totMerma = $totAgrega = $totLotes = 0.0;
        foreach ($data as $row) {
            $dias = max(1, (int)$row['dias_activos']);
            $prom = round((float)$row['litros_recibidos'] / $dias, 2);
            echo '<Row ss:Height="16">';
            $this->td($this->textoMes($row['mes']), $par, 'str');
            $this->td(number_format((float)$row['litros_recibidos'], 2, '.', ''), $par, 'num');
            $this->td((string)$dias, $par, 'int');
            $this->td(number_format($prom, 2, '.', ''), $par, 'num');
            $this->td(number_format((float)$row['unidades_producidas'], 2, '.', ''), $par, 'num');
            $this->td(number_format((float)$row['merma_total'], 2, '.', ''), $par, 'num');
            $this->td(number_format((float)$row['agrega_total'], 2, '.', ''), $par, 'num');
            $this->td((string)(int)$row['lotes_produccion'], $par, 'int');
            echo '</Row>' . "\n";
            $totLitros += (float)$row['litros_recibidos']; $totDias += $dias;
            $totUnid += (float)$row['unidades_producidas']; $totMerma += (float)$row['merma_total'];
            $totAgrega += (float)$row['agrega_total']; $totLotes += (int)$row['lotes_produccion'];
            $par = !$par;
        }
        echo '<Row ss:Height="18">';
        echo '<Cell ss:StyleID="rg_total_label"><Data ss:Type="String">TOTAL</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totLitros, 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . (int)$totDias . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_label"><Data ss:Type="String">—</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totUnid, 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totMerma, 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totAgrega, 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . (int)$totLotes . '</Data></Cell>';
        echo '</Row>' . "\n";
    }

    // ── Bloque 2: Ventas ──────────────────────────────────────────────────────

    private function bloqueVentas(array $data): void
    {
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="7" ss:StyleID="rg_grupo_venta"><Data ss:Type="String">2. VENTAS MENSUALES (TIENDA / PLANTA / AGRO)</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="28">';
        foreach (['MES', 'MÓDULO', 'N° VENTAS', 'VENTAS CONTADO', 'INGRESOS CONTADO (Bs)', 'VENTAS CRÉDITO', 'INGRESOS CRÉDITO (Bs)', 'TOTAL INGRESOS (Bs)'] as $h) { $this->th($h); }
        echo '</Row>' . "\n";

        $par = true;
        $totV = $totVC = $totIC = $totVR = $totIR = $totT = 0.0;
        foreach ($data as $row) {
            echo '<Row ss:Height="16">';
            $this->td($this->textoMes($row['mes']), $par, 'str');
            $this->td($row['modulo'], $par, 'str');
            $this->td((string)(int)$row['num_ventas'], $par, 'int');
            $this->td((string)(int)$row['ventas_contado'], $par, 'int');
            $this->td(number_format((float)$row['ingresos_contado'], 2, '.', ''), $par, 'num');
            $this->td((string)(int)$row['ventas_credito'], $par, 'int');
            $this->td(number_format((float)$row['ingresos_credito'], 2, '.', ''), $par, 'num');
            $this->td(number_format((float)$row['ingresos_total'], 2, '.', ''), $par, 'num');
            echo '</Row>' . "\n";
            $totV += (int)$row['num_ventas']; $totVC += (int)$row['ventas_contado'];
            $totIC += (float)$row['ingresos_contado']; $totVR += (int)$row['ventas_credito'];
            $totIR += (float)$row['ingresos_credito']; $totT += (float)$row['ingresos_total'];
            $par = !$par;
        }
        echo '<Row ss:Height="18">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="rg_total_label"><Data ss:Type="String">TOTAL</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . (int)$totV  . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . (int)$totVC . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totIC, 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . (int)$totVR . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totIR, 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totT,  2, '.', '') . '</Data></Cell>';
        echo '</Row>' . "\n";
    }

    // ── Bloque 3: Envíos ──────────────────────────────────────────────────────

    private function bloqueEnvios(array $data): void
    {
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="7" ss:StyleID="rg_grupo_envio"><Data ss:Type="String">3. ENVÍOS MENSUALES</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="28">';
        foreach (['MES', 'N° ENVÍOS', 'ENTREGADOS', 'EN TRÁNSITO', 'PENDIENTES', 'UNIDADES ENVIADAS', '', ''] as $h) { $this->th($h); }
        echo '</Row>' . "\n";

        $par = true;
        $totE = $totEnt = $totTr = $totPend = $totU = 0;
        foreach ($data as $row) {
            echo '<Row ss:Height="16">';
            $this->td($this->textoMes($row['mes']), $par, 'str');
            $this->td((string)(int)$row['num_envios'],        $par, 'int');
            $this->td((string)(int)$row['entregados'],        $par, 'int');
            $this->td((string)(int)$row['en_transito'],       $par, 'int');
            $this->td((string)(int)$row['pendientes'],        $par, 'int');
            $this->td((string)(int)$row['unidades_enviadas'], $par, 'int');
            $this->td('', $par, 'str'); $this->td('', $par, 'str');
            echo '</Row>' . "\n";
            $totE += (int)$row['num_envios']; $totEnt += (int)$row['entregados'];
            $totTr += (int)$row['en_transito']; $totPend += (int)$row['pendientes'];
            $totU += (int)$row['unidades_enviadas'];
            $par = !$par;
        }
        echo '<Row ss:Height="18">';
        echo '<Cell ss:StyleID="rg_total_label"><Data ss:Type="String">TOTAL</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . $totE    . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . $totEnt  . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . $totTr   . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . $totPend . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . $totU    . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_label"><Data ss:Type="String"></Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_label"><Data ss:Type="String"></Data></Cell>';
        echo '</Row>' . "\n";
    }

    // ── Bloque 4: Balance ─────────────────────────────────────────────────────

    private function bloqueBalance(array $data): void
    {
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="7" ss:StyleID="rg_grupo_balance"><Data ss:Type="String">4. BALANCE MENSUAL ESTIMADO</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="28">';
        foreach (['MES', 'PRODUCIDO (uds)', 'MERMA', 'AGREGA', 'N° VENTAS', 'INGRESOS TOTALES (Bs)', 'ENVIADO (uds)', 'BALANCE (uds)'] as $h) { $this->th($h); }
        echo '</Row>' . "\n";

        $par = true;
        $totProd = $totMerma = $totAgrega = $totV = $totIng = $totEnv = $totBal = 0.0;
        foreach ($data as $row) {
            echo '<Row ss:Height="16">';
            $this->td($this->textoMes($row['mes']), $par, 'str');
            $this->td(number_format((float)$row['producido'],  2, '.', ''), $par, 'num');
            $this->td(number_format((float)$row['merma'],      2, '.', ''), $par, 'num');
            $this->td(number_format((float)$row['agrega'],     2, '.', ''), $par, 'num');
            $this->td((string)(int)$row['n_ventas'],                        $par, 'int');
            $this->td(number_format((float)$row['ingresos'],   2, '.', ''), $par, 'num');
            $this->td((string)(int)$row['enviado'],                         $par, 'int');
            $this->td(number_format((float)$row['balance_u'],  2, '.', ''), $par, 'num');
            echo '</Row>' . "\n";
            $totProd += (float)$row['producido']; $totMerma += (float)$row['merma'];
            $totAgrega += (float)$row['agrega']; $totV += (int)$row['n_ventas'];
            $totIng += (float)$row['ingresos']; $totEnv += (int)$row['enviado'];
            $totBal += (float)$row['balance_u'];
            $par = !$par;
        }
        echo '<Row ss:Height="18">';
        echo '<Cell ss:StyleID="rg_total_label"><Data ss:Type="String">TOTAL</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totProd,   2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totMerma,  2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totAgrega, 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . (int)$totV . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totIng,    2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_int"><Data ss:Type="Number">' . (int)$totEnv . '</Data></Cell>';
        echo '<Cell ss:StyleID="rg_total_num"><Data ss:Type="Number">' . number_format($totBal,    2, '.', '') . '</Data></Cell>';
        echo '</Row>' . "\n";
    }

    // ── Helpers de indicadores ────────────────────────────────────────────────

    private function filaIndicador(string $label, string $valor): void
    {
        echo '<Row ss:Height="16">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="rg_ind_label"><Data ss:Type="String">' . htmlspecialchars($label, ENT_XML1) . '</Data></Cell>';
        echo '<Cell ss:MergeAcross="5" ss:StyleID="rg_ind_val"><Data ss:Type="String">'   . htmlspecialchars($valor, ENT_XML1) . '</Data></Cell>';
        echo '</Row>' . "\n";
    }

    private function tituloIndicadores(string $texto): void
    {
        echo '<Row ss:Height="18"><Cell ss:MergeAcross="7" ss:StyleID="rg_ind_titulo"><Data ss:Type="String">' . htmlspecialchars('  ★  ' . $texto, ENT_XML1) . '</Data></Cell></Row>' . "\n";
    }

    // ── Indicadores: Producción ───────────────────────────────────────────────

    private function indicadoresProduccion(array $produccion, string $fi, string $ff): void
    {
        if (empty($produccion)) return;
        $this->tituloIndicadores('INDICADORES DE PRODUCCIÓN');

        $tmp = $produccion;
        usort($tmp, fn($a, $b) => (float)$b['litros_recibidos'] <=> (float)$a['litros_recibidos']);
        // Indicador 1: día con más leche recibida
        $rowDiaLeche = $this->db->query("
            SELECT DATE(created_at) AS dia, SUM(stock) AS litros
            FROM condoriri.inventarios
            WHERE deleted_at IS NULL AND nombre = 'LECHE'
              AND created_at >= ? AND created_at <= ?
            GROUP BY DATE(created_at) ORDER BY litros DESC LIMIT 1
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getRow();
        if ($rowDiaLeche) {
            $this->filaIndicador('Día con más leche recibida', date('d/m/Y', strtotime($rowDiaLeche->dia)) . ' — ' . number_format((float)$rowDiaLeche->litros, 0, ',', '.') . ' L');
        }

        // Indicador 2: día con más unidades producidas
        $rowDiaProd = $this->db->query("
            SELECT DATE(p.created_at) AS dia, SUM(p.cantidad_produccion) AS total
            FROM condoriri.productos p
            JOIN condoriri.inventarios i ON i.id = p.inventario_id
            WHERE p.deleted_at IS NULL AND i.deleted_at IS NULL AND i.nombre = 'LECHE'
              AND p.created_at >= ? AND p.created_at <= ?
            GROUP BY DATE(p.created_at) ORDER BY total DESC LIMIT 1
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getRow();
        if ($rowDiaProd) {
            $this->filaIndicador('Día con más unidades producidas', date('d/m/Y', strtotime($rowDiaProd->dia)) . ' — ' . number_format((float)$rowDiaProd->total, 0, ',', '.') . ' uds');
        }

        $row = $this->db->query("
            SELECT p.nombre, SUM(p.cantidad_produccion) AS total
            FROM condoriri.productos p
            JOIN condoriri.inventarios i ON i.id = p.inventario_id
            WHERE p.deleted_at IS NULL AND i.deleted_at IS NULL AND i.nombre = 'LECHE'
              AND p.created_at >= ? AND p.created_at <= ?
            GROUP BY p.nombre ORDER BY total DESC LIMIT 1
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getRow();
        if ($row) {
            $this->filaIndicador('Producto más producido', $row->nombre . ' — ' . number_format((float)$row->total, 0, ',', '.') . ' uds');
        }
    }

    // ── Indicadores: Ventas ───────────────────────────────────────────────────

    private function indicadoresVentas(array $ventas, string $fi, string $ff): void
    {
        if (empty($ventas)) return;
        $this->tituloIndicadores('INDICADORES DE VENTAS');

        $contado = array_sum(array_column($ventas, 'ventas_contado'));
        $credito = array_sum(array_column($ventas, 'ventas_credito'));
        $tipoPopular = $contado >= $credito
            ? 'Contado (' . number_format((int)$contado, 0, ',', '.') . ' ventas)'
            : 'Crédito (' . number_format((int)$credito, 0, ',', '.') . ' ventas)';
        $this->filaIndicador('Tipo de pago más frecuente', $tipoPopular);

        $porMes = [];
        foreach ($ventas as $v) {
            $porMes[$v['mes']] = ($porMes[$v['mes']] ?? 0) + (float)$v['ingresos_total'];
        }
        arsort($porMes);
        $mesMejor = array_key_first($porMes);
        // Indicador 5: día con mayores ingresos
        $rowDiaVenta = $this->db->query("
            SELECT DATE(created_at) AS dia, SUM(monto_total) AS total
            FROM condoriri.ventas
            WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
            GROUP BY DATE(created_at) ORDER BY total DESC LIMIT 1
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getRow();
        if ($rowDiaVenta) {
            $this->filaIndicador('Día con mayores ingresos', date('d/m/Y', strtotime($rowDiaVenta->dia)) . ' — Bs. ' . number_format((float)$rowDiaVenta->total, 2, ',', '.'));
        }

        $porModulo = [];
        foreach ($ventas as $v) {
            $porModulo[$v['modulo']] = ($porModulo[$v['modulo']] ?? 0) + (float)$v['ingresos_total'];
        }
        arsort($porModulo);
        $modTop = array_key_first($porModulo);
        $this->filaIndicador('Módulo con más ingresos', $modTop . ' — Bs. ' . number_format($porModulo[$modTop], 2, ',', '.'));

        $row = $this->db->query("
            SELECT p.nombre, SUM(dv.cantidad) AS total
            FROM condoriri.detalle_venta dv
            JOIN condoriri.productos p ON p.id = dv.producto_id
            JOIN condoriri.ventas v ON v.id = dv.venta_id
            WHERE v.deleted_at IS NULL AND dv.deleted_at IS NULL
              AND v.created_at >= ? AND v.created_at <= ?
            GROUP BY p.nombre ORDER BY total DESC LIMIT 1
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getRow();
        if ($row) {
            $this->filaIndicador('Producto más vendido', $row->nombre . ' — ' . number_format((float)$row->total, 0, ',', '.') . ' uds');
        }

        $row2 = $this->db->query("
            SELECT COALESCE(p.nombre, ce.nombre, c.nombre_completo) AS cliente,
                   COUNT(DISTINCT v.id) AS num_ventas
            FROM condoriri.ventas v
            LEFT JOIN condoriri.clientes c ON c.id = v.cliente_id
            LEFT JOIN condoriri.clientes_externos ce ON ce.id = v.cliente_externo_id
            LEFT JOIN public.personas p ON p.id_persona = v.personal_uto_id
            WHERE v.deleted_at IS NULL
              AND (v.cliente_id IS NOT NULL OR v.personal_uto_id IS NOT NULL OR v.cliente_externo_id IS NOT NULL)
              AND v.created_at >= ? AND v.created_at <= ?
            GROUP BY cliente ORDER BY num_ventas DESC LIMIT 1
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getRow();
        if ($row2 && $row2->cliente) {
            $this->filaIndicador('Cliente más frecuente', $row2->cliente . ' — ' . $row2->num_ventas . ' compras');
        }
    }

    // ── Indicadores: Envíos ───────────────────────────────────────────────────

    private function indicadoresEnvios(array $envios, string $fi, string $ff): void
    {
        if (empty($envios)) return;
        $this->tituloIndicadores('INDICADORES DE ENVÍOS');

        $tmp = $envios;
        usort($tmp, fn($a, $b) => (int)$b['num_envios'] <=> (int)$a['num_envios']);
        // Indicador 9: día con más envíos
        $rowDiaEnvio = $this->db->query("
            SELECT DATE(created_at) AS dia, COUNT(*) AS total
            FROM condoriri.envios
            WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
            GROUP BY DATE(created_at) ORDER BY total DESC LIMIT 1
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getRow();
        if ($rowDiaEnvio) {
            $this->filaIndicador('Día con más envíos', date('d/m/Y', strtotime($rowDiaEnvio->dia)) . ' — ' . $rowDiaEnvio->total . ' envíos');
        }

        // Indicador 10 eliminado (mes con más unidades enviadas)

        $totEnvios     = array_sum(array_column($envios, 'num_envios'));
        $totEntregados = array_sum(array_column($envios, 'entregados'));
        $pct = $totEnvios > 0 ? round($totEntregados / $totEnvios * 100, 1) : 0;
        $this->filaIndicador('Tasa de entrega', $pct . '% (' . $totEntregados . ' de ' . $totEnvios . ' envíos entregados)');

        $row = $this->db->query("
            SELECT s.nombre, COUNT(*) AS total
            FROM condoriri.envios e
            JOIN condoriri.sucursales s ON s.id = e.sucursal_destino_id
            WHERE e.deleted_at IS NULL AND e.created_at >= ? AND e.created_at <= ?
            GROUP BY s.nombre ORDER BY total DESC LIMIT 1
        ", [$fi . ' 00:00:00', $ff . ' 23:59:59'])->getRow();
        if ($row) {
            $this->filaIndicador('Destino más frecuente', $row->nombre . ' — ' . $row->total . ' envíos');
        }
    }
}
