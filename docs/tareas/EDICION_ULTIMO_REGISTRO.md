# Tarea: Edición del Último Registro por Usuario

## Resumen de la solicitud

El cliente solicita que los usuarios puedan corregir errores de tipeo en registros ya guardados.
Por seguridad, **solo se puede editar el último registro creado por ese usuario**, y únicamente
dentro de una **ventana de tiempo de 1 día** (mismo día de creación).

Aplica globalmente a ventas, clientes, usuarios, inventarios, etc.

---

## Reglas globales definidas

| Regla | Valor |
|---|---|
| ¿Qué registro se puede editar? | Solo el último registrado por el usuario en sesión |
| Criterio de "último" | Último por `user_id = session()->get('id')`, ordenado por `id DESC` |
| Ventana de tiempo | Solo si `created_at` es del día de hoy |
| Historial de cambios | No requerido, se sobreescribe directamente |
| Restricción por rol | Ninguna, todos los roles aplican igual |

---

## Plan general por prioridad

Se trabaja de menor a mayor complejidad/riesgo:

| Prioridad | Módulo | Complejidad | Estado |
|---|---|---|---|
| 🟢 Baja | Clientes (`condoriri.clientes`) | Sin dependencias de stock | ✅ Completado (contado) |
| 🟢 Baja | Clientes externos (`condoriri.clientes_externos`) | Sin dependencias de stock | ✅ Completado (contado + crédito) |
| 🟢 Baja | Usuarios | Ya tiene edición implementada | ⏭️ Omitido (ya existe) |
| 🟢 Baja | Catálogos (categorías, unidades, sucursales) | Ya tiene edición implementada | ⏭️ Omitido (ya existe) |
| 🟡 Media | Inventarios de leche | Afecta reserva acumulativa | ✅ Completado |
| 🟡 Media | Productos lácteos / agro (stock) | `stock_inve`/`cantidad_inve` vinculado a ventas | ⏳ Pendiente |
| 🔴 Alta | Ventas (3 módulos) | Requiere revertir stock + recalcular totales | ⏳ Pendiente |
| 🔴 Alta | Envíos confirmados | Requiere revertir stock de `stock_sucursales` | ⏳ Pendiente |

---

## Módulo Clientes — Detalle

### Decisiones tomadas

- Edición disponible **desde las pantallas de venta** (POS), no desde un apartado separado.
- Al seleccionar un cliente en el buscador, si cumple las condiciones (mismo `user_id`, mismo día),
  aparece un **panel inline** debajo del input con los campos editables y un recuadro informativo.
- Si no cumple las condiciones, el panel no aparece.
- La edición usa **AJAX (fetch)** sin recargar la página.
- Los errores del servidor se muestran dentro del panel, no con `alert()`.
- El recuadro informativo dice: *"Puede editar el último cliente registrado por usted hoy."*

### Campos editables

| Tabla | Campos editables |
|---|---|
| `condoriri.clientes` | `nombre_completo`, `ci_nit` |
| `condoriri.clientes_externos` | `nombre`, `dip`, `segmento` |

---

## Cambios ya realizados

### `app/Controllers/cliente/clienteController.php`

- **`update()`** — modificado para agregar validaciones:
  1. Verifica que el `id` recibido sea el último cliente registrado por `user_id` en sesión
  2. Verifica que `created_at` sea del día de hoy
  3. Devuelve `nombre_completo` y `ci_nit` en la respuesta JSON exitosa (para actualizar el input sin recargar)
- **`updateExterno()`** — método nuevo con la misma lógica sobre `condoriri.clientes_externos`
  - Valida `nombre`, `dip` y `segmento`
  - Usa `ClienteExternoModel` (inyectado en constructor)

### `app/Config/Routes.php`

- Ruta nueva agregada en el grupo `cliente`:
  ```php
  $routes->post('updateExterno', 'cliente\clienteController::updateExterno',
      ['filter' => 'role:admin,vendedor,agropecuario,ganaderia,almacen']);
  ```

### Vistas de venta a **contado** — 3 archivos modificados

Los 3 archivos recibieron cambios idénticos:

| Archivo | Estado |
|---|---|
| `app/Views/ventas/ventasIndex.php` | ✅ Modificado |
| `app/Views/inventarios/ventasIndex.php` | ✅ Modificado |
| `app/Views/productosAgro/ventasIndex.php` | ✅ Modificado |

**Cambios en cada vista:**

1. **HTML** — panel `#clientEditPanel` debajo del `#clientResults`, oculto por defecto:
   - Recuadro `alert-info` con texto explicativo
   - Input `#editNombre` y `#editCi`
   - Botón `#btnGuardarEdit` y `#btnCancelarEdit`
   - `#editClientError` para mostrar errores inline

2. **JS** — variables de sesión inyectadas desde PHP:
   ```js
   const SESSION_USER_ID = <?= (int)session()->get('id') ?>;
   const TODAY = '<?= date('Y-m-d') ?>';
   ```

3. **JS** — `buscarClientes()`: los items del dropdown ahora incluyen
   `data-user-id` y `data-created-at` en cada `<a>`.

4. **JS** — `selectClient()`: evalúa `user_id === SESSION_USER_ID` y
   `created_at.substring(0,10) === TODAY`. Si cumple, muestra el panel pre-llenado.

5. **JS** — listener `#btnGuardarEdit`: hace `fetch POST /cliente/update`,
   actualiza `clientSearch.value` y `selectedClient` en éxito, muestra error inline en fallo.

6. **JS** — listener `#btnCancelarEdit`: oculta el panel.

---

## Cambios pendientes

### 1. ~~Vistas de venta a **crédito** — clientes externos~~ ✅ Completado

