<?php

namespace App\Controllers\reportes;

use App\Controllers\BaseController;
use App\Services\Shared\ResumenGlobalService;
use App\Libraries\ResumenGlobalPdf;

class ResumenGlobalController extends BaseController
{
    /**
     * GET resumen-global/exportar?mes=YYYY-MM
     *
     * Recibe el mes seleccionado en el modal (formato YYYY-MM),
     * calcula el primer y último día del mes y delega al servicio.
     */
    public function exportar(): void
    {
        $mes = $this->request->getGet('mes');

        // Validar formato YYYY-MM
        if (!$mes || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
            // Fallback: mes actual
            $mes = date('Y-m');
        }

        $fecha_inicio = $mes . '-01';
        $fecha_fin    = date('Y-m-t', strtotime($fecha_inicio)); // último día del mes

        $service = new ResumenGlobalService();
        $service->exportar($fecha_inicio, $fecha_fin);
    }

    /**
     * GET resumen-global/exportarPdf?mes=YYYY-MM
     */
    public function exportarPdf(): void
    {
        $mes = $this->request->getGet('mes');
        if (!$mes || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }
        $fecha_inicio = $mes . '-01';
        $fecha_fin    = date('Y-m-t', strtotime($fecha_inicio));

        $pdf = new ResumenGlobalPdf();
        $pdf->generar($fecha_inicio, $fecha_fin);
    }
}
