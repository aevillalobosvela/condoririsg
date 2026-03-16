<?php

namespace App\Services\Inventarios;

use App\Models\Inventario\InventarioModel;
use App\Models\Producto\ProductoModel;

class ExportacionExcelService
{
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
        // Obtener inventarios filtrados
        $inventarios = $this->inventarioModel->getFilteredInventarios($nombre, $fecha_inicio, $fecha_fin);

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
                    'stock' => 0,
                    'cantidad_produccion' => 0
                ];
            }

            $totalAgrega = 0;
            $totalMerma  = 0;

            // Sumar valores de productos con el mismo nombre
            foreach ($productos as $prod) {
                $nombreProducto = $this->normalizarNombreProducto(trim($prod->nombre ?? ''));
                if (isset($productosAgrupados[$nombreProducto])) {
                    $productosAgrupados[$nombreProducto]['stock'] += ($prod->stock ?? 0);
                    $productosAgrupados[$nombreProducto]['cantidad_produccion'] += ($prod->cantidad_produccion ?? 0);
                }
                $totalAgrega += ($prod->agrega ?? 0);
                $totalMerma  += ($prod->merma ?? 0);
            }

            $datosConProductos[] = [
                'inventario' => $inv,
                'productos'  => $productosAgrupados,
                'agrega'     => $totalAgrega,
                'merma'      => $totalMerma,
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

        // Iniciar hoja
        echo '<Worksheet ss:Name="Reporte General">' . "\n";
        echo '<Table>' . "\n";

        // Definir anchos de columna
        $this->definirAnchos($productosUnicos);

        // Título del reporte
        $totalColumnas = 7 + (count($productosUnicos) * 2) - 1;
        echo '<Row ss:Height="25">';
        echo '<Cell ss:MergeAcross="' . $totalColumnas . '" ss:StyleID="titulo"><Data ss:Type="String">REPORTE GENERAL</Data></Cell>';
        echo '</Row>' . "\n";

        // Subtítulo con fechas
        $textoFechas = $this->obtenerTextoFechas($fecha_inicio, $fecha_fin);
        echo '<Row ss:Height="20">';
        echo '<Cell ss:MergeAcross="' . $totalColumnas . '" ss:StyleID="subtitulo"><Data ss:Type="String">' . htmlspecialchars($textoFechas, ENT_XML1) . '</Data></Cell>';
        echo '</Row>' . "\n";

        echo '<Row></Row>' . "\n"; // Fila vacía

        // Datos (los encabezados se repiten por mes dentro de generarDatos)
        $this->generarDatos($datosConProductos, $productosUnicos);

        echo '</Table></Worksheet>' . "\n";

        // Hoja de resumen
        echo '<Worksheet ss:Name="Resumen">' . "\n";
        echo '<Table>' . "\n";
        echo '<Column ss:Width="200"/>' . "\n";
        echo '<Column ss:Width="100"/>' . "\n";
        $this->generarResumen($datosConProductos, $productosUnicos, $totalColumnas);
        echo '</Table></Worksheet>' . "\n";

        echo '</Workbook>';
        exit;
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
        echo '<Interior ss:Color="#DC143C" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";

        // Subtítulo
        echo '<Style ss:ID="subtitulo">';
        echo '<Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#8B0000" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";

        // Encabezado columnas fijas
        echo '<Style ss:ID="header">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#DC143C" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Encabezado productos dinámicos
        echo '<Style ss:ID="header_producto">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4472C4"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4472C4"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4472C4"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4472C4"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Encabezado productos con separador derecho
        echo '<Style ss:ID="header_producto_sep">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4472C4"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4472C4"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4472C4"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas amarillas (día par)
        echo '<Style ss:ID="celda_amarilla">';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas azules (día impar)
        echo '<Style ss:ID="celda_azul">';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas amarillas con borde superior grueso (separador de día)
        echo '<Style ss:ID="celda_amarilla_dia">';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Celdas azules con borde superior grueso (separador de día)
        echo '<Style ss:ID="celda_azul_dia">';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos
        echo '<Style ss:ID="numero_amarillo">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos con separador derecho
        echo '<Style ss:ID="numero_amarillo_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules
        echo '<Style ss:ID="numero_azul">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules con separador derecho
        echo '<Style ss:ID="numero_azul_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos con borde superior grueso
        echo '<Style ss:ID="numero_amarillo_dia">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos con borde superior grueso y separador derecho
        echo '<Style ss:ID="numero_amarillo_dia_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules con borde superior grueso
        echo '<Style ss:ID="numero_azul_dia">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules con borde superior grueso y separador derecho
        echo '<Style ss:ID="numero_azul_dia_sep">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#333333"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos bold
        echo '<Style ss:ID="numero_amarillo_bold">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números amarillos bold con borde superior grueso
        echo '<Style ss:ID="numero_amarillo_dia_bold">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#FFF9E6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules bold
        echo '<Style ss:ID="numero_azul_bold">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Números azules bold con borde superior grueso
        echo '<Style ss:ID="numero_azul_dia_bold">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders>';
        echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="3" ss:Color="#666666"/>';
        echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DC143C"/>';
        echo '</Borders>';
        echo '</Style>' . "\n";

        // Título de mes
        echo '<Style ss:ID="titulo_mes">';
        echo '<Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#8B0000" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";

        // Título de sección resumen
        echo '<Style ss:ID="resumen_titulo">';
        echo '<Font ss:Bold="1" ss:Size="13" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#1F4E79" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '</Style>' . "\n";

        // Encabezado tabla resumen
        echo '<Style ss:ID="resumen_header">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#2E75B6" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        // Celda de dato resumen (par)
        echo '<Style ss:ID="resumen_par">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#DEEAF1" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        // Celda de dato resumen (impar)
        echo '<Style ss:ID="resumen_impar">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5FBFF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        // Celda nombre producto en resumen
        echo '<Style ss:ID="resumen_nombre">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#DEEAF1" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="resumen_nombre_impar">';
        echo '<Font ss:Bold="1" ss:Size="10" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#F5FBFF" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders>';
        echo '</Style>' . "\n";

        // Fila de total general
        echo '<Style ss:ID="resumen_total">';
        echo '<NumberFormat ss:Format="0.00"/>';
        echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#1F4E79" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/></Borders>';
        echo '</Style>' . "\n";

        echo '<Style ss:ID="resumen_total_label">';
        echo '<Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF" ss:FontName="Arial"/>';
        echo '<Interior ss:Color="#1F4E79" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
        echo '<Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/></Borders>';
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
        echo '<Column ss:Width="70"/>' . "\n";  // AGREGA
        echo '<Column ss:Width="70"/>' . "\n";  // MERMA

        // Columnas dinámicas de productos
        foreach ($productosUnicos as $producto) {
            echo '<Column ss:Width="80"/>' . "\n";  // Stock del producto
            echo '<Column ss:Width="80"/>' . "\n";  // Cant. Producción del producto
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

        // Columnas fijas
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">FECHA</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">TURNO</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">NOMBRE</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">STOCK (L)</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">RESERVA</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">AGREGA</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">MERMA</Data></Cell>';

        // Columnas dinámicas de productos
        $totalProductos = count($productosUnicos);
        $indice = 0;
        foreach ($productosUnicos as $producto) {
            $nombreProducto = htmlspecialchars($producto, ENT_XML1);
            echo '<Cell ss:StyleID="header_producto"><Data ss:Type="String">' . $nombreProducto . ' Stock</Data></Cell>';
            
            // La segunda columna (Cant.Prod) tiene separador derecho si no es el último producto
            $esUltimoProducto = ($indice === $totalProductos - 1);
            $estiloSegundaColumna = $esUltimoProducto ? 'header_producto' : 'header_producto_sep';
            echo '<Cell ss:StyleID="' . $estiloSegundaColumna . '"><Data ss:Type="String">' . $nombreProducto . ' Cant.Prod</Data></Cell>';
            
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

        $totalColumnas = 7 + (count($productosUnicos) * 2) - 1;
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

                echo '<Row ss:Height="20">' . "\n";

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
                echo '<Cell ss:StyleID="' . $estiloNegrita . '"><Data ss:Type="Number">' . number_format($dato['agrega'], 2, '.', '') . '</Data></Cell>';
                echo '<Cell ss:StyleID="' . $estiloNegrita . '"><Data ss:Type="Number">' . number_format($dato['merma'], 2, '.', '') . '</Data></Cell>';

                $totalProductos = count($productos);
                $indice = 0;
                foreach ($productos as $datosProducto) {
                    $esUltimoProducto = ($indice === $totalProductos - 1);
                    echo '<Cell ss:StyleID="' . $estiloNumero . '"><Data ss:Type="Number">' . number_format($datosProducto['stock'], 2, '.', '') . '</Data></Cell>';

                    $estiloSegundaColumna = $estiloNumero;
                    if (!$esUltimoProducto) {
                        if ($esPrimerRegistroDelDia && $diaAnterior !== null) {
                            $estiloSegundaColumna = $esAmarillo ? 'numero_amarillo_dia_sep' : 'numero_azul_dia_sep';
                        } else {
                            $estiloSegundaColumna = $esAmarillo ? 'numero_amarillo_sep' : 'numero_azul_sep';
                        }
                    }
                    echo '<Cell ss:StyleID="' . $estiloSegundaColumna . '"><Data ss:Type="Number">' . number_format($datosProducto['cantidad_produccion'], 2, '.', '') . '</Data></Cell>';

                    $indice++;
                }

                echo '</Row>' . "\n";
                $diaAnterior = $diaActual;
            }
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
            $stockTotal  += ($dato['inventario']->stock ?? 0);
            $agregaTotal += $dato['agrega'];
            $mermaTotal  += $dato['merma'];

            foreach ($productosUnicos as $nombre) {
                $porProducto[$nombre]['stock'] += ($dato['productos'][$nombre]['stock'] ?? 0);
            }
        }

        // Necesitamos agrega/merma por producto: re-consultar desde los datos de productos
        // Los datos de agrega/merma están a nivel de inventario (suma de todos sus productos),
        // para el desglose por producto los obtenemos del modelo directamente
        // Usamos los datos ya preparados: recorremos de nuevo sumando por nombre de producto
        // Nota: agrega y merma en $datosConProductos son totales del inventario, no por producto.
        // Para el resumen por producto solo tenemos stock disponible desde los datos preparados.
        // agrega/merma globales se muestran en la fila TOTAL.

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
