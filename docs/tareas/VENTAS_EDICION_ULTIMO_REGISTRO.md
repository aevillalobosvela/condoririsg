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

## Estado actual del módulo (revisión al 2026-04-28)

### Controllers — estado real

**`ventasController`** (`app/Controllers/ventas/ventasController.php`):
- `guardarVenta()` — usa `stock_sucursales`, campo `stock`, detalle con `stock_id` ✅
- `guardarCreditoVenta()` — soporta `tipo_receptor` uto/externo ✅
- `buscarPersonalUto()` — devuelve uto + externos con `user_id` y `created_at` ✅
- `ultimaVenta()` — ✅ Implementado y verificado
- `updateUltimaVenta()` — ✅ Implementado, verificado y **bug corregido** (ver nota `uk_detalle_stock`)

**`inventariosController`** (`app/Controllers/inventarios/inventariosController.php`):
- `guardarVenta()` — usa `condoriri.productos`, campo `stock_inve`, detalle con `producto_id` ✅
- `guardarCreditoVenta()` — soporta `tipo_receptor` uto/externo ✅
- `updateCantidad()` / `updateCalidad()` — ya implementados (inventarios de leche) ✅
- `ultimaVenta()` — ✅ Implementado y verificado
- `updateUltimaVenta()` — ✅ Implementado y verificado

**`ventasAgroController`** (`app/Controllers/productosAgro/ventasAgroController.php`):
- `guardarVenta()` — usa `condoriri.productos_agro`, campo `cantidad_inve`, detalle con `producto_agro_id` ✅
- `guardarCreditoVenta()` — soporta `tipo_receptor` uto/externo ✅
- `ultimaVenta()` — ✅ Implementado y verificado
- `updateUltimaVenta()` — ✅ Implementado y verificado

### Vistas — estado real

| Vista | Panel "Última venta" | Modal edición |
|---|---|---|
| `ventas/ventasIndex.php` | ✅ Completo | ✅ Completo |
| `inventarios/ventasIndex.php` | ✅ Completo | ✅ Completo |
| `productosAgro/ventasIndex.php` | ✅ Completo | ✅ Completo |
| `ventas/ventasCredito.php` | ✅ Completo | ✅ Completo |
| `inventarios/ventasCredito.php` | ✅ Completo | ✅ Completo |
| `productosAgro/ventasCredito.php` | ✅ Completo | ✅ Completo |

### Routes.php — estado real
- Grupo `ventas`: `ultimaVenta` + `updateUltimaVenta` ✅ Agregadas
- Grupo `inventarios`: `ultimaVenta` + `updateUltimaVenta` ✅ Agregadas
- Grupo `productosagro`: `ultimaVenta` + `updateUltimaVenta` ✅ Agregadas

---

## Plan de acción por partes

### Parte 1 — Controller `ventasController` + Routes (lácteos tienda) ✅

### Parte 2 — Controller `inventariosController` + Routes (lácteos planta) ✅

### Parte 3 — Controller `ventasAgroController` + Routes (agropecuario) ✅

---

### Parte 2 — Controller `inventariosController` + Routes (lácteos planta)
**Objetivo:** replicar la lógica de la Parte 1 para el módulo planta.

Tareas:
1. Agregar `ultimaVenta()` en `inventariosController` — query con `sucursal_id=4` + filtro no-agro
2. Agregar `updateUltimaVenta()` en `inventariosController` — transacción con `condoriri.productos` (`stock_inve`)
3. Agregar las 2 rutas en `Routes.php` (grupo `inventarios`)

Criterio de éxito: mismo que Parte 1 pero en `/inventarios/ultimaVenta` y `/inventarios/updateUltimaVenta`

---

### Parte 3 — Controller `ventasAgroController` + Routes (agropecuario)
**Objetivo:** replicar para el módulo agro, con la diferencia de `productos_agro` y filtro EXISTS.

Tareas:
1. Agregar `ultimaVenta()` en `ventasAgroController` — query con `EXISTS (producto_agro_id)` + `sucursal_id` de sesión
2. Agregar `updateUltimaVenta()` en `ventasAgroController` — transacción con `condoriri.productos_agro` (`cantidad_inve`)
3. Agregar las 2 rutas en `Routes.php` (grupo `productosagro`)

