# Cambio de Sistema de Códigos de Venta

## Estado actual (activo)

Los códigos de venta se generan usando el `id` autoincremental del registro en
`condoriri.ventas`, formateado con el prefijo `VENTA-` y 6 dígitos con ceros a
la izquierda.

**Formato:** `VENTA-000001`, `VENTA-000042`, `VENTA-001337`

**Lógica:**
```php
$this->ventaModel->update($ventaId, ['code' => 'VENTA-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT)]);
```

Este código se asigna inmediatamente después del `insert()` de la venta, usando
el mismo `$ventaId` devuelto por el modelo. No depende de secuencias PostgreSQL.

---

## Sistema anterior (desactivado temporalmente)

Los códigos se generaban mediante secuencias PostgreSQL independientes por
sucursal y tipo de pago, con prefijos que identificaban el módulo.

### Formato ventas normales (`/ventas` e `/inventarios/ventas`)

```
{PREFIJO_SUCURSAL}-{PREFIJO_TIPO}-{SECUENCIAL_6_DIGITOS}
```

Ejemplos: `SC-CO-000001`, `SC-CR-000012`, `PP-CO-000003`

**Lógica:**
```php
$codigoVenta = $this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago);
$this->ventaModel->update($ventaId, ['code' => $codigoVenta]);
```

### Formato ventas agro (`/productosagro/ventas`)

```
AG-{PREFIJO_SUCURSAL}-{PREFIJO_TIPO}-{SECUENCIAL_6_DIGITOS}
```

Ejemplos: `AG-SC-CO-000001`, `AG-PP-CR-000005`

**Lógica:**
```php
$codigoVenta = $this->ventaModel->generarCodigoAgroVenta($sucursalId, $tipoPago);
$this->ventaModel->update($ventaId, ['code' => $codigoVenta]);
```

Los métodos `generarCodigoVenta()` y `generarCodigoAgroVenta()` siguen presentes
en `app/Models/Venta/VentaModel.php` y las secuencias PostgreSQL siguen activas
en la base de datos. Ver `docs/CODIGOS_VENTA.md` para detalle completo.

---

## Archivos y líneas modificadas

### `app/Controllers/ventas/ventasController.php`

| Línea | Método | Código actual | Para revertir |
|---|---|---|---|
| 798 | `guardarVenta()` | `'VENTA-' . str_pad(...)` | `$this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago)` |
| 969 | `guardarCreditoVenta()` | `'VENTA-' . str_pad(...)` | `$this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago)` |

### `app/Controllers/inventarios/inventariosController.php`

| Línea | Método | Código actual | Para revertir |
|---|---|---|---|
| 1087 | `guardarVenta()` | `'VENTA-' . str_pad(...)` | `$this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago)` |
| 1236 | `guardarCreditoVenta()` | `'VENTA-' . str_pad(...)` | `$this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago)` |

### `app/Controllers/productosAgro/ventasAgroController.php`

| Línea | Método | Código actual | Para revertir |
|---|---|---|---|
| 486 | `guardarVenta()` | `'VENTA-' . str_pad(...)` | `$this->ventaModel->generarCodigoAgroVenta($sucursalId, $tipoPago)` |
| 634 | `guardarCreditoVenta()` | `'VENTA-' . str_pad(...)` | `$this->ventaModel->generarCodigoAgroVenta($sucursalId, $tipoPago)` |

---

## Cómo revertir

En cada una de las 6 líneas listadas, reemplazar:

```php
// ACTUAL
$this->ventaModel->update($ventaId, ['code' => 'VENTA-' . str_pad($ventaId, 6, '0', STR_PAD_LEFT)]);
```

Por el bloque de dos líneas correspondiente al módulo:

```php
// VENTAS e INVENTARIOS — revertir a:
$codigoVenta = $this->ventaModel->generarCodigoVenta($sucursalId, $tipoPago);
$this->ventaModel->update($ventaId, ['code' => $codigoVenta]);

// AGRO — revertir a:
$codigoVenta = $this->ventaModel->generarCodigoAgroVenta($sucursalId, $tipoPago);
$this->ventaModel->update($ventaId, ['code' => $codigoVenta]);
```

> **Nota:** Las líneas indicadas pueden desplazarse ±5 si se agregan o eliminan
> líneas en los métodos anteriores. Buscar por el texto `str_pad($ventaId` para
> localizarlas con certeza.

---

## Fecha del cambio

2026-04-01
