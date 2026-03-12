<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use App\Models\Producto\ProductoModel;
use FPDF; // FPDF ya fue incluido via require_once, pero se define para el tipado.

class ReporteLacteos extends FPDF
{
    protected $pag_ini;
    protected $pag_total;
    protected $reporteTitle = 'REPORTE DE INVENTARIO Y STOCK';

    // Propiedades para los anchos de columna de la tabla de productos
    protected $anchoTabla;

    public function __construct($pag_ini = 0, $pag_total = 0)
    {
        // Se mantiene el formato LEGAL apaisado ('L')
        parent::__construct('L', 'mm', 'LEGAL'); 
        $this->pag_ini = $pag_ini;
        $this->pag_total = $pag_total;

        $this->SetAuthor('DTIC');
        $this->SetTitle('Reporte de Inventarios');
        $this->SetSubject('Control de Inventarios');
        // Márgenes ajustados
        $this->SetMargins(15, 30, 15);
        $this->SetAutoPageBreak(true, 15);
        $this->SetFont('Arial', '', 10);
        $this->AliasNbPages(); // Necesario para el pie de página {nb}
    }

    // Adaptación del estilo de Header de CierreVentaInve
    public function Header()
    {
        // Logos (Asegúrate que las rutas sean correctas en tu entorno CodeIgniter)
        $logo_left = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

        // Altura del encabezado: 30 mm
        $this->SetY(10);

        // Logo izquierdo
        if (file_exists($logo_left)) {
            $this->Image($logo_left, 15, 10, 22);
        }

        // Logo derecho
        if (file_exists($logo_right)) {
            $this->Image($logo_right, $this->GetPageWidth() - 37, 10, 22);
        }

        // Títulos institucionales centrados
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 6, utf8_decode("UNIVERSIDAD TÉCNICA DE ORURO"), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 5, utf8_decode("FACULTAD DE CIENCIAS AGRARIAS Y NATURALES"), 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, utf8_decode("CONDORIRI - LABORATORIO DE INNOVACIÓN"), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode("Telf.: 5281745 | Interno: 120 | FAX: 5242215 | Casilla 49"), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode("Email: dpdi@uto.edu.bo | www.uto.edu.bo"), 0, 1, 'C');

        // Línea divisoria
        $this->Ln(4);
        $this->SetDrawColor(0, 0, 0);
        $this->Line(15, $this->GetY(), $this->GetPageWidth() - 15, $this->GetY());
        $this->Ln(6);