Criterio de éxito: mismo que Parte 1 pero en `/productosagro/ultimaVenta` y `/productosagro/updateUltimaVenta`

---

### Parte 4 — Vistas contado (3 × ventasIndex.php)
**Estado: ✅ Completado (2026-04-28)**

**Panel `#panelUltimaVenta` — contenido final:**
- Código de venta (`code`) como badge
- Texto explicativo: *"¿Cometió un error en la última venta? Puede corregir los productos o el cliente antes de cerrar el día. Solo aplica a su última venta registrada hoy."*
- Nombre del cliente/receptor (o "Consumidor Final")
- Monto total
- Lista de productos: `nombre × cantidad × precio_unitario`, con scroll si hay muchos
- Botón "Corregir esta venta"

**Modal `#modalEditarVenta` — comportamiento final:**
- Carrito pre-llenado con productos y precios originales, editable (cantidad, agregar, eliminar)
- Buscador de cliente: input con dropdown, filtra sobre `uvData.clientes` por nombre o CI (máx. 8), pre-llenado con receptor actual; borrar el input vuelve a "Consumidor Final" (id=0)
- `<select>` para agregar productos con stock disponible
- Total recalculado en tiempo real
- Al guardar con éxito: `window.open(recibo_url, '_blank')` + `window.location.reload()`
- Al guardar con error: mensaje inline, modal no se cierra, botón se restaura

**JSON de `ultimaVenta()` — estructura final (los 3 módulos):**
```json
{
  "venta": {"id": 42, "code": "SC-CO-000012", "monto_total": "150.00", ...},
  "detalles": [{"stock_id": 3, "cantidad": 2, "precio_unitario": "25.00", "producto": "LECHE"}],
  "receptor": {"tipo": "cliente", "id": 5, "nombre": "Juan Pérez"},
  "productos_disponibles": [...],
  "clientes": [{"id": 1, "nombre_completo": "...", "ci_nit": "..."}]
}
```

Ver nota técnica `uk_detalle_stock` para el bug resuelto en el módulo tienda.

---

### Parte 5 — Vistas crédito (3 × ventasCredito.php)
**Estado: ✅ Completado (2026-04-28)**

**Diferencias clave respecto a las vistas de contado:**

- El receptor es `personal_uto_id` (tipo `uto`) o `cliente_externo_id` (tipo `externo`), nunca `cliente_id`
- El buscador de receptor en el modal usa `fetch GET /[modulo]/buscarPersonalUto?dip=TERMINO` (mín. 3 caracteres), igual que el POS de crédito existente
- Los resultados muestran badge azul para `uto` y badge amarillo para `externo`
- Al seleccionar, se actualiza `#editReceptorId` y `#editTipoReceptor` (`'uto'` o `'externo'`)
- El payload POST incluye `tipo_receptor` — el controller ya lo procesa correctamente
- No es necesario incluir `clientes` en el JSON de `ultimaVenta()` para estas vistas
- Receptor es obligatorio (validación antes de enviar: no existe "Consumidor Final" en crédito)

**Implementación final en los 3 módulos:**

| Módulo | `ENDPOINT_GET/POST` | `ENDPOINT_UTO` | `CAMPO_ID` | Stock en selector |
|---|---|---|---|---|
| ventas | `ventas/ultima...` | `ventas/buscarPersonalUto` | `stock_id` | `p.stock` |
| inventarios | `inventarios/ultima...` | `inventarios/buscarPersonalUto` | `producto_id` | `p.stock_inve` |
| productosAgro | `productosagro/ultima...` | `productosagro/buscarPersonalUto` | `producto_agro_id` | `p.cantidad_inve` |

---

## Notas técnicas importantes

### ⚠️ Constraint `uk_detalle_stock` en `condoriri.detalle_venta` (bug resuelto en módulo tienda)

La tabla `condoriri.detalle_venta` tiene una constraint UNIQUE `uk_detalle_stock` sobre `(venta_id, stock_id)`
**sin condición parcial** — es decir, incluye también las filas con `deleted_at IS NOT NULL`.

