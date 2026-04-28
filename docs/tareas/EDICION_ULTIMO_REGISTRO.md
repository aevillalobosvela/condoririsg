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
| 🟡 Media | Productos lácteos / agro (stock) | `stock_inve`/`cantidad_inve` vinculado a ventas | ✅ Completado (ayudas visuales) |
| 🔴 Alta | Ventas (3 módulos) | Requiere revertir stock + recalcular totales | ⏳ Pendiente |
| 🔴 Alta | Envíos confirmados | Requiere revertir stock de `stock_sucursales` | ⏳ Postergado (fuera de esta etapa) |

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

### 3. ~~Módulo Productos~~ ✅ Completado

**Decisión tomada:** en lugar de permitir edición post-creación (demasiado riesgo de desincronizar
inventario, reserva y ventas), se mejoró la experiencia de creación para prevenir errores.

**Archivo modificado:** `app/Views/productos/productosform.php`

**Cambios en modo creación:**
- Panel de reserva del inventario con barra de progreso visual que cambia de color según el % de uso (verde / amarillo ≥90% / rojo =100%)
- Panel de resultados del cálculo rediseñado: muestra unidades, litros usados y reserva restante con colores de alerta
- Badge de estado en el panel de resultados: "Listo para guardar" / "Uso alto de reserva" / "0 unidades — revise los valores"
- Alerta inline cuando el cálculo produce 0 unidades
- Botón "Crear Producto" deshabilitado hasta que el cálculo sea válido (≥1 unidad, litros ≤ reserva)
- Modal de confirmación antes del POST con resumen completo: nombre, categoría, precios, litros, unidades, reserva restante

**Cambios en modo edición:**
- Reemplazado el `alert-warning` genérico por un panel claro que muestra stock original y actual, y explica explícitamente qué campos sí y qué no se pueden modificar

**Productos agro:** omitidos por ser poco frecuentes.

### 4. Módulo Ventas — los 3 módulos (alta complejidad)

**Tablas afectadas por venta:**

| Módulo | Tablas |
|---|---|
| Lácteos tienda | `condoriri.ventas` + `condoriri.detalle_venta` + `condoriri.stock_sucursales` |
| Lácteos planta | `condoriri.ventas` + `condoriri.detalle_venta` + `condoriri.productos` |
| Agropecuario | `condoriri.ventas` + `condoriri.detalle_venta` + `condoriri.productos_agro` |

---

## Módulo Ventas — Decisiones y contexto de implementación

### Contexto de uso

El usuario que opera la tienda atiende clientes en tiempo real mientras maneja el software.
En horarios pico se forman colas largas, lo que aumenta el riesgo de errores de tipeo.
El propósito de esta función es ofrecer una **válvula de escape rápida** sin interrumpir
el flujo de venta: un panel visible en la misma pantalla POS que permita corregir la última
venta sin salir de la pantalla.

### Reglas específicas para ventas

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

### Campos editables confirmados

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

### Lógica de actualización de stock (transacción)

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

### Tablas de stock por módulo

| Módulo | Tabla de stock | Campo cantidad | Campo en detalle |
|---|---|---|---|
| Lácteos tienda (`/ventas`) | `condoriri.stock_sucursales` | `stock` | `stock_id` |
| Lácteos planta (`/inventarios`) | `condoriri.productos` | `stock_inve` | `producto_id` |
| Agropecuario (`/productosagro`) | `condoriri.productos_agro` | `cantidad_inve` | `producto_agro_id` |

### Filtro de módulo en la query de "última venta"

Las 3 tablas de ventas son la misma (`condoriri.ventas`), diferenciadas por:
- Lácteos tienda: `sucursal_id = 2` + `NOT EXISTS (detalle con producto_agro_id)`
- Lácteos planta: `sucursal_id = 4` + `NOT EXISTS (detalle con producto_agro_id)`
- Agropecuario: `sucursal_id = session sucursal_id` + `EXISTS (detalle con producto_agro_id)`

### Diseño de interfaz

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

### Métodos nuevos por controller

| Controller | Método | Tipo | Descripción |
|---|---|---|---|
| `ventasController` | `ultimaVenta()` | GET | Devuelve JSON con datos de la última venta del usuario hoy |
| `ventasController` | `updateUltimaVenta()` | POST | Ejecuta la transacción de corrección |
| `inventariosController` | `ultimaVenta()` | GET | Ídem para módulo planta |
| `inventariosController` | `updateUltimaVenta()` | POST | Ídem para módulo planta |
| `ventasAgroController` | `ultimaVenta()` | GET | Ídem para módulo agro |
| `ventasAgroController` | `updateUltimaVenta()` | POST | Ídem para módulo agro |

### Rutas nuevas en `Routes.php`

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

### Orden de implementación

1. Los 3 controllers (`ultimaVenta` + `updateUltimaVenta` en cada uno)
2. `Routes.php` — 6 rutas nuevas
3. Las 3 vistas de contado (`ventasIndex.php` × 3) — reemplazar panel + agregar modal
4. Las 3 vistas de crédito (`ventasCredito.php` × 3) — mismo panel y modal, adaptado para receptor UTO/externo
5. Envíos: **postergado**, no se toca en esta etapa

### Estado actual

| Subtarea | Estado |
|---|---|
| Decisiones de negocio definidas | ✅ Completado |
| Dudas técnicas resueltas | ✅ Completado |
| Controllers (3 × 2 métodos) | ⏳ Pendiente |
| Routes.php (6 rutas) | ⏳ Pendiente |
| Vistas contado (3 × ventasIndex.php) | ⏳ Pendiente |
| Vistas crédito (3 × ventasCredito.php) | ⏳ Pendiente |

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
