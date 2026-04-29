# Edición del Último Registro — Módulo Ventas (3 módulos) ✅ COMPLETADO

## Contexto general

El usuario que opera la tienda atiende clientes en tiempo real mientras maneja el software.
En horarios pico se forman colas largas, lo que aumenta el riesgo de errores de tipeo.
El propósito es ofrecer una **válvula de escape rápida** sin interrumpir el flujo de venta:
un panel visible en la misma pantalla POS que permita corregir la última venta sin salir de la pantalla.

---

## Reglas de negocio

| Regla | Valor |
|---|---|
| ¿Qué venta se puede editar? | Solo la última venta registrada por el usuario en sesión |
| Criterio de "última" | `user_id = session()->get('id')`, ordenado por `id DESC` |
| Ventana de tiempo | Solo si `DATE(created_at) = CURRENT_DATE` |
| Tipo de pago | **Prohibido modificar** — las secuencias PostgreSQL son para auditoría |
| Campo `code` | **Prohibido modificar** — identificador de auditoría, secuencia ya consumida |
| Campos editables | Receptor (cliente/personal UTO/externo) + productos del carrito |
| Precios en carrito | Se usan los de `detalle_venta.precio_unitario` (los cobrados originalmente) |
| Recibo | Lee directo de BD, ya refleja los datos nuevos automáticamente |
| Recibo anterior | No se invalida explícitamente (el usuario generalmente no lo imprimió aún) |
| Stock insuficiente | Rechazar **toda** la edición (opción A) — la venta original queda intacta |
| Historial de cambios | No requerido, se sobreescribe directamente |

---

## Campos editables confirmados

**Receptor en contado:**
- `cliente_id` — se puede cambiar por otro cliente de `condoriri.clientes`
- No se puede cruzar a crédito desde contado ni viceversa

**Receptor en crédito:**
- `personal_uto_id` (referencia a `public.personas`) o `cliente_externo_id` (referencia a `condoriri.clientes_externos`)
- Se puede cambiar libremente entre tipo `uto` y tipo `externo`
- Cuando se cambia el tipo, el campo que no aplica se pone en `null`

**Productos del carrito:**
- Se pueden modificar cantidades de productos existentes
- Se pueden agregar productos nuevos que no estaban en la venta original
- Se pueden eliminar productos del carrito
- El modal muestra **todos los productos disponibles con stock** (igual que el POS original)

---

## Tablas de stock por módulo

| Módulo | Tabla de stock | Campo cantidad | Campo en detalle |
|---|---|---|---|
| Lácteos tienda (`/ventas`) | `condoriri.stock_sucursales` | `stock` | `stock_id` |
| Lácteos planta (`/inventarios`) | `condoriri.productos` | `stock_inve` | `producto_id` |
| Agropecuario (`/productosagro`) | `condoriri.productos_agro` | `cantidad_inve` | `producto_agro_id` |

---

## Filtro de módulo en la query de "última venta"

Las 3 tablas de ventas son la misma (`condoriri.ventas`), diferenciadas por:
- Lácteos tienda: `sucursal_id = 2` + `NOT EXISTS (detalle con producto_agro_id)`
- Lácteos planta: `sucursal_id = 4` + `NOT EXISTS (detalle con producto_agro_id)`
- Agropecuario: `sucursal_id = session sucursal_id` + `EXISTS (detalle con producto_agro_id)`

---

## Lógica de actualización de stock (transacción)

1. Obtener la última venta del usuario hoy con su `sucursal_id` y filtro de módulo
2. Leer los `detalle_venta` originales de esa venta
3. **Devolver** el stock de cada producto original a su tabla correspondiente
4. Validar que el nuevo carrito tenga stock suficiente en **todos** los productos antes de descontar
5. Si alguno falla → lanzar excepción, el `transRollback` restaura el stock devuelto en el paso 3
6. Soft-delete de los registros originales en `condoriri.detalle_venta` (`deleted_at = NOW()`)
7. Insertar los nuevos registros en `condoriri.detalle_venta`
8. Descontar el stock de los productos nuevos en su tabla correspondiente
9. Actualizar `monto_total` en `condoriri.ventas`
10. Todo dentro de `transBegin / transCommit / transRollback`

