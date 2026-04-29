<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use App\Models\Producto\ProductoModel;
use FPDF;

class ReporteLacteos extends FPDF
{
    protected $reporteTitle = 'REPORTE GENERAL';

    public function setReporteTitle(string $title): void
    {
        $this->reporteTitle = $title;
    }

    // Anchos de columna calculados dinámicamente
    protected $wFecha   = 22;
    protected $wTurno   = 12;
    protected $wNombre  = 30;
    protected $wStock   = 18;
    protected $wReserva = 18;
    protected $wAgrega  = 18;
    protected $wMerma   = 18;
    protected $wProd    = 20; // ancho por cada columna de producto (Stock y Cant.Prod)

    // Altura de fila de datos
    protected $hRow = 6;
    // Altura de línea dentro del encabezado (para MultiCell)
    protected $hHeaderLine = 4;
    // Alto total del encabezado (calculado dinámicamente)
    protected $hHeader = 16;

    public function __construct()
    {
        // Orientación landscape, A3 para más espacio horizontal
        parent::__construct('L', 'mm', 'A3');
        $this->SetAuthor('DTIC');
        $this->SetTitle('Reporte General de Inventarios');
        $this->SetMargins(10, 38, 10);
        $this->SetAutoPageBreak(true, 15);
        $this->SetFont('Arial', '', 8);
        $this->AliasNbPages();
    }

