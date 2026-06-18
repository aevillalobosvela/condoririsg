# CHANGELOG — Fase 2 (Abril – Mayo 2026)

Historial integrado de cambios generales y bitácora de tareas completadas correspondiente a la **Fase 2 (Abril – Mayo 2026)** del proyecto Condoriri SG.

---

## Tareas de Desarrollo (Fase 2)

Las siguientes tareas secuenciales corresponden a esta fase de consolidación y reportes avanzados:

### TAREA 14 — Mejoras a la pantalla `/stockinventario`
> Fuente: [`TAREAS_2026-05-28c.md`](../tareas/completadas/TAREAS_2026-05-28c.md)
* **Alcance:** Múltiples mejoras visuales y funcionales a la pantalla de resumen de inventario agrupado:
  - Cards de resumen por producto con color de borde según nivel de stock.
  - Tabla con paginación cliente y búsqueda instantánea por nombre de producto.
  - Gráfico de barras (Chart.js) mostrando stock por producto.
  - Exportación rápida a CSV desde la misma pantalla.
* **Archivos modificados:** `stockinventario.php`, `StockInventarioController.php`.

### TAREA 13 — Bandeja de presets en formulario de creación de productos
> Fuente: [`TAREAS_2026-05-28b.md`](../tareas/completadas/TAREAS_2026-05-28b.md)
* **Alcance:** En el formulario `/productos/register/:inventarioId`, se agrega una bandeja horizontal de chips (presets) con los últimos 5 productos registrados por el usuario en la misma sucursal. Al hacer clic en un chip, se autocompletan todos los campos excepto la cantidad, y el foco se mueve al input de cantidad. Incluye textos de ayuda para usuarios no técnicos.
* **Archivos modificados:** `productosform.php`, `productosController.php` (método `create()`).

### TAREA 12 — Inventarios eliminados siguen visibles en `/inventarios`
> Fuente: [`TAREAS_2026-05-28.md`](../tareas/completadas/TAREAS_2026-05-28.md)
* **Alcance:** Tras hacer soft-delete de un inventario desde `/inventarios/show/{id}`, el registro aparecía nuevamente en la tabla principal. Se añadió el filtro `deleted_at IS NULL` en `inventariosController::index()` y en `InventarioModel::getFilteredInventarios()`.
* **Archivos modificados:** `inventariosController.php`, `InventarioModel.php`.

### TAREA 11 — Deduplicación de empleados en búsqueda de receptor para ventas a crédito
> Fuente: [`TAREAS_2026-05-27e.md`](../tareas/completadas/TAREAS_2026-05-27e.md)
* **Alcance:** En la búsqueda de receptor UTO para ventas a crédito, una misma persona podía aparecer múltiples veces porque tiene varios registros activos en `rrhh.empleados` (multi-cargo). Se corrigió la query para usar `DISTINCT ON (p.id_persona)` y seleccionar el registro activo con mayor `id` (más reciente), eliminando los duplicados en los resultados.
* **Archivos modificados:** `ventasController.php`, `inventariosController.php`, `ventasAgroController.php` (método `buscarPersonalUto()`).

### TAREA 10 — Control de duplicidad del CI/DIP entre fuentes de datos
> Fuente: [`TAREAS_2026-05-27d.md`](../tareas/completadas/TAREAS_2026-05-27d.md)
* **Alcance:** Prevenir la creación de perfiles con el mismo CI/DIP en `condoriri.clientes` y `condoriri.clientes_externos`. Se añadió un constraint `UNIQUE` parcial en `clientes_externos.dip` y validación en el controller antes de insertar un nuevo cliente contado (`clientes.ci_nit`). Corrección manual de duplicados existentes en BD.
* **Archivos modificados:** `clienteController.php`, migración SQL de constraint.

### TAREA 9 — Normalización de nombres en formularios de registro de clientes
> Fuente: [`TAREAS_2026-05-27c.md`](../tareas/completadas/TAREAS_2026-05-27c.md)
* **Alcance:** Los nombres registrados para clientes ahora deben cumplir el formato institucional: `APELLIDO_PATERNO APELLIDO_MATERNO NOMBRE(S)` (mínimo 3 palabras, todo en mayúsculas). Se reemplazó el input único por 3 inputs separados (ap. paterno, ap. materno, nombres) con validación JS en tiempo real. El backend concatena y valida antes de insertar.
* **Archivos modificados:** `ventasIndex.php` (contado ×3), `ventasCredito.php` (×3), `clienteController.php`, `clienteExternoController.php`.

