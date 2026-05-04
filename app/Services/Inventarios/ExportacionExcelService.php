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

        // Agrupar inventarios por día
        $inventariosPorDia = $this->agruparInventariosPorDia($inventarios);

        // Preparar datos con productos agrupados
        $datosConProductos = $this->prepararDatosConProductos($inventarios, $productosUnicos);

        // Generar archivo Excel
        $this->generarArchivoExcel($datosConProductos, $productosUnicos, $inventariosPorDia, $fecha_inicio, $fecha_fin);
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
                    }
                    $productosAgrupados[$nombreProducto]['stock']               += ($prod->stock ?? 0);
                    $productosAgrupados[$nombreProducto]['cantidad_produccion'] += ($prod->cantidad_produccion ?? 0);
                    $productosAgrupados[$nombreProducto]['merma']               += ($prod->merma ?? 0);
                    $productosAgrupados[$nombreProducto]['agrega']              += ($prod->agrega ?? 0);
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
    private function generarArchivoExcel($datosConProductos, $productosUnicos, $inventariosPorDia, $fecha_inicio, $fecha_fin)
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

        echo '</Workbook>';
        exit;
    }

    /**
     * Generar una hoja con el formato del reporte general + resumen
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

        echo '<Row></Row>' . "\n";
        echo '<Row></Row>' . "\n";

        $this->generarResumen($datosConProductos, $productosUnicos, $totalColumnas);

        echo '</Table></Worksheet>' . "\n";
    }

    /**
     * Definir estilos del Excel
     */
    private function definirEstilos()
    {
        echo '<Styles>' . "\n";

        // Título principal
        echo '<Style ss:ID="titulo">';
        echo '<Font ss:Bold="1" ss:Size="16" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#2E5090" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";

        // Subtítulo
        echo '<Style ss:ID="subtitulo">';
        echo '<Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#3D6BA8" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";

        // Encabezado columnas fijas
        echo '<Style ss:ID="header">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#4A6FA5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Encabezado productos dinámicos
        echo '<Style ss:ID="header_producto">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#5D8A8A" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Encabezado productos con separador derecho
        echo '<Style ss:ID="header_producto_sep">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#5D8A8A" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas amarillas (día par)
        echo '<Style ss:ID="celda_amarilla">';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas azules (día impar)
        echo '<Style ss:ID="celda_azul">';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas amarillas con borde superior grueso (separador de día)
        echo '<Style ss:ID="celda_amarilla_dia">';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas azules con borde superior grueso (separador de día)
        echo '<Style ss:ID="celda_azul_dia">';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos
        echo '<Style ss:ID="numero_amarillo">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos con separador derecho
        echo '<Style ss:ID="numero_amarillo_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules
        echo '<Style ss:ID="numero_azul">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules con separador derecho
        echo '<Style ss:ID="numero_azul_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos con borde superior grueso
        echo '<Style ss:ID="numero_amarillo_dia">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos con borde superior grueso y separador derecho
        echo '<Style ss:ID="numero_amarillo_dia_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules con borde superior grueso
        echo '<Style ss:ID="numero_azul_dia">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules con borde superior grueso y separador derecho
        echo '<Style ss:ID="numero_azul_dia_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos bold
        echo '<Style ss:ID="numero_amarillo_bold">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos bold con borde superior grueso
        echo '<Style ss:ID="numero_amarillo_dia_bold">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules bold
        echo '<Style ss:ID="numero_azul_bold">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules bold con borde superior grueso
        echo '<Style ss:ID="numero_azul_dia_bold">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Título de mes
        echo '<Style ss:ID="titulo_mes">';
        echo '<Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#6A7FA8" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";

        // Filas de cierre mensual (total y promedio)
        echo '<Style ss:ID="total_mes_label">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFFDE7" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="total_mes_numero">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFFDE7" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="total_mes_numero_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFFDE7" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";



        // Título de sección resumen
        echo '<Style ss:ID="resumen_titulo">';
        echo '<Font ss:Bold="1" ss:Size="13" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#4D6A83" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";

        // Encabezado tabla resumen
        echo '<Style ss:ID="resumen_header">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#6E8EA3" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        // Celda de dato resumen (par)
        echo '<Style ss:ID="resumen_par">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#EEF4F1" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        // Celda de dato resumen (impar)
        echo '<Style ss:ID="resumen_impar">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F4F5F7" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        // Celda nombre producto en resumen
        echo '<Style ss:ID="resumen_nombre">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#EEF4F1" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="resumen_nombre_impar">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F4F5F7" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        // Fila de total general
        echo '<Style ss:ID="resumen_total">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#4D6A83" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/></Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="resumen_total_label">';
        echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#4D6A83" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/></Borders>';
        echo '</Style>' . "\n";

        // Encabezado merma/agrega (compacto, color diferenciado)
        echo '<Style ss:ID="header_ma">';
        echo '<Font ss:Bold="1" ss:Size="9" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#7A6A8A" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Encabezado merma/agrega con separador derecho
        echo '<Style ss:ID="header_ma_sep">';
        echo '<Font ss:Bold="1" ss:Size="9" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#7A6A8A" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas compactas merma/agrega (amarillo)
        echo '<Style ss:ID="ma_amarillo">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Size="9" ss:FontName="Arial" ss:Color="#5A4A6A"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas compactas merma/agrega (amarillo) con separador derecho
        echo '<Style ss:ID="ma_amarillo_sep">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Size="9" ss:FontName="Arial" ss:Color="#5A4A6A"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas compactas merma/agrega (gris)
        echo '<Style ss:ID="ma_azul">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Size="9" ss:FontName="Arial" ss:Color="#5A4A6A"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas compactas merma/agrega (gris) con separador derecho
        echo '<Style ss:ID="ma_azul_sep">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Size="9" ss:FontName="Arial" ss:Color="#5A4A6A"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Variantes con borde superior grueso (primer registro del día)
        echo '<Style ss:ID="ma_amarillo_dia">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Size="9" ss:FontName="Arial" ss:Color="#5A4A6A"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="ma_amarillo_dia_sep">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Size="9" ss:FontName="Arial" ss:Color="#5A4A6A"/>';
        echo '<Interior ss:Color="#F0F7F0" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="ma_azul_dia">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Size="9" ss:FontName="Arial" ss:Color="#5A4A6A"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="ma_azul_dia_sep">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Size="9" ss:FontName="Arial" ss:Color="#5A4A6A"/>';
        echo '<Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Total mes merma/agrega compacto
        echo '<Style ss:ID="total_mes_ma">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Bold="1" ss:Size="9" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFFDE7" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="total_mes_ma_sep">';
        echo '<NumberFormat ss:Format="0"/>';
        echo '<Font ss:Bold="1" ss:Size="9" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFFDE7" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        echo '</Styles>' . "\n";
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

    /**
     * Generar sección de resumen al final del reporte
     */
    private function generarResumen($datosConProductos, $productosUnicos, $totalColumnas)
    {
        // Acumular totales desde todos los datos
        $stockTotal  = 0;
        $agregaTotal = 0;
        $mermaTotal  = 0;
        $porProducto = []; // [nombre => ['stock'=>0,'agrega'=>0,'merma'=>0]]

        foreach ($productosUnicos as $nombre) {
            $porProducto[$nombre] = ['stock' => 0, 'agrega' => 0, 'merma' => 0];
        }

        foreach ($datosConProductos as $dato) {
            $stockTotal += ($dato['inventario']->stock ?? 0);

            foreach ($productosUnicos as $nombre) {
                $porProducto[$nombre]['stock']  += ($dato['productos'][$nombre]['stock']  ?? 0);
                $porProducto[$nombre]['agrega'] += ($dato['productos'][$nombre]['agrega'] ?? 0);
                $porProducto[$nombre]['merma']  += ($dato['productos'][$nombre]['merma']  ?? 0);
            }
        }

        $agregaTotal = array_sum(array_column($porProducto, 'agrega'));
        $mermaTotal  = array_sum(array_column($porProducto, 'merma'));

        // --- Título resumen ---
        echo '<Row ss:Height="24">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="resumen_titulo"><Data ss:Type="String">RESUMEN GENERAL</Data></Cell>';
        echo '</Row>' . "\n";

        // --- Totales globales (2 columnas: label + valor) ---
        foreach ([
            'STOCK TOTAL'  => $stockTotal,
            'AGREGA TOTAL' => $agregaTotal,
            'MERMA TOTAL'  => $mermaTotal,
        ] as $label => $valor) {
            echo '<Row ss:Height="20">';
            echo '<Cell ss:StyleID="resumen_total_label"><Data ss:Type="String">' . $label . '</Data></Cell>';
            echo '<Cell ss:StyleID="resumen_total"><Data ss:Type="Number">' . number_format($valor, 2, '.', '') . '</Data></Cell>';
            echo '</Row>' . "\n";
        }

        echo '<Row></Row>' . "\n";

        // --- Tabla resumen por tipo de producto ---
        echo '<Row ss:Height="22">';
        echo '<Cell ss:MergeAcross="1" ss:StyleID="resumen_titulo"><Data ss:Type="String">RESUMEN POR TIPO DE PRODUCTO</Data></Cell>';
        echo '</Row>' . "\n";

        echo '<Row ss:Height="18">';
        echo '<Cell ss:StyleID="resumen_header"><Data ss:Type="String">PRODUCTO</Data></Cell>';
        echo '<Cell ss:StyleID="resumen_header"><Data ss:Type="String">STOCK TOTAL</Data></Cell>';
        echo '</Row>' . "\n";

        $par = true;
        foreach ($porProducto as $nombre => $totales) {
            $estiloNombre = $par ? 'resumen_nombre'  : 'resumen_nombre_impar';
            $estiloDato   = $par ? 'resumen_par'     : 'resumen_impar';
            echo '<Row ss:Height="18">';
            echo '<Cell ss:StyleID="' . $estiloNombre . '"><Data ss:Type="String">' . htmlspecialchars($nombre, ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="' . $estiloDato   . '"><Data ss:Type="Number">' . number_format($totales['stock'], 2, '.', '') . '</Data></Cell>';
            echo '</Row>' . "\n";
            $par = !$par;
        }
    }

    /**
     * Exportar reporte de control de calidad (mantener método existente)
     */
    public function exportarCalidadExcel($nombre = '', $fecha_inicio = '', $fecha_fin = '')
    {
    }
}
