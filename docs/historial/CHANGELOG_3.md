# CHANGELOG — Fase 3 (Mayo – Junio 2026)

Historial integrado de cambios generales y bitácora de tareas completadas correspondiente a la **Fase 3 (Mayo – Junio 2026 - Actual)** del proyecto Condoriri SG.

---

## Tareas de Desarrollo (Fase 3)

Las siguientes tareas e integraciones secuenciales corresponden a esta fase reciente de optimización y mejoras visuales del sistema:

### 2026-06-23 ✅ COMPLETADO

### TAREA 31 — Corrección de redirección en paginación de ventas para el rol de almacén
* **Alcance:** Corrección del error que redirigía a los usuarios con rol `almacen` al panel de inicio al navegar por las páginas de la tabla de ventas realizadas.
* **Detalles del trabajo:**
  1. Se cambió la dirección base de paginación `$baseUrl` de `base_url('ventas')` a `base_url('inventarios/ventas')` en `inventariosController::indexVenta` y `indexVenta1`. Esto evita que el filtro de autenticación por roles bloquee a los usuarios con rol `almacen` (quienes tienen acceso a `/inventarios/ventas` pero no al grupo general de `/ventas`).
  2. Se actualizó de manera preventiva la URL de paginación en `contabilidadController::indexLactos` a `base_url('contabilidad/ventas')`.
* **Archivos modificados:**
  - `app/Controllers/inventarios/inventariosController.php`
  - `app/Controllers/contabilidad/contabilidadController.php`

---

### 2026-06-18 ✅ COMPLETADO

### TAREA 30 — División e integración de archivos de historial (Changelogs)
* **Alcance:** División del contenido de `CHANGELOG.md` y `CHANGELOG_TAREAS.md` en tres archivos integrados y numerados por fases del proyecto para facilitar el seguimiento del avance de las tareas, y actualización de las referencias en el índice central y guía de desarrollo.
* **Archivos modificados:**
  - `docs/README.md`
  - `CLAUDE.md`
  - `docs/historial/CHANGELOG_1.md` (Nuevo)
  - `docs/historial/CHANGELOG_2.md` (Nuevo)
  - `docs/historial/CHANGELOG_3.md` (Modificado)
  - `docs/historial/CHANGELOG.md` (Eliminado)
  - `docs/historial/CHANGELOG_TAREAS.md` (Eliminado)

---

### TAREA 29 — Integración de nuevas imágenes y alineación de fuentes de stock en POS
* **Alcance:** Integración del nuevo set de fotos descriptivas para productos lácteos en las tarjetas de productos, y estandarización del tamaño de fuente de stock.
* **Detalles del trabajo:**
  1. **Nuevos recursos `.jpeg`**: Integración de `mantequilla-250-gramos.jpeg`, `crema-de-leche.jpeg`, y reemplazo de los antiguos archivos mixtos por `queso-900-gramos.jpeg`, `queso-sin-sal-500-gramos.jpeg`, `requeson-250-gramos.jpeg` y `yogurt-1-litro.jpeg`.
  2. **Actualización de Mapeos**: Reconfiguración de la constante `LOCAL_IMAGE_MAP` en las 4 vistas de ventas del módulo de lácteos e inventarios.
  3. **Alineación de Fuentes**: Reemplazo de los badges de stock pequeños (`0.65rem`) en las vistas a crédito de ventas e inventarios por el formato de texto destacado (`font-size: 1.05rem; font-weight: 700;`) utilizado en la vista al contado.
* **Archivos modificados:**
  - `app/Views/ventas/ventasIndex.php`
  - `app/Views/ventas/ventasCredito.php`
  - `app/Views/inventarios/ventasIndex.php`
  - `app/Views/inventarios/ventasCredito.php`
  - Eliminación de imágenes antiguas `.png` y `.jpg` e incorporación de los nuevos recursos `.jpeg`.

---

### 2026-06-16 ✅ COMPLETADO
> Fuente: [`TAREAS_2026-06-16.md`](../tareas/completadas/TAREAS_2026-06-16.md)

### TAREA 28 — Manejo dinámico de esquema de base de datos y zona horaria local en pantallas de venta
* **Alcance:** Corrección del error 500 al buscar personal mediante la detección dinámica de las columnas `telefono` y `celular` en `public.personas`. Ajuste de la zona horaria a `America/La_Paz` en la lógica de obtención de la última venta del día.
* **Archivos modificados:** `ventasController.php`, `inventariosController.php`, `ventasAgroController.php`.

### TAREA 27 — Flexibilización de campos obligatorios en el registro de clientes al contado/externos
* **Alcance:** Hacer opcional el campo de nombres al agregar clientes contado o externos desde las pantallas POS de venta. Únicamente el Apellido Paterno y el CI/DIP se mantienen obligatorios.
* **Archivos modificados:** `clienteController.php`, `ventasController.php`, `inventariosController.php`, `ventasAgroController.php`, vistas POS (×6).

### TAREA 26 — Deduplicación de empleados multi-cargo con DISTINCT ON
* **Alcance:** Evitar la duplicidad de registros de venta y reportes para personal UTO con múltiples cargos activos, utilizando `DISTINCT ON (id_persona)` en la unión con `rrhh.empleados`.
* **Archivos modificados:** `ventasController.php`, `inventariosController.php`, `ventasAgroController.php`, `VentaModel.php`.

---

### 2026-06-08 ✅ COMPLETADO
> Fuente: [`TAREAS_2026-06-08.md`](../tareas/completadas/TAREAS_2026-06-08.md)

