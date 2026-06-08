# CHANGELOG — Tareas Completadas

Registro cronológico de todas las tareas completadas documentadas en `docs/tareas/completadas/`.  
Cada entrada referencia el archivo fuente correspondiente y resume el alcance, archivos afectados y resultado.

---

## 2026-06-08 ✅ COMPLETADO
> Fuente: [`TAREAS_2026-06-08.md`](tareas/completadas/TAREAS_2026-06-08.md)

### TAREA 25 — Sincronización del cargador de imágenes y mapeo de LECHE en pantallas de venta a crédito

**Alcance:** Sincronización del cargador dinámico de imágenes con prioridad de 4 niveles en las pantallas de venta a crédito del módulo lácteos y mapeo de la imagen de Leche en `LOCAL_IMAGE_MAP`.

**Decisiones clave:**
- **Sincronización del cargador dinámico**: Replicado el flujo de prioridad asíncrono (BD, local, remoto UTO e iconos) en las vistas de crédito.
- **Mapeo de LECHE**: Añadida la clave `LECHE` en el `LOCAL_IMAGE_MAP` apuntando al recurso local `leche.png`.
- **Carga asíncrona**: Modificada la inicialización del POS de crédito para esperar por la resolución de imágenes mediante la promesa `resolveAllImages()`.

**Archivos modificados:**
- `app/Views/inventarios/ventasCredito.php`
- `app/Views/inventarios/ventasIndex.php`
- `app/Views/ventas/ventasCredito.php`
- `app/Views/ventas/ventasIndex.php`

---

## 2026-06-01 ✅ COMPLETADO
> Fuente: [`TAREAS_2026-06-01.md`](tareas/completadas/TAREAS_2026-06-01.md)

### TAREA 24 — Rediseño de la hoja secundaria "Balance General" en el Reporte General de Inventarios (Excel y PDF)

**Alcance:** Reestructuración completa de la segunda hoja del reporte Excel (antes "Resumen Detallado", ahora "Balance General") para mostrar bloques mensuales de producción y ventas en formato tabular limpio, y sincronización del reporte PDF para reflejar el mismo layout.

**Decisiones clave:**
- Columnas `MES ANT` y `EN ALMACEN` eliminadas por generar datos engañosos (valores negativos debido a que el sistema arranca desde 0 sin histórico previo).
- Columna `ORURO` calculada desde `stock_sucursales` (`SUM(cantidad) - SUM(stock)`, `sucursal_id=2`).
- Columna `PROD/MES` usa `productos.stock` (unidades producidas, no litros de leche).
- Todas las variantes de `YOGURT GRIEGO` consolidadas bajo un único nombre: `YOGURT GRIEGO 250 GRAMOS`.
- Fila de leche cruda (`leche producida: N`) al pie de cada bloque mensual, combinada en todo el ancho.
- Bloque consolidado `PRODUCCION Y VENTAS — CONSOLIDADO POR PRODUCTO` preservado al final con rango de meses en el título.
- Todos los valores numéricos en enteros (sin decimales).

**Layout final — Balance General (8 columnas):**

| DETALLE | PROD/MES | MERMA | ORURO | CONTADO | CRÉDITO | TOTAL | OBSERVACION |

**Archivos modificados:**
- `app/Services/Inventarios/ExportacionExcelService.php` — `generarWorksheetResumenDetallado()`, eliminación del bloque de cálculo encadenado de stock.
- `app/Libraries/ReporteLacteos.php` — `generarResumen()` reescrito: bloques mensuales Balance General + bloque consolidado; nuevas queries para Oruro (`stock_sucursales`) y leche cruda (`inventarios`).

---

## 2026-05-29 ✅ COMPLETADO

### TAREA 23 — Asegurar exclusión de inventarios eliminados en reportes y consultas
> Fuente: [`TAREAS_2026-05-29h.md`](tareas/completadas/TAREAS_2026-05-29h.md)

**Alcance:** Extender el filtro `deleted_at IS NULL` aplicado en la Tarea 12 a todos los métodos de consulta de inventarios que no lo incluían, garantizando que los registros eliminados nunca aparezcan en reportes, resúmenes ni en la generación de archivos Excel/PDF.

**Archivos modificados:** `InventarioModel.php`, `ExportacionExcelService.php`, `ReporteLacteos.php`.

---

### TAREA 22 — Priorizar columna de LECHE al inicio de los productos dinámicos
> Fuente: [`TAREAS_2026-05-29g.md`](tareas/completadas/TAREAS_2026-05-29g.md)

**Alcance:** En los reportes PDF (`ReporteLacteos`) y Excel (`ExportacionExcelService`), el producto `LECHE` (columna simple `LECHE (L)`) ahora siempre aparece en la primera posición de las columnas dinámicas, independientemente del orden alfabético. El resto de productos se ordena alfabéticamente a continuación.

**Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

---

### TAREA 21 — Formato de decimales y reajuste de totales (Reserva y Alineación)
> Fuente: [`TAREAS_2026-05-29f.md`](tareas/completadas/TAREAS_2026-05-29f.md)

**Alcance:** Ajustes de presentación numérica en columnas dinámicas de PDF y Excel:
- Columna de leche utilizada: 1 decimal.
- Columna de producción (stock/unidades): entero sin decimales.
- Columna de reserva en la fila `TOTAL MES`: vacía (no suma porque es un dato de estado, no acumulable).
- Corrección de alineación de subencabezados para columnas de productos complejos.

**Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

---

### TAREA 20 — Fusión de encabezados y subencabezados de productos en PDF y Excel
> Fuente: [`TAREAS_2026-05-29e.md`](tareas/completadas/TAREAS_2026-05-29e.md)

**Alcance:** Los reportes mostraban nombres de producto repetidos en celdas adyacentes para cada subcolumna (`[PRODUCTO] Stk` / `[PRODUCTO] Prod`). Se restructuró el encabezado para mostrar el nombre del producto centrado y combinado sobre las dos subcolumnas (Leche utilizada + Producción), con subencabezados en una segunda fila. En PDF se implementó con posicionamiento manual `SetXY`; en Excel con `MergeAcross`.

**Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

---

### TAREA 19 — Tratamiento especial del producto LECHE en el Reporte General
> Fuente: [`TAREAS_2026-05-29d.md`](tareas/completadas/TAREAS_2026-05-29d.md)

**Alcance:** El producto `LECHE` en `condoriri.productos` corresponde a materia prima (no producto terminado), por lo que no tiene columna `Stock` ni `MERMA/AGREGA`. Se implementó la lógica `esProductoSimple()` para renderizarlo como columna simple (`LECHE (L)`) con solo `cantidad_produccion` (litros), sin las subcolumnas de Stock, MERMA y AGREGA.

**Archivos modificados:** `ReporteLacteos.php`, `ExportacionExcelService.php`.

---

### TAREA 18 — Mejoras al Reporte General de inventario (Excel + PDF)
> Fuente: [`TAREAS_2026-05-29c.md`](tareas/completadas/TAREAS_2026-05-29c.md)

**Alcance:** Conjunto de mejoras sobre los reportes ya existentes:
- Separadores visuales entre días (borde grueso en la primera fila de cada nuevo día).
- Fila `TOTAL MES` con fondo amarillo al cierre de cada mes.
- Columna `RESERVA` presente pero sin suma acumulada en el total.
- Paginación mejorada para evitar encabezados huérfanos al pie de página en PDF.
- Paleta de colores suave alineada entre PDF y Excel.

**Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

---

### TAREA 17 — Acceso del rol `almacen` a la pantalla de gestión de clientes
> Fuente: [`TAREAS_2026-05-29b.md`](tareas/completadas/TAREAS_2026-05-29b.md)

**Alcance:** El rol `almacen` ahora puede acceder a `/cliente/lista` desde el sidebar y tiene permisos para editar clientes al contado (`cliente/update/:id`). El rol `almacen` no puede crear ni eliminar clientes.

**Archivos modificados:** `Routes.php`, `sidebar.php`.

---

### TAREA 16 — Agregar columna `imagen` a `productos_agro`
> Fuente: [`TAREAS_2026-05-29.md`](tareas/completadas/TAREAS_2026-05-29.md)

**Alcance:** Soporte de imagen para productos agropecuarios. La imagen es opcional (NULL en BD). Se muestra en las tarjetas del grid de ventas POS (`/productosagro/ventas`). Incluye subida de archivo en el formulario de creación/edición y vista previa inline.

**Archivos modificados:** `ProductoAgroModel.php`, `ProductosAgroController.php`, `productosAgroFrom.php`, `ventasIndex.php` (agro), migración SQL.

---

## 2026-05-28 ✅ COMPLETADO

### TAREA 15 — Mejoras visuales en pantallas de venta (3 módulos POS contado)
> Fuente: [`TAREAS_2026-05-28d.md`](tareas/completadas/TAREAS_2026-05-28d.md)

**Alcance:** Tres mejoras en las pantallas de venta al contado de los 3 módulos (tienda, inventarios, agro):
1. Tarjetas de productos con imagen de portada si está disponible.
2. Badge de stock visible en cada tarjeta, con color diferenciado (verde/amarillo/rojo).
3. Campo de búsqueda de producto en tiempo real sobre el grid de tarjetas (filtrado por nombre sin recargar página).

**Archivos modificados:** `ventasIndex.php` (×3 módulos).

---

### TAREA 14 — Mejoras a la pantalla `/stockinventario`
> Fuente: [`TAREAS_2026-05-28c.md`](tareas/completadas/TAREAS_2026-05-28c.md)