---

## Diseño de interfaz

- La sección **"Ventas del día"** (actualmente sin uso real) se **reemplaza** por un panel **"Última venta"**
- El panel está en la columna de resumen del POS, debajo de los botones de finalizar/cancelar venta
- Está presente en los **6 puntos de venta** (3 contado + 3 crédito)
- El panel **solo aparece** si existe una venta del usuario que cumpla las condiciones; si no, no se renderiza
- El panel muestra: código de venta (`code`), monto total, y botón **"Corregir esta venta"**
- El botón abre un **modal de edición** (`#modalEditarVenta`) con:
  - Carrito pre-llenado con productos originales y sus precios originales
  - Receptor pre-seleccionado (cliente o personal UTO/externo)
  - Buscador de productos para agregar nuevos (todos los disponibles con stock)
  - Total recalculado en tiempo real
  - Botón "Guardar corrección"
- Al guardar: `fetch POST` al endpoint → transacción completa → respuesta JSON
- Si éxito: el panel actualiza el monto mostrado + aparece enlace **"Ver nuevo recibo"** (nueva pestaña)
- Si error: mensaje inline dentro del modal, sin cerrar
- El usuario cierra el modal y continúa vendiendo sin cambiar de ruta

---

## Métodos nuevos por controller

| Controller | Método | Tipo | Descripción |
|---|---|---|---|
| `ventasController` | `ultimaVenta()` | GET | Devuelve JSON con datos de la última venta del usuario hoy |
| `ventasController` | `updateUltimaVenta()` | POST | Ejecuta la transacción de corrección |
| `inventariosController` | `ultimaVenta()` | GET | Ídem para módulo planta |
| `inventariosController` | `updateUltimaVenta()` | POST | Ídem para módulo planta |
| `ventasAgroController` | `ultimaVenta()` | GET | Ídem para módulo agro |
| `ventasAgroController` | `updateUltimaVenta()` | POST | Ídem para módulo agro |

---

## Rutas nuevas en `Routes.php`

```php
// Grupo ventas
$routes->get('ultimaVenta', 'ventas\ventasController::ultimaVenta', ['filter' => 'role:admin,vendedor']);
$routes->post('updateUltimaVenta', 'ventas\ventasController::updateUltimaVenta', ['filter' => 'role:admin,vendedor']);

// Grupo inventarios
$routes->get('ultimaVenta', 'inventarios\inventariosController::ultimaVenta', ['filter' => 'role:admin,almacen']);
$routes->post('updateUltimaVenta', 'inventarios\inventariosController::updateUltimaVenta', ['filter' => 'role:admin,almacen']);

// Grupo productosagro
$routes->get('ultimaVenta', 'productosAgro\ventasAgroController::ultimaVenta', ['filter' => 'role:admin,ganaderia,agropecuario,vendedor,almacen']);
$routes->post('updateUltimaVenta', 'productosAgro\ventasAgroController::updateUltimaVenta', ['filter' => 'role:admin,ganaderia,agropecuario,vendedor,almacen']);
```

---

## Archivos a modificar

```
app/Controllers/ventas/ventasController.php           ← +ultimaVenta() +updateUltimaVenta()
app/Controllers/inventarios/inventariosController.php ← +ultimaVenta() +updateUltimaVenta()
app/Controllers/productosAgro/ventasAgroController.php← +ultimaVenta() +updateUltimaVenta()
app/Config/Routes.php                                 ← 6 rutas nuevas
app/Views/ventas/ventasIndex.php                      ← panel + modal contado
app/Views/inventarios/ventasIndex.php                 ← panel + modal contado
app/Views/productosAgro/ventasIndex.php               ← panel + modal contado
app/Views/ventas/ventasCredito.php                    ← panel + modal crédito
app/Views/inventarios/ventasCredito.php               ← panel + modal crédito
app/Views/productosAgro/ventasCredito.php             ← panel + modal crédito
```

---

## Estado actual del módulo (revisión al 2026-04-17)

### Controllers — estado real

