<?php

namespace App\Services\Inventarios;

use App\Models\Inventario\InventarioModel;
use App\Models\Producto\ProductoModel;

class ExportacionExcelService
{
    private const ALTURA_FILA_DATOS = 18;
    private const ALTURA_FILA_CIERRE = 18;

    protected $inventarioModel;
    protected $productoModel;
    protected $db;

    public function __construct()
    {
        $this->inventarioModel = new InventarioModel();
        $this->productoModel = new ProductoModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Exportar reporte general de inventarios con productos agrupados
     */
    public function exportarReporteGeneral($nombre = '', $fecha_inicio = '', $fecha_fin = '')
    {
        // Reporte general ahora solo contempla materia prima LECHE
        $inventariosFiltrados = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);
        $inventariosFiltrados = $this->ordenarPorDiaYTurno($inventariosFiltrados);
        $inventarios = array_values(array_filter($inventariosFiltrados, static function ($inv) {
            return strtoupper(trim($inv->nombre ?? '')) === 'LECHE';
        }));

        // Obtener todos los productos únicos de todos los inventarios
        $productosUnicos = $this->obtenerProductosUnicos($inventarios);

        // Preparar datos con productos agrupados
        $datosConProductos = $this->prepararDatosConProductos($inventarios, $productosUnicos);

        // Generar archivo Excel
        $this->generarArchivoExcel($datosConProductos, $productosUnicos, $fecha_inicio, $fecha_fin);
    }

    /**
     * Exportar reporte separado de inventarios (solo SUERO y OTROS)
     */
    public function exportarReporteLecheOtros($nombre = '', $fecha_inicio = '', $fecha_fin = '')
    {
        $inventariosFiltrados = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);
        $inventariosFiltrados = $this->ordenarPorDiaYTurno($inventariosFiltrados);
        $inventariosOtros = array_values(array_filter($inventariosFiltrados, static function ($inv) {
            return strtoupper(trim($inv->nombre ?? '')) !== 'LECHE';
        }));

        $filename = 'reporte_suero_otros_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        $this->definirEstilos();

        $productosOtros = $this->obtenerProductosUnicos($inventariosOtros);
        $datosOtros = $this->prepararDatosConProductos($inventariosOtros, $productosOtros);
        $this->generarWorksheetReporteGeneral('SUERO Y OTROS', $datosOtros, $productosOtros, $fecha_inicio, $fecha_fin);