        // Título del reporte
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, utf8_decode($this->reporteTitle), 0, 1, 'C');
        $this->Ln(4);
    }

    // Adaptación del estilo de Footer de CierreVentaInve
    public function Footer()
    {
        $this->SetY(-18);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100);
        $this->Cell(0, 4, utf8_decode('Generado el: ' . date('d/m/Y H:i:s')), 0, 1, 'L');
        $this->Cell(0, 4, utf8_decode('Sistema de Gestión de Inventarios CEAC-UTO'), 0, 1, 'L');
        $this->Ln(2);
        $this->SetTextColor(0);
        $this->Cell(0, 6, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    /**
     * Organiza productos en estructura jerárquica: padres → hijos
     */
    private function organizarProductosJerarquicos(array $productos): array
    {
        $padres = [];
        $hijos  = [];

        foreach ($productos as $p) {
            // Asegurar que se usen objetos
            $p = (object)$p; // Casting a objeto por seguridad
            $id = $p->id;
            $parentId = $p->parent_id;

            if (empty($parentId) || $parentId == 0) {
                $padres[$id] = $p;
                $padres[$id]->subproductos = [];
            } else {
                $hijos[] = $p;
            }
        }

        foreach ($hijos as $hijo) {
            $pid = $hijo->parent_id;
            $id = $hijo->id;
            if (isset($padres[$pid])) {
                $padres[$pid]->subproductos[] = $hijo;
            } else {
                // Si el padre no existe, lo tratamos como raíz (o producto huérfano)
                $padres[$id] = $hijo;
                $padres[$id]->subproductos = [];
            }
        }

        // Ordenar los padres por nombre para una mejor visualización
        usort($padres, function($a, $b) {
            return strcmp($a->nombre, $b->nombre);
        });

        return $padres;
    }

    /**
     * Título de sección con estilo
     */
    private function seccionTitulo(string $texto)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetDrawColor(0, 100, 200); // Borde azul
        $this->SetFillColor(240, 245, 255); // Fondo azul muy claro
        $this->Cell(0, 7, utf8_decode($texto), 1, 1, 'L', true); // Se añade borde
        $this->Ln(2);
    }
    
    /**
     * Título de subsección con estilo (Específico para subproductos)
     */
    private function seccionSubtitulo(string $texto)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(230, 240, 255); // Fondo azul muy claro, diferente al principal
        $this->Cell(0, 6, utf8_decode("  » " . $texto), 1, 1, 'L', true); // Se usa borde completo
    }

    /**
     * Muestra los datos técnicos solo si están presentes
     */
    private function mostrarDatosTecnicos($producto)
    {
        $ph = $producto->ph ?? null;
        $acidez = $producto->acides ?? null;
        $porocidad = $producto->porocidad ?? null;
        $consistencia = $producto->consistencia ?? null;
        $color = $producto->color ?? null;
        $olor = $producto->olor ?? null;
        $textura = $producto->textura ?? null;
        $materia_sub = $producto->materia_sub ?? null;

        if ($ph || $acidez || $porocidad || $consistencia || $color || $olor || $textura || $materia_sub) {
            $this->SetFont('Arial', 'I', 8);
            
            $data = [
                'PH' => $ph, 
                'Acidez' => $acidez, 
                'Porocidad' => $porocidad, 
                'Consistencia' => $consistencia,
                'Color' => $color,
                'Olor' => $olor,
                'Textura' => $textura,
                'Materia/Sub.' => $materia_sub
            ];
            
            // Ancho para la etiqueta y el valor
            $wData = 25; 
            $currentW = 0;
            $start_x = $this->GetX();

            // Inicio de la fila de datos técnicos con un pequeño margen
            $this->Cell(0, 0, '', 0, 1);
            $this->SetX($start_x + 5);
            $this->SetFillColor(250, 250, 250); // Fondo claro para los datos técnicos
            $this->SetDrawColor(200, 200, 200); // Borde más suave

            $datos_existentes = 0;
            foreach ($data as $label => $value) {
                if (!empty($value)) {
                    $datos_existentes++;
                    // Si el siguiente par no cabe, salta línea y ajusta X
                    if (($this->GetX() + $wData * 2 + 10) > ($this->GetPageWidth() - 15) && $currentW > 0) {
                        $this->Ln(5);
                        $this->SetX($start_x + 5);
                        $currentW = 0;
                    }

                    $this->SetFont('Arial', 'B', 8);
                    $this->Cell($wData, 5, utf8_decode($label . ':'), 'LTB', 0, 'R', true);
                    $this->SetFont('Arial', '', 8);
                    $this->Cell($wData, 5, utf8_decode($value), 'RTB', 0, 'L', true);
                    $currentW += $wData * 2;
                }
            }
            if ($datos_existentes > 0) {
                 $this->Ln(6); // Salto de línea al final de los datos técnicos
            }
            $this->SetDrawColor(0, 0, 0); // Restaurar color de borde
        }
    }

    /**
     * Dibuja una fila de producto (padre o hijo) en la tabla.
     * @param object $producto El objeto del producto/subproducto.
     * @param bool $is_subproducto Indica si es un subproducto para el formato visual.
     * @param bool $skip_border_top Omite el borde superior para el subproducto.
     */
    private function dibujarFilaProducto($producto, bool $is_subproducto, bool $skip_border_top = false)
    {
        $h = $is_subproducto ? 6 : 7;
        $fill_color = $is_subproducto ? [250, 253, 255] : [240, 247, 255];
        $prefix = $is_subproducto ? "  ▸ " : "■ ";
        $font_style = $is_subproducto ? '' : 'B';
        
        // El borde para el subproducto es solo izquierdo, derecho e inferior (LRB) 
        // para que parezca contiguo a la cabecera de Subproductos.
        $border_style = $is_subproducto ? 'LRB' : 1;
        
        $this->SetFont('Arial', $font_style, 9);
        $this->SetFillColor(...$fill_color);

        // Celda de Nombre
        $this->Cell($this->anchoTabla[0], $h, utf8_decode($prefix . $producto->nombre), $border_style, 0, 'L', true);
        
        // Celdas de Datos Numéricos
        $this->Cell($this->anchoTabla[1], $h, $producto->stock, $border_style, 0, 'C');
        $this->Cell($this->anchoTabla[2], $h, 'Bs ' . number_format($producto->precio_credito, 2), $border_style, 0, 'R');
        $this->Cell($this->anchoTabla[3], $h, 'Bs ' . number_format($producto->precio_contado, 2), $border_style, 0, 'R');
        $this->Cell($this->anchoTabla[4], $h, $producto->cantidad_produccion, $border_style, 0, 'C');
        
        // Reserva y Merma
        $reserva_merma = (isset($producto->reserva) ? "R: $producto->reserva" : '') . 
                         (isset($producto->merma) && isset($producto->reserva) ? ' | ' : '') . 
                         (isset($producto->merma) ? "M: $producto->merma" : '');
        $this->Cell($this->anchoTabla[5], $h, utf8_decode($reserva_merma), $border_style, 0, 'C');

        // Vencimiento y Descripción
        $fecha_vencimiento = $producto->fecha_vencimiento ? date('d/m/Y', strtotime($producto->fecha_vencimiento)) : '—';
        $descripcion_corta = substr($producto->descripcion ?? '', 0, 20) . (strlen($producto->descripcion ?? '') > 20 ? '...' : '');
        $venc_obs = "$fecha_vencimiento | $descripcion_corta";
        $this->Cell($this->anchoTabla[6], $h, utf8_decode($venc_obs), $border_style, 1, 'L');
    }

    /**
     * Imprime la cabecera de la tabla de productos
     */
    private function imprimirCabeceraTabla()
    {
        $this->SetFillColor(200, 220, 255);
        $this->SetFont('Arial', 'B', 10);
        $headers = [utf8_decode('Producto'), 'Stock', utf8_decode('P. Crédito'), utf8_decode('P. Contado'), utf8_decode('Prod. (L/Kg)'), 'Reserva/Merma', utf8_decode('Venc. | Obs.')];
        
        foreach ($headers as $i => $h) {
            $this->Cell($this->anchoTabla[$i], 7, utf8_decode($h), 1, 0, 'C', true);
        }
        $this->Ln();
    }


    public function generarReporte(array $inventarios, array $filtros)
    {
        $this->AddPage();
        if ($this->pag_total === 0) {
            $this->pag_total = $this->PageNo();
        }

        // --- 1. Sección de Filtros ---
        $this->seccionTitulo('1. Filtros Aplicados');
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(60, 6, utf8_decode('Nombre/Código: ') . ($filtros['nombre'] ?? 'Todos'), 0, 0);
        $this->Cell(60, 6, utf8_decode('Fecha Inicio: ') . ($filtros['fecha_inicio'] ?? 'N/A'), 0, 0);
        $this->Cell(60, 6, utf8_decode('Fecha Fin: ') . ($filtros['fecha_fin'] ?? 'N/A'), 0, 1);
        $this->Ln(8);

        $productoModel = new ProductoModel();

        $grandTotalStock = 0;
        $grandTotalCredito = 0;
        $grandTotalContado = 0;
        $totalsByProduct = [];

        // Definir anchos de tabla (calculado solo una vez)
        $pageWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $this->anchoTabla = [
            $pageWidth * 0.25, // Nombre
            $pageWidth * 0.10, // Stock
            $pageWidth * 0.12, // P. Crédito
            $pageWidth * 0.12, // P. Contado
            $pageWidth * 0.10, // Cant. Prod.
            $pageWidth * 0.10, // Reserva/Merma (Unificado)
            $pageWidth * 0.21, // Vencimiento y Observaciones (Unificado)
        ];


        // --- 2. Recorrido por Inventario ---
        $this->seccionTitulo('2. Detalle de Inventarios');
        $this->Ln(3);

        foreach ($inventarios as $inventario) {
            // Verificar salto de página para el encabezado del inventario
            if ($this->GetY() > $this->GetPageHeight() - 40) {
                $this->AddPage();
            }

            $this->SetFont('Arial', 'B', 12);
            $this->SetFillColor(220, 235, 255);
            $this->Cell(0, 10, utf8_decode("Inventario: {$inventario->nombre} (Cód: {$inventario->code})"), 1, 1, 'L', true);
            $this->SetFont('Arial', '', 10);
            
            // Fila 1 de datos del inventario
            $this->Cell(70, 6, utf8_decode('Descripción: ') . ($inventario->descripcion ?? ''), 1, 0, 'L');
            $this->Cell(50, 6, 'Stock General: ' . ($inventario->stock ?? 0), 1, 0, 'L');
            $this->Cell(50, 6, 'Turno: ' . ($inventario->turno ?? ''), 1, 0, 'L');
            $this->Cell(0, 6, 'Estado: ' . ($inventario->estado ? 'Activo' : 'Inactivo'), 1, 1, 'L');
            $this->Ln(5);

            // Productos del inventario
            $productos = $productoModel
                ->select('id, nombre, descripcion, stock, precio_credito, precio_contado, cantidad_produccion, fecha_vencimiento, parent_id, ph, acides, porocidad, consistencia, color, olor, textura, materia_sub, litros, merma, reserva')
                ->where('inventario_id', $inventario->id)
                ->orderBy('parent_id', 'ASC')
                ->orderBy('nombre', 'ASC')
                ->findAll();

            if (empty($productos)) {
                $this->SetFont('Arial', 'I', 10);
                $this->Cell(0, 10, utf8_decode('⚠️ No hay productos asociados.'), 0, 1);
                $this->Ln(5);
                continue;
            }

            // Organizar en jerarquía
            $productosJerarquicos = $this->organizarProductosJerarquicos($productos);

            // --- Tabla principal de Productos ---
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 7, 'Productos y Subproductos:', 0, 1);
            $this->Ln(2);

            // Imprimir cabecera de la tabla
            $this->imprimirCabeceraTabla();

            $this->SetFont('Arial', '', 9);

            foreach ($productosJerarquicos as $padre) {
                // Casting a objeto para asegurar el acceso con flecha ->
                $padre = (object)$padre; 

                // Verificar salto de página antes de dibujar el padre
                if ($this->GetY() > $this->GetPageHeight() - 25) {
                    $this->AddPage();
                    $this->imprimirCabeceraTabla();
                }

                // ▼ Producto principal (nivel 1)
                $this->dibujarFilaProducto($padre, false);

                // ▸ Mostrar datos técnicos si existen
                $this->mostrarDatosTecnicos($padre);
               
                // Acumular totales del padre
                $key = utf8_decode($padre->nombre);
                if (!isset($totalsByProduct[$key])) {
                    $totalsByProduct[$key] = ['stock' => 0, 'credito' => 0, 'contado' => 0];
                }
                $grandTotalStock += $padre->stock;
                $grandTotalCredito += ($padre->stock * $padre->precio_credito);
                $grandTotalContado += ($padre->stock * $padre->precio_contado);
                $totalsByProduct[$key]['stock'] += $padre->stock;
                $totalsByProduct[$key]['credito'] += ($padre->stock * $padre->precio_credito);
                $totalsByProduct[$key]['contado'] += ($padre->stock * $padre->precio_contado);

                // Subproductos (nivel 2)
                if (!empty($padre->subproductos)) {
                    
                    // Asegurar que el subtítulo no quede al final de la página
                    if ($this->GetY() > $this->GetPageHeight() - 25) {
                        $this->AddPage();
                        $this->imprimirCabeceraTabla();
                    }

                    // --- SUBTÍTULO PARA SUBPRODUCTOS ---
                    $this->seccionSubtitulo('SUBPRODUCTOS ASOCIADOS A: ' . $padre->nombre);
                    // ----------------------------------------

                    foreach ($padre->subproductos as $hijo) {
                         // Casting a objeto
                        $hijo = (object)$hijo;

                         // Verificar salto de página antes de dibujar el hijo
                        if ($this->GetY() > $this->GetPageHeight() - 20) {
                            $this->AddPage();
                            $this->imprimirCabeceraTabla();
                        }
                        
                        // Dibujar hijo
                        $this->dibujarFilaProducto($hijo, true, true);

                        // Acumular
                        $grandTotalStock += $hijo->stock;
                        $grandTotalCredito += ($hijo->stock * $hijo->precio_credito);
                        $grandTotalContado += ($hijo->stock * $hijo->precio_contado);
                        
                        $totalsByProduct[$key]['stock'] += $hijo->stock;
                        $totalsByProduct[$key]['credito'] += ($hijo->stock * $hijo->precio_credito);
                        $totalsByProduct[$key]['contado'] += ($hijo->stock * $hijo->precio_contado);

                        // Datos técnicos del subproducto
                        $this->mostrarDatosTecnicos($hijo);
                    }
                    
                    // Se agrega un salto de línea después de los subproductos
                    $this->Ln(1); 
                }
                $this->Ln(4);
            }
            $this->Ln(8);
        }

        // --- 3. Sección de Totales Finales ---
        $this->seccionTitulo('3. Resumen General del Inventario');
        $this->Ln(3);

        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(255, 240, 200); // Fondo Amarillo Suave

        $wTotal = 70;
        $wMonto = 60;
        
        $x = $this->GetX();

        // Columna 1 (Stock)
        $this->Cell($wTotal, 8, utf8_decode('STOCK TOTAL (Litros/Kg):'), 1, 0, 'L', true);
        $this->SetFont('Arial', '', 11);
        $this->Cell($wMonto, 8, number_format($grandTotalStock, 2) . ' L/Kg', 1, 0, 'R');
        $this->SetX($x + $wTotal + $wMonto + 10); // Salto a la segunda columna

        // Columna 2 - Valor Crédito
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($wTotal, 8, utf8_decode('VALOR CRÉDITO TOTAL (Bs):'), 1, 0, 'L', true);
        $this->SetFont('Arial', '', 11);
        $this->Cell($wMonto, 8, 'Bs ' . number_format($grandTotalCredito, 2), 1, 1, 'R');

        // Fila 2 (solo Valor Contado)
        $this->SetX($x);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($wTotal, 8, utf8_decode('VALOR CONTADO TOTAL (Bs):'), 1, 0, 'L', true);
        $this->SetFont('Arial', '', 11);
        $this->Cell($wMonto, 8, 'Bs ' . number_format($grandTotalContado, 2), 1, 1, 'R');
        
        $this->Ln(10);

        // --- 4. Sección de Totales por Producto ---
        if (!empty($totalsByProduct)) {
            $this->seccionTitulo('4. Resumen Agrupado por Tipo de Producto');
            $this->Ln(2);

            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(220, 230, 245); // Fondo azul suave para cabeceras de tabla
            
            $wProd = 90;
            $wLitros = 30;
            $wCredito = 50;
            $wContado = 50;

            $this->Cell($wProd, 7, utf8_decode('Producto'), 1, 0, 'C', true);
            $this->Cell($wLitros, 7, 'Stock (L/Kg)', 1, 0, 'C', true);
            $this->Cell($wCredito, 7, utf8_decode('Valor Crédito (Bs)'), 1, 0, 'C', true);
            $this->Cell($wContado, 7, utf8_decode('Valor Contado (Bs)'), 1, 1, 'C', true);

            $this->SetFont('Arial', '', 9);
            $fill = false;
            foreach ($totalsByProduct as $nombre => $tot) {
                // Alternar colores
                $fill = !$fill;
                $this->SetFillColor($fill ? 245 : 255, 245, 255);
                
                $this->Cell($wProd, 6, $nombre, 1, 0, 'L', $fill);
                $this->Cell($wLitros, 6, number_format($tot['stock'], 2), 1, 0, 'R', $fill);
                $this->Cell($wCredito, 6, number_format($tot['credito'], 2), 1, 0, 'R', $fill);
                $this->Cell($wContado, 6, number_format($tot['contado'], 2), 1, 1, 'R', $fill);
            }
        }

        $this->Output('D', 'reporte_inventarios_' . date('Ymd') . '.pdf');
    }
}