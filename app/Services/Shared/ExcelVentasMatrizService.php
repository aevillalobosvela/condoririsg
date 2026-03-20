<?php

namespace App\Services\Shared;

use App\Models\Venta\VentaModel;

class ExcelVentasMatrizService
{
    // Constantes de tienda para evitar strings mágicos
    const TIENDA_CEAC    = 'ceac';
    const TIENDA_LACTEOS = 'lacteos';

    private VentaModel $ventaModel;

    public function __construct()
    {
        $this->ventaModel = new VentaModel();
    }

    public function exportar(string $fecha_inicio, string $fecha_fin, string $tipo, string $tienda): void
    {
        $tipoConsulta = ($tipo === 'deposito_contado') ? 'contado' : $tipo;
        if (!in_array($tipoConsulta, ['contado', 'credito', 'general'])) {
            $tipoConsulta = 'general';
            $tipo = 'general';
        }

        // Seleccionar método del modelo según la tienda
        $reportData = ($tienda === self::TIENDA_LACTEOS)
            ? $this->ventaModel->getDailySalesReportDataInve($fecha_inicio, $fecha_fin, $tipoConsulta)
            : $this->ventaModel->getDailySalesReportData($fecha_inicio, $fecha_fin, $tipoConsulta);

        // Título según tienda
        $nombreTienda = ($tienda === self::TIENDA_LACTEOS) ? 'TIENDA LACTEOS' : 'TIENDA CEAC';

        // Procesar datos en formato matricial
        $productosUnicos  = [];
        $ventasAgrupadas  = [];
        $totalGeneralBs   = 0;
        $totalGeneralCant = 0;

        foreach ($reportData as $item) {
            if ($item->estado_venta != 1) continue;
            $tipoPago = strtolower($item->tipo_pago);

            if ($tipo === 'contado' && $tipoPago !== 'contado' && $tipoPago !== 'deposito_contado') continue;
            if ($tipo === 'credito' && $tipoPago !== 'credito') continue;

            $prodNombre = trim(preg_replace('/\s+/', ' ', str_replace(['(', ')'], '', $item->producto_nombre)));

            if (!isset($productosUnicos[$prodNombre])) {
                $productosUnicos[$prodNombre] = ['precio' => $item->precio_unitario, 'total_cantidad' => 0];
            }
            $productosUnicos[$prodNombre]['total_cantidad'] += $item->cantidad;

            $ventaId = $item->venta_id;
            if (!isset($ventasAgrupadas[$ventaId])) {
                $cliente = $item->cliente_nombre ?? 'Consumidor Final';
                if (stripos($cliente, 'Sin Nombre') !== false) {
                    $cliente = '';
                }
                $ventasAgrupadas[$ventaId] = ['cliente' => $cliente, 'notas' => $ventaId, 'total_venta' => 0, 'items' => []];
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

        // Texto de fechas
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
                  '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $d = date('d', strtotime($fecha_inicio));
        $m = date('m', strtotime($fecha_inicio));
        $y = date('Y', strtotime($fecha_inicio));

        if ($fecha_inicio === $fecha_fin) {
            $fechaTexto = "Del: $d de {$meses[$m]} de $y Al: $d de {$meses[$m]} de $y";
        } else {
            $d2 = date('d', strtotime($fecha_fin));
            $m2 = date('m', strtotime($fecha_fin));
            $y2 = date('Y', strtotime($fecha_fin));
            $fechaTexto = "Del: $d de {$meses[$m]} de $y Al: $d2 de {$meses[$m2]} de $y2";
        }

        $tipoLabel = $tipo === 'contado' ? 'AL CONTADO' : ($tipo === 'credito' ? 'A CRÉDITO' : 'GENERALES');
        $tipoNota  = $tipo === 'contado' ? 'al contado' : ($tipo === 'credito' ? 'a crédito' : 'generales');

        $filename = 'ventas_' . strtolower(str_replace(' ', '_', $nombreTienda)) . '_' . $tipo . '_' . $fecha_inicio . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // ── Estilos ────────────────────────────────────────────────────────────
        echo '<Styles>';
        echo '<Style ss:ID="titulo_uto"><Font ss:Bold="1" ss:Size="14" ss:Color="#1F4E78" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="subtitulo_uto"><Font ss:Bold="1" ss:Size="11" ss:Color="#1F4E78" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="info_uto"><Font ss:Size="9" ss:Color="#404040" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="header"><Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF" ss:FontName="Calibri"/><Interior ss:Color="#2E5090" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/></Borders></Style>';
        echo '<Style ss:ID="header_gray"><Font ss:Bold="1" ss:Size="8" ss:Color="#000000" ss:FontName="Calibri"/><Interior ss:Color="#DCDCDC" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="header_prod"><Font ss:Bold="1" ss:Size="8" ss:Color="#000000" ss:FontName="Calibri"/><Interior ss:Color="#C8C8C8" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="number"><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="integer"><NumberFormat ss:Format="#,##0"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="center"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="center_wrap"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="left"><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="row_even"><Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '</Styles>';

        // ── Hoja ───────────────────────────────────────────────────────────────
        $numProds  = count($productosUnicos);
        $totalCols = 1 + ($numProds * 2) + 2 - 1;

        echo '<Worksheet ss:Name="VENTAS">';
        echo '<Table>';

        echo '<Column ss:Width="150"/>';
        foreach ($productosUnicos as $prod => $info) {
            echo '<Column ss:Width="40"/>';
            echo '<Column ss:Width="40"/>';
        }
        echo '<Column ss:Width="80"/>';
        echo '<Column ss:Width="100"/>';

        // Encabezado institucional
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="' . $totalCols . '" ss:StyleID="titulo_uto"><Data ss:Type="String">UNIVERSIDAD TÉCNICA DE ORURO</Data></Cell></Row>';
        echo '<Row ss:Height="18"><Cell ss:MergeAcross="' . $totalCols . '" ss:StyleID="subtitulo_uto"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRARIAS Y NATURALES</Data></Cell></Row>';
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="' . $totalCols . '" ss:StyleID="info_uto"><Data ss:Type="String">CONDORIRI - LABORATORIO DE INNOVACIÓN</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="' . $totalCols . '" ss:StyleID="info_uto"><Data ss:Type="String">Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="' . $totalCols . '" ss:StyleID="info_uto"><Data ss:Type="String">Email: dpdi@uto.edu.bo | www.uto.edu.bo</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="' . $totalCols . '" ss:StyleID="header"><Data ss:Type="String">VENTAS ' . $tipoLabel . ' - ' . $nombreTienda . '</Data></Cell></Row>';
        echo '<Row></Row>';

        // Fecha y total general
        echo '<Row><Cell ss:MergeAcross="' . ($totalCols - 1) . '"><Data ss:Type="String">' . htmlspecialchars($fechaTexto, ENT_XML1) . '</Data></Cell><Cell ss:StyleID="number"><Data ss:Type="String">TOTAL: ' . number_format($totalGeneralBs, 2, ',', '.') . ' Bs.</Data></Cell></Row>';
        echo '<Row></Row>';

        // Fila: TOTAL BOLIVIANOS
        echo '<Row><Cell ss:StyleID="header_gray"><Data ss:Type="String">TOTAL BOLIVIANOS:</Data></Cell>';
        foreach ($productosUnicos as $prod => $info) {
            echo '<Cell ss:MergeAcross="1" ss:StyleID="integer"><Data ss:Type="Number">' . round($info['precio'] * $info['total_cantidad']) . '</Data></Cell>';
        }
        echo '<Cell ss:StyleID="header_gray"><Data ss:Type="String"></Data></Cell>';
        echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . number_format($totalGeneralBs, 2, '.', '') . '</Data></Cell></Row>';

        // Fila: TOTAL CANTIDADES
        echo '<Row><Cell ss:StyleID="header_gray"><Data ss:Type="String">TOTAL CANTIDADES:</Data></Cell>';
        foreach ($productosUnicos as $prod => $info) {
            echo '<Cell ss:MergeAcross="1" ss:StyleID="integer"><Data ss:Type="Number">' . $info['total_cantidad'] . '</Data></Cell>';
        }
        echo '<Cell ss:StyleID="header_gray"><Data ss:Type="String"></Data></Cell>';
        echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . $totalGeneralCant . '</Data></Cell></Row>';

        // Fila: PRODUCTOS
        echo '<Row ss:Height="30"><Cell ss:StyleID="header_gray"><Data ss:Type="String">PRODUCTOS:</Data></Cell>';
        foreach ($productosUnicos as $prod => $info) {
            echo '<Cell ss:MergeAcross="1" ss:StyleID="center_wrap"><Data ss:Type="String">' . htmlspecialchars($prod, ENT_XML1) . '</Data></Cell>';
        }
        echo '<Cell ss:StyleID="header_gray"><Data ss:Type="String"></Data></Cell>';
        echo '<Cell ss:StyleID="header_gray"><Data ss:Type="String">TOTAL</Data></Cell></Row>';

        // Fila: UNIDADES DE MEDIDA
        $unidadesMedida = [
            'LACTOFRUIT 120 ML'        => 'BOLSA',
            'LECHE'                    => 'LITRO',
            'QUESO 900 GRAMOS'         => 'PIEZA',
            'QUESO SIN SAL 500 GRAMOS' => 'PIEZA',
            'REQUESON 250 GRAMOS'      => 'BOLSA',
            'YOGURT 1 LITRO'           => 'BOLSA',
            'YOGURT 120 ML'            => 'BOLSA',
            'YOGURT GRIEGO 250 GRAMOS' => 'PIEZA',
        ];
        echo '<Row><Cell ss:StyleID="header_gray"><Data ss:Type="String">UNIDADES DE MEDIDA:</Data></Cell>';
        foreach ($productosUnicos as $prod => $info) {
            $unidad = $unidadesMedida[$prod] ?? 'PIEZA';
            echo '<Cell ss:MergeAcross="1" ss:StyleID="center"><Data ss:Type="String">' . $unidad . '</Data></Cell>';
        }
        echo '<Cell ss:StyleID="header_gray"><Data ss:Type="String"></Data></Cell>';
        echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . number_format($totalGeneralBs, 2, '.', '') . '</Data></Cell></Row>';

        // Fila: PRECIOS
        echo '<Row><Cell ss:StyleID="header_gray"><Data ss:Type="String">PRECIOS:</Data></Cell>';
        foreach ($productosUnicos as $prod => $info) {
            echo '<Cell ss:MergeAcross="1" ss:StyleID="number"><Data ss:Type="Number">' . number_format($info['precio'], 2, '.', '') . '</Data></Cell>';
        }
        echo '<Cell ss:StyleID="header_gray"><Data ss:Type="String"></Data></Cell>';
        echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . number_format($totalGeneralBs, 2, '.', '') . '</Data></Cell></Row>';

        echo '<Row></Row>';

        // Encabezado de tabla
        echo '<Row><Cell ss:StyleID="header_prod"><Data ss:Type="String"> Y NOMBRES:</Data></Cell>';
        foreach ($productosUnicos as $prod => $info) {
            echo '<Cell ss:StyleID="header_prod"><Data ss:Type="String">Q</Data></Cell>';
            echo '<Cell ss:StyleID="header_prod"><Data ss:Type="String">Bs</Data></Cell>';
        }
        echo '<Cell ss:StyleID="header_prod"><Data ss:Type="String">Nro. Venta</Data></Cell>';
        echo '<Cell ss:StyleID="header_prod"><Data ss:Type="String">TOTAL</Data></Cell></Row>';

        // Filas de ventas
        $fill = false;
        foreach ($ventasAgrupadas as $venta) {
            $rowStyle = $fill ? 'row_even' : 'left';
            echo '<Row><Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . htmlspecialchars(substr($venta['cliente'], 0, 30), ENT_XML1) . '</Data></Cell>';
            foreach ($productosUnicos as $prod => $info) {
                if (isset($venta['items'][$prod])) {
                    $q  = $venta['items'][$prod]['q'];
                    $bs = $venta['items'][$prod]['bs'];
                    echo '<Cell ss:StyleID="center"><Data ss:Type="String">' . ($q > 0 ? $q : '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . ($bs > 0 ? number_format($bs, 2, '.', '') : '') . '</Data></Cell>';
                } else {
                    echo '<Cell ss:StyleID="center"><Data ss:Type="String"></Data></Cell>';
                    echo '<Cell ss:StyleID="center"><Data ss:Type="String"></Data></Cell>';
                }
            }
            echo '<Cell ss:StyleID="center"><Data ss:Type="Number">' . $venta['notas'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="number"><Data ss:Type="Number">' . number_format($venta['total_venta'], 2, '.', '') . '</Data></Cell></Row>';
            $fill = !$fill;
        }

        echo '<Row></Row>';

        // Literal del total
        if (class_exists('NumberFormatter')) {
            $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
            $literal   = strtoupper($formatter->format((float)$totalGeneralBs));
        } else {
            $literal = number_format($totalGeneralBs, 2);
        }
        echo '<Row><Cell ss:MergeAcross="' . $totalCols . '"><Data ss:Type="String">TOTAL VENTAS ' . $tipoLabel . ': ' . $literal . ' BOLIVIANOS</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row><Cell ss:MergeAcross="' . $totalCols . '"><Data ss:Type="String">NOTA.- El día ' . $d . ' de ' . $meses[$m] . ' de ' . $y . ', ventas ' . $tipoNota . '.</Data></Cell></Row>';

        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
    }
}