### TAREA 8 — Resumen de crédito del receptor en POS de venta a crédito y recibos
> Fuente: [`TAREAS_2026-05-27b.md`](../tareas/completadas/TAREAS_2026-05-27b.md)
* **Alcance:** En las 3 pantallas de venta a crédito y en los 3 recibos, mostrar un resumen del estado crediticio del receptor en el período activo (del día 11 del mes anterior al día 10 del mes actual). El panel muestra: N° ventas del período, monto acumulado y saldo estimado. En el recibo se imprime como sección adicional de "Estado de cuenta".
* **Archivos modificados:** `ventasController.php`, `inventariosController.php`, `ventasAgroController.php`, `ventasCredito.php` (×3), `recibo_print.php` (×3).

### TAREA 7 — Normalización y completitud del nombre de receptor en reportes Excel de ventas
> Fuente: [`TAREAS_2026-05-27.md`](../tareas/completadas/TAREAS_2026-05-27.md)
* **Alcance:** Los reportes Excel de ventas a crédito mostraban nombres truncados o en formato incorrecto (nombres primero, en lugar de `PATERNO MATERNO NOMBRES`). Se corrigió el `COALESCE` en `VentaModel` para usar `p.nombre` (campo de concatenación correcta `PATERNO MATERNO NOMBRES`) en lugar de `p.nombre_completo` o `p.nombres`.
* **Archivos modificados:** `VentaModel.php` (`getDailySalesReportData()`, `getDailySalesReportDataInve()`, `getDailySalesReportDataAdmin()`).

### TAREA 6 — Columna "Categoría" en reportes PDF y Excel de ventas a crédito
> Fuente: [`TAREAS_2026-05-26.md`](../tareas/completadas/TAREAS_2026-05-26.md)
* **Alcance:** Agregar en los reportes PDF y Excel de ventas **a crédito** (3 módulos: tienda CEAC, planta lácteos, agropecuario) una columna/campo "Categoría" que identifique el tipo de receptor:
  - Personal UTO → `te.tipo_empleado` de `rrhh.tipos_empleados` (ej. `ADMINISTRATIVO`, `DOCENTE TC`, `EVENTUAL`).
  - Cliente externo → segmento (`SEGURO UNIVERSITARIO`, `SPECTROLAB`, `OTROS`).
  - Solo aplica a reportes de **crédito**; los reportes de contado y general no se modifican.
* **Archivos modificados:**
  - `VentaModel.php` — `getDailySalesReportData()`, `getDailySalesReportDataInve()`, `getDailySalesReportDataAdmin()`: `COALESCE(te.tipo_empleado, ce.segmento, NULL) AS receptor_categoria` + JOIN a `rrhh.tipos_empleados`.
  - `CierreVentaPdf.php`, `CierreVentaInve.php`, `CierreVentaAgroPdf.php` — columna "Categoría" (22mm, condicional `$tipo === 'credito'`).
  - `CierreVentaBasePdf.php` — fila "Categoría:" en bloque de datos de crédito.
  - `ExcelVentasMatrizService.php`, `ExcelVentasAgroService.php` — columna `CATEGORÍA` al final de cada fila; `$totalCols` ajustado dinámicamente.

---

## Historial Cronológico de Versiones

## 2026-05-26 ✅ COMPLETADO