    public function Header()
    {
        $logo_left  = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

        $this->SetY(5);

        if (file_exists($logo_left)) {
            $this->Image($logo_left, 10, 5, 20);
        }
        if (file_exists($logo_right)) {
            $this->Image($logo_right, $this->GetPageWidth() - 30, 5, 20);
        }

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 5, utf8_decode('UNIVERSIDAD TÉCNICA DE ORURO'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 4, utf8_decode('FACULTAD DE CIENCIAS AGRARIAS Y NATURALES'), 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 3, utf8_decode('CONDORIRI - LABORATORIO DE INNOVACIÓN'), 0, 1, 'C');
        $this->Cell(0, 3, utf8_decode('Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49'), 0, 1, 'C');
        $this->Cell(0, 3, utf8_decode('Email: dpdi@uto.edu.bo | www.uto.edu.bo'), 0, 1, 'C');

        $this->Ln(2);
        $this->SetDrawColor(0, 0, 0);
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
        $this->Ln(2);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, utf8_decode($this->reporteTitle), 0, 1, 'C');
        $this->Ln(1);
    }

    public function Footer()
    {
        $this->SetY(-14);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(100);
        $this->Cell(0, 4, utf8_decode('Generado el: ' . date('d/m/Y H:i:s') . '   |   Sistema de Gestión de Inventarios CEAC-UTO'), 0, 1, 'L');
        $this->SetTextColor(0);
        $this->Cell(0, 4, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // -------------------------------------------------------------------------
    // Método principal
    // -------------------------------------------------------------------------
    public function generarReporte(array $inventarios, array $filtros)
    {
        $productoModel = new ProductoModel();

        // 1. Obtener productos únicos (igual que el Excel)
        $productosUnicos = $this->obtenerProductosUnicos($inventarios, $productoModel);

        // 2. Calcular anchos dinámicos según número de productos
        $this->calcularAnchos(count($productosUnicos));

        // 3. Preparar datos con productos agrupados
        $datosConProductos = $this->prepararDatos($inventarios, $productosUnicos, $productoModel);

        // 4. Generar contenido
        $this->AddPage();

        // Subtítulo de fechas
        $textoFechas = $this->textoFechas($filtros['fecha_inicio'] ?? '', $filtros['fecha_fin'] ?? '');
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, utf8_decode($textoFechas), 0, 1, 'C');
        $this->Ln(2);

        // Agrupar por mes
        $datosPorMes = [];
        foreach ($datosConProductos as $dato) {
            $mes = date('Y-m', strtotime($dato['inventario']->created_at));
            $datosPorMes[$mes][] = $dato;
        }

        $meses_es = [
            'January'   => 'Enero',   'February'  => 'Febrero',  'March'     => 'Marzo',
            'April'     => 'Abril',   'May'        => 'Mayo',     'June'      => 'Junio',
            'July'      => 'Julio',   'August'     => 'Agosto',   'September' => 'Septiembre',
            'October'   => 'Octubre', 'November'   => 'Noviembre','December'  => 'Diciembre',
        ];

        foreach ($datosPorMes as $mes => $datosDelMes) {
            // Título del mes
            $mesTexto = strtoupper(str_replace(
                array_keys($meses_es),
                array_values($meses_es),
                date('F Y', strtotime($mes . '-01'))
            ));

            // Evitar encabezados "huerfanos" al final de pagina:
            // titulo de mes + encabezado de tabla + al menos 1 fila de datos.
            $altoEncabezado = $this->calcularAlturaEncabezado($productosUnicos);
            $altoMinimoBloque = 7 + $altoEncabezado + $this->hRow;
            if ($this->GetY() + $altoMinimoBloque > $this->GetPageHeight() - 15) {
                $this->AddPage();
            }

            $this->filasMes($mesTexto, $productosUnicos);
            $this->filasEncabezados($productosUnicos);

            $indiceDia  = 0;
            $diaAnterior = null;
            $sumStock = 0.0;
            $sumReserva = 0.0;
            $sumAgrega = 0.0;
            $sumMerma = 0.0;
            $sumProductos = [];
            foreach ($productosUnicos as $nombreProducto) {
                $sumProductos[$nombreProducto] = [
                    'stock' => 0.0,
                    'cantidad_produccion' => 0.0,
                ];
            }

            foreach ($datosDelMes as $dato) {
                $inv      = $dato['inventario'];
                $productos = $dato['productos'];

                $diaActual            = date('Y-m-d', strtotime($inv->created_at));
                $esPrimerDelDia       = ($diaActual !== $diaAnterior);

                if ($esPrimerDelDia && $diaAnterior !== null) {
                    $indiceDia++;
                }

                $esAmarillo   = ($indiceDia % 2 === 0);
                $bordeTop     = ($esPrimerDelDia && $diaAnterior !== null);

                // Salto de página si no cabe la fila (encabezado + fila de datos)
                if ($this->GetY() + $this->hHeader + $this->hRow > $this->GetPageHeight() - 15) {
                    $this->AddPage();
                    $this->filasMes($mesTexto, $productosUnicos);
                    $this->filasEncabezados($productosUnicos);
                    $diaAnterior = null; // reset para que no dibuje borde grueso en la primera fila tras salto
                }

                $this->filasDatos($inv, $productos, $productosUnicos, $esAmarillo, $bordeTop, $esPrimerDelDia, $dato['agrega'], $dato['merma']);

                $sumStock += (float)($inv->stock ?? 0);
                $sumReserva += (float)($inv->reserva ?? 0);
                $sumAgrega += (float)($dato['agrega'] ?? 0);
                $sumMerma += (float)($dato['merma'] ?? 0);
                foreach ($productosUnicos as $nombreProducto) {
                    $sumProductos[$nombreProducto]['stock'] += (float)($productos[$nombreProducto]['stock'] ?? 0);
                    $sumProductos[$nombreProducto]['cantidad_produccion'] += (float)($productos[$nombreProducto]['cantidad_produccion'] ?? 0);
                }

                $diaAnterior = $diaActual;
            }

            $this->filaTotalMes($sumStock, $sumReserva, $sumAgrega, $sumMerma, $sumProductos, $productosUnicos);
            $this->Ln(4);
        }

        $this->generarResumen($datosConProductos, $productosUnicos);

        $this->Output('D', 'reporte_general_' . date('Ymd') . '.pdf');
    }

    // -------------------------------------------------------------------------
    // Helpers de datos (misma lógica que ExportacionExcelService)
    // -------------------------------------------------------------------------
    private function obtenerProductosUnicos(array $inventarios, ProductoModel $productoModel): array
    {
        $set = [];
        foreach ($inventarios as $inv) {
            $productos = $productoModel->where('inventario_id', $inv->id)->findAll();
            foreach ($productos as $prod) {
                $nombre = $this->normalizarNombre(trim($prod->nombre ?? ''));
                if (!empty($nombre)) {
                    $set[$nombre] = true;
                }
            }
        }
        $lista = array_keys($set);
        sort($lista);
        return $lista;
    }

    private function normalizarNombre(string $nombre): string
    {
        if ($nombre === 'YOGURT GRIEGO (250 GRAMOS)') {
            return 'YOGURT GRIEGO 250 GRAMOS';
        }
        return $nombre;
    }

    private function prepararDatos(array $inventarios, array $productosUnicos, ProductoModel $productoModel): array
    {
        $resultado = [];
        foreach ($inventarios as $inv) {
            $productos = $productoModel->where('inventario_id', $inv->id)->findAll();

            $agrupados = [];
            foreach ($productosUnicos as $nombre) {
                $agrupados[$nombre] = ['stock' => null, 'cantidad_produccion' => null];
            }

            $totalAgrega = 0;
            $totalMerma  = 0;

            foreach ($productos as $prod) {
                $nombre = $this->normalizarNombre(trim($prod->nombre ?? ''));
                if (isset($agrupados[$nombre])) {
                    if ($agrupados[$nombre]['stock'] === null) {
                        $agrupados[$nombre]['stock'] = 0;
                    }
                    if ($agrupados[$nombre]['cantidad_produccion'] === null) {
                        $agrupados[$nombre]['cantidad_produccion'] = 0;
                    }
                    $agrupados[$nombre]['stock']              += ($prod->stock ?? 0);
                    $agrupados[$nombre]['cantidad_produccion'] += ($prod->cantidad_produccion ?? 0);
                }
                $totalAgrega += ($prod->agrega ?? 0);
                $totalMerma  += ($prod->merma  ?? 0);
            }

            $resultado[] = [
                'inventario' => $inv,
                'productos'  => $agrupados,
                'agrega'     => $totalAgrega,
                'merma'      => $totalMerma,
            ];
        }
        return $resultado;
    }

    // -------------------------------------------------------------------------
    // Cálculo dinámico de anchos
    // -------------------------------------------------------------------------
    private function calcularAnchos(int $numProductos): void
    {
        $pageW    = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $wFijas   = $this->wFecha + $this->wTurno + $this->wNombre + $this->wStock
                  + $this->wReserva + $this->wAgrega + $this->wMerma;
        $wDinamica = $pageW - $wFijas;

        if ($numProductos > 0) {
            // Cada producto ocupa 2 columnas (Stock + Cant.Prod)
            $this->wProd = max(14, floor($wDinamica / ($numProductos * 2)));
        }

        // Si las columnas fijas + dinámicas superan el ancho, reducir columnas fijas proporcionalmente
        $totalUsado = $wFijas + ($numProductos * 2 * $this->wProd);
        if ($totalUsado > $pageW && $numProductos > 0) {
            $factor = $pageW / $totalUsado;
            $this->wFecha   = floor($this->wFecha   * $factor);
            $this->wTurno   = floor($this->wTurno   * $factor);
            $this->wNombre  = floor($this->wNombre  * $factor);
            $this->wStock   = floor($this->wStock   * $factor);
            $this->wReserva = floor($this->wReserva * $factor);
            $this->wAgrega  = floor($this->wAgrega  * $factor);
            $this->wMerma   = floor($this->wMerma   * $factor);
            $this->wProd    = floor($this->wProd    * $factor);
        }
    }

    // -------------------------------------------------------------------------
    // Dibujo de filas
    // -------------------------------------------------------------------------
    private function anchoTotal(int $numProductos): float
    {
        return $this->wFecha + $this->wTurno + $this->wNombre + $this->wStock
             + $this->wReserva + $this->wAgrega + $this->wMerma
             + ($numProductos * 2 * $this->wProd);
    }

    private function filasMes(string $mesTexto, array $productosUnicos): void
    {
        $w = $this->anchoTotal(count($productosUnicos));
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(106, 127, 168);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($w, 7, utf8_decode($mesTexto), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
    }

    private function filasEncabezados(array $productosUnicos): void
    {
        $this->SetFont('Arial', 'B', 7);

        // Calcular el alto necesario: máximo de líneas que ocupa cualquier etiqueta de producto
        $maxLineas = 1;
        foreach ($productosUnicos as $nombre) {
            $etiquetas = [utf8_decode($nombre) . ' Stk', utf8_decode($nombre) . ' Prod'];
            foreach ($etiquetas as $etiqueta) {
                $lineas = $this->contarLineas($etiqueta, $this->wProd);
                if ($lineas > $maxLineas) {
                    $maxLineas = $lineas;
                }
            }
        }
        $this->hHeader = max(5 * $this->hHeaderLine + 4, $maxLineas * $this->hHeaderLine + 4);

        $yInicio = $this->GetY();
        $xInicio = $this->GetX();

        // --- Columnas fijas (Cell normal, centrado vertical manual) ---
        $this->SetFillColor(74, 111, 165);
        $this->SetTextColor(255, 255, 255);

        $fijas = [
            ['FECHA',     $this->wFecha],
            ['TURNO',     $this->wTurno],
            ['NOMBRE',    $this->wNombre],
            ['STOCK (L)', $this->wStock],
            ['RESERVA',   $this->wReserva],
            ['AGREGA',    $this->wAgrega],
            ['MERMA',     $this->wMerma],
        ];

        foreach ($fijas as [$label, $w]) {
            $this->celdaMultilineaCentrada($label, $w, $this->hHeader, $this->hHeaderLine, 220, 20, 60);
        }

        // --- Columnas dinámicas de productos (MultiCell con posición restaurada) ---
        foreach ($productosUnicos as $nombre) {
            $this->celdaMultilineaCentrada(
                utf8_decode($nombre) . ' Stk',
                $this->wProd, $this->hHeader, $this->hHeaderLine,
                93, 138, 138
            );
            $this->celdaMultilineaCentrada(
                utf8_decode($nombre) . ' Prod',
                $this->wProd, $this->hHeader, $this->hHeaderLine,
                123, 164, 164
            );
        }

        $this->SetTextColor(0, 0, 0);
        // Avanzar al final de la fila de encabezados
        $this->SetXY($xInicio, $yInicio + $this->hHeader);
        $this->Ln(0);
    }

    private function calcularAlturaEncabezado(array $productosUnicos): float
    {
        $this->SetFont('Arial', 'B', 7);
        $maxLineas = 1;
        foreach ($productosUnicos as $nombre) {
            $etiquetas = [utf8_decode($nombre) . ' Stk', utf8_decode($nombre) . ' Prod'];
            foreach ($etiquetas as $etiqueta) {
                $lineas = $this->contarLineas($etiqueta, $this->wProd);
                if ($lineas > $maxLineas) {
                    $maxLineas = $lineas;
                }
            }
        }

        return max(5 * $this->hHeaderLine + 4, $maxLineas * $this->hHeaderLine + 4);
    }

    /**
     * Dibuja una celda con texto multilínea centrado vertical y horizontalmente.
     * Restaura la posición X al lado derecho de la celda para continuar en la misma fila.
     */
    private function celdaMultilineaCentrada(string $texto, float $w, float $hTotal, float $hLinea, int $r, int $g, int $b): void
    {
        $x = $this->GetX();
        $y = $this->GetY();

        // Borde exterior de la celda
        $this->SetFillColor($r, $g, $b);
        $this->Rect($x, $y, $w, $hTotal, 'DF');

        // Calcular cuántas líneas ocupa el texto
        $lineas   = $this->contarLineas($texto, $w);
        $altoTexto = $lineas * $hLinea;
        // Offset vertical para centrar
        $offsetY  = ($hTotal - $altoTexto) / 2;

        // Posicionar y escribir con MultiCell
        $this->SetXY($x, $y + $offsetY);
        $this->SetTextColor(255, 255, 255);
        $this->MultiCell($w, $hLinea, $texto, 0, 'C');

        // Restaurar posición al lado derecho de la celda para continuar la fila
        $this->SetXY($x + $w, $y);
    }

    /**
     * Cuenta cuántas líneas ocupa un texto en una celda de ancho $w con la fuente actual.
     */
    private function contarLineas(string $texto, float $w): int
    {
        // Ancho de un carácter promedio con fuente Arial Bold 7pt ≈ 1.8 mm
        $charW    = $this->GetStringWidth('M'); // ancho de un carácter representativo
        $charsMax = max(1, floor($w / $charW));
        $palabras = explode(' ', $texto);
        $linea    = '';
        $lineas   = 1;
        foreach ($palabras as $palabra) {
            $prueba = $linea === '' ? $palabra : $linea . ' ' . $palabra;
            if ($this->GetStringWidth($prueba) > $w - 1) {
                $lineas++;
                $linea = $palabra;
            } else {
                $linea = $prueba;
            }
        }
        return $lineas;
    }

    private function filasDatos($inv, array $productos, array $productosUnicos, bool $esAmarillo, bool $bordeTop, bool $esPrimerDelDia, float $agrega, float $merma): void
    {
        if ($esAmarillo) {
            $this->SetFillColor(240, 247, 240);
        } else {
            $this->SetFillColor(245, 245, 245);
        }

        // Línea separadora de día más gruesa
        if ($bordeTop) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetDrawColor(100, 100, 100);
            $this->SetLineWidth(0.5);
            $this->Line($x, $y, $x + $this->anchoTotal(count($productosUnicos)), $y);
            $this->SetDrawColor(0, 0, 0);
            $this->SetLineWidth(0.2);
        }

        $this->SetFont('Arial', '', 7);

        $fecha = $esPrimerDelDia ? utf8_decode(date('d-M-y', strtotime($inv->created_at))) : '';
        $this->Cell($this->wFecha,   $this->hRow, $fecha,                                              1, 0, 'C', true);
        $this->Cell($this->wTurno,   $this->hRow, strtoupper($inv->turno ?? 'AM'),                     1, 0, 'C', true);
        $this->Cell($this->wNombre,  $this->hRow, utf8_decode($this->truncar($inv->nombre ?? '', 18)), 1, 0, 'L', true);

        $this->SetFont('Arial', 'B', 7);
        $this->Cell($this->wStock,   $this->hRow, number_format($inv->stock   ?? 0, 2), 1, 0, 'C', true);
        $this->SetFont('Arial', '', 7);
        $this->Cell($this->wReserva, $this->hRow, number_format($inv->reserva ?? 0, 2), 1, 0, 'C', true);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell($this->wAgrega,  $this->hRow, number_format($agrega, 2),            1, 0, 'C', true);
        $this->Cell($this->wMerma,   $this->hRow, number_format($merma,  2),            1, 0, 'C', true);

        $this->SetFont('Arial', '', 7);
        foreach ($productosUnicos as $nombre) {
            $datoProd = $productos[$nombre] ?? ['stock' => null, 'cantidad_produccion' => null];
            $textoStock = $datoProd['stock'] === null ? '-' : number_format($datoProd['stock'], 2);
            $textoProd = $datoProd['cantidad_produccion'] === null ? '-' : number_format($datoProd['cantidad_produccion'], 2);
            $this->Cell($this->wProd, $this->hRow, $textoStock, 1, 0, 'C', true);
            $this->Cell($this->wProd, $this->hRow, $textoProd, 1, 0, 'C', true);
        }

        $this->Ln();
    }

    private function generarResumen(array $datosConProductos, array $productosUnicos): void
    {
        // Acumular totales
        $stockTotal  = 0;
        $agregaTotal = 0;
        $mermaTotal  = 0;
        $porProducto = [];
        foreach ($productosUnicos as $nombre) {
            $porProducto[$nombre] = 0;
        }

        foreach ($datosConProductos as $dato) {
            $stockTotal  += ($dato['inventario']->stock ?? 0);
            $agregaTotal += $dato['agrega'];
            $mermaTotal  += $dato['merma'];
            foreach ($productosUnicos as $nombre) {
                $porProducto[$nombre] += ($dato['productos'][$nombre]['stock'] ?? 0);
            }
        }

        // Anchos compactos fijos para el resumen (independientes de la tabla principal)
        $wLabel = 100;
        $wVal   = 35;

        $altoEstimado = 14 + 7 * 3 + 8 + 8 + count($productosUnicos) * 7;
        if ($this->GetY() + $altoEstimado > $this->GetPageHeight() - 15) {
            $this->AddPage();
        }

        $this->Ln(6);

        // --- Título RESUMEN GENERAL ---
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(31, 78, 121);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($wLabel + $wVal, 8, utf8_decode('RESUMEN GENERAL'), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        foreach ([
            utf8_decode('STOCK TOTAL')  => $stockTotal,
            utf8_decode('AGREGA TOTAL') => $agregaTotal,
            utf8_decode('MERMA TOTAL')  => $mermaTotal,
        ] as $label => $valor) {
            $this->SetFont('Arial', 'B', 8);
            $this->SetFillColor(31, 78, 121);
            $this->SetTextColor(255, 255, 255);
            $this->Cell($wLabel, 7, $label, 1, 0, 'L', true);
            $this->SetFillColor(222, 234, 246);
            $this->SetTextColor(0, 0, 0);
            $this->Cell($wVal, 7, number_format($valor, 2), 1, 1, 'C', true);
        }

        $this->Ln(4);

        // --- Título RESUMEN POR TIPO DE PRODUCTO ---
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(31, 78, 121);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($wLabel + $wVal, 8, utf8_decode('RESUMEN POR TIPO DE PRODUCTO'), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(46, 117, 182);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($wLabel, 7, utf8_decode('PRODUCTO'),    1, 0, 'C', true);
        $this->Cell($wVal,   7, utf8_decode('STOCK TOTAL'), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);

        $par = true;
        foreach ($porProducto as $nombre => $stockProd) {
            if ($this->GetY() + 7 > $this->GetPageHeight() - 15) {
                $this->AddPage();
            }
            $rgb = $par ? [222, 234, 246] : [245, 251, 255];
            $this->SetFillColor(...$rgb);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell($wLabel, 7, utf8_decode($this->truncar($nombre, 40)), 1, 0, 'L', true);
            $this->SetFont('Arial', '', 8);
            $this->Cell($wVal,   7, number_format($stockProd, 2), 1, 1, 'C', true);
            $par = !$par;
        }
    }

    private function filaTotalMes(
        float $sumStock,
        float $sumReserva,
        float $sumAgrega,
        float $sumMerma,
        array $sumProductos,
        array $productosUnicos
    ): void {
        if ($this->GetY() + $this->hRow > $this->GetPageHeight() - 15) {
            $this->AddPage();
        }

        $this->SetFillColor(255, 253, 231);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell($this->wFecha, $this->hRow, utf8_decode('TOTAL MES'), 1, 0, 'C', true);
        $this->Cell($this->wTurno, $this->hRow, '', 1, 0, 'C', true);
        $this->Cell($this->wNombre, $this->hRow, '', 1, 0, 'C', true);
        $this->Cell($this->wStock, $this->hRow, number_format($sumStock, 2), 1, 0, 'C', true);
        $this->Cell($this->wReserva, $this->hRow, number_format($sumReserva, 2), 1, 0, 'C', true);
        $this->Cell($this->wAgrega, $this->hRow, number_format($sumAgrega, 2), 1, 0, 'C', true);
        $this->Cell($this->wMerma, $this->hRow, number_format($sumMerma, 2), 1, 0, 'C', true);

        foreach ($productosUnicos as $nombre) {
            $this->Cell($this->wProd, $this->hRow, number_format($sumProductos[$nombre]['stock'] ?? 0, 2), 1, 0, 'C', true);
            $this->Cell($this->wProd, $this->hRow, number_format($sumProductos[$nombre]['cantidad_produccion'] ?? 0, 2), 1, 0, 'C', true);
        }

        $this->Ln();
    }

    // -------------------------------------------------------------------------
    // Utilidades
    // -------------------------------------------------------------------------
    private function textoFechas(string $inicio, string $fin): string
    {
        if (empty($inicio) && empty($fin)) {
            return 'Todos los registros';
        }
        $desde = !empty($inicio) ? date('d/m/Y', strtotime($inicio)) : 'N/A';
        $hasta = !empty($fin)    ? date('d/m/Y', strtotime($fin))    : 'N/A';
        return "Desde: {$desde} - Hasta: {$hasta}";
    }

    private function truncar(string $texto, int $max): string
    {
        return mb_strlen($texto) > $max ? mb_substr($texto, 0, $max - 1) . '.' : $texto;
    }
}