**`ventasController`** (`app/Controllers/ventas/ventasController.php`):
- `guardarVenta()` — usa `stock_sucursales`, campo `stock`, detalle con `stock_id` ✅
- `guardarCreditoVenta()` — soporta `tipo_receptor` uto/externo ✅
- `buscarPersonalUto()` — devuelve uto + externos con `user_id` y `created_at` ✅
- `ultimaVenta()` — ✅ Implementado
- `updateUltimaVenta()` — ✅ Implementado

**`inventariosController`** (`app/Controllers/inventarios/inventariosController.php`):
- `guardarVenta()` — usa `condoriri.productos`, campo `stock_inve`, detalle con `producto_id` ✅
- `guardarCreditoVenta()` — soporta `tipo_receptor` uto/externo ✅
- `updateCantidad()` / `updateCalidad()` — ya implementados (inventarios de leche) ✅
- `ultimaVenta()` — ✅ Implementado
- `updateUltimaVenta()` — ✅ Implementado

**`ventasAgroController`** (`app/Controllers/productosAgro/ventasAgroController.php`):
- `guardarVenta()` — usa `condoriri.productos_agro`, campo `cantidad_inve`, detalle con `producto_agro_id` ✅
- `guardarCreditoVenta()` — soporta `tipo_receptor` uto/externo ✅
- `ultimaVenta()` — ✅ Implementado
- `updateUltimaVenta()` — ✅ Implementado

### Vistas — estado real

| Vista | Panel "Última venta" | Modal edición |
|---|---|---|
| `ventas/ventasIndex.php` | ✅ Implementado | ✅ Implementado |
| `inventarios/ventasIndex.php` | ✅ Implementado | ✅ Implementado |
| `productosAgro/ventasIndex.php` | ✅ Implementado | ✅ Implementado |
| `ventas/ventasCredito.php` | ✅ Implementado | ✅ Implementado |
| `inventarios/ventasCredito.php` | ✅ Implementado | ✅ Implementado |
| `productosAgro/ventasCredito.php` | ✅ Implementado | ✅ Implementado |

### Routes.php — estado real
- Las 6 rutas nuevas (`ultimaVenta` + `updateUltimaVenta` × 3 grupos) ✅ Implementadas

---

## Plan de acción por partes

### Parte 1 — Controller `ventasController` + Routes (lácteos tienda) ✅

### Parte 2 — Controller `inventariosController` + Routes (lácteos planta) ✅

### Parte 3 — Controller `ventasAgroController` + Routes (agropecuario) ✅

### Parte 4 — Vistas contado (3 × ventasIndex.php) ✅

### Parte 5 — Vistas crédito (3 × ventasCredito.php) ✅

---

## Notas técnicas importantes

### Soft-delete en `detalle_venta`
El modelo `DetalleModel` debe tener `useSoftDeletes = true` o la query de soft-delete debe hacerse
directamente con `$db->query("UPDATE condoriri.detalle_venta SET deleted_at = NOW() WHERE venta_id = ?", [$ventaId])`.
Verificar antes de implementar la Parte 1.

### Respuesta JSON de `ultimaVenta()`
Estructura esperada:
```json
{
  "venta": {
    "id": 42,
    "code": "SC-CO-000012",
    "monto_total": "150.00",
    "tipo_pago": "contado",
    "cliente_id": 5,
    "personal_uto_id": null,
    "cliente_externo_id": null
  },
  "detalles": [
    {"id": 101, "stock_id": 3, "cantidad": 2, "precio_unitario": "25.00", "subtotal": "50.00", "producto": "LECHE"}
  ],
  "receptor": {
    "tipo": "cliente",
    "id": 5,
    "nombre": "Juan Pérez"
  },
  "productos_disponibles": [...]
}
```

### Payload de `updateUltimaVenta()`
```json
{
  "venta_id": 42,
  "receptor_id": 5,
  "tipo_receptor": "cliente",
  "carrito": [
    {"id": 3, "cantidad": 3, "precio_unitario": "25.00"}
  ]
}
```

### Diferencia clave en el campo de detalle por módulo
Al insertar nuevos detalles, el campo que se usa varía:
- Tienda: `stock_id` (FK a `condoriri.stock_sucursales`)
- Planta: `producto_id` (FK a `condoriri.productos`)
- Agro: `producto_agro_id` (FK a `condoriri.productos_agro`)

Los otros dos campos deben quedar en `null` en cada caso.