        echo '</Workbook>';
        exit;
    }

    /**
     * Obtener lista de productos únicos de todos los inventarios
     */
    private function obtenerProductosUnicos($inventarios)
    {
        $productosSet = [];

        foreach ($inventarios as $inv) {
            $productos = $this->productoModel
                ->where('inventario_id', $inv->id)
                ->findAll();

            foreach ($productos as $prod) {
                $nombreProducto = $this->normalizarNombreProducto(trim($prod->nombre ?? ''));
                if (!empty($nombreProducto)) {
                    $productosSet[$nombreProducto] = true;
                }
            }
        }

        $productosUnicos = array_keys($productosSet);
        sort($productosUnicos); // Ordenar alfabéticamente

        return $productosUnicos;
    }

    /**
     * Normalizar nombre de producto para casos especiales
     */
    private function normalizarNombreProducto($nombre)
    {
        // Caso especial: unificar variantes de yogurt griego 250 gramos
        if ($nombre === 'YOGURT GRIEGO (250 GRAMOS)') {
            return 'YOGURT GRIEGO 250 GRAMOS';
        }
        
        return $nombre;
    }

    /**
     * Agrupar inventarios por día
     */
    private function agruparInventariosPorDia($inventarios)
    {
        $inventariosPorDia = [];

        foreach ($inventarios as $inv) {
            $dia = date('Y-m-d', strtotime($inv->created_at));
            if (!isset($inventariosPorDia[$dia])) {
                $inventariosPorDia[$dia] = [];
            }
            $inventariosPorDia[$dia][] = $inv;
        }

        return $inventariosPorDia;
    }

    /**
     * Ordenar por día (desc) y dentro del día: AM -> PM
     */
    private function ordenarPorDiaYTurno(array $inventarios): array
    {
        usort($inventarios, static function ($a, $b) {
            $fechaA = date('Y-m-d', strtotime($a->created_at));
            $fechaB = date('Y-m-d', strtotime($b->created_at));

            if ($fechaA !== $fechaB) {
                return strcmp($fechaB, $fechaA);
            }

            $turnoOrden = ['AM' => 0, 'PM' => 1];
            $turnoA = strtoupper(trim($a->turno ?? 'AM'));
            $turnoB = strtoupper(trim($b->turno ?? 'AM'));
            $ordenA = $turnoOrden[$turnoA] ?? 99;
            $ordenB = $turnoOrden[$turnoB] ?? 99;

            if ($ordenA !== $ordenB) {
                return $ordenA <=> $ordenB;
            }

            return strtotime($b->created_at) <=> strtotime($a->created_at);
        });

        return $inventarios;
    }

    /**
     * Preparar datos con productos agrupados por nombre
     */
    private function prepararDatosConProductos($inventarios, $productosUnicos)
    {
        $datosConProductos = [];

        foreach ($inventarios as $inv) {
            // Obtener productos del inventario
            $productos = $this->productoModel
                ->where('inventario_id', $inv->id)
                ->findAll();

            // Agrupar productos por nombre
            $productosAgrupados = [];
            foreach ($productosUnicos as $nombreProducto) {
                $productosAgrupados[$nombreProducto] = [
                    // null = el inventario no tiene este producto (se mostrara "-")
                    'stock'               => null,
                    'cantidad_produccion' => null,
                    'merma'               => null,
                    'agrega'              => null,
                    'litros_usados'       => null,
                    'valor_contado'       => null,
                    'valor_credito'       => null,
                ];
            }

            // Sumar valores de productos con el mismo nombre
            foreach ($productos as $prod) {
                $nombreProducto = $this->normalizarNombreProducto(trim($prod->nombre ?? ''));
                if (isset($productosAgrupados[$nombreProducto])) {
                    if ($productosAgrupados[$nombreProducto]['stock'] === null) {
                        $productosAgrupados[$nombreProducto]['stock']               = 0;
                        $productosAgrupados[$nombreProducto]['cantidad_produccion'] = 0;
                        $productosAgrupados[$nombreProducto]['merma']               = 0;
                        $productosAgrupados[$nombreProducto]['agrega']              = 0;
                        $productosAgrupados[$nombreProducto]['litros_usados']       = 0;
                        $productosAgrupados[$nombreProducto]['valor_contado']       = 0;
                        $productosAgrupados[$nombreProducto]['valor_credito']       = 0;
                    }
                    $cantidadProduccion = (float)($prod->cantidad_produccion ?? 0);
                    $cantidadUnidad = (float)($prod->cantidad_unidad ?? 0);
                    $precioContado = (float)($prod->precio_contado ?? 0);
                    $precioCredito = (float)($prod->precio_credito ?? 0);

                    $productosAgrupados[$nombreProducto]['stock']               += ($prod->stock ?? 0);
                    $productosAgrupados[$nombreProducto]['cantidad_produccion'] += $cantidadProduccion;
                    $productosAgrupados[$nombreProducto]['merma']               += ($prod->merma ?? 0);
                    $productosAgrupados[$nombreProducto]['agrega']              += ($prod->agrega ?? 0);
                    $productosAgrupados[$nombreProducto]['litros_usados']       += ($cantidadProduccion * $cantidadUnidad);
                    $productosAgrupados[$nombreProducto]['valor_contado']       += ($cantidadProduccion * $precioContado);
                    $productosAgrupados[$nombreProducto]['valor_credito']       += ($cantidadProduccion * $precioCredito);
                }
            }

            $datosConProductos[] = [
                'inventario' => $inv,
                'productos'  => $productosAgrupados,
            ];
        }

        return $datosConProductos;
    }

    /**
     * Generar archivo Excel con formato mejorado
     */
    private function generarArchivoExcel($datosConProductos, $productosUnicos, $fecha_inicio, $fecha_fin)
    {
        $filename = 'reporte_general_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // Definir estilos
        $this->definirEstilos();

        $this->generarWorksheetReporteGeneral('Reporte General', $datosConProductos, $productosUnicos, $fecha_inicio, $fecha_fin);
        $resumenMensual = $this->construirResumenDetalladoMensual($datosConProductos, $productosUnicos);
        $this->generarWorksheetResumenDetallado($resumenMensual, $productosUnicos, $fecha_inicio, $fecha_fin);

        echo '</Workbook>';
        exit;
    }

    /**
     * Generar una hoja con el formato del reporte general
     */
    private function generarWorksheetReporteGeneral($nombreHoja, $datosConProductos, $productosUnicos, $fecha_inicio, $fecha_fin)
    {
        echo '<Worksheet ss:Name="' . htmlspecialchars($nombreHoja, ENT_XML1) . '">' . "\n";
        echo '<Table>' . "\n";

        $this->definirAnchos($productosUnicos);

        $totalColumnas = 5 + (count($productosUnicos) * 4) - 1;
        echo '<Row ss:Height="25">';
        echo '<Cell ss:MergeAcross="' . $totalColumnas . '" ss:StyleID="titulo"><Data ss:Type="String">REPORTE GENERAL</Data></Cell>';
        echo '</Row>' . "\n";

        $textoFechas = $this->obtenerTextoFechas($fecha_inicio, $fecha_fin);
        echo '<Row ss:Height="20">';
        echo '<Cell ss:MergeAcross="' . $totalColumnas . '" ss:StyleID="subtitulo"><Data ss:Type="String">' . htmlspecialchars($textoFechas, ENT_XML1) . '</Data></Cell>';
        echo '</Row>' . "\n";

        echo '<Row></Row>' . "\n";

        $this->generarDatos($datosConProductos, $productosUnicos);

        echo '</Table></Worksheet>' . "\n";
    }

    /**
     * Definir estilos del Excel
     */
    private function definirEstilos()
    {
        echo '<Styles>' . "\n";
        $style = function (
            string $id,
            array $font = [],
            array $interior = [],
            array $alignment = [],
            ?string $numberFormat = null,
            array $borders = []
        ): void {
            $this->estilo($id, [
                'font'         => $font,
                'interior'     => $interior,
                'alignment'    => $alignment,
                'numberFormat' => $numberFormat,
                'borders'      => $borders,
            ]);
        };

        $bordesSuaves = $this->crearBordes('#CCCCCC');
        $bordesSuavesSep = $this->crearBordes('#CCCCCC', 3, '#333333');
        $bordesDia = $this->crearBordes('#CCCCCC', 1, '#CCCCCC', 3, '#666666');
        $bordesDiaSep = $this->crearBordes('#CCCCCC', 3, '#333333', 3, '#666666');
        $bordesTotal = $this->crearBordes('#CCCCCC', 1, '#CCCCCC', 2, '#666666');
        $bordesTotalSep = $this->crearBordes('#CCCCCC', 3, '#333333', 2, '#666666');
        $bordesResumen = $this->crearBordes(null);
        $bordesResumenTotal = $this->crearBordes(null, 1, null, 2, null, 2);

        $fondoPar = ['color' => '#F0F7F0', 'pattern' => 'Solid'];
        $fondoImpar = ['color' => '#F5F5F5', 'pattern' => 'Solid'];

        $style('titulo', ['bold' => true, 'size' => 16, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#2E5090', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center']);
        $style('subtitulo', ['bold' => true, 'size' => 12, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#3D6BA8', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center']);

        $style('header', ['bold' => true, 'size' => 10, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#4A6FA5', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'wrapText' => true], null, $bordesSuaves);
        $style('header_producto', ['bold' => true, 'size' => 10, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#5D8A8A', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'wrapText' => true], null, $bordesSuaves);
        $style('header_producto_sep', ['bold' => true, 'size' => 10, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#5D8A8A', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'wrapText' => true], null, $bordesSuavesSep);

        $style('celda_amarilla', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], null, $bordesSuaves);
        $style('celda_azul', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], null, $bordesSuaves);
        $style('celda_amarilla_dia', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], null, $bordesDia);
        $style('celda_azul_dia', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], null, $bordesDia);

        $style('numero_amarillo', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesSuaves);
        $style('numero_amarillo_sep', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesSuavesSep);
        $style('numero_azul', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesSuaves);
        $style('numero_azul_sep', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesSuavesSep);
        $style('numero_amarillo_dia', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesDia);
        $style('numero_amarillo_dia_sep', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesDiaSep);
        $style('numero_azul_dia', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesDia);
        $style('numero_azul_dia_sep', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesDiaSep);

        $style('numero_amarillo_bold', ['bold' => true, 'size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesSuaves);
        $style('numero_amarillo_dia_bold', ['bold' => true, 'size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesDia);
        $style('numero_azul_bold', ['bold' => true, 'size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesSuaves);
        $style('numero_azul_dia_bold', ['bold' => true, 'size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesDia);

        $style('titulo_mes', ['bold' => true, 'size' => 12, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#6A7FA8', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center']);

        $style('total_mes_label', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], null, $bordesTotal);
        $style('total_mes_numero', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesTotal);
        $style('total_mes_numero_sep', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesTotalSep);

        $style('resumen_titulo', ['bold' => true, 'size' => 13, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#4D6A83', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center']);
        $style('resumen_header', ['bold' => true, 'size' => 10, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#6E8EA3', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], null, $bordesResumen);
        $style('resumen_par', ['size' => 10, 'name' => 'Arial'], ['color' => '#EEF4F1', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesResumen);
        $style('resumen_impar', ['size' => 10, 'name' => 'Arial'], ['color' => '#F4F5F7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesResumen);
        $style('resumen_nombre', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#EEF4F1', 'pattern' => 'Solid'], ['horizontal' => 'Left', 'vertical' => 'Center'], null, $bordesResumen);
        $style('resumen_nombre_impar', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#F4F5F7', 'pattern' => 'Solid'], ['horizontal' => 'Left', 'vertical' => 'Center'], null, $bordesResumen);
        $style('resumen_total', ['bold' => true, 'size' => 11, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#4D6A83', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesResumenTotal);
        $style('resumen_total_label', ['bold' => true, 'size' => 11, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#4D6A83', 'pattern' => 'Solid'], ['horizontal' => 'Left', 'vertical' => 'Center'], null, $bordesResumenTotal);

        $style('header_ma', ['bold' => true, 'size' => 9, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#7A6A8A', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'wrapText' => true], null, $bordesSuaves);
        $style('header_ma_sep', ['bold' => true, 'size' => 9, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#7A6A8A', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'wrapText' => true], null, $bordesSuavesSep);

        $style('ma_amarillo', ['size' => 9, 'color' => '#5A4A6A', 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesSuaves);
        $style('ma_amarillo_sep', ['size' => 9, 'color' => '#5A4A6A', 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesSuavesSep);
        $style('ma_azul', ['size' => 9, 'color' => '#5A4A6A', 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesSuaves);
        $style('ma_azul_sep', ['size' => 9, 'color' => '#5A4A6A', 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesSuavesSep);
        $style('ma_amarillo_dia', ['size' => 9, 'color' => '#5A4A6A', 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesDia);
        $style('ma_amarillo_dia_sep', ['size' => 9, 'color' => '#5A4A6A', 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesDiaSep);
        $style('ma_azul_dia', ['size' => 9, 'color' => '#5A4A6A', 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesDia);
        $style('ma_azul_dia_sep', ['size' => 9, 'color' => '#5A4A6A', 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesDiaSep);
        $style('total_mes_ma', ['bold' => true, 'size' => 9, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesTotal);
        $style('total_mes_ma_sep', ['bold' => true, 'size' => 9, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesTotalSep);

        echo '</Styles>' . "\n";
    }

    private function estilo(string $id, array $config): void
    {
        echo '<Style ss:ID="' . $id . '">';

        if (!empty($config['numberFormat'])) {
            echo '<NumberFormat ss:Format="' . $config['numberFormat'] . '"/>';
        }

        if (!empty($config['font'])) {
            $attrs = [];
            if (!empty($config['font']['bold'])) {
                $attrs[] = 'ss:Bold="1"';
            }
            if (!empty($config['font']['size'])) {
                $attrs[] = 'ss:Size="' . (int) $config['font']['size'] . '"';
            }
            if (!empty($config['font']['color'])) {
                $attrs[] = 'ss:Color="' . $config['font']['color'] . '"';
            }
            if (!empty($config['font']['name'])) {
                $attrs[] = 'ss:FontName="' . $config['font']['name'] . '"';
            }
            echo '<Font ' . implode(' ', $attrs) . '/>';
        }

        if (!empty($config['interior'])) {
            $attrs = [];
            if (!empty($config['interior']['color'])) {
                $attrs[] = 'ss:Color="' . $config['interior']['color'] . '"';
            }
            if (!empty($config['interior']['pattern'])) {
                $attrs[] = 'ss:Pattern="' . $config['interior']['pattern'] . '"';
            }
            echo '<Interior ' . implode(' ', $attrs) . '/>';
        }

        if (!empty($config['alignment'])) {
            $attrs = [];
            if (!empty($config['alignment']['horizontal'])) {
                $attrs[] = 'ss:Horizontal="' . $config['alignment']['horizontal'] . '"';
            }
            if (!empty($config['alignment']['vertical'])) {
                $attrs[] = 'ss:Vertical="' . $config['alignment']['vertical'] . '"';
            }
            if (!empty($config['alignment']['wrapText'])) {
                $attrs[] = 'ss:WrapText="1"';
            }
            echo '<Alignment ' . implode(' ', $attrs) . '/>';
        }

        if (!empty($config['borders'])) {
            echo '<Borders>';
            foreach ($config['borders'] as $border) {
                $attrs = [
                    'ss:Position="' . $border['position'] . '"',
                    'ss:LineStyle="Continuous"',
                    'ss:Weight="' . (int) $border['weight'] . '"',
                ];
                if (array_key_exists('color', $border) && $border['color'] !== null) {
                    $attrs[] = 'ss:Color="' . $border['color'] . '"';
                }
                echo '<Border ' . implode(' ', $attrs) . '/>';
            }
            echo '</Borders>';
        }

        echo '</Style>' . "\n";
    }

    private function crearBordes(
        ?string $baseColor,
        int $rightWeight = 1,
        ?string $rightColor = null,
        int $topWeight = 1,
        ?string $topColor = null,
        int $bottomWeight = 1,
        ?string $bottomColor = null
    ): array {
        return [
            ['position' => 'Left', 'weight' => 1, 'color' => $baseColor],
            ['position' => 'Right', 'weight' => $rightWeight, 'color' => $rightColor ?? $baseColor],
            ['position' => 'Top', 'weight' => $topWeight, 'color' => $topColor ?? $baseColor],
            ['position' => 'Bottom', 'weight' => $bottomWeight, 'color' => $bottomColor ?? $baseColor],
        ];
    }

    /**
     * Definir anchos de columna
     */
    private function definirAnchos($productosUnicos)
    {
        echo '<Column ss:Width="80"/>' . "\n";  // FECHA
        echo '<Column ss:Width="50"/>' . "\n";  // TURNO
        echo '<Column ss:Width="120"/>' . "\n"; // NOMBRE
        echo '<Column ss:Width="70"/>' . "\n";  // STOCK (L)
        echo '<Column ss:Width="70"/>' . "\n";  // RESERVA

        // Columnas dinámicas de productos: Stock | Cant.Prod | Merma | Agrega
        foreach ($productosUnicos as $producto) {
            echo '<Column ss:Width="65"/>' . "\n";  // Stock
            echo '<Column ss:Width="65"/>' . "\n";  // Cant.Prod
            echo '<Column ss:Width="42"/>' . "\n";  // Merma
            echo '<Column ss:Width="42"/>' . "\n";  // Agrega
        }
    }

    /**
     * Obtener texto de fechas para el subtítulo
     */
    private function obtenerTextoFechas($fecha_inicio, $fecha_fin)
    {
        if (empty($fecha_inicio) && empty($fecha_fin)) {
            return 'Todos los registros';
        }

        $desde = !empty($fecha_inicio) ? date('d/m/Y', strtotime($fecha_inicio)) : 'N/A';
        $hasta = !empty($fecha_fin) ? date('d/m/Y', strtotime($fecha_fin)) : 'N/A';

        return "Desde: {$desde} - Hasta: {$hasta}";
    }

    /**
     * Generar encabezados de columnas
     */
    private function generarEncabezados($productosUnicos)
    {
        echo '<Row ss:Height="50">' . "\n";

        // Columnas fijas (sin AGREGA ni MERMA)
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">FECHA</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">TURNO</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">NOMBRE</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">STOCK (L)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">RESERVA</Data></Cell>';

        // Columnas dinámicas: Stock | Cant.Prod | M. | Ag.
        $totalProductos = count($productosUnicos);
        $indice = 0;
        foreach ($productosUnicos as $producto) {
            $np = htmlspecialchars($producto, ENT_XML1);
            $esUltimo = ($indice === $totalProductos - 1);

            echo '<Cell ss:StyleID="header_producto"><Data ss:Type="String">' . $np . ' Stock</Data></Cell>';
            echo '<Cell ss:StyleID="header_producto"><Data ss:Type="String">' . $np . ' Cant.Prod</Data></Cell>';
            echo '<Cell ss:StyleID="header_ma"><Data ss:Type="String">M.</Data></Cell>';

            $estiloAg = $esUltimo ? 'header_ma' : 'header_ma_sep';
            echo '<Cell ss:StyleID="' . $estiloAg . '"><Data ss:Type="String">Ag.</Data></Cell>';

            $indice++;
        }

        echo '</Row>' . "\n";
    }

    /**
     * Generar filas de datos agrupadas por mes
     */
    private function generarDatos($datosConProductos, $productosUnicos)
    {
        $meses_es = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];

        // Agrupar datos por mes
        $datosPorMes = [];
        foreach ($datosConProductos as $dato) {
            $mes = date('Y-m', strtotime($dato['inventario']->created_at));
            $datosPorMes[$mes][] = $dato;
        }

        $totalColumnas = 5 + (count($productosUnicos) * 4) - 1;
        $primerMes = true;

        foreach ($datosPorMes as $mes => $datosDelMes) {
            // Fila vacía entre meses (excepto antes del primero)
            if (!$primerMes) {
                echo '<Row></Row>' . "\n";
            }
            $primerMes = false;

            // Título del mes
            $mesTexto = str_replace(
                array_keys($meses_es),
                array_values($meses_es),
                date('F Y', strtotime($mes . '-01'))
            );
            echo '<Row ss:Height="22">';
            echo '<Cell ss:MergeAcross="' . $totalColumnas . '" ss:StyleID="titulo_mes"><Data ss:Type="String">' . strtoupper($mesTexto) . '</Data></Cell>';
            echo '</Row>' . "\n";

            // Encabezados para este mes
            $this->generarEncabezados($productosUnicos);

            // Filas de datos del mes
            $indiceDia = 0;
            $diaAnterior = null;

            $sumStock   = 0.0;
            $sumReserva = 0.0;
            $countFilasMes = 0;

            $sumProductos = [];
            foreach ($productosUnicos as $nombreProducto) {
                $sumProductos[$nombreProducto] = [
                    'stock'               => 0.0,
                    'cantidad_produccion' => 0.0,
                    'merma'               => 0.0,
                    'agrega'              => 0.0,
                ];
            }

            foreach ($datosDelMes as $dato) {
                $inv = $dato['inventario'];
                $productos = $dato['productos'];

                $diaActual = date('Y-m-d', strtotime($inv->created_at));
                $esPrimerRegistroDelDia = ($diaActual !== $diaAnterior);

                if ($esPrimerRegistroDelDia && $diaAnterior !== null) {
                    $indiceDia++;
                }

                $esAmarillo = ($indiceDia % 2 === 0);
                $estiloCelda  = $esAmarillo ? 'celda_amarilla'     : 'celda_azul';
                $estiloNumero = $esAmarillo ? 'numero_amarillo'     : 'numero_azul';
                $estiloNegrita = $esAmarillo ? 'numero_amarillo_bold' : 'numero_azul_bold';

                if ($esPrimerRegistroDelDia && $diaAnterior !== null) {
                    $estiloCelda  = $esAmarillo ? 'celda_amarilla_dia'      : 'celda_azul_dia';
                    $estiloNumero = $esAmarillo ? 'numero_amarillo_dia'      : 'numero_azul_dia';
                    $estiloNegrita = $esAmarillo ? 'numero_amarillo_dia_bold' : 'numero_azul_dia_bold';
                }

                echo '<Row ss:Height="' . self::ALTURA_FILA_DATOS . '">' . "\n";

                if ($esPrimerRegistroDelDia) {
                    $fecha = date('d-M-y', strtotime($inv->created_at));
                    echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String">' . htmlspecialchars($fecha, ENT_XML1) . '</Data></Cell>';
                } else {
                    echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String"></Data></Cell>';
                }

                $turno = strtoupper($inv->turno ?? 'AM');
                echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String">' . htmlspecialchars($turno, ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $estiloCelda . '"><Data ss:Type="String">' . htmlspecialchars($inv->nombre ?? '', ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $estiloNegrita . '"><Data ss:Type="Number">' . number_format($inv->stock ?? 0, 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->reserva ?? 0, 2, '.', '') . '</Data></Cell>';

                $sumStock   += (float)($inv->stock ?? 0);
                $sumReserva += (float)($inv->reserva ?? 0);
                $countFilasMes++;

                $totalProductos = count($productos);
                $indice = 0;
                foreach ($productos as $nombreProducto => $datosProducto) {
                    $esUltimoProducto = ($indice === $totalProductos - 1);

                    // Stock
                    if ($datosProducto['stock'] === null) {
                        echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="String">-</Data></Cell>';
                    } else {
                        $sumProductos[$nombreProducto]['stock'] += (float)$datosProducto['stock'];
                        echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($datosProducto['stock'], 2, '.', '') . '</Data></Cell>';
                    }

                    // Cant.Prod
                    if ($datosProducto['cantidad_produccion'] === null) {
                        echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="String">-</Data></Cell>';
                    } else {
                        $sumProductos[$nombreProducto]['cantidad_produccion'] += (float)$datosProducto['cantidad_produccion'];
                        echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($datosProducto['cantidad_produccion'], 2, '.', '') . '</Data></Cell>';
                    }

                    // Merma (compacta)
                    $estiloMa     = $esAmarillo ? ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'ma_amarillo_dia'     : 'ma_amarillo')     : ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'ma_azul_dia'     : 'ma_azul');
                    $estiloMaSep  = $esAmarillo ? ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'ma_amarillo_dia_sep' : 'ma_amarillo_sep') : ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'ma_azul_dia_sep' : 'ma_azul_sep');

                    if ($datosProducto['merma'] === null) {
                        echo '<Cell ss:StyleID="' . $estiloMa . '"><Data ss:Type="String">-</Data></Cell>';
                    } else {
                        $sumProductos[$nombreProducto]['merma'] += (float)$datosProducto['merma'];
                        echo '<Cell ss:StyleID="' . $estiloMa . '"><Data ss:Type="Number">' . (int)$datosProducto['merma'] . '</Data></Cell>';
                    }

                    // Agrega (compacta, con separador si no es último)
                    $estiloAg = $esUltimoProducto ? $estiloMa : $estiloMaSep;
                    if ($datosProducto['agrega'] === null) {
                        echo '<Cell ss:StyleID="' . $estiloAg . '"><Data ss:Type="String">-</Data></Cell>';
                    } else {
                        $sumProductos[$nombreProducto]['agrega'] += (float)$datosProducto['agrega'];
                        echo '<Cell ss:StyleID="' . $estiloAg . '"><Data ss:Type="Number">' . (int)$datosProducto['agrega'] . '</Data></Cell>';
                    }

                    $indice++;
                }

                echo '</Row>' . "\n";
                $diaAnterior = $diaActual;
            }

            // Fila TOTAL MES
            echo '<Row ss:Height="' . self::ALTURA_FILA_CIERRE . '">' . "\n";
            echo '<Cell ss:StyleID="total_mes_label"><Data ss:Type="String">TOTAL MES</Data></Cell>';
            echo '<Cell ss:StyleID="total_mes_label"><Data ss:Type="String"></Data></Cell>';
            echo '<Cell ss:StyleID="total_mes_label"><Data ss:Type="String"></Data></Cell>';
            echo '<Cell ss:StyleID="total_mes_numero"><Data ss:Type="Number">' . number_format($sumStock, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="total_mes_numero"><Data ss:Type="Number">' . number_format($sumReserva, 2, '.', '') . '</Data></Cell>';

            $totalProductosUnicos = count($productosUnicos);
            $indiceProducto = 0;
            foreach ($productosUnicos as $nombreProducto) {
                $esUltimo = ($indiceProducto === $totalProductosUnicos - 1);

                echo '<Cell ss:StyleID="total_mes_numero"><Data ss:Type="Number">' . number_format($sumProductos[$nombreProducto]['stock'], 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="total_mes_numero"><Data ss:Type="Number">' . number_format($sumProductos[$nombreProducto]['cantidad_produccion'], 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="total_mes_ma"><Data ss:Type="Number">' . (int)$sumProductos[$nombreProducto]['merma'] . '</Data></Cell>';

                $estiloAg = $esUltimo ? 'total_mes_ma' : 'total_mes_ma_sep';
                echo '<Cell ss:StyleID="' . $estiloAg . '"><Data ss:Type="Number">' . (int)$sumProductos[$nombreProducto]['agrega'] . '</Data></Cell>';

                $indiceProducto++;
            }
            echo '</Row>' . "\n";

        }
    }

    private function construirResumenDetalladoMensual(array $datosConProductos, array $productosUnicos): array
    {
        $mensual = [];
        foreach ($datosConProductos as $dato) {
            $inv = $dato['inventario'];
            $mes = date('Y-m', strtotime($inv->created_at));
            $dia = date('Y-m-d', strtotime($inv->created_at));
            $turno = strtoupper(trim($inv->turno ?? 'AM'));

            if (!isset($mensual[$mes])) {
                $mensual[$mes] = [
                    'mes' => $mes,
                    'leche' => ['registros' => 0, 'litros_recibidos' => 0.0, 'reserva_total' => 0.0, 'dias' => [], 'turnos_am' => 0, 'turnos_pm' => 0],
                    'productos' => [],
                ];
                foreach ($productosUnicos as $producto) {
                    $mensual[$mes]['productos'][$producto] = [
                        'unidades' => 0.0,
                        'litros_usados' => 0.0,
                        'merma' => 0.0,
                        'agrega' => 0.0,
                        'valor_contado' => 0.0,
                        'valor_credito' => 0.0,
                    ];
                }
            }

            $mensual[$mes]['leche']['registros']++;
            $mensual[$mes]['leche']['litros_recibidos'] += (float)($inv->stock ?? 0);
            $mensual[$mes]['leche']['reserva_total'] += (float)($inv->reserva ?? 0);
            $mensual[$mes]['leche']['dias'][$dia] = true;
            if ($turno === 'PM') {
                $mensual[$mes]['leche']['turnos_pm']++;
            } else {
                $mensual[$mes]['leche']['turnos_am']++;
            }

            foreach ($dato['productos'] as $producto => $valores) {
                if ($valores['cantidad_produccion'] === null) {
                    continue;
                }
                $unidades = (float)$valores['cantidad_produccion'];
                $mensual[$mes]['productos'][$producto]['unidades'] += $unidades;
                $mensual[$mes]['productos'][$producto]['litros_usados'] += (float)($valores['litros_usados'] ?? 0);
                $mensual[$mes]['productos'][$producto]['merma'] += (float)($valores['merma'] ?? 0);
                $mensual[$mes]['productos'][$producto]['agrega'] += (float)($valores['agrega'] ?? 0);
                $mensual[$mes]['productos'][$producto]['valor_contado'] += (float)($valores['valor_contado'] ?? 0);
                $mensual[$mes]['productos'][$producto]['valor_credito'] += (float)($valores['valor_credito'] ?? 0);
            }
        }

        return $mensual;
    }

    private function generarWorksheetResumenDetallado(array $mensual, array $productosUnicos, $fecha_inicio, $fecha_fin): void
    {
        $columnas = 8;
        echo '<Worksheet ss:Name="Resumen Detallado">' . "\n";
        echo '<Table>' . "\n";
        for ($i = 0; $i < $columnas; $i++) {
            $ancho = $i === 0 ? 170 : 110;
            echo '<Column ss:Width="' . $ancho . '"/>' . "\n";
        }

        echo '<Row ss:Height="24"><Cell ss:MergeAcross="' . ($columnas - 1) . '" ss:StyleID="titulo"><Data ss:Type="String">RESUMEN DETALLADO</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="' . ($columnas - 1) . '" ss:StyleID="subtitulo"><Data ss:Type="String">' . htmlspecialchars($this->obtenerTextoFechas($fecha_inicio, $fecha_fin), ENT_XML1) . '</Data></Cell></Row>' . "\n";
        echo '<Row></Row>' . "\n";

        $this->renderBloqueResumenLeche($mensual, $columnas);
        echo '<Row></Row>' . "\n";
        $this->renderBloqueProduccionMensual($mensual, $productosUnicos, $columnas);
        echo '<Row></Row>' . "\n";
        $this->renderBloqueEficiencia($mensual, $productosUnicos, $columnas);

        echo '</Table></Worksheet>' . "\n";
    }

    private function renderBloqueResumenLeche(array $mensual, int $columnas): void
    {
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="' . ($columnas - 1) . '" ss:StyleID="resumen_titulo"><Data ss:Type="String">1) RESUMEN MENSUAL DE LECHE</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="18">';
        foreach (['MES', 'REGISTROS', 'LITROS RECIBIDOS', 'PROMEDIO DIARIO', 'RESERVA TOTAL', 'TURNOS AM', 'TURNOS PM'] as $titulo) {
            echo '<Cell ss:StyleID="resumen_header"><Data ss:Type="String">' . $titulo . '</Data></Cell>';
        }
        echo '<Cell ss:StyleID="resumen_header"><Data ss:Type="String">DIAS ACTIVOS</Data></Cell></Row>' . "\n";

        ksort($mensual);
        $par = true;
        foreach ($mensual as $item) {
            $styleNombre = $par ? 'resumen_nombre' : 'resumen_nombre_impar';
            $styleDato = $par ? 'resumen_par' : 'resumen_impar';
            $diasActivos = max(1, count($item['leche']['dias']));
            $promedio = $item['leche']['litros_recibidos'] / $diasActivos;

            echo '<Row ss:Height="18">';
            echo '<Cell ss:StyleID="' . $styleNombre . '"><Data ss:Type="String">' . htmlspecialchars($this->textoMes($item['mes']), ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . (int)$item['leche']['registros'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($item['leche']['litros_recibidos'], 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($promedio, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($item['leche']['reserva_total'], 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . (int)$item['leche']['turnos_am'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . (int)$item['leche']['turnos_pm'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . $diasActivos . '</Data></Cell>';
            echo '</Row>' . "\n";
            $par = !$par;
        }
    }

    private function renderBloqueProduccionMensual(array $mensual, array $productosUnicos, int $columnas): void
    {
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="' . ($columnas - 1) . '" ss:StyleID="resumen_titulo"><Data ss:Type="String">2) PRODUCCION MENSUAL POR PRODUCTO</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="18">';
        foreach (['MES', 'PRODUCTO', 'UNIDADES', 'LITROS USADOS', 'MERMA', 'AGREGA', 'VALOR CONTADO', 'VALOR CREDITO'] as $titulo) {
            echo '<Cell ss:StyleID="resumen_header"><Data ss:Type="String">' . $titulo . '</Data></Cell>';
        }
        echo '</Row>' . "\n";

        ksort($mensual);
        $par = true;
        $totales = ['unidades' => 0.0, 'litros_usados' => 0.0, 'merma' => 0.0, 'agrega' => 0.0, 'valor_contado' => 0.0, 'valor_credito' => 0.0];

        foreach ($mensual as $item) {
            foreach ($productosUnicos as $producto) {
                $p = $item['productos'][$producto];
                if ($p['unidades'] <= 0 && $p['litros_usados'] <= 0 && $p['merma'] <= 0 && $p['agrega'] <= 0) {
                    continue;
                }
                $styleNombre = $par ? 'resumen_nombre' : 'resumen_nombre_impar';
                $styleDato = $par ? 'resumen_par' : 'resumen_impar';

                echo '<Row ss:Height="18">';
                echo '<Cell ss:StyleID="' . $styleNombre . '"><Data ss:Type="String">' . htmlspecialchars($this->textoMes($item['mes']), ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleNombre . '"><Data ss:Type="String">' . htmlspecialchars($producto, ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($p['unidades'], 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($p['litros_usados'], 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($p['merma'], 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($p['agrega'], 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($p['valor_contado'], 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($p['valor_credito'], 2, '.', '') . '</Data></Cell>';
                echo '</Row>' . "\n";

                foreach ($totales as $k => $v) {
                    $totales[$k] += (float)$p[$k];
                }
                $par = !$par;
            }
        }

        echo '<Row ss:Height="20">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="resumen_total_label"><Data ss:Type="String">TOTAL GENERAL</Data></Cell>';
        echo '<Cell ss:StyleID="resumen_total"><Data ss:Type="Number">' . number_format($totales['unidades'], 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="resumen_total"><Data ss:Type="Number">' . number_format($totales['litros_usados'], 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="resumen_total"><Data ss:Type="Number">' . number_format($totales['merma'], 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="resumen_total"><Data ss:Type="Number">' . number_format($totales['agrega'], 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="resumen_total"><Data ss:Type="Number">' . number_format($totales['valor_contado'], 2, '.', '') . '</Data></Cell>';
        echo '<Cell ss:StyleID="resumen_total"><Data ss:Type="Number">' . number_format($totales['valor_credito'], 2, '.', '') . '</Data></Cell>';
        echo '</Row>' . "\n";
    }

    private function renderBloqueEficiencia(array $mensual, array $productosUnicos, int $columnas): void
    {
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="' . ($columnas - 1) . '" ss:StyleID="resumen_titulo"><Data ss:Type="String">3) EFICIENCIA DE CONVERSION LECHE -> PRODUCTO</Data></Cell></Row>' . "\n";
        echo '<Row ss:Height="18">';
        foreach (['MES', 'LITROS RECIBIDOS', 'LITROS USADOS', '% APROVECHAMIENTO', 'DIFERENCIA (RECIBIDO-USADO)', 'VALOR CONTADO', 'VALOR CREDITO', 'OBSERVACION'] as $titulo) {
            echo '<Cell ss:StyleID="resumen_header"><Data ss:Type="String">' . $titulo . '</Data></Cell>';
        }
        echo '</Row>' . "\n";

        ksort($mensual);
        $par = true;
        foreach ($mensual as $item) {
            $litrosUsados = 0.0;
            $valorContado = 0.0;
            $valorCredito = 0.0;
            foreach ($productosUnicos as $producto) {
                $litrosUsados += (float)$item['productos'][$producto]['litros_usados'];
                $valorContado += (float)$item['productos'][$producto]['valor_contado'];
                $valorCredito += (float)$item['productos'][$producto]['valor_credito'];
            }

            $litrosRecibidos = (float)$item['leche']['litros_recibidos'];
            $aprovechamiento = $litrosRecibidos > 0 ? ($litrosUsados / $litrosRecibidos) * 100 : 0;
            $diferencia = $litrosRecibidos - $litrosUsados;
            $obs = $aprovechamiento >= 95 ? 'Eficiencia alta' : ($aprovechamiento >= 80 ? 'Eficiencia media' : 'Revisar mermas/proceso');

            $styleNombre = $par ? 'resumen_nombre' : 'resumen_nombre_impar';
            $styleDato = $par ? 'resumen_par' : 'resumen_impar';
            echo '<Row ss:Height="18">';
            echo '<Cell ss:StyleID="' . $styleNombre . '"><Data ss:Type="String">' . htmlspecialchars($this->textoMes($item['mes']), ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($litrosRecibidos, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($litrosUsados, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($aprovechamiento, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($diferencia, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($valorContado, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . number_format($valorCredito, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="String">' . $obs . '</Data></Cell>';
            echo '</Row>' . "\n";
            $par = !$par;
        }
    }

    private function textoMes(string $mes): string
    {
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];
        [$anio, $numMes] = explode('-', $mes);
        return ($meses[$numMes] ?? $numMes) . ' ' . $anio;
    }

    /**
     * Exportar reporte de control de calidad (mantener método existente)
     */
    public function exportarCalidadExcel($nombre = '', $fecha_inicio = '', $fecha_fin = '')
    {
    }
}
