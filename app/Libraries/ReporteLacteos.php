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
    protected $wNombre  = 21;
    protected $wStock   = 18;
    protected $wReserva = 18;
    protected $wProd    = 20; // ancho por cada columna Stock y Cant.Prod
    protected $wMA      = 10; // ancho compacto para columnas M. y Ag.

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
                $inv      = $dato['inventario'];
                $productos = $dato['productos'];

                $diaActual            = date('Y-m-d', strtotime($inv->created_at));
                $esPrimerDelDia       = ($diaActual !== $diaAnterior);

                if ($esPrimerDelDia && $diaAnterior !== null) {
                    $indiceDia++;
                }

                $esAmarillo   = ($indiceDia % 2 === 0);
                $bordeTop     = ($esPrimerDelDia && $diaAnterior !== null);

                if ($this->GetY() + $this->hHeader + $this->hRow > $this->GetPageHeight() - 15) {
                    $this->AddPage();
                    $this->filasMes($mesTexto, $productosUnicos);
                    $this->filasEncabezados($productosUnicos);
                    $diaAnterior = null;
                }

                $this->filasDatos($inv, $productos, $productosUnicos, $esAmarillo, $bordeTop, $esPrimerDelDia);

                $sumStock   += (float)($inv->stock   ?? 0);
                $sumReserva += (float)($inv->reserva ?? 0);
                foreach ($productosUnicos as $nombreProducto) {
                    $sumProductos[$nombreProducto]['stock']               += (float)($productos[$nombreProducto]['stock']               ?? 0);
                    $sumProductos[$nombreProducto]['cantidad_produccion'] += (float)($productos[$nombreProducto]['cantidad_produccion'] ?? 0);
                    $sumProductos[$nombreProducto]['merma']               += (float)($productos[$nombreProducto]['merma']               ?? 0);
                    $sumProductos[$nombreProducto]['agrega']              += (float)($productos[$nombreProducto]['agrega']              ?? 0);
                }

                $diaAnterior = $diaActual;
            }

            $this->filaTotalMes($sumStock, $sumReserva, 0, 0, $sumProductos, $productosUnicos);
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
                $agrupados[$nombre] = [
                    'stock'               => null,
                    'cantidad_produccion' => null,
                    'merma'               => null,
                    'agrega'              => null,
                ];
            }

            foreach ($productos as $prod) {
                $nombre = $this->normalizarNombre(trim($prod->nombre ?? ''));
                if (isset($agrupados[$nombre])) {
                    if ($agrupados[$nombre]['stock'] === null) {
                        $agrupados[$nombre]['stock']               = 0;
                        $agrupados[$nombre]['cantidad_produccion'] = 0;
                        $agrupados[$nombre]['merma']               = 0;
                        $agrupados[$nombre]['agrega']              = 0;
                    }
                    $agrupados[$nombre]['stock']               += ($prod->stock ?? 0);
                    $agrupados[$nombre]['cantidad_produccion'] += ($prod->cantidad_produccion ?? 0);
                    $agrupados[$nombre]['merma']               += ($prod->merma  ?? 0);
                    $agrupados[$nombre]['agrega']              += ($prod->agrega ?? 0);
                }
            }

            $resultado[] = [
                'inventario' => $inv,
                'productos'  => $agrupados,
            ];
        }
        return $resultado;
    }

    // -------------------------------------------------------------------------
    // Cálculo dinámico de anchos
    // -------------------------------------------------------------------------
    private function calcularAnchos(int $numProductos): void
    {
        $pageW  = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        // Columnas fijas: sin AGREGA ni MERMA
        $wFijas = $this->wFecha + $this->wTurno + $this->wNombre + $this->wStock + $this->wReserva;
        // Cada producto ocupa 4 columnas: Stock + Cant.Prod + M. + Ag.
        // wMA fijo en 10mm; wProd calculado con el espacio restante
        $wMATotal   = $numProductos > 0 ? $numProductos * 2 * $this->wMA : 0;
        $wDinamica  = $pageW - $wFijas - $wMATotal;

        if ($numProductos > 0) {
            $this->wProd = max(12, floor($wDinamica / ($numProductos * 2)));
        }

        // Si aún no cabe, reducir todo proporcionalmente
        $totalUsado = $wFijas + ($numProductos * 2 * $this->wProd) + $wMATotal;
        if ($totalUsado > $pageW && $numProductos > 0) {
            $factor = $pageW / $totalUsado;
            $this->wFecha   = floor($this->wFecha   * $factor);
            $this->wTurno   = floor($this->wTurno   * $factor);
            $this->wNombre  = floor($this->wNombre  * $factor);
            $this->wStock   = floor($this->wStock   * $factor);
            $this->wReserva = floor($this->wReserva * $factor);
            $this->wProd    = floor($this->wProd    * $factor);
            $this->wMA      = max(7, floor($this->wMA * $factor));
        }
    }

    // -------------------------------------------------------------------------
    // Dibujo de filas
    // -------------------------------------------------------------------------
    private function anchoTotal(int $numProductos): float
    {
        return $this->wFecha + $this->wTurno + $this->wNombre + $this->wStock
             + $this->wReserva
             + ($numProductos * (2 * $this->wProd + 2 * $this->wMA));
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
        $this->SetFont('Arial', 'B', 6);

        // Calcular alto: máximo de líneas en etiquetas de producto (Stk y Prod)
        $maxLineas = 1;
        foreach ($productosUnicos as $nombre) {
            foreach ([utf8_decode($nombre) . ' Stk', utf8_decode($nombre) . ' Prod'] as $etiqueta) {
                $lineas = $this->contarLineas($etiqueta, $this->wProd);
                if ($lineas > $maxLineas) {
                    $maxLineas = $lineas;
                }
            }
        }
        $this->hHeader = max(5 * $this->hHeaderLine + 4, $maxLineas * $this->hHeaderLine + 4);

        $yInicio = $this->GetY();
        $xInicio = $this->GetX();

        // Columnas fijas (sin AGREGA ni MERMA)
        $this->SetFillColor(74, 111, 165);
        $this->SetTextColor(255, 255, 255);

        $fijas = [
            ['FECHA',     $this->wFecha],
            ['TURNO',     $this->wTurno],
            ['NOMBRE',    $this->wNombre],
            ['STOCK (L)', $this->wStock],
            ['RESERVA',   $this->wReserva],
        ];

        foreach ($fijas as [$label, $w]) {
            $this->celdaMultilineaCentrada($label, $w, $this->hHeader, $this->hHeaderLine, 74, 111, 165);
        }

        $this->SetFont('Arial', 'B', 6);
        // Columnas dinámicas: Stock | Cant.Prod | M. | Ag.
        foreach ($productosUnicos as $nombre) {
            $this->celdaMultilineaCentrada(utf8_decode($nombre) . ' Stk',  $this->wProd, $this->hHeader, $this->hHeaderLine, 93, 138, 138);
            $this->celdaMultilineaCentrada(utf8_decode($nombre) . ' Prod', $this->wProd, $this->hHeader, $this->hHeaderLine, 123, 164, 164);
            $this->celdaMultilineaCentrada('M.',  $this->wMA, $this->hHeader, $this->hHeaderLine, 122, 106, 138);
            $this->celdaMultilineaCentrada('Ag.', $this->wMA, $this->hHeader, $this->hHeaderLine, 122, 106, 138);
        }

        $this->SetTextColor(0, 0, 0);
        $this->SetXY($xInicio, $yInicio + $this->hHeader);
        $this->Ln(0);
    }

    private function calcularAlturaEncabezado(array $productosUnicos): float
    {
        $this->SetFont('Arial', 'B', 6);
        $maxLineas = 1;
        foreach ($productosUnicos as $nombre) {
            foreach ([utf8_decode($nombre) . ' Stk', utf8_decode($nombre) . ' Prod'] as $etiqueta) {
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

    private function filasDatos($inv, array $productos, array $productosUnicos, bool $esAmarillo, bool $bordeTop, bool $esPrimerDelDia, float $agrega = 0, float $merma = 0): void
    {
        if ($esAmarillo) {
            $this->SetFillColor(240, 247, 240);
        } else {
            $this->SetFillColor(245, 245, 245);
        }

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

        foreach ($productosUnicos as $nombre) {
            $datoProd   = $productos[$nombre] ?? ['stock' => null, 'cantidad_produccion' => null, 'merma' => null, 'agrega' => null];
            $textoStock = $datoProd['stock']               === null ? '-' : number_format($datoProd['stock'], 2);
            $textoProd  = $datoProd['cantidad_produccion'] === null ? '-' : number_format($datoProd['cantidad_produccion'], 2);
            $textoMerma = $datoProd['merma']               === null ? '-' : (string)(int)$datoProd['merma'];
            $textoAgrega = $datoProd['agrega']             === null ? '-' : (string)(int)$datoProd['agrega'];

            $this->SetFont('Arial', '', 6);
            $this->Cell($this->wProd, $this->hRow, $textoStock,  1, 0, 'C', true);
            $this->Cell($this->wProd, $this->hRow, $textoProd,   1, 0, 'C', true);

            // M. y Ag. en fuente más pequeña para caber en columna compacta
            $this->SetFont('Arial', '', 6);
            $this->SetFillColor(238, 232, 245); // fondo morado muy suave
            $this->Cell($this->wMA, $this->hRow, $textoMerma,  1, 0, 'C', true);
            $this->Cell($this->wMA, $this->hRow, $textoAgrega, 1, 0, 'C', true);

            // Restaurar fuente y color de fondo para el siguiente producto
            $this->SetFont('Arial', '', 7);
            if ($esAmarillo) {
                $this->SetFillColor(240, 247, 240);
            } else {
                $this->SetFillColor(245, 245, 245);
            }
        }

        $this->Ln();
    }

    private function generarResumen(array $datosConProductos, array $productosUnicos): void
    {
        // ── Construir datos mensuales (misma lógica que ExportacionExcelService) ──
        $mensual = [];
        foreach ($datosConProductos as $dato) {
            $inv = $dato['inventario'];
            $mes = date('Y-m', strtotime($inv->created_at));
            $dia = date('Y-m-d', strtotime($inv->created_at));

            if (!isset($mensual[$mes])) {
                $mensual[$mes] = [
                    'mes'   => $mes,
                    'leche' => ['litros_usados' => 0.0, 'dias' => []],
                    'productos' => [],
                ];
                foreach ($productosUnicos as $p) {
                    $mensual[$mes]['productos'][$p] = [
                        'producido'          => 0.0, 'merma'           => 0.0,
                        'agrega'             => 0.0, 'vendido'         => 0.0,
                        'vendido_contado'    => 0.0, 'vendido_credito' => 0.0,
                        'ingresos'           => 0.0, 'ingresos_contado'=> 0.0,
                        'ingresos_credito'   => 0.0,
                    ];
                }
            }

            $mensual[$mes]['leche']['dias'][$dia] = true;

            foreach ($dato['productos'] as $nombre => $vals) {
                if ($vals['cantidad_produccion'] === null) continue;
                $litros = (float)($vals['cantidad_produccion'] ?? 0) * 0; // litros_usados no disponible aquí
                $mensual[$mes]['productos'][$nombre]['producido'] += (float)($vals['cantidad_produccion'] ?? 0);
                $mensual[$mes]['productos'][$nombre]['merma']     += (float)($vals['merma']  ?? 0);
                $mensual[$mes]['productos'][$nombre]['agrega']    += (float)($vals['agrega'] ?? 0);
            }
        }

        // ── Query de ventas reales ────────────────────────────────────────────
        $mesesKeys = array_keys($mensual);
        if (!empty($mesesKeys)) {
            sort($mesesKeys);
            $fechaDesde = $mesesKeys[0] . '-01 00:00:00';
            $fechaHasta = date('Y-m-t 23:59:59', strtotime(end($mesesKeys) . '-01'));
            $db  = \Config\Database::connect();
            $sql = "
                SELECT TO_CHAR(v.created_at,'YYYY-MM') AS mes,
                       p.nombre AS producto, v.tipo_pago,
                       SUM(dv.cantidad) AS vendido, SUM(dv.subtotal) AS ingresos
                FROM condoriri.ventas v
                JOIN condoriri.detalle_venta dv ON dv.venta_id=v.id AND dv.deleted_at IS NULL
                JOIN condoriri.productos p      ON p.id=dv.producto_id AND p.deleted_at IS NULL
                WHERE v.deleted_at IS NULL AND v.sucursal_id=4
                  AND dv.producto_agro_id IS NULL
                  AND v.created_at >= ? AND v.created_at <= ?
                GROUP BY TO_CHAR(v.created_at,'YYYY-MM'), p.nombre, v.tipo_pago
                ORDER BY mes, p.nombre
            ";
            foreach ($db->query($sql, [$fechaDesde, $fechaHasta])->getResult() as $venta) {
                $mes  = $venta->mes;
                $prod = $this->normalizarNombre(trim($venta->producto ?? ''));
                if (!isset($mensual[$mes]['productos'][$prod])) continue;
                $esContado = strtolower($venta->tipo_pago ?? '') === 'contado';
                $mensual[$mes]['productos'][$prod]['vendido']  += (float)$venta->vendido;
                $mensual[$mes]['productos'][$prod]['ingresos'] += (float)$venta->ingresos;
                if ($esContado) {
                    $mensual[$mes]['productos'][$prod]['vendido_contado']  += (float)$venta->vendido;
                    $mensual[$mes]['productos'][$prod]['ingresos_contado'] += (float)$venta->ingresos;
                } else {
                    $mensual[$mes]['productos'][$prod]['vendido_credito']  += (float)$venta->vendido;
                    $mensual[$mes]['productos'][$prod]['ingresos_credito'] += (float)$venta->ingresos;
                }
            }
        }
        ksort($mensual);

        // ── Colores reutilizables ─────────────────────────────────────────────
        $azulOsc  = [31,  78, 121];   // encabezado título bloque
        $azulMed  = [46, 117, 182];   // encabezado columnas
        $amarillo = [255, 253, 231];  // subtotal mes
        $verdePar = [222, 234, 246];  // fila par
        $grisImpar= [245, 251, 255];  // fila impar
        $azulTotal= [31,  78, 121];   // fila total general

        // ── BLOQUE 1 — Desglose por mes → producto ────────────────────────────
        $this->AddPage();
        $this->Ln(2);
        $this->resumenTitulo(utf8_decode('PRODUCCION Y VENTAS  DESGLOSE POR MES'), $azulOsc);

        // Columnas: MES(35) | PRODUCTO(50) | PROD(22) | MERMA(18) | AGREGA(18) |
        //           VTA.CO(22) | VTA.CR(22) | TOT.VTA(22) | ING.CO(30) | ING.CR(30) | TOT.ING(30)
        $cols1 = [
            utf8_decode('MES')            => 35,
            utf8_decode('PRODUCTO')       => 50,
            utf8_decode('PRODUCIDO')      => 22,
            utf8_decode('MERMA')          => 18,
            utf8_decode('AGREGA')         => 18,
            utf8_decode('VTA. CONTADO')   => 22,
            utf8_decode('VTA. CREDITO')   => 22,
            utf8_decode('TOTAL VENDIDO')  => 22,
            utf8_decode('ING. CONTADO')   => 30,
            utf8_decode('ING. CREDITO')   => 30,
            utf8_decode('TOTAL INGRESOS') => 30,
        ];
        $this->resumenEncabezadoColumnas($cols1, $azulMed);

        $totGral1 = array_fill_keys(['producido','merma','agrega','vendido','vendido_contado','vendido_credito','ingresos','ingresos_contado','ingresos_credito'], 0.0);
        $par = true;
        $primerMes = true;

        foreach ($mensual as $item) {
            $mesTexto = $this->textoMesCorto($item['mes']);
            $totMes   = array_fill_keys(array_keys($totGral1), 0.0);
            $hayFilas = false;

            foreach ($productosUnicos as $prod) {
                $p = $item['productos'][$prod];
                if ($p['producido'] <= 0 && $p['vendido'] <= 0 && $p['merma'] <= 0 && $p['agrega'] <= 0) continue;

                // Separador visual entre meses
                $bordeTop = !$primerMes && !$hayFilas;
                $rgb = $par ? $verdePar : $grisImpar;
                $this->resumenFila([
                    $mesTexto,
                    utf8_decode($this->truncar($prod, 28)),
                    number_format($p['producido'],       2),
                    number_format($p['merma'],           2),
                    number_format($p['agrega'],          2),
                    number_format($p['vendido_contado'], 2),
                    number_format($p['vendido_credito'], 2),
                    number_format($p['vendido'],         2),
                    number_format($p['ingresos_contado'],2),
                    number_format($p['ingresos_credito'],2),
                    number_format($p['ingresos'],        2),
                ], array_values($cols1), $rgb, $bordeTop ? 2 : 0.2);

                foreach (array_keys($totMes) as $k) { $totMes[$k] += $p[$k]; }
                $par = !$par;
                $hayFilas = true;
                $primerMes = false;
            }

            if ($hayFilas) {
                // Subtotal mes
                $this->resumenFila([
                    utf8_decode('TOTAL ' . strtoupper($mesTexto)), '',
                    number_format($totMes['producido'],       2),
                    number_format($totMes['merma'],           2),
                    number_format($totMes['agrega'],          2),
                    number_format($totMes['vendido_contado'], 2),
                    number_format($totMes['vendido_credito'], 2),
                    number_format($totMes['vendido'],         2),
                    number_format($totMes['ingresos_contado'],2),
                    number_format($totMes['ingresos_credito'],2),
                    number_format($totMes['ingresos'],        2),
                ], array_values($cols1), $amarillo, 0.2, true);
                foreach (array_keys($totGral1) as $k) { $totGral1[$k] += $totMes[$k]; }
            }
        }
        // Total general bloque 1
        $this->resumenFilaTotal([
            utf8_decode('TOTAL GENERAL'), '',
            number_format($totGral1['producido'],       2),
            number_format($totGral1['merma'],           2),
            number_format($totGral1['agrega'],          2),
            number_format($totGral1['vendido_contado'], 2),
            number_format($totGral1['vendido_credito'], 2),
            number_format($totGral1['vendido'],         2),
            number_format($totGral1['ingresos_contado'],2),
            number_format($totGral1['ingresos_credito'],2),
            number_format($totGral1['ingresos'],        2),
        ], array_values($cols1), $azulTotal);

        // ── BLOQUE 2 — Consolidado por producto ───────────────────────────────
        $this->Ln(8);
        if ($this->GetY() + 60 > $this->GetPageHeight() - 15) { $this->AddPage(); }

        // Calcular rango de meses
        $mesesKeys = array_keys($mensual);
        $rangoMes  = $this->textoRangoPdf($mesesKeys);

        $this->resumenTitulo(utf8_decode('PRODUCCION Y VENTAS  CONSOLIDADO POR PRODUCTO'), $azulOsc);
        $this->resumenEncabezadoColumnas($cols1, $azulMed);

        $totGral2 = array_fill_keys(array_keys($totGral1), 0.0);
        $par = true;

        foreach ($productosUnicos as $prod) {
            $acc = array_fill_keys(array_keys($totGral1), 0.0);
            foreach ($mensual as $item) {
                $p = $item['productos'][$prod];
                foreach (array_keys($acc) as $k) { $acc[$k] += $p[$k]; }
            }
            if ($acc['producido'] <= 0 && $acc['vendido'] <= 0 && $acc['merma'] <= 0 && $acc['agrega'] <= 0) continue;

            $rgb = $par ? $verdePar : $grisImpar;
            $this->resumenFila([
                utf8_decode($rangoMes),
                utf8_decode($this->truncar($prod, 28)),
                number_format($acc['producido'],       2),
                number_format($acc['merma'],           2),
                number_format($acc['agrega'],          2),
                number_format($acc['vendido_contado'], 2),
                number_format($acc['vendido_credito'], 2),
                number_format($acc['vendido'],         2),
                number_format($acc['ingresos_contado'],2),
                number_format($acc['ingresos_credito'],2),
                number_format($acc['ingresos'],        2),
            ], array_values($cols1), $rgb, 0.2);

            foreach (array_keys($totGral2) as $k) { $totGral2[$k] += $acc[$k]; }
            $par = !$par;
        }
        $this->resumenFilaTotal([
            utf8_decode('TOTAL GENERAL'), '',
            number_format($totGral2['producido'],       2),
            number_format($totGral2['merma'],           2),
            number_format($totGral2['agrega'],          2),
            number_format($totGral2['vendido_contado'], 2),
            number_format($totGral2['vendido_credito'], 2),
            number_format($totGral2['vendido'],         2),
            number_format($totGral2['ingresos_contado'],2),
            number_format($totGral2['ingresos_credito'],2),
            number_format($totGral2['ingresos'],        2),
        ], array_values($cols1), $azulTotal);

        // ── BLOQUE 3 — Consolidado por mes ────────────────────────────────────
        $this->Ln(8);
        if ($this->GetY() + 60 > $this->GetPageHeight() - 15) { $this->AddPage(); }

        $this->resumenTitulo(utf8_decode(' CONSOLIDADO POR MES'), $azulOsc);

        $cols3 = [
            utf8_decode('MES')                  => 40,
            utf8_decode('UNIDADES PRODUCIDAS')  => 35,
            utf8_decode('ING. CONTADO (Bs)')    => 38,
            utf8_decode('ING. CREDITO (Bs)')    => 38,
            utf8_decode('TOTAL INGRESOS (Bs)')  => 38,
        ];
        $this->resumenEncabezadoColumnas($cols3, $azulMed);

        $totLitros = 0.0; $totProd3 = 0.0; $totCo3 = 0.0; $totCr3 = 0.0;
        $par = true;

        foreach ($mensual as $item) {
            $producido  = 0.0; $ingCo = 0.0; $ingCr = 0.0;
            foreach ($productosUnicos as $prod) {
                $p = $item['productos'][$prod];
                $producido += $p['producido'];
                $ingCo     += $p['ingresos_contado'];
                $ingCr     += $p['ingresos_credito'];
            }
            $totProd3 += $producido; $totCo3 += $ingCo; $totCr3 += $ingCr;

            $rgb = $par ? $verdePar : $grisImpar;
            $this->resumenFila([
                utf8_decode($this->textoMesCorto($item['mes'])),
                number_format($producido,        2),
                number_format($ingCo,            2),
                number_format($ingCr,            2),
                number_format($ingCo + $ingCr,   2),
            ], array_values($cols3), $rgb, 0.2);
            $par = !$par;
        }
        $this->resumenFilaTotal([
            utf8_decode('TOTAL GENERAL'),
            number_format($totProd3,          2),
            number_format($totCo3,            2),
            number_format($totCr3,            2),
            number_format($totCo3 + $totCr3,  2),
        ], array_values($cols3), $azulTotal);
    }

    // ── Helpers de renderizado para el resumen ────────────────────────────────

    private function resumenTitulo(string $texto, array $rgb): void
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(...$rgb);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, $texto, 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);
    }

    private function resumenEncabezadoColumnas(array $cols, array $rgb): void
    {
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(...$rgb);
        $this->SetTextColor(255, 255, 255);
        foreach ($cols as $label => $w) {
            $this->Cell($w, 7, $label, 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }

    /**
     * Fila de datos del resumen.
     * $bordeGrosor: 0.2 normal, 2 separador de grupo (borde superior grueso).
     */
    private function resumenFila(array $valores, array $anchos, array $rgb, float $bordeGrosor = 0.2, bool $negrita = false): void
    {
        if ($this->GetY() + 6 > $this->GetPageHeight() - 15) {
            $this->AddPage();
        }
        $this->SetFillColor(...$rgb);
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth($bordeGrosor);

        $font = $negrita ? 'B' : '';
        $this->SetFont('Arial', $font, 7);

        foreach ($valores as $i => $val) {
            $align = $i < 2 ? 'L' : 'R';
            $this->Cell($anchos[$i], 6, $val, 1, 0, $align, true);
        }
        $this->Ln();
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
    }

    private function resumenFilaTotal(array $valores, array $anchos, array $rgb): void
    {
        if ($this->GetY() + 7 > $this->GetPageHeight() - 15) {
            $this->AddPage();
        }
        $this->SetFillColor(...$rgb);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        foreach ($valores as $i => $val) {
            $align = $i < 2 ? 'L' : 'R';
            $this->Cell($anchos[$i], 7, $val, 1, 0, $align, true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }

    private function textoMesCorto(string $mes): string
    {
        $meses = [
            '01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril',
            '05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto',
            '09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre',
        ];
        [$anio, $numMes] = explode('-', $mes);
        return ($meses[$numMes] ?? $numMes) . ' ' . $anio;
    }

    private function textoRangoPdf(array $mesesKeys): string
    {
        if (empty($mesesKeys)) return '';
        sort($mesesKeys);
        $primero = $mesesKeys[0];
        $ultimo  = end($mesesKeys);
        if ($primero === $ultimo) return $this->textoMesCorto($primero);
        [$anioP, $mesP] = explode('-', $primero);
        [$anioU, $mesU] = explode('-', $ultimo);
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril',
                  '05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto',
                  '09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        if ($anioP === $anioU) {
            return ($meses[$mesP] ?? $mesP) . ' - ' . ($meses[$mesU] ?? $mesU) . ' ' . $anioU;
        }
        return $this->textoMesCorto($primero) . ' - ' . $this->textoMesCorto($ultimo);
    }

    private function filaTotalMes(
        float $sumStock,
        float $sumReserva,
        float $_sumAgrega,
        float $_sumMerma,
        array $sumProductos,
        array $productosUnicos
    ): void {
        if ($this->GetY() + $this->hRow > $this->GetPageHeight() - 15) {
            $this->AddPage();
        }

        $this->SetFillColor(255, 253, 231);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell($this->wFecha,   $this->hRow, utf8_decode('TOTAL MES'), 1, 0, 'C', true);
        $this->Cell($this->wTurno,   $this->hRow, '', 1, 0, 'C', true);
        $this->Cell($this->wNombre,  $this->hRow, '', 1, 0, 'C', true);
        $this->Cell($this->wStock,   $this->hRow, number_format($sumStock,   2), 1, 0, 'C', true);
        $this->Cell($this->wReserva, $this->hRow, number_format($sumReserva, 2), 1, 0, 'C', true);

        foreach ($productosUnicos as $nombre) {
            $this->Cell($this->wProd, $this->hRow, number_format($sumProductos[$nombre]['stock']               ?? 0, 2), 1, 0, 'C', true);
            $this->Cell($this->wProd, $this->hRow, number_format($sumProductos[$nombre]['cantidad_produccion'] ?? 0, 2), 1, 0, 'C', true);
            $this->SetFont('Arial', 'B', 6);
            $this->SetFillColor(238, 232, 245);
            $this->Cell($this->wMA, $this->hRow, (string)(int)($sumProductos[$nombre]['merma']  ?? 0), 1, 0, 'C', true);
            $this->Cell($this->wMA, $this->hRow, (string)(int)($sumProductos[$nombre]['agrega'] ?? 0), 1, 0, 'C', true);
            $this->SetFont('Arial', 'B', 7);
            $this->SetFillColor(255, 253, 231);
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