**Alcance:** Múltiples mejoras visuales y funcionales a la pantalla de resumen de inventario agrupado:
- Cards de resumen por producto con color de borde según nivel de stock.
- Tabla con paginación cliente y búsqueda instantánea por nombre de producto.
- Gráfico de barras (Chart.js) mostrando stock por producto.
- Exportación rápida a CSV desde la misma pantalla.

**Archivos modificados:** `stockinventario.php`, `StockInventarioController.php`.

---

### TAREA 13 — Bandeja de presets en formulario de creación de productos
> Fuente: [`TAREAS_2026-05-28b.md`](tareas/completadas/TAREAS_2026-05-28b.md)

**Alcance:** En el formulario `/productos/register/:inventarioId`, se agrega una bandeja horizontal de chips (presets) con los últimos 5 productos registrados por el usuario en la misma sucursal. Al hacer clic en un chip, se autocompletan todos los campos excepto la cantidad, y el foco se mueve al input de cantidad. Incluye textos de ayuda para usuarios no técnicos.

**Archivos modificados:** `productosform.php`, `productosController.php` (método `create()`).

---

### TAREA 12 — Inventarios eliminados siguen visibles en `/inventarios`
> Fuente: [`TAREAS_2026-05-28.md`](tareas/completadas/TAREAS_2026-05-28.md)

**Alcance:** Tras hacer soft-delete de un inventario desde `/inventarios/show/{id}`, el registro aparecía nuevamente en la tabla principal. Se añadió el filtro `deleted_at IS NULL` en `inventariosController::index()` y en `InventarioModel::getFilteredInventarios()`.

**Archivos modificados:** `inventariosController.php`, `InventarioModel.php`.

---

## 2026-05-27 ✅ COMPLETADO

### TAREA 11 — Deduplicación de empleados en búsqueda de receptor para ventas a crédito
> Fuente: [`TAREAS_2026-05-27e.md`](tareas/completadas/TAREAS_2026-05-27e.md)

**Alcance:** En la búsqueda de receptor UTO para ventas a crédito, una misma persona podía aparecer múltiples veces porque tiene varios registros activos en `rrhh.empleados` (multi-cargo). Se corrigió la query para usar `DISTINCT ON (p.id_persona)` y seleccionar el registro activo con mayor `id` (más reciente), eliminando los duplicados en los resultados.

**Archivos modificados:** `ventasController.php`, `inventariosController.php`, `ventasAgroController.php` (método `buscarPersonalUto()`).

---

### TAREA 10 — Control de duplicidad del CI/DIP entre fuentes de datos
> Fuente: [`TAREAS_2026-05-27d.md`](tareas/completadas/TAREAS_2026-05-27d.md)

**Alcance:** Prevenir la creación de perfiles con el mismo CI/DIP en `condoriri.clientes` y `condoriri.clientes_externos`. Se añadió un constraint `UNIQUE` parcial en `clientes_externos.dip` y validación en el controller antes de insertar un nuevo cliente contado (`clientes.ci_nit`). Corrección manual de duplicados existentes en BD.

**Archivos modificados:** `clienteController.php`, migración SQL de constraint.

---

### TAREA 9 — Normalización de nombres en formularios de registro de clientes
> Fuente: [`TAREAS_2026-05-27c.md`](tareas/completadas/TAREAS_2026-05-27c.md)

**Alcance:** Los nombres registrados para clientes ahora deben cumplir el formato institucional: `APELLIDO_PATERNO APELLIDO_MATERNO NOMBRE(S)` (mínimo 3 palabras, todo en mayúsculas). Se reemplazó el input único por 3 inputs separados (ap. paterno, ap. materno, nombres) con validación JS en tiempo real. El backend concatena y valida antes de insertar.

**Archivos modificados:** `ventasIndex.php` (contado ×3), `ventasCredito.php` (×3), `clienteController.php`, `clienteExternoController.php`.

---

### TAREA 8 — Resumen de crédito del receptor en POS de venta a crédito y recibos
> Fuente: [`TAREAS_2026-05-27b.md`](tareas/completadas/TAREAS_2026-05-27b.md)

**Alcance:** En las 3 pantallas de venta a crédito y en los 3 recibos, mostrar un resumen del estado crediticio del receptor en el período activo (del día 11 del mes anterior al día 10 del mes actual). El panel muestra: N° ventas del período, monto acumulado y saldo estimado. En el recibo se imprime como sección adicional de "Estado de cuenta".

**Archivos modificados:** `ventasController.php`, `inventariosController.php`, `ventasAgroController.php`, `ventasCredito.php` (×3), `recibo_print.php` (×3).

---

