# Historial de Cambios — Sistema de Códigos de Venta

## Estado actual (activo desde 2026-04-17)

Los códigos se generan mediante secuencias PostgreSQL independientes por sucursal
y tipo de pago. Es el sistema original, reactivado tras el período temporal con
formato `VENTA-{id}`.

### Ventas normales (`/ventas` e `/inventarios/ventas`)

```
{PREFIJO_SUCURSAL}-{PREFIJO_TIPO}-{SECUENCIAL_6_DIGITOS}
```

Ejemplos: `SC-CO-000001`, `SC-CR-000012`, `PP-CO-000003`

```php
$codigoVenta = $this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago);
$this->ventaModel->update($ventaId, ['code' => $codigoVenta]);
```

### Ventas agro (`/productosagro/ventas`)

```
AG-{PREFIJO_SUCURSAL}-{PREFIJO_TIPO}-{SECUENCIAL_6_DIGITOS}
```

Ejemplos: `AG-SC-CO-000001`, `AG-PP-CR-000005`

```php
$codigoVenta = $this->ventaModel->generarCodigoAgroVenta($sucursalId, $tipoPago);
$this->ventaModel->update($ventaId, ['code' => $codigoVenta]);
```

Ver `docs/CODIGOS_VENTA.md` para detalle de secuencias y scripts SQL.

---

## Líneas relevantes en los controllers

### `app/Controllers/ventas/ventasController.php`

| Línea aprox. | Método | Lógica activa |
|---|---|---|
| 799 | `guardarVenta()` | `generarCodigoVenta($sucursalId, $tipoPago)` |
| 971 | `guardarCreditoVenta()` | `generarCodigoVenta($sucursalId, $tipoPago)` |

### `app/Controllers/inventarios/inventariosController.php`

| Línea aprox. | Método | Lógica activa |
|---|---|---|
| 1088 | `guardarVenta()` | `generarCodigoVenta($sucursalId, $tipoPago)` |
| 1238 | `guardarCreditoVenta()` | `generarCodigoVenta($sucursalId, $tipoPago)` |

### `app/Controllers/productosAgro/ventasAgroController.php`

| Línea aprox. | Método | Lógica activa |
|---|---|---|
| ~486 | `guardarVenta()` | `generarCodigoAgroVenta($sucursalId, $tipoPago)` |
| ~634 | `guardarCreditoVenta()` | `generarCodigoAgroVenta($sucursalId, $tipoPago)` |

> Las líneas pueden desplazarse ±5. Buscar por `generarCodigoVenta` o
> `generarCodigoAgroVenta` para localizarlas con certeza.

---

## Período temporal con formato VENTA-{id} (2026-04-01 al 2026-04-17)

Durante este período los 3 módulos usaron:

```php
$this->ventaModel->update($ventaId, ['code' => 'VENTA-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT)]);
```

Formato: `VENTA-000001`, `VENTA-000042`. No dependía de secuencias PostgreSQL.
Los registros creados en ese período conservan ese formato en la BD.
