# Condoriri SG — Módulo Ventas Agropecuarias

## Archivos principales
```
app/Controllers/productosAgro/ventasAgroController.php   ← controller ventas
app/Controllers/productosAgro/productosAgroController.php ← controller productos
app/Models/Venta/VentaModel.php                          ← generarCodigoAgroVenta()
app/Libraries/Agro/CierreVentaAgroPdf.php                ← reporte PDF
app/Services/Agro/ExcelVentasAgroService.php             ← reporte Excel
app/Views/productosAgro/                                 ← vistas
```

## Tabla de base de datos
- Productos: `condoriri.productos_agro`
- Ventas: `condoriri.ventas` (misma tabla que lácteos, diferenciado por sucursal_id)
- Detalle: `condoriri.detalle_venta` — campo `producto_agro_id` (vs `stock_id` en lácteos)

## Códigos de venta Agro
Formato: `AG-{SUCURSAL}-{TIPO}-{000001}`

| sucursal_id | Prefijo | tipo_pago | Prefijo | Ejemplo |
|---|---|---|---|---|
| 2 | SC | contado | CO | `AG-SC-CO-000001` |
| 2 | SC | credito | CR | `AG-SC-CR-000001` |
| 4 | PP | contado | CO | `AG-PP-CO-000001` |
| 4 | PP | credito | CR | `AG-PP-CR-000001` |

Método en `VentaModel`:
```php
$codigo = $this->ventaModel->generarCodigoAgroVenta($sucursal_id, $tipo_pago);
// Lanza InvalidArgumentException si sucursal_id no es 2 ni 4
```

Secuencias PostgreSQL (schema `condoriri`):
- `seq_agro_venta_sc_co`
- `seq_agro_venta_sc_cr`
- `seq_agro_venta_pp_co`
- `seq_agro_venta_pp_cr`

Ver `CODIGOS_VENTA.md` para scripts SQL completos.

## Rutas del módulo (`/productosagro`)
```
GET  /productosagro/              → index (lista productos)
GET  /productosagro/create        → formulario nuevo producto
POST /productosagro/store         → guardar producto
GET  /productosagro/edit/:id      → formulario editar
POST /productosagro/update/:id    → actualizar
GET  /productosagro/delete/:id    → eliminar

GET  /productosagro/ventas        → lista ventas agro
GET  /productosagro/registerVentas → POS contado
GET  /productosagro/credito       → POS crédito
POST /productosagro/guardarVenta  → guardar venta contado
POST /productosagro/guardarCreditoVenta → guardar venta crédito
GET  /productosagro/recibo/:id    → recibo de venta
GET  /productosagro/exportarExcelVentas → reporte Excel
GET  /productosagro/exportarPdfVentas   → reporte PDF
GET  /productosagro/buscarPersonalUto   → búsqueda personal UTO (AJAX)
```

Roles con acceso: `admin, agropecuario, ganaderia, vendedor, almacen`
Contabilidad solo tiene acceso a reportes.

## Búsqueda de personal UTO (créditos)
Endpoint AJAX: `GET /productosagro/buscarPersonalUto?dip=TERMINO`

Busca por CI (`p.dip`) O nombre (`p.nombre`) con mínimo 3 caracteres.
Retorna JSON con: `id_persona, nombre, dip, telefono, cargo, seccion`.

## Filtro de stock en vistas POS
Los métodos `register()` y `credito()` filtran `cantidad_inve > 0` para ocultar
productos sin stock del grid de venta.

## Diferencias clave vs módulo Lácteos
| Aspecto | Lácteos | Agro |
|---|---|---|
| Tabla productos | `condoriri.stock_sucursales` | `condoriri.productos_agro` |
| Campo detalle | `stock_id` | `producto_agro_id` |
| Código venta | `SC-CO-000001` | `AG-SC-CO-000001` |
| Sucursales | 2 (fijo en index) | 2 y 4 |
| Unidad default | variable | `UND` |
| Controller | `ventas\ventasController` | `productosAgro\ventasAgroController` |

## Vistas destacadas
- `productosAgro/ventasIndex.php` — POS contado con cards JS, paleta de 7 colores por producto
- `productosAgro/ventasCredito.php` — POS crédito con búsqueda personal UTO por nombre o CI
- `productosAgro/productosAgroIndex.php` — lista con 4 stat cards, filtros JS, badges de stock
- `productosAgro/productosAgroFrom.php` — formulario 2 columnas, navegación con Enter/tabindex
- `productosAgro/recibo_print.php` — recibo unificado con watermark, header institucional UTO
