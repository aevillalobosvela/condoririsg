# Condoriri SG — Guía de Reportes PDF

## Librería base
Se usa FPDF (en `app/ThirdParty/fpdf/`) con una clase base propia:
```
app/Libraries/CierreVentaBasePdf.php   ← clase base abstracta
app/Libraries/CierreVentaPdf.php       ← reporte ventas lácteos
app/Libraries/CierreVentaContadoPdf.php
app/Libraries/CierreVentaAdminPdf.php  ← reporte admin multi-sucursal
app/Libraries/CierreVentaInve.php      ← reporte inventario/planta
app/Libraries/Agro/CierreVentaAgroPdf.php ← reporte ventas agro
app/Libraries/FacturaPdf.php           ← facturas/recibos
app/Libraries/MYPDF.php                ← clase FPDF extendida base
```

## Cómo crear un nuevo reporte PDF

### 1. Crear la clase en `app/Libraries/`
```php
<?php
namespace App\Libraries;

use App\Libraries\CierreVentaBasePdf;

class MiNuevoReportePdf extends CierreVentaBasePdf
{
    public function generarReporte(array $data, array $filters): void
    {
        $this->setReporteTitle('MI REPORTE');
        $this->AddPage('L'); // 'L' landscape, 'P' portrait

        // Usar métodos heredados:
        $this->seccionTitulo('Sección 1');
        $this->tablaProductos($data['productos']);
        $this->tablaResumenFinanciero($data['resumen'], $data['total'], 'contado');
        $this->mostrarVentas($data['ventas'], false);
    }
}
```

### 2. Métodos disponibles en `CierreVentaBasePdf`
| Método | Descripción |
|---|---|
| `setReporteTitle(string $title)` | Título que aparece en el header |
| `Header()` | Auto-llamado por FPDF, muestra logo + título |
| `Footer()` | Auto-llamado, muestra número de página |
| `seccionTitulo(string $texto)` | Banda de título de sección |
| `tablaProductos(array $ventasPorProducto)` | Tabla matricial productos/columnas |
| `tablaResumenFinanciero(array $resumen, float $total, string $tipo)` | Resumen financiero |
| `tablaAcumuladoClientes(array $acumuladoClientes)` | Tabla acumulado por cliente |
| `mostrarVentas(array $ventas, bool $esCredito)` | Lista de ventas detallada |
| `numeroALiteral(float $monto)` | Convierte número a texto (ej: "CIEN 00/100") |

### 3. Instanciar en el controller
```php
use App\Libraries\MiNuevoReportePdf;

public function exportarPdf(): void
{
    $data = $this->miModel->getDailySalesReportData($fechaInicio, $fechaFin);
    
    $pdf = new MiNuevoReportePdf();
    $pdf->generarReporte($data, [
        'fecha_inicio' => $fechaInicio,
        'fecha_hasta'  => $fechaFin,
    ]);

    // Output directo al navegador
    $pdf->Output('I', 'reporte.pdf'); // 'I'=inline, 'D'=download
    exit;
}
```

### 4. Registrar la ruta
```php
// En Routes.php, dentro del grupo correspondiente:
$routes->get('exportarPdf', 'dominio\MiController::exportarPdf',
    ['filter' => 'role:admin,contabilidad']);
```

## Patrón de datos para `tablaProductos`
La tabla matricial espera ventas agrupadas por producto como columnas:
```php
// Estructura esperada: array de ventas con producto_nombre y cantidad
$ventasPorProducto = [
    'Producto A' => ['total_cantidad' => 10, 'total_monto' => 150.00],
    'Producto B' => ['total_cantidad' => 5,  'total_monto' => 75.00],
];
```

## Ejemplo real: CierreVentaAgroPdf
```php
// app/Libraries/Agro/CierreVentaAgroPdf.php
class CierreVentaAgroPdf extends CierreVentaBasePdf
{
    public function generarReporteVentas(array $reportData, array $filters): void
    {
        // Título específico del módulo
        $this->setReporteTitle('CONDORIRI AGROPECUARIO');
        // ... lógica de generación
    }
}
```

## Reportes Excel
Los reportes Excel usan servicios dedicados en `app/Services/`:
```
app/Services/Agro/ExcelVentasAgroService.php
```
Patrón: el controller delega a `ExcelVentasAgroService::generar($data, $filters)`.