| Archivo | Estado |
|---|---|
| `app/Views/ventas/ventasCredito.php` | ✅ Modificado |
| `app/Views/inventarios/ventasCredito.php` | ✅ Modificado |
| `app/Views/productosAgro/ventasCredito.php` | ✅ Modificado |

Además se corrigieron los 3 endpoints `buscarPersonalUto` en los controllers para incluir
`user_id` y `created_at` en los resultados de `clientes_externos`, necesarios para la
evaluación de condiciones en el JS.

**Diferencias implementadas respecto a contado:**
- Panel `#clienteExternoEditPanel` con campos `nombre`, `dip`, `segmento`
- Solo aparece para resultados de tipo `externo`; los de tipo `uto` nunca muestran el panel
- Al crear un externo nuevo con "Nuevo externo", el panel aparece automáticamente
- Llama a `POST /cliente/updateExterno`
- Actualiza el `#personalInfo` con el nombre/DIP nuevos sin recargar

### 2. ~~Módulo Inventarios de leche~~ ✅ Completado

**Archivos modificados:**
- `app/Controllers/inventarios/inventariosController.php`
  - `show()` — ahora calcula y pasa a la vista: `puedeEditarCantidad`, `puedeEditarCalidad`, `tieneProductos`, `esUltimoDelUsuario`, `esDehoy`
  - `updateCantidad(int $id)` — método nuevo: valida último del usuario + hoy + sin productos, actualiza `stock` y recalcula `reserva`
  - `updateCalidad(int $id)` — método nuevo: valida `rol_id` en [1,3], actualiza los 10 campos de calidad
- `app/Config/Routes.php` — 2 rutas nuevas: `POST inventarios/update-cantidad/(:num)` y `POST inventarios/update-calidad/(:num)`
- `app/Views/inventarios/inventariosShow.php`
  - Card "Detalles del Inventario": formulario inline de cantidad visible solo si `$puedeEditarCantidad`; mensaje de bloqueo si tiene productos
  - Card "Control de Calidad": formulario de edición de los 10 campos visible solo si `$puedeEditarCalidad`

**Reglas implementadas para cantidad:**
- Mismo `user_id` que el usuario en sesión
- `created_at` del día de hoy
- Sin productos en `condoriri.productos` con ese `inventario_id`
- Recalcula `reserva`: para LECHE = `reserva_inventario_anterior + nueva_cantidad`; para otros = `nueva_cantidad`

**Reglas implementadas para calidad:**
- Solo `rol_id` 1 (admin) o 3 (almacen)
- Sin restricción de día ni de productos

### 3. Módulo Productos — `condoriri.productos` y `condoriri.productos_agro`

**Riesgo medio:** `stock_inve` / `cantidad_inve` están vinculados a ventas y mermas.

**Preguntas pendientes antes de implementar:**
- ¿Qué campos son editables? (precios, nombre, descripción, stock, calidad)
- ¿El stock es editable directamente o solo a través de merma/agregar?

### 4. Módulo Ventas — los 3 módulos (alta complejidad)

**Tablas afectadas por venta:**

| Módulo | Tablas |
|---|---|
| Lácteos tienda | `condoriri.ventas` + `condoriri.detalle_venta` + `condoriri.stock_sucursales` |
| Lácteos planta | `condoriri.ventas` + `condoriri.detalle_venta` + `condoriri.productos` |
| Agropecuario | `condoriri.ventas` + `condoriri.detalle_venta` + `condoriri.productos_agro` |

**Preguntas pendientes antes de implementar:**
- ¿Qué campos son editables en una venta? (receptor, observaciones, productos del carrito)
- ¿Se puede editar el tipo de pago? (afecta el `code` de secuencia ya consumido)
- ¿El recibo regenerado muestra los datos nuevos?

### 5. Módulo Envíos — `condoriri.envios`

**Riesgo alto:** envíos confirmados (`estado_id = 2`) ya descontaron stock de `stock_sucursales`.

**Preguntas pendientes antes de implementar:**
- ¿Solo se editan envíos en estado pendiente (`estado_id = 1`)?
- ¿O también confirmados (requiere revertir stock)?

---

## Archivos clave de referencia

```
app/Controllers/cliente/clienteController.php     ← controller clientes (modificado)
app/Config/Routes.php                             ← rutas (modificado)
app/Views/ventas/ventasIndex.php                  ← POS contado lácteos (modificado)
app/Views/inventarios/ventasIndex.php             ← POS contado planta (modificado)
app/Views/productosAgro/ventasIndex.php           ← POS contado agro (modificado)
app/Views/ventas/ventasCredito.php                ← POS crédito lácteos (pendiente)
app/Views/inventarios/ventasCredito.php           ← POS crédito planta (pendiente)
app/Views/productosAgro/ventasCredito.php         ← POS crédito agro (pendiente)
app/Models/Cliente/ClienteModel.php               ← modelo clientes
app/Models/ClienteExterno/ClienteExternoModel.php ← modelo clientes externos
```

---

## Contexto del proyecto

- **Framework:** CodeIgniter 4 (PHP 8.1)
- **BD:** PostgreSQL, schema principal `condoriri`
- **Schemas adicionales:** `public` (personas UTO), `rrhh` (empleados, cargos, secciones)
- **Auto-routing:** deshabilitado, todas las rutas son explícitas en `Routes.php`
- **Patrones:** queries SQL directas para consultas complejas, transacciones con `transBegin/transCommit/transRollback`
- **Sesión:** `session()->get('id')`, `session()->get('rol_nombre')`, `session()->get('sucursal_id')`
- **Docs del proyecto:** `docs/ARCHITECTURE.md`, `docs/CHANGELOG.md`, `docs/CODIGOS_VENTA.md`, `docs/VENTAS_AGRO.md`, `docs/PDF_REPORTS.md`
