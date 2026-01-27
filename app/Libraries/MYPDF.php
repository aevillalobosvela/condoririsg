<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

use App\Models\Producto\ProductoModel;

class MYPDF extends \FPDF
{
    protected $pag_ini;
    protected $pag_total;

    public function __construct($pag_ini = 0, $pag_total = 0)
    {
        parent::__construct('L', 'mm', 'LEGAL');
        $this->pag_ini = $pag_ini;
        $this->pag_total = $pag_total;

        $this->SetAuthor('DTIC');
        $this->SetTitle('Reporte de Inventarios');
        $this->SetSubject('Control de Inventarios');
        $this->SetMargins(15, 44, 15);
        $this->SetAutoPageBreak(true, 15);
        $this->SetFont('Arial', '', 10);
    }

    public function Header()
    {
        $logo_left  = FCPATH . 'assets/img/condoriri.jpeg';
        $logo_right = FCPATH . 'assets/img/uto.jpeg';

        if (file_exists($logo_left)) {
            $this->Image($logo_left, 15, 10, 25);
        }
        if (file_exists($logo_right)) {
            $this->Image($logo_right, $this->GetPageWidth() - 40, 10, 25);
        }

        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 7, "UNIVERSIDAD TECNICA DE ORURO", 0, 1, 'C');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, "CONDORIRI - AGRONOMIA", 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, "Telf.: 5281745 – Interno: 120;  FAX  5242215;  Casilla 49", 0, 1, 'C');
        $this->Cell(0, 5, "Email: dpdi@uto.edu.bo; Internet: www.uto.edu.bo", 0, 1, 'C');
        $this->Ln(10);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/' . $this->pag_total, 0, 0, 'C');
    }

    /**
     * Organiza productos en estructura jerárquica: padres → hijos
     */
    private function organizarProductosJerarquicos(array $productos): array
    {
        $padres = [];
        $hijos  = [];

        foreach ($productos as $p) {
            if (empty($p->parent_id) || $p->parent_id == 0) {
                $padres[$p->id] = $p;
                $padres[$p->id]->subproductos = [];
            } else {
                $hijos[] = $p;
            }
        }

        foreach ($hijos as $hijo) {
            $pid = $hijo->parent_id;
            if (isset($padres[$pid])) {
                $padres[$pid]->subproductos[] = $hijo;
            } else {
                // Si el padre no existe, lo tratamos como raíz
                $padres[$hijo['id']] = $hijo;
                $padres[$hijo['id']]['subproductos'] = [];
            }
        }

        return array_values($padres);
    }

    /**
     * Muestra los datos técnicos solo si están presentes
     */
 

    public function generarReporte(array $inventarios, array $filtros)
    {
        $this->AddPage();
        if ($this->pag_total === 0) {
            $this->pag_total = $this->PageNo();
        }

        // --- Filtros ---
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Filtros aplicados:', 0, 1);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Nombre/Codigo: ' . ($filtros['nombre'] ?? 'Todos'), 0, 1);
        $this->Cell(0, 6, 'Fecha Inicio: ' . ($filtros['fecha_inicio'] ?? 'N/A'), 0, 1);
        $this->Cell(0, 6, 'Fecha Fin: ' . ($filtros['fecha_fin'] ?? 'N/A'), 0, 1);
        $this->Ln(8);

        $productoModel = new ProductoModel();

        $grandTotalStock = 0;
        $grandTotalCredito = 0;
        $grandTotalContado = 0;
        $totalsByProduct = [];

        foreach ($inventarios as $inventario) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetFillColor(220, 220, 220);
            $this->Cell(0, 10, "Inventario: {$inventario->nombre} (Cód: {$inventario->code})", 0, 1, 'L', true);
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, 'Descripcion: ' . ($inventario->descripcion ?? ''), 0, 1);
            $this->Cell(0, 6, 'Stock General: ' . ($inventario->stock ?? 0), 0, 1);
            $this->Cell(0, 6, 'Turno: ' . ($inventario->turno ?? ''), 0, 1);
            $this->Cell(0, 6, 'Estado: ' . ($inventario->estado ? 'Activo' : 'Inactivo'), 0, 1);
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
                $this->Cell(0, 10, '⚠️ No hay productos asociados.', 0, 1);
                $this->Ln(5);
                continue;
            }

            // Organizar en jerarquía
            $productosJerarquicos = $this->organizarProductosJerarquicos($productos);

            // --- Tabla principal ---
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 7, 'Productos y Subproductos:', 0, 1);
            $this->Ln(2);

            $pageWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
            $ancho = [
                $pageWidth * 0.20, // Nombre
                $pageWidth * 0.15, // Stock
                $pageWidth * 0.12, // P. Crédito
                $pageWidth * 0.12, // P. Contado
                $pageWidth * 0.10, // Cant. Prod.
                $pageWidth * 0.15, // Vencimiento
                $pageWidth * 0.16, // Observaciones
            ];

            $this->SetFillColor(200, 220, 255);
            $headers = ['Producto', 'Stock', 'Crédito', 'Contado', 'Prod.', 'Vencimiento', 'Obs.'];
            foreach ($headers as $i => $h) {
                $this->Cell($ancho[$i], 7, $h, 1, 0, 'C', true);
            }
            $this->Ln();

            $this->SetFont('Arial', '', 8);

            foreach ($productosJerarquicos as $padre) {
                // ▼ Producto principal (nivel 1)
                $this->SetFillColor(240, 247, 255);
                $this->Cell($ancho[0], 7, "■ " . $padre->nombre, 1, 0, 'L', true);
                $this->Cell($ancho[1], 7, $padre->stock, 1, 0, 'C');
                $this->Cell($ancho[2], 7, 'Bs' . number_format($padre->precio_credito, 2), 1, 0, 'R');
                $this->Cell($ancho[3], 7, 'Bs' . number_format($padre->precio_contado, 2), 1, 0, 'R');
                $this->Cell($ancho[4], 7, $padre->cantidad_produccion, 1, 0, 'C');
                $this->Cell($ancho[5], 7, $padre->fecha_vencimiento ? date('d/m/Y', strtotime($padre->fecha_vencimiento)) : '—', 1, 0, 'C');
                $this->Cell($ancho[6], 7, substr($padre->descripcion ?? '', 0, 30) . (strlen($padre->descripcion ?? '') > 30 ? '...' : ''), 1, 1, 'L');

                // Acumular totales
                $grandTotalStock += $padre->stock;
                $grandTotalCredito += ($padre->stock * $padre->precio_credito);
                $grandTotalContado += ($padre->stock * $padre->precio_contado);
                $key = $padre->nombre;
                if (!isset($totalsByProduct[$key])) {
                    $totalsByProduct[$key] = ['stock' => 0, 'credito' => 0, 'contado' => 0];
                }
                $totalsByProduct[$key]['stock'] += $padre->stock;
                $totalsByProduct[$key]['credito'] += ($padre->stock * $padre->precio_credito);
                $totalsByProduct[$key]['contado'] += ($padre->stock * $padre->precio_contado);

                // ▸ Mostrar datos técnicos si existen
               

                // Subproductos (nivel 2)
                if (!empty($padre->subproductos)) {
                    foreach ($padre->subproductos as $hijo) {
                        $this->SetFillColor(250, 253, 255);
                        $this->Cell($ancho[0], 6, "  ▸ " . $hijo->nombre, 1, 0, 'L', true);
                        $this->Cell($ancho[1], 6, $hijo->stock, 1, 0, 'C');
                        $this->Cell($ancho[2], 6, 'Bs' . number_format($hijo->precio_credito, 2), 1, 0, 'R');
                        $this->Cell($ancho[3], 6, 'Bs' . number_format($hijo->precio_contado, 2), 1, 0, 'R');
                        $this->Cell($ancho[4], 6, $hijo->cantidad_produccion, 1, 0, 'C');
                        $this->Cell($ancho[5], 6, $hijo->fecha_vencimiento ? date('d/m/Y', strtotime($hijo->fecha_vencimiento)) : '—', 1, 0, 'C');
                        $this->Cell($ancho[6], 6, substr($hijo->descripcion ?? '', 0, 30) . (strlen($hijo->descripcion ?? '') > 30 ? '...' : ''), 1, 1, 'L');

                        // Acumular
                        $grandTotalStock += $hijo->stock;
                        $grandTotalCredito += ($hijo->stock * $hijo->precio_credito);
                        $grandTotalContado += ($hijo->stock * $hijo->precio_contado);
                        if (!isset($totalsByProduct[$key])) {
                            $totalsByProduct[$key] = ['stock' => 0, 'credito' => 0, 'contado' => 0];
                        }
                        $totalsByProduct[$key]['stock'] += $hijo->stock;
                        $totalsByProduct[$key]['credito'] += ($hijo->stock * $hijo->precio_credito);
                        $totalsByProduct[$key]['contado'] += ($hijo->stock * $hijo->precio_contado);

                        // Datos técnicos del subproducto
                     
                    }
                }
                $this->Ln(2);
            }
            $this->Ln(8);
        }

        // --- TOTALES FINALES ---
        $this->SetFont('Arial', 'B', 14);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(0, 10, "TOTAL GENERAL", 1, 1, 'C', true);
        $this->Ln(3);

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(70, 8, 'Litros Totales:', 1, 0, 'L', false);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 8, number_format($grandTotalStock, 2) . ' L', 1, 1);

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(70, 8, 'Valor Crédito Total:', 1, 0, 'L', false);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 8, 'Bs ' . number_format($grandTotalCredito, 2), 1, 1);

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(70, 8, 'Valor Contado Total:', 1, 0, 'L', false);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 8, 'Bs ' . number_format($grandTotalContado, 2), 1, 1);
        $this->Ln(10);

        // --- TOTALES POR PRODUCTO ---
        if (!empty($totalsByProduct)) {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, 'Resumen por Tipo de Producto', 0, 1, 'C');
            $this->Ln(2);

            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(200, 220, 255);
            $this->Cell(80, 7, 'Producto', 1, 0, 'C', true);
            $this->Cell(30, 7, 'Litros', 1, 0, 'C', true);
            $this->Cell(50, 7, 'Crédito (Bs)', 1, 0, 'C', true);
            $this->Cell(50, 7, 'Contado (Bs)', 1, 1, 'C', true);

            $this->SetFont('Arial', '', 9);
            foreach ($totalsByProduct as $nombre => $tot) {
                $this->Cell(80, 6, $nombre, 1);
                $this->Cell(30, 6, number_format($tot['stock'], 2), 1, 0, 'R');
                $this->Cell(50, 6, number_format($tot['credito'], 2), 1, 0, 'R');
                $this->Cell(50, 6, number_format($tot['contado'], 2), 1, 1, 'R');
            }
        }

        $this->Output('D', 'reporte_inventarios_' . date('Ymd') . '.pdf');
    }
}