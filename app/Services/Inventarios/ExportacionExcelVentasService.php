<?php

namespace App\Services\Inventarios;

use App\Services\Shared\ExcelVentasMatrizService;

/**
 * Servicio de exportación Excel de ventas para el módulo Inventarios (Planta).
 * Delega al servicio compartido ExcelVentasMatrizService con tienda LACTEOS.
 */
class ExportacionExcelVentasService
{
    public function exportar(string $fecha_inicio, string $fecha_fin, string $tipo): void
    {
        $service = new ExcelVentasMatrizService();
        $service->exportar($fecha_inicio, $fecha_fin, $tipo, ExcelVentasMatrizService::TIENDA_LACTEOS);
    }
}