### Added
- **Recibos de venta — QR rediseñado (layout 3/4 + 1/4):** sección cliente reestructurada en dos columnas flex — 3/4 para nombre/datos del cliente, 1/4 para el QR alineado a la derecha a la misma altura. Eliminados el label "Documento:", el texto "Escanear para ver CI" y reemplazados por etiqueta compacta "CI" debajo del QR. Estilos obsoletos `.qr-doc-wrapper`, `.qr-doc-hint`, `.qr-doc-label` eliminados de los tres archivos.
- El dato "Documento/DIP" en los 3 recibos de venta (`ventas/`, `inventarios/`, `productosAgro/`) fue reemplazado por un código QR generado en el cliente con `qrcode.min.js` (descargado a `public/assets/js/`). El CI no queda visible en texto en el papel impreso — se escanea el QR para acceder al dato. Aplica a personal UTO, clientes externos y clientes contado. Clientes sin documento no muestran QR.
- **Reportes PDF de ventas — resumen financiero al final:** método `protected renderResumenFinanciero()` agregado a `CierreVentaBasePdf`. Se activa solo en reportes de tipo `contado` y `credito` (no `general`) en los 3 módulos. Bloque 1: N° ventas, monto total, ticket promedio, venta mín/máx, producto más vendido. Bloque 2: top 5 clientes por monto acumulado. Todo calculado desde datos en memoria sin queries adicionales. Salto de página automático si no hay espacio.
- `labelWidth` ahora es dinámico (calculado con `GetStringWidth()` sobre los nombres reales del reporte, entre 40mm y 70mm). Fuente de filas de datos reducida de 7pt a 6pt. Eliminadas declaraciones duplicadas de `$esCredito`/`$wCategoria`. Afecta `CierreVentaPdf`, `CierreVentaInve` y `CierreVentaAgroPdf`.
- Los tres reportes matriciales de ventas (`CierreVentaPdf`, `CierreVentaInve`, `CierreVentaAgroPdf`) cambiaron de A4 portrait a A4 landscape. El ancho útil pasa de 190mm a 277mm, mejorando la legibilidad con múltiples productos y la columna "Categoría". `labelWidth` ampliado de 50mm a 65mm para nombres más largos. Saltos de página internos también actualizados a `AddPage('L', 'A4')`.
- **Reportes de ventas a crédito — columna "Categoría":** los reportes PDF y Excel de ventas a crédito (3 módulos: tienda CEAC, planta lácteos, agropecuario) ahora muestran la categoría laboral/institucional del receptor en cada venta.
  - Personal UTO: muestra `tipo_empleado` de `rrhh.tipos_empleados` (ej. `ADMINISTRATIVO`, `DOCENTE TC`, `DOCENTE TH`, `EVENTUAL`).
  - Clientes externos: muestra el segmento (`SEGURO_UNIV`, `SPECTROLAB`, `OTROS`).
  - Solo aparece en reportes de **crédito** — contado y general no se modifican.
  - **Capa de datos:** `VentaModel::getDailySalesReportData()`, `getDailySalesReportDataInve()` y `getDailySalesReportDataAdmin()` — agregado `COALESCE(te.tipo_empleado, ce.segmento, NULL) AS receptor_categoria` y JOIN a `rrhh.tipos_empleados`.
  - **PDF:** `CierreVentaPdf`, `CierreVentaInve`, `CierreVentaAgroPdf` — columna "Categoría" en tabla matricial (22mm, condicional a `$tipo === 'credito'`). `CierreVentaBasePdf::mostrarVentas()` y `ArqueoVentasPdf::mostrarVentas()` — fila "Categoría:" en bloque de datos del receptor.
  - **Excel:** `ExcelVentasMatrizService` y `ExcelVentasAgroService` — columna `CATEGORÍA` al final de cada fila, `$totalCols` ajustado dinámicamente.

---

## 2026-05-07 ✅ COMPLETADO

### Added
- **Envíos — Ordenamiento de tabla:** botones de orden (Fecha, Código, Estado) encima de cada tab con indicador visual asc/desc. Cada tab mantiene su estado independiente. Por defecto: fecha descendente.
- **Envíos — Reporte Excel global:** `EnviosController::exportarExcelEnvios()` + ruta `GET envios/exportarExcelEnvios`. Subfilas de productos agrupadas por nombre, subtotal por envío, nombre completo del transportista. Botón en barra de filtros (extremo derecho).
- **Ventas — Eliminación de última venta (6 puntos de venta):** `deleteUltimaVenta()` en `ventasController` (hard-delete por `uk_detalle_stock`), `inventariosController` y `ventasAgroController` (soft-delete). Repone stock en `stock_sucursales`, `stock_inve` o `cantidad_inve` según módulo. 3 rutas `POST deleteUltimaVenta`. Botón + modal de confirmación en las 6 vistas (3 contado + 3 crédito).
- **Resumen Global — Excel + PDF:** nueva funcionalidad accesible desde el sidebar (admin y almacen) mediante modal con selector de mes.
  - `app/Services/Shared/ResumenGlobalService.php` — Excel con 4 bloques en una sola hoja: Producción, Ventas (tienda/planta/agro), Envíos, Balance estimado. 12 indicadores entre secciones (días pico, producto más vendido, cliente más frecuente, tasa de entrega, etc.).
  - `app/Libraries/ResumenGlobalPdf.php` — PDF con encabezado institucional UTO/Condoriri, mismos 4 bloques + indicadores, orientación landscape A4.
  - `app/Controllers/reportes/ResumenGlobalController.php` — métodos `exportar()` y `exportarPdf()`.
  - Rutas `GET resumen-global/exportar` y `GET resumen-global/exportarPdf`.
  - Modal en `layouts/main.php` con dos botones (Excel / PDF), visible solo para admin y almacen.
  - Enlace "Resumen Global" en sidebar para roles admin y almacen.