### TAREA 25 — Sincronización del cargador de imágenes y mapeo de LECHE en pantallas de venta a crédito
* **Alcance:** Sincronización del cargador dinámico de imágenes con prioridad de 4 niveles en las pantallas de venta a crédito del módulo lácteos y mapeo de la imagen de Leche en `LOCAL_IMAGE_MAP`.
* **Archivos modificados:** `ventasCredito.php` (ventas), `ventasIndex.php` (ventas), `ventasCredito.php` (inventarios), `ventasIndex.php` (inventarios).

---

### 2026-06-01 ✅ COMPLETADO
> Fuente: [`TAREAS_2026-06-01.md`](../tareas/completadas/TAREAS_2026-06-01.md)

### TAREA 24 — Rediseño de la hoja secundaria "Balance General" en el Reporte General de Inventarios (Excel y PDF)
* **Alcance:** Reestructuración completa de la segunda hoja del reporte Excel (Balance General) para mostrar bloques mensuales de producción y ventas en formato tabular limpio, y sincronización del reporte PDF.
* **Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

---

### 2026-05-29 ✅ COMPLETADO

### TAREA 23 — Asegurar exclusión de inventarios eliminados en reportes y consultas
> Fuente: [`TAREAS_2026-05-29h.md`](../tareas/completadas/TAREAS_2026-05-29h.md)
* **Alcance:** Extender el filtro `deleted_at IS NULL` aplicado en la Tarea 12 a todos los métodos de consulta de inventarios que no lo incluían, garantizando que los registros eliminados nunca aparezcan en reportes, resúmenes ni en la generación de archivos Excel/PDF.
* **Archivos modificados:** `InventarioModel.php`, `ExportacionExcelService.php`, `ReporteLacteos.php`.

### TAREA 22 — Priorizar columna de LECHE al inicio de los productos dinámicos
> Fuente: [`TAREAS_2026-05-29g.md`](../tareas/completadas/TAREAS_2026-05-29g.md)
* **Alcance:** En los reportes PDF (`ReporteLacteos`) y Excel (`ExportacionExcelService`), el producto `LECHE` (columna simple `LECHE (L)`) ahora siempre aparece en la primera posición de las columnas dinámicas, independientemente del orden alfabético.
* **Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

### TAREA 21 — Formato de decimales y reajuste de totales (Reserva y Alineación)
> Fuente: [`TAREAS_2026-05-29f.md`](../tareas/completadas/TAREAS_2026-05-29f.md)
* **Alcance:** Ajustes de presentación numérica en columnas dinámicas de PDF y Excel: decimales, filas totales de reserva vacías y corrección de alineaciones de subencabezados.
* **Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

### TAREA 20 — Fusión de encabezados y subencabezados de productos en PDF y Excel
> Fuente: [`TAREAS_2026-05-29e.md`](../tareas/completadas/TAREAS_2026-05-29e.md)
* **Alcance:** Reestructuración de encabezados repetitivos fusionándolos y centrándolos sobre las subcolumnas correspondientes (Leche utilizada + Producción). Uso de `SetXY` en PDF y `MergeAcross` en Excel.
* **Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

### TAREA 19 — Tratamiento especial del producto LECHE en el Reporte General
> Fuente: [`TAREAS_2026-05-29d.md`](../tareas/completadas/TAREAS_2026-05-29d.md)
* **Alcance:** El producto `LECHE` en `condoriri.productos` corresponde a materia prima, por lo que se implementó la lógica `esProductoSimple()` para renderizarlo como columna simple con solo `cantidad_produccion` (litros), sin las subcolumnas de Stock, MERMA y AGREGA.
* **Archivos modificados:** `ReporteLacteos.php`, `ExportacionExcelService.php`.

### TAREA 18 — Mejoras al Reporte General de inventario (Excel + PDF)
> Fuente: [`TAREAS_2026-05-29c.md`](../tareas/completadas/TAREAS_2026-05-29c.md)
* **Alcance:** Separadores visuales entre días, fila `TOTAL MES` con fondo amarillo, visualización de `RESERVA` sin acumulación, y mejoras de paginación para evitar encabezados huérfanos.
* **Archivos modificados:** `ExportacionExcelService.php`, `ReporteLacteos.php`.

### TAREA 17 — Acceso del rol `almacen` a la pantalla de gestión de clientes
> Fuente: [`TAREAS_2026-05-29b.md`](../tareas/completadas/TAREAS_2026-05-29b.md)
* **Alcance:** El rol `almacen` ahora puede acceder a `/cliente/lista` desde el sidebar y tiene permisos para editar clientes al contado.
* **Archivos modificados:** `Routes.php`, `sidebar.php`.

### TAREA 16 — Agregar columna `imagen` a `productos_agro`
> Fuente: [`TAREAS_2026-05-29.md`](../tareas/completadas/TAREAS_2026-05-29.md)
* **Alcance:** Soporte de imagen para productos agropecuarios en el grid de ventas POS. Incluye subida de archivo y vista previa inline.
* **Archivos modificados:** `ProductoAgroModel.php`, `ProductosAgroController.php`, `productosAgroFrom.php`, `ventasIndex.php` (agro), migración SQL.

---

### 2026-05-28 ✅ COMPLETADO

### TAREA 15 — Mejoras visuales en pantallas de venta (3 módulos POS contado)
> Fuente: [`TAREAS_2026-05-28d.md`](../tareas/completadas/TAREAS_2026-05-28d.md)
* **Alcance:** Imagen de portada para tarjetas de productos, badge de stock dinámico (verde/amarillo/rojo) y buscador de productos en tiempo real sobre el grid sin recargar página.
* **Archivos modificados:** `ventasIndex.php` (×3 módulos).