### TAREA 7 — Normalización y completitud del nombre de receptor en reportes Excel de ventas
> Fuente: [`TAREAS_2026-05-27.md`](tareas/completadas/TAREAS_2026-05-27.md)

**Alcance:** Los reportes Excel de ventas a crédito mostraban nombres truncados o en formato incorrecto (nombres primero, en lugar de `PATERNO MATERNO NOMBRES`). Se corrigió el `COALESCE` en `VentaModel` para usar `p.nombre` (campo de concatenación correcta `PATERNO MATERNO NOMBRES`) en lugar de `p.nombre_completo` o `p.nombres`.

**Archivos modificados:** `VentaModel.php` (`getDailySalesReportData()`, `getDailySalesReportDataInve()`, `getDailySalesReportDataAdmin()`).

---

## 2026-05-26 ✅ COMPLETADO
> Fuente: [`TAREAS_2026-05-26.md`](tareas/completadas/TAREAS_2026-05-26.md)

### TAREA 6 — Columna "Categoría" en reportes PDF y Excel de ventas a crédito

**Alcance:** Agregar en los reportes PDF y Excel de ventas **a crédito** (3 módulos: tienda CEAC, planta lácteos, agropecuario) una columna/campo "Categoría" que identifique el tipo de receptor:
- Personal UTO → `te.tipo_empleado` de `rrhh.tipos_empleados` (ej. `ADMINISTRATIVO`, `DOCENTE TC`, `EVENTUAL`).
- Cliente externo → segmento (`SEGURO UNIVERSITARIO`, `SPECTROLAB`, `OTROS`).

Solo aplica a reportes de **crédito**; los reportes de contado y general no se modifican.

**Archivos modificados:**
- `VentaModel.php` — `getDailySalesReportData()`, `getDailySalesReportDataInve()`, `getDailySalesReportDataAdmin()`: `COALESCE(te.tipo_empleado, ce.segmento, NULL) AS receptor_categoria` + JOIN a `rrhh.tipos_empleados`.
- `CierreVentaPdf.php`, `CierreVentaInve.php`, `CierreVentaAgroPdf.php` — columna "Categoría" (22mm, condicional `$tipo === 'credito'`).
- `CierreVentaBasePdf.php` — fila "Categoría:" en bloque de datos de crédito.
- `ExcelVentasMatrizService.php`, `ExcelVentasAgroService.php` — columna `CATEGORÍA` al final de cada fila; `$totalCols` ajustado dinámicamente.

---

## Tareas anteriores (documentos de referencia temática)

Las siguientes tareas están documentadas en archivos independientes (sin numeración secuencial) y corresponden a trabajo completado antes de la serie TAREAS_2026-05-xx:

### Edición del Último Registro — Inventarios y Clientes ✅
> Fuente: [`EDICION_ULTIMO_REGISTRO.md`](tareas/completadas/EDICION_ULTIMO_REGISTRO.md)

**Alcance:** Implementación del flujo completo de edición del último registro del día para el módulo de inventarios y clientes. Reglas de negocio: solo el propio usuario puede editar su último registro del día; la edición de cantidad de leche recalcula la reserva; la edición de calidad está restringida por rol.

**Módulos cubiertos:** `inventariosController.php` (métodos `updateCantidad()`, `updateCalidad()`), `inventariosShow.php`, `productosform.php` (panel de reserva con barra de progreso y modal de confirmación).

---

### Edición del Último Registro — Módulo Ventas (3 módulos) ✅
> Fuente: [`VENTAS_EDICION_ULTIMO_REGISTRO.md`](tareas/completadas/VENTAS_EDICION_ULTIMO_REGISTRO.md)

**Alcance:** Flujo de edición de la última venta registrada en los 3 módulos de ventas (tienda CEAC, planta lácteos, agropecuario), tanto para ventas a contado como a crédito. La operación es una transacción atómica: devolver stock → soft-delete de detalles anteriores → insertar nuevos detalles → descontar stock → actualizar monto. Panel "Última venta" visible en las 6 vistas de venta (3 contado + 3 crédito) con modal de confirmación.

**Archivos nuevos/modificados:** `ventasController.php`, `inventariosController.php`, `ventasAgroController.php` (método `deleteUltimaVenta()`), `ventasIndex.php` (×3), `ventasCredito.php` (×3), `Routes.php`.

---

### Mejoras a Reportes del Módulo Inventarios ✅
> Fuente: [`REPORTES_INVENTARIOS.md`](tareas/completadas/REPORTES_INVENTARIOS.md)

**Alcance:** Primera iteración de mejoras a los reportes del módulo de inventarios. Definición del alcance inicial, separación de reportes LECHE vs "Suero y Otros", orden por turno AM/PM dentro del día, paleta visual suave. Punto de partida para las tareas 18–23 de la serie 2026-05-29.

**Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`, `inventariosController.php`.