### Fixed
- **Envíos — Filtros de búsqueda no funcionales:** `EnviosController::index()` ignoraba los parámetros GET. Ahora aplica `sucursal_origen_id`, `sucursal_destino_id`, `estado_id`, `fecha_inicio` y `fecha_fin`.
- **Inventarios — `ExportacionExcelVentasService` inexistente:** el controller apuntaba a una clase que no existía. Creado como wrapper de `ExcelVentasMatrizService` con `TIENDA_LACTEOS`.

### Changed
- **Reportes Excel de ventas (3 módulos):** columnas `FECHA` (dd/mm/yyyy) y `ORIGEN` (`SUCURSAL CENTRO` / `PLANTA PRODUCCION` / `OTRO ORIGEN`) agregadas al final de cada fila. Afecta `ExcelVentasMatrizService`, `ExcelVentasAgroService`.
- **Reportes PDF de ventas (3 módulos):** columnas `Fecha` y `Origen` agregadas en `CierreVentaPdf`, `CierreVentaInve` y `CierreVentaAgroPdf`. Anchos de columna recalculados para A4 portrait.
- **ArqueoVentasPdf — reestructuración:** fuentes reducidas ~25% (métodos sobreescritos localmente sin afectar clase base). Anchos dinámicos basados en `$pageW`. Bloque 1 con desglose contado/crédito por producto (doble encabezado, 6 columnas, fila TOTAL). Bloque 4 dividido en dos tablas separadas (contado / crédito) con sub-fila de códigos de venta y "Ventas sin cliente registrado" al final. Resumen financiero con columna `N° Ventas`.

---

## 2026-05-05 ✅ COMPLETADO

### Added
- **Envíos — Ordenamiento de tabla:** botones de orden visibles encima de cada tab (Fecha, Código, Estado) con indicador de dirección asc/desc. Cada tab mantiene su estado de ordenamiento independiente. Por defecto: fecha descendente (más reciente primero). Funciona sobre las filas ya filtradas por el servidor.
- **Envíos — Reporte Excel global:** nuevo método `exportarExcelEnvios()` en `EnviosController` y ruta `GET envios/exportarExcelEnvios`. Acepta los mismos filtros del índice (`sucursal_origen_id`, `sucursal_destino_id`, `estado_id`, `fecha_inicio`, `fecha_fin`) más `sort_by` y `sort_dir`. Incluye subfilas de productos agrupadas por nombre (SUM cantidad, SUM subtotal), fila de subtotal por envío con borde superior grueso, nombre completo del transportista (`nombre || apellidos`), y subtítulo con filtros y orden aplicados. Botón "Reporte general de envíos" en la barra de filtros (extremo derecho) con estilo gradiente verde, hint de texto y hover con elevación.

### Fixed
- **Envíos — Filtros de búsqueda no funcionales:** `EnviosController::index()` ignoraba los parámetros GET del formulario. Ahora aplica `sucursal_origen_id`, `sucursal_destino_id`, `estado_id`, `fecha_inicio` y `fecha_fin` como condiciones al query antes del `findAll()`.

---

## 2026-05-05 ✅ COMPLETADO

