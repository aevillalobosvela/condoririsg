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
        $fecha_fin    = $this->request->getGet('fecha_fin')    ?? date('Y-m-d');
        $tipo         = $this->request->getGet('tipo')         ?? 'general';

        $service = new \App\Services\Shared\ExcelVentasMatrizService();
        $service->exportar($fecha_inicio, $fecha_fin, $tipo, \App\Services\Shared\ExcelVentasMatrizService::TIENDA_LACTEOS);
    }
}