Esto causaba que `updateUltimaVenta()` en `ventasController` fallara silenciosamente:
1. El soft-delete dejaba los detalles originales en la tabla con `deleted_at` poblado
2. Al reinsertar los nuevos detalles con el mismo `venta_id + stock_id`, PostgreSQL lanzaba:
   `duplicate key value violates unique constraint "uk_detalle_stock"`
3. La excepción era un `ErrorException` (no `\Exception`), por lo que el `catch` del controller
   no la capturaba y el fetch recibía una respuesta de error 500 sin JSON — el modal no mostraba
   nada porque el `.catch()` del JS solo ponía "Error de conexión" pero el error no llegaba
   a mostrarse por un problema de timing en el render.

**Solución aplicada:** reemplazar el soft-delete por hard-delete directo con SQL en el paso 4
de la transacción de `ventasController::updateUltimaVenta()`:
```php
// Antes (fallaba):
$this->detalleModel->delete($det->id);

// Después (correcto):
$db->query("DELETE FROM condoriri.detalle_venta WHERE id = ?", [$det->id]);
```

Los módulos `inventarios` y `productosagro` no tienen esta constraint sobre sus respectivos
campos (`producto_id`, `producto_agro_id`), por eso funcionaban correctamente con soft-delete.

### Soft-delete en `detalle_venta`
El modelo `DetalleModel` tiene `useSoftDeletes = true`. Para el módulo tienda (`ventasController`)
se usa hard-delete por la constraint `uk_detalle_stock` (ver nota anterior). Para los módulos
`inventarios` y `productosagro` se puede usar `$this->detalleModel->delete($id)` sin problema.

**Precaución para la Parte 5:** si en el futuro se agrega una constraint similar sobre
`(venta_id, producto_id)` o `(venta_id, producto_agro_id)`, aplicar el mismo hard-delete.

### Comportamiento post-guardado en las vistas
Al guardar con éxito:
1. `window.open(data.recibo_url, '_blank')` — abre el recibo actualizado en nueva pestaña
2. `window.location.reload()` — recarga la página, actualizando stocks y `uvData`

Al guardar con error:
- Mensaje inline en `#editVentaError`, modal no se cierra, botón se restaura

### Buscador de clientes en el modal (vistas contado — Parte 4)
- Filtra sobre `uvData.clientes` (cargado en el GET, no requiere AJAX adicional)
- Búsqueda por `nombre_completo` o `ci_nit`, máx. 8 resultados
- Al seleccionar: actualiza `#editReceptorId` (hidden) y `#editClienteSeleccionado` (texto)
- Al borrar el input: vuelve a "Consumidor Final" (id=0)
- `#editTipoReceptor` siempre es `'cliente'` en las vistas de contado

### Buscador de receptor en el modal (vistas crédito — Parte 5)
- Endpoint AJAX: `GET /[modulo]/buscarPersonalUto?dip=TERMINO` (mín. 3 caracteres)
- Devuelve array con `tipo: 'uto'` o `tipo: 'externo'`
- Badge azul para UTO, badge amarillo para externo
- Al seleccionar: actualiza `#editReceptorId` y `#editTipoReceptor`
- No requiere `clientes` en el JSON del GET

### Respuesta JSON de `ultimaVenta()` — estructura final
```json
{
  "venta": {
    "id": 42, "code": "SC-CO-000012", "monto_total": "150.00",
    "tipo_pago": "contado", "cliente_id": 5,
    "personal_uto_id": null, "cliente_externo_id": null
  },
  "detalles": [
    {"id": 101, "stock_id": 3, "cantidad": 2, "precio_unitario": "25.00", "subtotal": "50.00", "producto": "LECHE"}
  ],
  "receptor": {"tipo": "cliente", "id": 5, "nombre": "Juan Pérez"},
  "productos_disponibles": [...],
  "clientes": [{"id": 1, "nombre_completo": "...", "ci_nit": "..."}]
}
```
`clientes` se incluye en los 3 módulos de contado. Para crédito (Parte 5) no es necesario.

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