### Changed
- **Inventarios — Reporte General PDF — resumen final reemplazado:** el antiguo "RESUMEN GENERAL" (stock/agrega/merma totales + stock por producto) fue reemplazado por los mismos 3 bloques del Excel en páginas propias al final del PDF:
  1. **Desglose por mes → producto** con subtotal por mes y total general. Separador visual (borde grueso) entre grupos de mes.
  2. **Consolidado por producto** (una fila por producto, rango de meses en col. MES).
  3. **Consolidado por mes** (una fila por mes: unidades producidas, ingresos contado/crédito/total).
  - Columnas bloques 1 y 2: MES | PRODUCTO | PRODUCIDO | MERMA | AGREGA | VTA. CONTADO | VTA. CRÉDITO | TOTAL VENDIDO | ING. CONTADO | ING. CRÉDITO | TOTAL INGRESOS.
  - Ventas reales obtenidas via SQL (`detalle_venta JOIN ventas`, `sucursal_id=4`, excluyendo agro).
  - Helpers nuevos en `ReporteLacteos`: `resumenTitulo()`, `resumenEncabezadoColumnas()`, `resumenFila()`, `resumenFilaTotal()`, `textoMesCorto()`, `textoRangoPdf()`.

---

## 2026-05-05 ✅ COMPLETADO

### Changed
- **Inventarios — Reporte General Excel — hoja "Resumen Detallado" reestructurada (v2):** eliminado el bloque de materia prima; la hoja queda con 2 bloques de producción/ventas con la misma estructura de columnas pero diferente agrupación:
  1. **Desglose por mes:** filas ordenadas mes → producto. Separador visual (borde superior azul grueso) entre cada cambio de mes.
  2. **Desglose por producto:** mismas columnas, filas ordenadas producto → mes. Separador visual entre cada cambio de producto.
  - Columnas de ambos bloques: MES | PRODUCTO | PRODUCIDO | MERMA | AGREGA | VENDIDO | STOCK RESTANTE | INGRESOS (Bs) | P. PROM. VENTA.
  - Lógica compartida extraída a métodos reutilizables: `renderEncabezadosProduccionVentas()`, `renderFilaTotalesProduccionVentas()`, `renderFilaProduccionVentas()`.
  - 4 estilos nuevos `resumen_sep_*` con borde superior `weight=2` color `#4D6A83` para los separadores de grupo.
  - Bloque 2 nuevo — **Consolidado por mes:** una fila por mes con litros usados en producción, unidades producidas totales, ingresos contado, ingresos crédito y total ingresos. Query SQL extendida con `tipo_pago` para separar contado/crédito. Fila TOTAL GENERAL al pie.
  - Bloque 3 (antes bloque 2) — Consolidado por producto: sin cambios de lógica, renumerado.

---

## 2026-05-05 ✅ COMPLETADO

### Added
- **Inventarios — Reporte General Excel — hoja de resumen detallado:** segunda hoja `Resumen Detallado` agregada al archivo Excel del Reporte General con 3 tablas usando los mismos estilos visuales de la hoja principal:
  1. **Resumen mensual de leche (materia prima):** por mes — registros, litros recibidos, promedio diario, reserva total, turnos AM/PM, días activos.
  2. **Producción mensual por producto:** filas = mes+producto. Columnas: unidades producidas, litros usados (`cantidad_produccion × cantidad_unidad`), merma, agrega, valor contado y valor crédito (`cantidad_produccion × precio`). Fila TOTAL GENERAL al final.
  3. **Eficiencia de conversión leche→producto por mes:** litros recibidos vs litros usados, % de aprovechamiento, diferencia, valor contado/crédito y observación automática (Eficiencia alta ≥95%, media ≥80%, Revisar mermas/proceso).
  - Métodos nuevos: `construirResumenDetalladoMensual()`, `generarWorksheetResumenDetallado()`, `renderBloqueResumenLeche()`, `renderBloqueProduccionMensual()`, `renderBloqueEficiencia()`, `textoMes()`.
  - `prepararDatosConProductos()` extendido con campos `litros_usados`, `valor_contado`, `valor_credito` por producto.

### Changed
- **Inventarios — Refactorización `ExportacionExcelService`:** `definirEstilos()` reducido de 556 a ~90 líneas mediante helper interno `estilo(string $id, array $config)` y `crearBordes()`. El archivo pasó de 1153 a 926 líneas (−20%). Sin cambios en estilos ni funcionalidad existente.

