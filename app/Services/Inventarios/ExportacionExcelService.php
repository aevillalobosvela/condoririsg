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
        $resumenOtros = $this->construirResumenDetalladoMensual($datosOtros, $productosOtros);
        $this->generarWorksheetResumenDetallado($resumenOtros, $productosOtros, $fecha_inicio, $fecha_fin);

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
        $idx = array_search('LECHE', $productosUnicos);
        if ($idx !== false) {
            unset($productosUnicos[$idx]);
            $productosUnicos = array_values($productosUnicos);
            array_unshift($productosUnicos, 'LECHE');
        }

        return $productosUnicos;
    }

    /**
     * Normalizar nombre de producto para casos especiales
     */
    private function normalizarNombreProducto($nombre)
    {
        $nombreUpper = strtoupper(trim($nombre ?? ''));
        // Caso especial: unificar variantes de yogurt griego 250 gramos
        if ($nombreUpper === 'YOGURT GRIEGO' || $nombreUpper === 'YOGURT GRIEGO (250 GRAMOS)' || $nombreUpper === 'YOGURT GRIEGO 250 GRAMOS') {
            return 'YOGURT GRIEGO 250 GRAMOS';
        }
        
        return $nombre;
    }

    /**
     * Productos "simples": una sola columna de cantidad_produccion, sin Stock, MERMA ni AGREGA.
     */
    private function esProductoSimple(string $nombre): bool
    {
        return strtoupper(trim($nombre)) === 'LECHE';
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
                return strcmp($fechaA, $fechaB); // ascendente: más antiguo primero
            }

            $turnoOrden = ['AM' => 0, 'PM' => 1];
            $turnoA = strtoupper(trim($a->turno ?? 'AM'));
            $turnoB = strtoupper(trim($b->turno ?? 'AM'));
            $ordenA = $turnoOrden[$turnoA] ?? 99;
            $ordenB = $turnoOrden[$turnoB] ?? 99;

            if ($ordenA !== $ordenB) {
                return $ordenA <=> $ordenB;
            }

            return strtotime($a->created_at) <=> strtotime($b->created_at); // ascendente
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

        $simples   = array_filter($productosUnicos, fn($p) => $this->esProductoSimple($p));
        $complejos = array_filter($productosUnicos, fn($p) => !$this->esProductoSimple($p));
        $totalColumnas = 4 + count($simples) + (count($complejos) * 4) - 1;
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

        // Estilos para Leche Utilizada (1 decimal) y Producción (0 decimales)
        $style('leche_amarillo', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesSuaves);
        $style('leche_amarillo_sep', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesSuavesSep);
        $style('leche_azul', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesSuaves);
        $style('leche_azul_sep', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesSuavesSep);
        $style('leche_amarillo_dia', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesDia);
        $style('leche_amarillo_dia_sep', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesDiaSep);
        $style('leche_azul_dia', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesDia);
        $style('leche_azul_dia_sep', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesDiaSep);

        $style('prod_amarillo', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesSuaves);
        $style('prod_amarillo_sep', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesSuavesSep);
        $style('prod_azul', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesSuaves);
        $style('prod_azul_sep', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesSuavesSep);
        $style('prod_amarillo_dia', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesDia);
        $style('prod_amarillo_dia_sep', ['size' => 10, 'name' => 'Arial'], $fondoPar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesDiaSep);
        $style('prod_azul_dia', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesDia);
        $style('prod_azul_dia_sep', ['size' => 10, 'name' => 'Arial'], $fondoImpar, ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesDiaSep);

        $style('titulo_mes', ['bold' => true, 'size' => 12, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#6A7FA8', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center']);

        $style('total_mes_label', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], null, $bordesTotal);
        $style('total_mes_numero', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesTotal);
        $style('total_mes_numero_sep', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesTotalSep);
        $style('total_mes_leche', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesTotal);
        $style('total_mes_leche_sep', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.0', $bordesTotalSep);
        $style('total_mes_prod', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesTotal);
        $style('total_mes_prod_sep', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#FFFDE7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesTotalSep);

        $style('resumen_titulo', ['bold' => true, 'size' => 13, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#4D6A83', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center']);
        $style('resumen_header', ['bold' => true, 'size' => 10, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#6E8EA3', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], null, $bordesResumen);
        $style('resumen_par', ['size' => 10, 'name' => 'Arial'], ['color' => '#EEF4F1', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesResumen);
        $style('resumen_impar', ['size' => 10, 'name' => 'Arial'], ['color' => '#F4F5F7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesResumen);
        $style('resumen_nombre', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#EEF4F1', 'pattern' => 'Solid'], ['horizontal' => 'Left', 'vertical' => 'Center'], null, $bordesResumen);
        $style('resumen_nombre_impar', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#F4F5F7', 'pattern' => 'Solid'], ['horizontal' => 'Left', 'vertical' => 'Center'], null, $bordesResumen);

        // Variantes con borde superior grueso para separar grupos (mes o producto)
        $bordesSep     = $this->crearBordes(null, 1, null, 2, '#4D6A83');
        $style('resumen_sep_nombre',       ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#EEF4F1', 'pattern' => 'Solid'], ['horizontal' => 'Left',   'vertical' => 'Center'], null,   $bordesSep);
        $style('resumen_sep_nombre_impar', ['bold' => true, 'size' => 10, 'name' => 'Arial'], ['color' => '#F4F5F7', 'pattern' => 'Solid'], ['horizontal' => 'Left',   'vertical' => 'Center'], null,   $bordesSep);
        $style('resumen_sep_par',          ['size' => 10,                  'name' => 'Arial'], ['color' => '#EEF4F1', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesSep);
        $style('resumen_sep_impar',        ['size' => 10,                  'name' => 'Arial'], ['color' => '#F4F5F7', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0.00', $bordesSep);

        $style('resumen_total', ['bold' => true, 'size' => 11, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#4D6A83', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center'], '0', $bordesResumenTotal);
        $style('resumen_total_label', ['bold' => true, 'size' => 11, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#4D6A83', 'pattern' => 'Solid'], ['horizontal' => 'Left', 'vertical' => 'Center'], null, $bordesResumenTotal);

        $style('header_ma', ['bold' => true, 'size' => 9, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#7A6A8A', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'wrapText' => true], null, $bordesSuaves);
        $style('header_ma_sep', ['bold' => true, 'size' => 9, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#7A6A8A', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'wrapText' => true], null, $bordesSuavesSep);
        // Variantes con texto rotado 90° para MERMA / AGREGA
        $style('header_ma_rotado',     ['bold' => true, 'size' => 9, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#7A6A8A', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'verticalText' => true], null, $bordesSuaves);
        $style('header_ma_sep_rotado', ['bold' => true, 'size' => 9, 'color' => '#FFFFFF', 'name' => 'Arial'], ['color' => '#7A6A8A', 'pattern' => 'Solid'], ['horizontal' => 'Center', 'vertical' => 'Center', 'verticalText' => true], null, $bordesSuavesSep);

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
            if (!empty($config['alignment']['verticalText'])) {
                $attrs[] = 'ss:VerticalText="1"';
            }
            if (isset($config['alignment']['rotate'])) {
                $attrs[] = 'ss:Rotate="' . (int) $config['alignment']['rotate'] . '"';
            }
            if (isset($config['alignment']['textRotation'])) {
                $attrs[] = 'ss:TextRotation="' . (int) $config['alignment']['textRotation'] . '"';
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
        echo '<Column ss:Width="70"/>' . "\n";  // CANT. LECHE
        echo '<Column ss:Width="70"/>' . "\n";  // RESERVA

        // Columnas dinámicas: simples → 1 col, complejos → 4 cols (Stock | Cant.Prod | MERMA | AGREGA)
        foreach ($productosUnicos as $producto) {
            if ($this->esProductoSimple($producto)) {
                echo '<Column ss:Width="65"/>' . "\n";  // LECHE (L)
            } else {
                echo '<Column ss:Width="65"/>' . "\n";  // Stock
                echo '<Column ss:Width="65"/>' . "\n";  // Cant.Prod
                echo '<Column ss:Width="52"/>' . "\n";  // MERMA
                echo '<Column ss:Width="52"/>' . "\n";  // AGREGA
            }
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
        // Fila 1 del encabezado
        echo '<Row ss:Height="65">' . "\n";

        // Columnas fijas (sin AGREGA ni MERMA) - combinadas verticalmente
        echo '<Cell ss:MergeDown="1" ss:StyleID="header"><Data ss:Type="String">FECHA</Data></Cell>';
        echo '<Cell ss:MergeDown="1" ss:StyleID="header"><Data ss:Type="String">TURNO</Data></Cell>';
        echo '<Cell ss:MergeDown="1" ss:StyleID="header"><Data ss:Type="String">CANT. LECHE</Data></Cell>';
        echo '<Cell ss:MergeDown="1" ss:StyleID="header"><Data ss:Type="String">RESERVA</Data></Cell>';

        // Columnas dinámicas: simples → LECHE (L), complejos → Stock | Cant.Prod | MERMA | AGREGA
        $totalProductos = count($productosUnicos);
        $indice = 0;
        foreach ($productosUnicos as $producto) {
            $np = htmlspecialchars($producto, ENT_XML1);
            $esUltimo = ($indice === $totalProductos - 1);

            if ($this->esProductoSimple($producto)) {
                $estiloSimple = $esUltimo ? 'header_producto' : 'header_producto_sep';
                echo '<Cell ss:MergeDown="1" ss:StyleID="' . $estiloSimple . '"><Data ss:Type="String">LECHE (L)</Data></Cell>';
            } else {
                // Producto complejo: nombre arriba combinando 2 columnas, y MERMA/AGREGA combinados verticalmente
                echo '<Cell ss:MergeAcross="1" ss:StyleID="header_producto"><Data ss:Type="String">' . $np . '</Data></Cell>';
                echo '<Cell ss:MergeDown="1" ss:StyleID="header_ma_rotado"><Data ss:Type="String">MERMA</Data></Cell>';
                $estiloAg = $esUltimo ? 'header_ma_rotado' : 'header_ma_sep_rotado';
                echo '<Cell ss:MergeDown="1" ss:StyleID="' . $estiloAg . '"><Data ss:Type="String">AGREGA</Data></Cell>';
            }

            $indice++;
        }

        echo '</Row>' . "\n";

        // Fila 2 del encabezado (solo subencabezados de productos complejos)
        echo '<Row ss:Height="20">' . "\n";
        $colIndex = 5; // Columns 1-4 are fixed (FECHA, TURNO, CANT. LECHE, RESERVA)
        foreach ($productosUnicos as $producto) {
            if ($this->esProductoSimple($producto)) {
                $colIndex += 1;
            } else {
                echo '<Cell ss:Index="' . $colIndex . '" ss:StyleID="header_producto"><Data ss:Type="String">Leche utilizada</Data></Cell>';
                echo '<Cell ss:Index="' . ($colIndex + 1) . '" ss:StyleID="header_producto"><Data ss:Type="String">Producción</Data></Cell>';
                $colIndex += 4; // 2 product columns + 1 MERMA + 1 AGREGA
            }
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

        $simples   = array_filter($productosUnicos, fn($p) => $this->esProductoSimple($p));
        $complejos = array_filter($productosUnicos, fn($p) => !$this->esProductoSimple($p));
        $totalColumnas = 4 + count($simples) + (count($complejos) * 4) - 1;
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
                echo '<Cell ss:StyleID="' . $estiloNegrita . '"><Data ss:Type="Number">' . number_format($inv->stock ?? 0, 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($inv->reserva ?? 0, 2, '.', '') . '</Data></Cell>';

                $sumStock   += (float)($inv->stock ?? 0);
                $sumReserva += (float)($inv->reserva ?? 0);
                $countFilasMes++;

                $totalProductos = count($productos);
                $indice = 0;
                foreach ($productos as $nombreProducto => $datosProducto) {
                    $esUltimoProducto = ($indice === $totalProductos - 1);
                    $estiloLeche = $esAmarillo ? ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'leche_amarillo_dia' : 'leche_amarillo') : ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'leche_azul_dia' : 'leche_azul');
                    $estiloProd  = $esAmarillo ? ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'prod_amarillo_dia'  : 'prod_amarillo')  : ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'prod_azul_dia'  : 'prod_azul');

                    if ($this->esProductoSimple($nombreProducto)) {
                        // Producto simple: solo cantidad_produccion (1 decimal) con separador si no es último
                        $estiloCell = $esUltimoProducto ? $estiloLeche : ($estiloLeche . '_sep');
                        if ($datosProducto['cantidad_produccion'] === null) {
                            echo '<Cell ss:StyleID="' . $estiloCell . '"><Data ss:Type="String">-</Data></Cell>';
                        } else {
                            $sumProductos[$nombreProducto]['cantidad_produccion'] += (float)$datosProducto['cantidad_produccion'];
                            echo '<Cell ss:StyleID="' . $estiloCell . '"><Data ss:Type="Number">' . number_format($datosProducto['cantidad_produccion'], 1, '.', '') . '</Data></Cell>';
                        }
                    } else {
                        // Producto complejo: Leche utilizada (1 decimal) | Producción (entero) | MERMA | AGREGA
                        if ($datosProducto['cantidad_produccion'] === null) {
                            echo '<Cell ss:StyleID="' . $estiloLeche . '"><Data ss:Type="String">-</Data></Cell>';
                        } else {
                            $sumProductos[$nombreProducto]['cantidad_produccion'] += (float)$datosProducto['cantidad_produccion'];
                            echo '<Cell ss:StyleID="' . $estiloLeche . '"><Data ss:Type="Number">' . number_format($datosProducto['cantidad_produccion'], 1, '.', '') . '</Data></Cell>';
                        }

                        if ($datosProducto['stock'] === null) {
                            echo '<Cell ss:StyleID="' . $estiloProd . '"><Data ss:Type="String">-</Data></Cell>';
                        } else {
                            $sumProductos[$nombreProducto]['stock'] += (float)$datosProducto['stock'];
                            echo '<Cell ss:StyleID="' . $estiloProd . '"><Data ss:Type="Number">' . (int)round($datosProducto['stock']) . '</Data></Cell>';
                        }

                        $estiloMa    = $esAmarillo ? ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'ma_amarillo_dia'     : 'ma_amarillo')     : ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'ma_azul_dia'     : 'ma_azul');
                        $estiloMaSep = $esAmarillo ? ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'ma_amarillo_dia_sep' : 'ma_amarillo_sep') : ($esPrimerRegistroDelDia && $diaAnterior !== null ? 'ma_azul_dia_sep' : 'ma_azul_sep');

                        if ($datosProducto['merma'] === null) {
                            echo '<Cell ss:StyleID="' . $estiloMa . '"><Data ss:Type="String">-</Data></Cell>';
                        } else {
                            $sumProductos[$nombreProducto]['merma'] += (float)$datosProducto['merma'];
                            echo '<Cell ss:StyleID="' . $estiloMa . '"><Data ss:Type="Number">' . (int)$datosProducto['merma'] . '</Data></Cell>';
                        }

                        $estiloAg = $esUltimoProducto ? $estiloMa : $estiloMaSep;
                        if ($datosProducto['agrega'] === null) {
                            echo '<Cell ss:StyleID="' . $estiloAg . '"><Data ss:Type="String">-</Data></Cell>';
                        } else {
                            $sumProductos[$nombreProducto]['agrega'] += (float)$datosProducto['agrega'];
                            echo '<Cell ss:StyleID="' . $estiloAg . '"><Data ss:Type="Number">' . (int)$datosProducto['agrega'] . '</Data></Cell>';
                        }
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
            echo '<Cell ss:StyleID="total_mes_numero"><Data ss:Type="Number">' . number_format($sumStock, 2, '.', '') . '</Data></Cell>';
            echo '<Cell ss:StyleID="total_mes_label"><Data ss:Type="String"></Data></Cell>'; // Sumatoria de Reserva eliminada

            $totalProductosUnicos = count($productosUnicos);
            $indiceProducto = 0;
            foreach ($productosUnicos as $nombreProducto) {
                $esUltimo = ($indiceProducto === $totalProductosUnicos - 1);

                if ($this->esProductoSimple($nombreProducto)) {
                    // Producto simple: solo cantidad_produccion (1 decimal)
                    $estiloTotal = $esUltimo ? 'total_mes_leche' : 'total_mes_leche_sep';
                    echo '<Cell ss:StyleID="' . $estiloTotal . '"><Data ss:Type="Number">' . number_format($sumProductos[$nombreProducto]['cantidad_produccion'], 1, '.', '') . '</Data></Cell>';
                } else {
                    // Producto complejo: Leche utilizada (1 decimal) | Producción (entero) | MERMA | AGREGA
                    echo '<Cell ss:StyleID="total_mes_leche"><Data ss:Type="Number">' . number_format($sumProductos[$nombreProducto]['cantidad_produccion'], 1, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="total_mes_prod"><Data ss:Type="Number">' . (int)round($sumProductos[$nombreProducto]['stock']) . '</Data></Cell>';
                    echo '<Cell ss:StyleID="total_mes_ma"><Data ss:Type="Number">' . (int)$sumProductos[$nombreProducto]['merma'] . '</Data></Cell>';
                    $estiloAg = $esUltimo ? 'total_mes_ma' : 'total_mes_ma_sep';
                    echo '<Cell ss:StyleID="' . $estiloAg . '"><Data ss:Type="Number">' . (int)$sumProductos[$nombreProducto]['agrega'] . '</Data></Cell>';
                }

                $indiceProducto++;
            }
            echo '</Row>' . "\n";

        }
    }

    /**
     * Construir datos mensuales para la hoja Resumen Detallado.
     * Agrega producción por inventario y ventas reales
     */
    private function construirResumenDetalladoMensual(array $datosConProductos, array $productosUnicos): array
    {
        $mensual = [];

        // ── 1. Inicializar la estructura mensual ──────────
        foreach ($datosConProductos as $dato) {
            $inv = $dato['inventario'];
            $mes = date('Y-m', strtotime($inv->created_at));

            if (!isset($mensual[$mes])) {
                $mensual[$mes] = [
                    'mes'       => $mes,
                    'leche'     => [
                        'litros_recibidos' => 0.0,
                    ],
                    'productos' => [],
                ];
                foreach ($productosUnicos as $producto) {
                    $productoNorm = $this->normalizarNombreProducto($producto);
                    $mensual[$mes]['productos'][$productoNorm] = [
                        'mes_ant'          => 0.0,
                        'producido'        => 0.0,
                        'merma'            => 0.0,
                        'agrega'           => 0.0,
                        'oruro'            => 0.0,
                        'contado'          => 0.0,
                        'credito'          => 0.0,
                        'en_almacen'       => 0.0,
                        // Campos consolidados
                        'vendido'          => 0.0,
                        'ingresos'         => 0.0,
                        'vendido_contado'  => 0.0,
                        'ingresos_contado' => 0.0,
                        'vendido_credito'  => 0.0,
                        'ingresos_credito' => 0.0,
                    ];
                }
            }
        }

        // ── 2. Acumular datos de producción (usando stock para producido, merma para merma, y agrega para agrega) ──
        foreach ($datosConProductos as $dato) {
            $inv = $dato['inventario'];
            $mes = date('Y-m', strtotime($inv->created_at));

            foreach ($dato['productos'] as $producto => $valores) {
                $productoNorm = $this->normalizarNombreProducto($producto);
                if (!isset($mensual[$mes]['productos'][$productoNorm])) {
                    continue;
                }

                if ($valores['stock'] !== null) {
                    $mensual[$mes]['productos'][$productoNorm]['producido'] += (float)$valores['stock'];
                }
                if ($valores['merma'] !== null) {
                    $mensual[$mes]['productos'][$productoNorm]['merma'] += (float)$valores['merma'];
                }
                if ($valores['agrega'] !== null) {
                    $mensual[$mes]['productos'][$productoNorm]['agrega'] += (float)$valores['agrega'];
                }
            }
        }

        // ── 3. Cruzar con ventas reales en planta (sucursal_id = 4) ─────────────
        $mesesKeys = array_keys($mensual);
        if (!empty($mesesKeys)) {
            sort($mesesKeys);
            $fechaDesde = $mesesKeys[0] . '-01 00:00:00';
            $fechaHasta = date('Y-m-t 23:59:59', strtotime(end($mesesKeys) . '-01'));

            $sqlVentasPlanta = "
                SELECT
                    TO_CHAR(v.created_at, 'YYYY-MM')  AS mes,
                    p.nombre                           AS producto,
                    v.tipo_pago                        AS tipo_pago,
                    SUM(dv.cantidad)                   AS vendido,
                    SUM(dv.subtotal)                   AS ingresos
                FROM condoriri.ventas v
                JOIN condoriri.detalle_venta dv
                    ON dv.venta_id = v.id AND dv.deleted_at IS NULL
                JOIN condoriri.productos p
                    ON p.id = dv.producto_id AND p.deleted_at IS NULL
                WHERE v.deleted_at IS NULL
                  AND v.sucursal_id = 4
                  AND dv.producto_agro_id IS NULL
                  AND v.created_at >= ?
                  AND v.created_at <= ?
                GROUP BY TO_CHAR(v.created_at, 'YYYY-MM'), p.nombre, v.tipo_pago
            ";

            $ventasPlanta = $this->db->query($sqlVentasPlanta, [$fechaDesde, $fechaHasta])->getResult();

            foreach ($ventasPlanta as $venta) {
                $mes      = $venta->mes;
                $producto = $this->normalizarNombreProducto(trim($venta->producto ?? ''));

                if (!isset($mensual[$mes]) || !isset($mensual[$mes]['productos'][$producto])) {
                    continue;
                }

                $mensual[$mes]['productos'][$producto]['vendido']  += (float)$venta->vendido;
                $mensual[$mes]['productos'][$producto]['ingresos'] += (float)$venta->ingresos;

                if (strtolower($venta->tipo_pago ?? '') === 'contado') {
                    $mensual[$mes]['productos'][$producto]['contado']          += (float)$venta->vendido;
                    $mensual[$mes]['productos'][$producto]['vendido_contado']  += (float)$venta->vendido;
                    $mensual[$mes]['productos'][$producto]['ingresos_contado'] += (float)$venta->ingresos;
                } else {
                    $mensual[$mes]['productos'][$producto]['credito']          += (float)$venta->vendido;
                    $mensual[$mes]['productos'][$producto]['vendido_credito']  += (float)$venta->vendido;
                    $mensual[$mes]['productos'][$producto]['ingresos_credito'] += (float)$venta->ingresos;
                }
            }

            // ── 4. Cruzar con ventas en Oruro (stock_sucursales, sucursal_id = 2) ──
            $sqlVentasOruro = "
                SELECT
                    TO_CHAR(created_at, 'YYYY-MM') AS mes,
                    producto,
                    SUM(cantidad) - SUM(stock) AS vendido
                FROM condoriri.stock_sucursales
                WHERE sucursal_id = 2
                  AND created_at >= ?
                  AND created_at <= ?
                GROUP BY TO_CHAR(created_at, 'YYYY-MM'), producto
            ";

            $ventasOruro = $this->db->query($sqlVentasOruro, [$fechaDesde, $fechaHasta])->getResult();

            foreach ($ventasOruro as $vo) {
                $mes      = $vo->mes;
                $producto = $this->normalizarNombreProducto(trim($vo->producto ?? ''));

                if (!isset($mensual[$mes]) || !isset($mensual[$mes]['productos'][$producto])) {
                    continue;
                }

                $mensual[$mes]['productos'][$producto]['oruro'] += (float)$vo->vendido;
            }

            // ── 5. Consultar litros de leche cruda mensuales (inventarios stock where nombre = 'LECHE') ──
            $sqlLeche = "
                SELECT
                    TO_CHAR(created_at, 'YYYY-MM') AS mes,
                    SUM(stock) AS litros
                FROM condoriri.inventarios
                WHERE deleted_at IS NULL
                  AND UPPER(TRIM(nombre)) = 'LECHE'
                  AND created_at >= ?
                  AND created_at <= ?
                GROUP BY TO_CHAR(created_at, 'YYYY-MM')
            ";

            $lecheMensual = $this->db->query($sqlLeche, [$fechaDesde, $fechaHasta])->getResult();

            foreach ($lecheMensual as $lm) {
                $mes = $lm->mes;
                if (isset($mensual[$mes])) {
                    $mensual[$mes]['leche']['litros_recibidos'] = (float)$lm->litros;
                }
            }
        }

        ksort($mensual);

        return $mensual;
    }

    private function generarWorksheetResumenDetallado(array $mensual, array $productosUnicos, $fecha_inicio, $fecha_fin): void
    {
        // 8 columnas: DETALLE | PROD/MES | MERMA | ORURO | CONTADO | CRÉDITO | TOTAL | OBSERVACION
        $columnas = 8;

        echo '<Worksheet ss:Name="Balance General">' . "\n";
        echo '<Table>' . "\n";

        // Anchos de columna
        $anchos = [180, 80, 80, 80, 80, 80, 80, 120];
        foreach ($anchos as $ancho) {
            echo '<Column ss:Width="' . $ancho . '"/>' . "\n";
        }

        $primerMes = true;

        foreach ($mensual as $mes => $item) {
            // Fila separadora vacía entre meses (excepto antes del primero)
            if (!$primerMes) {
                echo '<Row></Row>' . "\n";
            }
            $primerMes = false;

            // Título del bloque mensual
            $tituloBloque = 'BALANCE GENERAL MES ' . strtoupper($this->textoMes($mes));

            echo '<Row ss:Height="26">';
            echo '<Cell ss:MergeAcross="7" ss:StyleID="titulo_mes"><Data ss:Type="String">' . htmlspecialchars($tituloBloque, ENT_XML1) . '</Data></Cell>';
            echo '</Row>' . "\n";

            // Encabezados de columnas
            $headers = [
                'DETALLE', 'PROD/MES', 'MERMA', 'ORURO', 'CONTADO', 'CRÉDITO', 'TOTAL', 'OBSERVACION'
            ];
            echo '<Row ss:Height="20">';
            foreach ($headers as $h) {
                echo '<Cell ss:StyleID="resumen_header"><Data ss:Type="String">' . $h . '</Data></Cell>';
            }
            echo '</Row>' . "\n";

            // Filas de productos
            $par = true;
            foreach ($productosUnicos as $producto) {
                $productoNorm = $this->normalizarNombreProducto($producto);
                if (!isset($item['productos'][$productoNorm])) {
                    continue;
                }

                $p = $item['productos'][$productoNorm];

                $valProducido = (int)round($p['producido']);
                $valMerma     = (int)round($p['merma']);
                $valOruro     = (int)round($p['oruro']);
                $valContado   = (int)round($p['contado']);
                $valCredito   = (int)round($p['credito']);
                $valTotal     = $valOruro + $valContado + $valCredito + $valMerma;

                // Omitir productos sin ninguna actividad en el mes
                if ($valProducido === 0 && $valMerma === 0 && $valOruro === 0 && $valContado === 0 && $valCredito === 0) {
                    continue;
                }

                $styleNombre = $par ? 'resumen_nombre' : 'resumen_nombre_impar';
                $styleDato   = $par ? 'resumen_par'    : 'resumen_impar';

                echo '<Row ss:Height="18">';
                echo '<Cell ss:StyleID="' . $styleNombre . '"><Data ss:Type="String">' . htmlspecialchars($productoNorm, ENT_XML1) . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . $valProducido . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . $valMerma . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . $valOruro . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . $valContado . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . $valCredito . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"><Data ss:Type="Number">' . $valTotal . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $styleDato . '"/>'; // OBSERVACION
                echo '</Row>' . "\n";

                $par = !$par;
            }

            // Fila LECHE (litros) - combinada horizontalmente
            $lecheLitros = (int)round($item['leche']['litros_recibidos'] ?? 0);

            echo '<Row ss:Height="18">';
            echo '<Cell ss:MergeAcross="7" ss:StyleID="total_mes_label"><Data ss:Type="String">leche producida: ' . $lecheLitros . '</Data></Cell>';
            echo '</Row>' . "\n";
        }

        // Fila separadora vacía antes del bloque consolidado
        echo '<Row></Row>' . "\n";

        // Renderizar bloque consolidado al final (usa 8 columnas)
        $this->renderBloqueProduccionVentasPorProducto($mensual, $productosUnicos, 8);

        echo '</Table></Worksheet>' . "\n";
    }

    private function renderEncabezadosProduccionVentas(): void
    {
        $headers = [
            'PRODUCTO', 'PRODUCIDO', 'MERMA', 'AGREGA',
            'CONTADO', 'CRÉDITO', 'VENDIDO',
            'CONTADO (Bs)', 'CRÉDITO (Bs)', 'TOTAL (Bs)',
        ];
        echo '<Row ss:Height="20">';
        foreach ($headers as $h) {
            echo '<Cell ss:StyleID="resumen_header"><Data ss:Type="String">' . $h . '</Data></Cell>';
        }
        echo '</Row>' . "\n";
    }

    private function renderFilaProduccionVentas(
        string $producto,
        array  $p,
        bool   $par,
        bool   $esFilaSeparador
    ): void {
        $styleNombre = $par ? 'resumen_nombre'      : 'resumen_nombre_impar';
        $styleDato   = $par ? 'resumen_par'         : 'resumen_impar';

        if ($esFilaSeparador) {
            $styleNombre = $par ? 'resumen_sep_nombre'      : 'resumen_sep_nombre_impar';
            $styleDato   = $par ? 'resumen_sep_par'         : 'resumen_sep_impar';
        }

        echo '<Row ss:Height="18">';
        echo '<Cell ss:StyleID="' . $styleNombre . '"><Data ss:Type="String">' . htmlspecialchars($producto, ENT_XML1) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['producido']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['merma']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['agrega']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['vendido_contado']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['vendido_credito']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['vendido']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['ingresos_contado']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['ingresos_credito']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="' . $styleDato   . '"><Data ss:Type="Number">' . (int)round($p['ingresos']) . '</Data></Cell>';
        echo '</Row>' . "\n";
    }

    private function renderBloqueProduccionVentasPorProducto(array $mensual, array $productosUnicos, int $columnas): void
    {
        $merge = $columnas - 1;
        $mesesKeys = array_keys($mensual);
        sort($mesesKeys);
        $rangoMes = $this->textoRangoMeses($mesesKeys);

        echo '<Row ss:Height="22"><Cell ss:MergeAcross="' . $merge . '" ss:StyleID="resumen_titulo"><Data ss:Type="String">PRODUCCION Y VENTAS — CONSOLIDADO POR PRODUCTO (' . $rangoMes . ')</Data></Cell></Row>' . "\n";
        $this->renderEncabezadosProduccionVentas();

        $productosNormUnicos = [];
        foreach ($productosUnicos as $producto) {
            $productoNorm = $this->normalizarNombreProducto($producto);
            $productosNormUnicos[$productoNorm] = true;
        }
        $productosNormUnicos = array_keys($productosNormUnicos);

        $consolidado = [];
        foreach ($productosNormUnicos as $productoNorm) {
            $consolidado[$productoNorm] = [
                'producido'          => 0.0,
                'merma'              => 0.0,
                'agrega'             => 0.0,
                'vendido'            => 0.0,
                'vendido_contado'    => 0.0,
                'vendido_credito'    => 0.0,
                'ingresos'           => 0.0,
                'ingresos_contado'   => 0.0,
                'ingresos_credito'   => 0.0,
            ];

            foreach ($mensual as $item) {
                if (!isset($item['productos'][$productoNorm])) {
                    continue;
                }
                $p = $item['productos'][$productoNorm];
                $consolidado[$productoNorm]['producido']         += $p['producido'];
                $consolidado[$productoNorm]['merma']             += $p['merma'];
                $consolidado[$productoNorm]['agrega']            += $p['agrega'];
                $consolidado[$productoNorm]['vendido']           += $p['vendido'];
                $consolidado[$productoNorm]['vendido_contado']   += $p['vendido_contado'];
                $consolidado[$productoNorm]['vendido_credito']   += $p['vendido_credito'];
                $consolidado[$productoNorm]['ingresos']          += $p['ingresos'];
                $consolidado[$productoNorm]['ingresos_contado']  += $p['ingresos_contado'];
                $consolidado[$productoNorm]['ingresos_credito']  += $p['ingresos_credito'];
            }
        }

        $par = true;

        foreach ($consolidado as $producto => $p) {
            if ($p['producido'] <= 0 && $p['vendido'] <= 0 && $p['merma'] <= 0 && $p['agrega'] <= 0) {
                continue;
            }

            $this->renderFilaProduccionVentas($producto, $p, $par, false);

            $par = !$par;
        }
    }

    /**
     * Genera el texto del rango de meses, p.ej.:
     *   ['2026-02', '2026-03', '2026-04'] → "Febrero – Abril 2026"
     *   ['2025-11', '2025-12', '2026-01'] → "Noviembre 2025 – Enero 2026"
     */
    private function textoRangoMeses(array $mesesKeys): string
    {
        if (empty($mesesKeys)) {
            return '';
        }

        $primero = $mesesKeys[0];
        $ultimo  = end($mesesKeys);

        if ($primero === $ultimo) {
            return $this->textoMes($primero);
        }

        [$anioP, $mesP] = explode('-', $primero);
        [$anioU, $mesU] = explode('-', $ultimo);

        $textoP = $this->textoMes($primero);
        $textoU = $this->textoMes($ultimo);

        // Mismo año: "Febrero – Mayo 2026"
        if ($anioP === $anioU) {
            $meses = ['01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                      '05' => 'Mayo',  '06' => 'Junio',   '07' => 'Julio', '08' => 'Agosto',
                      '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
            return ($meses[$mesP] ?? $mesP) . ' – ' . ($meses[$mesU] ?? $mesU) . ' ' . $anioU;
        }

        // Años distintos: "Noviembre 2025 – Enero 2026"
        return $textoP . ' – ' . $textoU;
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
