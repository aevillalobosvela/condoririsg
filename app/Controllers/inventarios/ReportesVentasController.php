<?php

namespace App\Controllers\Inventarios;

use App\Controllers\BaseController;
use App\Libraries\CierreVentaInve;
use App\Libraries\CierreVentaInve1;
use App\Models\Venta\VentaModel;

class ReportesVentasController extends BaseController
{
    protected $ventaModel;

    public function __construct()
    {
        $this->ventaModel = new VentaModel();
    }

    public function exportarPdfVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio');
        $fecha_fin    = $this->request->getGet('fecha_fin');
        $tipo         = $this->request->getGet('tipo');

        $hoy = date('Y-m-d');
        $fecha_inicio = $fecha_inicio ?: $hoy;
        $fecha_fin    = $fecha_fin ?: $hoy;

        if (!in_array($tipo, ['contado', 'credito', 'general', 'deposito_contado'])) {
            $tipo = 'general';
        }

        $tipoModelo = ($tipo === 'deposito_contado') ? 'contado' : $tipo;
        $reportData = $this->ventaModel->getDailySalesReportDataInve($fecha_inicio, $fecha_fin, $tipoModelo);

        if ($tipo === 'deposito_contado') {
            $pdfGenerator = new CierreVentaInve1();
        } else {
            $pdfGenerator = new CierreVentaInve();
        }