---

## 2026-05-05

### Changed
- **Inventarios — Reporte General PDF — ajuste de fuentes y columna NOMBRE:** en `app/Libraries/ReporteLacteos.php`, fuente de encabezados de columnas dinámicas reducida de 7pt a 6pt; fuente de datos numéricos (Stock/Cant.Prod) de columnas dinámicas reducida de 7pt a 6pt; `wNombre` reducido de 30mm a 21mm (−30%). `calcularAlturaEncabezado()` alineado a 6pt para consistencia. Mejora la legibilidad con 8-9 productos sin desbordar el ancho A3 landscape.

---

## 2026-05-04

### Added
- **Inventarios — Eliminación del último inventario:** botón "Eliminar este inventario" en `inventariosShow.php`, visible solo si el registro es el último del usuario hoy y no tiene productos asociados. Modal de confirmación con datos del inventario. Método `deleteUltimoInventario()` en `inventariosController` con transacción: soft-delete + recálculo de reserva LECHE del inventario anterior. Ruta `POST inventarios/deleteUltimo`.
- **Inventarios — Eliminación del último producto:** botón "Eliminar" en el árbol de productos de `inventariosShow.php`, visible solo en el último producto del usuario hoy sin subproductos ni ventas (`detalle_venta`). Modal de confirmación. Método `deleteUltimo()` en `productosController` con transacción: soft-delete + restauración de `cantidad_unidad` a `inventarios.reserva`. Ruta `POST productos/deleteUltimo`.
- **Inventarios — Botones de acción con texto:** los botones del árbol de productos (Editar, Merma, Agregar, Subproducto, Eliminar) ahora muestran icono + etiqueta de texto. Se cambiaron botones sólidos por variantes `outline` para reducir peso visual. `.product-actions` usa `flex-wrap` para adaptarse a múltiples botones.
- **Inventarios — Reporte General Excel — merma y agrega por producto:** las columnas fijas `AGREGA` y `MERMA` del inventario fueron eliminadas. Cada producto dinámico ahora incluye 4 columnas: `Stock | Cant.Prod | M. | Ag.`. Las columnas M. y Ag. son compactas (ancho 42px, fuente 9pt, color diferenciado morado) para minimizar el ancho total del documento. El separador grueso entre grupos de producto se ubica en la columna Ag. La fila `TOTAL MES` y la sección de resumen acumulan merma/agrega desde los datos individuales de cada producto.

### Fixed
- **Inventarios — Generación de código tras soft-delete:** `generateUniqueCode()` en `inventariosController` ahora usa `->withDeleted()` al buscar códigos existentes, evitando colisiones con registros eliminados que conservan su código único en la BD.

### Changed
- **Productos — Redirección tras edición:** `productosController::update()` ahora redirige a `inventarios/show/{inventario_id}` en lugar de `/productos`, usando el `inventario_id` del producto existente.
- **Productos — Formulario de creación (barra de reserva):** eliminado el color rojo del indicador de uso de reserva. El estado crítico (100% de uso) ahora usa amarillo igual que el estado de advertencia (≥90%), tanto en la barra como en el panel de resultados y el badge de estado.
- **Inventarios — Reporte General Excel/PDF (solo LECHE):** ambos reportes ahora consideran exclusivamente registros con `nombre = 'LECHE'`.
- **Inventarios — Reporte Suero y Otros (Excel/PDF):** se añadió exportación separada para `nombre != 'LECHE'` con dropdown dedicado en la interfaz (`Excel` y `PDF`).
- **Inventarios — orden por turno en reportes:** en Reporte General, Reporte Suero y Otros y Control de Calidad se mantiene la agrupación por día y, dentro del día, se ordena `AM` antes de `PM`.
- **Inventarios — Reporte General Excel:** nueva paleta visual suave, columnas dinámicas con `-` cuando no hay dato del producto, separación de stock por `LECHE` vs no `LECHE`, y cierre mensual con `TOTAL MES` (sin promedio en ajuste final).
- **Inventarios — Reporte General PDF:** se alineó el formato visual con el Excel (paleta suave), se añadió `TOTAL MES`, soporte de `-` in columnas dinámicas sin producto y corrección de paginación para evitar encabezados huérfanos al pie de página.