        $pdfGenerator->generarReporteVentas($reportData, [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin'    => $fecha_fin,
            'tipo'         => $tipo,
        ]);
    }

    public function exportarExcelVentas()
    {
        $fecha_inicio = $this->request->getGet('fecha_inicio') ?? date('Y-m-d');
        $fecha_fin = $this->request->getGet('fecha_fin') ?? date('Y-m-d');
        $tipo = $this->request->getGet('tipo') ?? 'general';

        if (!in_array($tipo, ['contado', 'credito', 'general'])) {
            $tipo = 'general';
        }

        $reportData = $this->ventaModel->getDailySalesReportDataInve($fecha_inicio, $fecha_fin, $tipo);

        $ventasPorProducto = [];
        $resumenPagos = ['Contado' => 0, 'Credito' => 0];
        $totalVentasFinalizadas = 0.00;
        $ventasContado = [];
        $ventasCredito = [];
        $processedVentas = [];

        foreach ($reportData as $item) {
            if ($item->estado_venta != 1) continue;
            $tipoActual = strtolower($item->tipo_pago);

            $esValida = match($tipo) {
                'contado' => $tipoActual === 'contado',
                'credito' => $tipoActual === 'credito',
                default => true,
            };

            if (!$esValida) continue;

            $producto = $item->producto_nombre;
            if (!isset($ventasPorProducto[$producto])) {
                $ventasPorProducto[$producto] = ['cantidad_total' => 0, 'monto_total' => 0.00];
            }
            $ventasPorProducto[$producto]['cantidad_total'] += $item->cantidad;
            $ventasPorProducto[$producto]['monto_total'] += $item->subtotal_item;

            if (!isset($processedVentas[$item->venta_id])) {
                $monto = $item->monto_total_venta;
                $totalVentasFinalizadas += $monto;
                if ($tipoActual === 'contado') {
                    $resumenPagos['Contado'] += $monto;
                } elseif ($tipoActual === 'credito') {
                    $resumenPagos['Credito'] += $monto;
                }
                $processedVentas[$item->venta_id] = true;
            }

            $clienteNombre = $item->cliente_nombre ?? 'Consumidor Final';
            $esCredito = ($tipoActual === 'credito');
            $ventaData = [
                'Nro' => $item->venta_id,
                'cliente' => $clienteNombre,
                'tipo_pago' => ucfirst($item->tipo_pago),
                'monto_total' => $item->monto_total_venta,
                'fecha' => $item->fecha_venta,
                'dip' => $item->personal_dip ?? '',
                'nombre_personal' => $item->personal_nombre ?? '',
                'items' => []
            ];

            if (!empty($item->producto_nombre)) {
                $ventaData['items'][] = [
                    'producto' => $item->producto_nombre,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio_unitario,
                    'subtotal' => $item->subtotal_item
                ];
            }

            $nuevoItemAgregado = end($ventaData['items']);

            if ($esCredito) {
                if (!isset($ventasCredito[$item->venta_id])) {
                    $ventasCredito[$item->venta_id] = $ventaData;
                } else {
                    $ventasCredito[$item->venta_id]['items'][] = $nuevoItemAgregado;
                }
            } else {
                if (!isset($ventasContado[$item->venta_id])) {
                    $ventasContado[$item->venta_id] = $ventaData;
                } else {
                    $ventasContado[$item->venta_id]['items'][] = $nuevoItemAgregado;
                }
            }
        }

        $tituloTipo = match($tipo) {
            'contado' => ' (VENTAS AL CONTADO)',
            'credito' => ' (VENTAS A CREDITO)',
            default => ' (VENTAS GENERALES)',
        };

        $filename = 'ventas_' . $tipo . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        
        echo '<Styles>';
        echo '<Style ss:ID="titulo"><Font ss:Bold="1" ss:Size="14" ss:Color="#1F4E78"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="subtitulo"><Font ss:Bold="1" ss:Size="11" ss:Color="#1F4E78"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="info"><Font ss:Size="9" ss:Color="#404040"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="header_reporte"><Font ss:Bold="1" ss:Size="14"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="periodo"><Font ss:Bold="1" ss:Size="11"/></Style>';
        echo '<Style ss:ID="seccion"><Font ss:Bold="1" ss:Size="12"/><Interior ss:Color="#F0F5FF" ss:Pattern="Solid"/></Style>';
        echo '<Style ss:ID="tabla_header"><Font ss:Bold="1" ss:Size="10"/><Interior ss:Color="#DCE6F5" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="celda"><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="celda_num"><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="celda_int"><NumberFormat ss:Format="#,##0"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="total"><Font ss:Bold="1" ss:Size="11"/><Interior ss:Color="#FFF2CC" ss:Pattern="Solid"/><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="venta_cab"><Font ss:Bold="1" ss:Size="10"/><Interior ss:Color="#E6F2FF" ss:Pattern="Solid"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="venta_fecha"><Font ss:Size="9"/><Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="personal"><Font ss:Size="9" ss:Italic="1"/><Interior ss:Color="#FAFCF0" ss:Pattern="Solid"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="item_header"><Font ss:Bold="1" ss:Size="9"/><Interior ss:Color="#DCE6F5" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="item"><Font ss:Size="9"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="item_num"><Font ss:Size="9"/><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Borders><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '</Styles>';

        echo '<Worksheet ss:Name="Reporte Ventas">';
        echo '<Table>';
        echo '<Column ss:Width="180"/><Column ss:Width="120"/><Column ss:Width="120"/><Column ss:Width="120"/>';

        echo '<Row ss:Height="20"><Cell ss:MergeAcross="3" ss:StyleID="titulo"><Data ss:Type="String">UNIVERSIDAD TÉCNICA DE ORURO</Data></Cell></Row>';
        echo '<Row ss:Height="18"><Cell ss:MergeAcross="3" ss:StyleID="subtitulo"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRARIAS Y NATURALES</Data></Cell></Row>';
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="3" ss:StyleID="info"><Data ss:Type="String">CONDORIRI - LABORATORIO DE INNOVACIÓN</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="3" ss:StyleID="info"><Data ss:Type="String">Telf.: 5281745 – Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="3" ss:StyleID="info"><Data ss:Type="String">Email: dpdi@uto.edu.bo | www.uto.edu.bo</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="3" ss:StyleID="header_reporte"><Data ss:Type="String">REPORTE DE CIERRE DE VENTAS Y ARQUEO LACTEOS' . $tituloTipo . '</Data></Cell></Row>';
        echo '<Row></Row>';
        
        $periodo_texto = ($fecha_inicio === $fecha_fin)
            ? "Día: " . date('d/m/Y', strtotime($fecha_inicio))
            : "Desde: " . date('d/m/Y', strtotime($fecha_inicio)) . " hasta: " . date('d/m/Y', strtotime($fecha_fin));
        echo '<Row><Cell ss:StyleID="periodo"><Data ss:Type="String">Período del Reporte</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String">' . $periodo_texto . '</Data></Cell></Row>';
        echo '<Row></Row>';

        echo '<Row><Cell ss:MergeAcross="3" ss:StyleID="seccion"><Data ss:Type="String">1. Resumen de Productos Vendidos</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="tabla_header"><Data ss:Type="String">Producto</Data></Cell><Cell ss:StyleID="tabla_header"><Data ss:Type="String">Cant.</Data></Cell><Cell ss:StyleID="tabla_header"><Data ss:Type="String">Monto (Bs.)</Data></Cell></Row>';
        
        foreach ($ventasPorProducto as $producto => $data) {
            echo '<Row><Cell ss:StyleID="celda"><Data ss:Type="String">' . htmlspecialchars($producto, ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="celda_int"><Data ss:Type="Number">' . $data['cantidad_total'] . '</Data></Cell>';
            echo '<Cell ss:StyleID="celda_num"><Data ss:Type="Number">' . number_format($data['monto_total'], 2, '.', '') . '</Data></Cell></Row>';
        }
        
        echo '<Row></Row>';
        echo '<Row><Cell ss:MergeAcross="3" ss:StyleID="seccion"><Data ss:Type="String">2. Resumen Financiero (Ventas Finalizadas)</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row><Cell ss:StyleID="tabla_header"><Data ss:Type="String">Tipo de Pago</Data></Cell><Cell ss:StyleID="tabla_header"><Data ss:Type="String">Monto (Bs.)</Data></Cell></Row>';
        
        $tipos = match($tipo) {
            'contado' => ['Contado'],
            'credito' => ['Crédito'],
            default => ['Contado', 'Crédito'],
        };

        foreach ($tipos as $t) {
            if (isset($resumenPagos[$t]) && $resumenPagos[$t] > 0) {
                echo '<Row><Cell ss:StyleID="celda"><Data ss:Type="String">' . $t . '</Data></Cell>';
                echo '<Cell ss:StyleID="celda_num"><Data ss:Type="Number">' . number_format($resumenPagos[$t], 2, '.', '') . '</Data></Cell></Row>';
            }
        }
        
        echo '<Row><Cell ss:StyleID="total"><Data ss:Type="String">TOTAL GENERAL</Data></Cell>';
        echo '<Cell ss:StyleID="total"><Data ss:Type="Number">' . number_format($totalVentasFinalizadas, 2, '.', '') . '</Data></Cell></Row>';
        echo '<Row></Row>';

        if ($tipo !== 'credito' && !empty($ventasContado)) {
            echo '<Row><Cell ss:MergeAcross="3" ss:StyleID="seccion"><Data ss:Type="String">3. Ventas al Contado</Data></Cell></Row>';
            echo '<Row></Row>';
            $this->generarVentasExcel($ventasContado, false);
            echo '<Row></Row>';
        }

        if ($tipo !== 'contado' && !empty($ventasCredito)) {
            $secNum = ($tipo === 'credito') ? '3' : '4';
            echo '<Row><Cell ss:MergeAcross="3" ss:StyleID="seccion"><Data ss:Type="String">' . $secNum . '. Ventas a Crédito</Data></Cell></Row>';
            echo '<Row></Row>';
            $this->generarVentasExcel($ventasCredito, true);
        }

        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
    }

    private function generarVentasExcel($ventas, $esCredito)
    {
        foreach ($ventas as $venta) {
            echo '<Row><Cell ss:StyleID="venta_cab"><Data ss:Type="String">Nro: ' . $venta['Nro'] . '</Data></Cell>';
            echo '<Cell ss:MergeAcross="1" ss:StyleID="venta_cab"><Data ss:Type="String">Cliente: ' . htmlspecialchars($venta['cliente'], ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="venta_cab"><Data ss:Type="String">Bs. ' . number_format($venta['monto_total'], 2, ',', '.') . '</Data></Cell></Row>';
            
            echo '<Row><Cell ss:MergeAcross="3" ss:StyleID="venta_fecha"><Data ss:Type="String">Fecha: ' . date('d/m/Y H:i', strtotime($venta['fecha'])) . '</Data></Cell></Row>';
            
            if ($esCredito && !empty($venta['dip'])) {
                echo '<Row><Cell ss:StyleID="personal"><Data ss:Type="String">DIP:</Data></Cell>';
                echo '<Cell ss:StyleID="personal"><Data ss:Type="String">' . $venta['dip'] . '</Data></Cell>';
                echo '<Cell ss:StyleID="personal"><Data ss:Type="String">Nombre:</Data></Cell>';
                echo '<Cell ss:StyleID="personal"><Data ss:Type="String">' . htmlspecialchars($venta['nombre_personal'], ENT_XML1) . '</Data></Cell></Row>';
            }
            
            echo '<Row><Cell ss:StyleID="item_header"><Data ss:Type="String">Cant.</Data></Cell>';
            echo '<Cell ss:StyleID="item_header"><Data ss:Type="String">Producto</Data></Cell>';
            echo '<Cell ss:StyleID="item_header"><Data ss:Type="String">Precio</Data></Cell>';
            echo '<Cell ss:StyleID="item_header"><Data ss:Type="String">Subtotal</Data></Cell></Row>';
            
            if (!empty($venta['items'])) {
                foreach ($venta['items'] as $item) {
                    echo '<Row><Cell ss:StyleID="celda_int"><Data ss:Type="Number">' . $item['cantidad'] . '</Data></Cell>';
                    echo '<Cell ss:StyleID="item"><Data ss:Type="String">' . htmlspecialchars($item['producto'], ENT_XML1) . '</Data></Cell>';
                    echo '<Cell ss:StyleID="item_num"><Data ss:Type="Number">' . number_format($item['precio'], 2, '.', '') . '</Data></Cell>';
                    echo '<Cell ss:StyleID="item_num"><Data ss:Type="Number">' . number_format($item['subtotal'], 2, '.', '') . '</Data></Cell></Row>';
                }
            } else {
                echo '<Row><Cell ss:MergeAcross="3" ss:StyleID="item"><Data ss:Type="String">Sin ítems registrados.</Data></Cell></Row>';
            }
            
            echo '<Row></Row>';
        }
    }
}
