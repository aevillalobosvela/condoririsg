# CHANGELOG

All notable changes to **Condoriri SG** are documented in this file.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) conventions.

---

## [Unreleased]

## 2026-05-26 ✅ COMPLETADO

### Added
- **Reportes PDF de ventas — orientación landscape (A4 horizontal):** los tres reportes matriciales de ventas (`CierreVentaPdf`, `CierreVentaInve`, `CierreVentaAgroPdf`) cambiaron de A4 portrait a A4 landscape. El ancho útil pasa de 190mm a 277mm, mejorando la legibilidad con múltiples productos y la columna "Categoría". `labelWidth` ampliado de 50mm a 65mm para nombres más largos. Saltos de página internos también actualizados a `AddPage('L', 'A4')`.

### Added
- **Reportes de ventas a crédito — columna "Categoría":** los reportes PDF y Excel de ventas a crédito (3 módulos: tienda CEAC, planta lácteos, agropecuario) ahora muestran la categoría laboral/institucional del receptor en cada venta.
  - Personal UTO: muestra `tipo_empleado` de `rrhh.tipos_empleados` (ej. `ADMINISTRATIVO`, `DOCENTE TC`, `DOCENTE TH`, `EVENTUAL`).
  - Clientes externos: muestra el segmento (`SEGURO_UNIV`, `SPECTROLAB`, `OTROS`).
  - Solo aparece en reportes de **crédito** — contado y general no se modifican.
  - **Capa de datos:** `VentaModel::getDailySalesReportData()`, `getDailySalesReportDataInve()` y `getDailySalesReportDataAdmin()` — agregado `COALESCE(te.tipo_empleado, ce.segmento, NULL) AS receptor_categoria` y JOIN a `rrhh.tipos_empleados`.
  - **PDF:** `CierreVentaPdf`, `CierreVentaInve`, `CierreVentaAgroPdf` — columna "Categoría" en tabla matricial (22mm, condicional a `$tipo === 'credito'`). `CierreVentaBasePdf::mostrarVentas()` y `ArqueoVentasPdf::mostrarVentas()` — fila "Categoría:" en bloque de datos del receptor.
  - **Excel:** `ExcelVentasMatrizService` y `ExcelVentasAgroService` — columna `CATEGORÍA` al final de cada fila, `$totalCols` ajustado dinámicamente.

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

## 2026-05-05 ✅ COMPLETADO

### Added
- **Envíos — Ordenamiento de tabla:** botones de orden visibles encima de cada tab (Fecha, Código, Estado) con indicador de dirección asc/desc. Cada tab mantiene su estado de ordenamiento independiente. Por defecto: fecha descendente (más reciente primero). Funciona sobre las filas ya filtradas por el servidor.
- **Envíos — Reporte Excel global:** nuevo método `exportarExcelEnvios()` en `EnviosController` y ruta `GET envios/exportarExcelEnvios`. Acepta los mismos filtros del índice (`sucursal_origen_id`, `sucursal_destino_id`, `estado_id`, `fecha_inicio`, `fecha_fin`) más `sort_by` y `sort_dir`. Incluye subfilas de productos agrupadas por nombre (SUM cantidad, SUM subtotal), fila de subtotal por envío con borde superior grueso, nombre completo del transportista (`nombre || apellidos`), y subtítulo con filtros y orden aplicados. Botón "Reporte general de envíos" en la barra de filtros (extremo derecho) con estilo gradiente verde, hint de texto y hover con elevación.

### Fixed
- **Envíos — Filtros de búsqueda no funcionales:** `EnviosController::index()` ignoraba los parámetros GET del formulario. Ahora aplica `sucursal_origen_id`, `sucursal_destino_id`, `estado_id`, `fecha_inicio` y `fecha_fin` como condiciones al query antes del `findAll()`.

## 2026-05-05 ✅ COMPLETADO

### Changed
- **Inventarios — Reporte General PDF — resumen final reemplazado:** el antiguo "RESUMEN GENERAL" (stock/agrega/merma totales + stock por producto) fue reemplazado por los mismos 3 bloques del Excel en páginas propias al final del PDF:
  1. **Desglose por mes → producto** con subtotal por mes y total general. Separador visual (borde grueso) entre grupos de mes.
  2. **Consolidado por producto** (una fila por producto, rango de meses en col. MES).
  3. **Consolidado por mes** (una fila por mes: unidades producidas, ingresos contado/crédito/total).
  - Columnas bloques 1 y 2: MES | PRODUCTO | PRODUCIDO | MERMA | AGREGA | VTA. CONTADO | VTA. CRÉDITO | TOTAL VENDIDO | ING. CONTADO | ING. CRÉDITO | TOTAL INGRESOS.
  - Ventas reales obtenidas via SQL (`detalle_venta JOIN ventas`, `sucursal_id=4`, excluyendo agro).
  - Helpers nuevos en `ReporteLacteos`: `resumenTitulo()`, `resumenEncabezadoColumnas()`, `resumenFila()`, `resumenFilaTotal()`, `textoMesCorto()`, `textoRangoPdf()`.

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


### Changed
- **Inventarios — Reporte General Excel/PDF (solo LECHE):** ambos reportes ahora consideran exclusivamente registros con `nombre = 'LECHE'`.
- **Inventarios — Reporte Suero y Otros (Excel/PDF):** se añadió exportación separada para `nombre != 'LECHE'` con dropdown dedicado en la interfaz (`Excel` y `PDF`).
- **Inventarios — orden por turno en reportes:** en Reporte General, Reporte Suero y Otros y Control de Calidad se mantiene la agrupación por día y, dentro del día, se ordena `AM` antes de `PM`.
- **Inventarios — Reporte General Excel:** nueva paleta visual suave, columnas dinámicas con `-` cuando no hay dato del producto, separación de stock por `LECHE` vs no `LECHE`, y cierre mensual con `TOTAL MES` (sin promedio en ajuste final).
- **Inventarios — Reporte General PDF:** se alineó el formato visual con el Excel (paleta suave), se añadió `TOTAL MES`, soporte de `-` en columnas dinámicas sin producto y corrección de paginación para evitar encabezados huérfanos al pie de página.

## 2026-04-28

### Added
- **Docs — estructura de tareas**: creada carpeta `docs/tareas/` con archivos de seguimiento por tarea (`EDICION_ULTIMO_REGISTRO.md`, `VENTAS_EDICION_ULTIMO_REGISTRO.md`, `REPORTES_INVENTARIOS.md`) para documentar decisiones, reglas de negocio y estado de implementación.

---

## 2026-04-27

### Added
- **Edición último registro — Ventas a crédito (3 módulos)**: panel "Última venta" y modal de edición implementados en las 3 vistas de crédito (`ventas/ventasCredito.php`, `inventarios/ventasCredito.php`, `productosAgro/ventasCredito.php`). Soporta cambio de receptor entre tipo `uto` y tipo `externo`; al cambiar tipo el campo que no aplica se pone en `null`.
- **Edición último registro — Clientes externos**: método `updateExterno()` en `clienteController` con validación de último registro del usuario hoy. Panel `#clienteExternoEditPanel` en las 3 vistas de crédito con campos `nombre`, `dip`, `segmento`. Aparece automáticamente al crear un externo nuevo.
- **Edición último registro — Inventarios de leche**: métodos `updateCantidad()` y `updateCalidad()` en `inventariosController`. Formularios inline en `inventariosShow.php`. Recalcula `reserva` al editar cantidad. Edición de calidad restringida a `rol_id` 1 y 3 sin restricción de día.
- **Edición último registro — Productos (mejora de creación)**: en `productosform.php` se agregó panel de reserva con barra de progreso visual (verde/amarillo ≥90%/rojo=100%), badge de estado del cálculo, botón "Crear Producto" deshabilitado hasta cálculo válido, y modal de confirmación con resumen completo antes del POST.

### Changed
- **`buscarPersonalUto()` (3 controllers)**: ahora incluye `user_id` y `created_at` en los resultados de `clientes_externos`, necesarios para la evaluación de condiciones de edición en el JS.
- **Edición último registro — Ventas a contado (3 módulos)**: panel "Última venta" y modal de edición ya implementados en las 3 vistas de contado (`ventasIndex.php` × 3) con soporte completo de transacción (devolver stock → validar → soft-delete detalles → insertar nuevos → descontar stock → actualizar monto).
- **`Routes.php`**: rutas `POST cliente/updateExterno`, `POST inventarios/update-cantidad/(:num)`, `POST inventarios/update-calidad/(:num)` registradas con filtros de rol correspondientes.

---

## 2026-04-17

### Fixed
- **Campo de nombre personal UTO — inconsistencia en 3 módulos**: el campo `p.nombre_completo` de `public.personas` (formato `NOMBRES PATERNO MATERNO`) fue reemplazado por `p.nombre` (formato `PATERNO MATERNO NOMBRES`) en todas las queries que referencian personal UTO. El campo `nombre_completo` tenía orden incorrecto; `nombre` es la concatenación con el orden deseado.
- **`ventasAgroController::generarRecibo()` — nombre truncado**: el SELECT usaba `p.nombre` (nombres de pila solamente, campo `nombres`) en lugar de `p.nombre_completo`. Corregido a `p.nombre` (la concatenación completa con apellidos primero). El recibo de agro mostraba solo los nombres de pila del personal UTO.

### Changed
- **`buscarPersonalUto()` — los 3 módulos** (`ventasController`, `inventariosController`, `ventasAgroController`): SELECT cambiado de `p.nombre_completo AS nombre` a `p.nombre AS nombre`; cláusula WHERE cambiada de `p.nombre_completo ILIKE ?` a `p.nombre ILIKE ?`. La barra de búsqueda ahora devuelve y busca por el formato `PATERNO MATERNO NOMBRES`.
- **`guardarCreditoVenta()` validación receptor — los 3 módulos**: SELECT cambiado de `p.nombre_completo AS nombre` a `p.nombre AS nombre`.
- **`generarRecibo()` — los 3 módulos**: SELECT cambiado de `p.nombre_completo` a `p.nombre` en la query de datos del personal UTO.
- **Vistas de recibo** (`ventas/recibo_print.php`, `inventarios/recibo_print.php`, `productosAgro/recibo_print.php`): `$personal['nombre_completo']` → `$personal['nombre']`.
- **Listado de ventas — los 3 módulos**: query principal cambiada de `p.nombre_completo AS nombre_personal` a `p.nombre AS nombre_personal`.
- **`VentaModel::getDailySalesReportData()` y `getDailySalesReportDataInve()`**: COALESCE de `cliente_nombre` y `personal_nombre` actualizados de `p.nombre_completo` a `p.nombre`.

## 2026-04-02

### Added

- se cambia el sidebar y se agrega arqueo

---

## 2026-04-01

### Fixed
- **`productosAgroIndex` — `mb_strimwidth()` error**: replaced call to `mb_strimwidth()` (requires `mbstring` extension) with a `substr()`-based equivalent for truncating product descriptions. No functional change.

### Added
- **Recibos — columna Unidad** (`ventas/recibo_print.php`, `inventarios/recibo_print.php`): agregada columna "Unidad" entre "Producto" y "P.U." en la tabla de detalle de productos. La unidad se resuelve mediante un mapa hardcodeado por nombre de producto (LECHE → LITRO, QUESO → PIEZA, YOGURT/REQUESON/LACTOFRUIT → BOLSA, etc.). Cubre variantes `LACTOFRUIT` y `LACTOFRUT`. Fallback `UND` para nombres no contemplados. Anchos de columna redistribuidos (12+33+15+18+22 = 100%).
- **Recibo Agro — columna Unidad** (`productosAgro/recibo_print.php`): agregada la misma columna "Unidad" en el recibo de ventas agropecuarias. A diferencia de los módulos lácteos, la unidad se obtiene dinámicamente desde la tabla `condoriri.unidades` mediante `LEFT JOIN` en `ventasAgroController::generarRecibo()` (campo `unidad_nombre`). Fallback `UND`.

### Changed
- **`ventasAgroController::generarRecibo()`**: la query de detalles del recibo ahora incluye `LEFT JOIN condoriri.unidades u ON u.id = SS.unidad_id` y selecciona `u.nombre AS unidad_nombre`.

---

## 2026-03-31

### Fixed
- **Búsqueda de receptor en crédito — límite combinado**: `buscarPersonalUto` en los 3 módulos ahora aplica `array_slice` al resultado combinado UTO + externos, garantizando máximo 3 resultados totales (antes podían ser hasta 6).
- **`ventasController::credito()` — bug de concatenación**: corregido `->where('stock' . '>' . 0)` por `->where('stock >', 0)`. Además se agrega filtro por `sucursal_id = 2` que faltaba.
- **`ventasAgroController::guardarCreditoVenta()` — validación de estado vacía**: el bloque de validación de estado del producto estaba vacío (comentario sin código). Ahora lanza excepción si `$producto->estado` es falso.

### Changed
- **Tablas de ventas — columna Código**: las 3 vistas de gestión de ventas (`/ventas`, `/inventarios/ventas`, `/productosagro/ventas`) reemplazaron la columna `Nro` (id numérico) por el campo `code` de la venta (ej. `SC-CO-000012`).
- **Tablas de ventas — cliente externo visible**: las queries de listado en los 3 controllers ahora incluyen `LEFT JOIN condoriri.clientes_externos` y las vistas muestran nombre, DIP y segmento del cliente externo en lugar de "Consumidor Final".
- **Tablas de ventas — esquema de colores independiente**: filas de tabla usan cian `#cffafe`/`#06b6d4` para contado y naranja `#ffedd5`/`#f97316` para crédito, desacoplado de los colores de las cards.
- **Reportes PDF — columna Nro. Venta**: los 3 PDFs (`CierreVentaPdf`, `CierreVentaInve`, `CierreVentaAgroPdf`) y los 2 servicios Excel (`ExcelVentasMatrizService`, `ExcelVentasAgroService`) ahora muestran el `code` completo en lugar del `id` numérico.
- **Reportes PDF — encabezado rango de ventas**: el texto "N° Venta: X al X" extrae solo el número final del code (`substr` + `strrpos`) para mostrar el rango limpio (ej. `1 al 15`).
- **Reportes PDF — ancho columna Nro. Venta**: `$wNota` aumentado de 18 a 22 (+22%); `$wTotal` reducido de 13 a 10 y header cambiado de `TOTAL` a `T` para compensar.
- **Búsqueda de receptor en crédito — límite por query**: `LIMIT 3` aplicado individualmente en las queries de UTO y externos en los 3 controllers.
- **Separación ventas agro / lácteos**: `ventasController::index()` e `inventariosController::indexVenta()` excluyen ventas agro con `NOT EXISTS (... producto_agro_id IS NOT NULL)` en conteos, totales y listado. `VentaModel::getDailySalesReportData()` acepta parámetro `$soloAgro` para filtrar en reportes. `getDailySalesReportDataInve()` también excluye ventas agro.
- **`ventasAgroController::index()` — filtro corregido**: reemplazado filtro por `user_id` por `sucursal_id` de sesión + `EXISTS (... producto_agro_id IS NOT NULL)`. URL de paginación corregida de `ventas` a `productosagro/ventas`.
- **Buscador de receptor unificado**: los 3 módulos de crédito muestran resultados de `public.personas` (badge azul, tipo `uto`) y `condoriri.clientes_externos` (badge amarillo, tipo `externo`) en lista clickeable.
- **Modal alta rápida cliente externo**: disponible en las 3 rutas de crédito con campos nombre, DIP y segmento. Verifica duplicados por DIP antes de insertar.
- **`condoriri.ventas` — campo `cliente_externo_id`**: nuevo campo para referenciar clientes externos, paralelo a `personal_uto_id`.
- **Recibos — cliente externo**: los 3 `generarRecibo` cargan datos de `clientes_externos` cuando aplica; las 3 vistas de recibo muestran nombre, DIP y segmento.

### Added
- **`condoriri.clientes_externos`**: nueva tabla para personas ajenas a la UTO pero vinculadas institucionalmente (Seguro Universitario, Spectrolab). No modifica `public.personas` ni `condoriri.clientes`.
- **`ClienteExternoModel`**: `app/Models/ClienteExterno/ClienteExternoModel.php` con soft delete.
- **Rutas `guardarClienteExterno`**: registradas en los 3 grupos (`ventas`, `inventarios`, `productosagro`).
- **Migración**: `database/migrations/crear_clientes_externos.sql`.

### Removed
- **Rutas huérfanas `buscar-clientes`, `buscar-productos`, `guardar-cliente`**: eliminadas de los grupos `ventas` e `inventarios` en `Routes.php` (métodos inexistentes en los controllers).

---

## 2026-03-30

### Added
- **Clientes externos con crédito**: nueva tabla `condoriri.clientes_externos` para registrar personas ajenas a la UTO pero vinculadas institucionalmente (Seguro Universitario, Spectrolab, etc.). No modifica `public.personas` ni `condoriri.clientes`.
- **`condoriri.ventas` — campo `cliente_externo_id`**: campo nuevo paralelo a `personal_uto_id` para referenciar clientes externos en ventas a crédito.
- **`ClienteExternoModel`**: nuevo modelo en `app/Models/ClienteExterno/ClienteExternoModel.php` con soft delete.
- **Alta rápida de cliente externo**: los 3 módulos de venta a crédito (`/ventas/credito`, `/inventarios/credito`, `/productosagro/credito`) incluyen un botón "Nuevo externo" que abre un modal para registrar nombre, DIP y segmento sin salir de la pantalla de venta.
- **Buscador unificado**: `buscarPersonalUto` en los 3 controllers ahora devuelve resultados de `public.personas` (tipo `uto`) y `condoriri.clientes_externos` (tipo `externo`) en un solo array, diferenciados por badge en la vista.
- **Rutas `guardarClienteExterno`**: registradas en los 3 grupos (`ventas`, `inventarios`, `productosagro`).

### Changed
- **`guardarCreditoVenta` en los 3 controllers**: refactorizado para aceptar `tipo_receptor` (`uto` | `externo`) y poblar `personal_uto_id` o `cliente_externo_id` según corresponda.
- **Vistas `ventasCredito.php`** (3 módulos): buscador muestra lista de resultados clickeables en lugar de seleccionar automáticamente el primero; campo hidden `tipo_receptor` añadido al formulario.
- **`buscarPersonalUto` en `ventasAgroController`**: corregido `p.nombre` → `p.nombre_completo AS nombre` en SELECT y WHERE (bug previo que devolvía nombre truncado).

### Fixed
- **`ventasController.guardarCreditoVenta`**: eliminado `var_dump()` de depuración que quedó activo en producción.

### Migration
- `database/migrations/crear_clientes_externos.sql`

---

## 2026-03-30

### Added
- **Agro — alta rápida de unidad**: implementado `storeUnidadRapida()` en el controlador de productos agro. Ruta registrada con control de acceso por rol. Vista `productosAgroFrom` actualizada con modal de alta rápida de unidad.
- **Unidades — vista de tabla**: agregada vista de tabla para unidades con renderizado condicional y botones de acción.
- **Unidades — campo `tipo` en modelo**: añadido `tipo` a `allowedFields` en `UnidadModel`.

### Changed
- **`ventasAgroController` — optimización de productos**: mejorada la consulta de productos para incluir el nombre de la unidad; vistas actualizadas para mostrar la unidad de medida.
- **Agro — filtro de categoría en ventas**: añadido filtro por categoría en el listado de productos de ventas agro con lógica de renderizado actualizada.
- **Agro — selección de categoría con botones visuales**: reemplazado el selector de categoría por botones visuales con mecanismo de feedback mejorado.
- **Agro — tabla de productos**: ajustado tamaño de fuente en cabecera y añadida columna `Unidad` en `productosAgro`.
- **Unidades — índice**: `unidadesIndex` ahora muestra `Tipo` en lugar de `Descripción` con badges condicionales.
- **Productos — filtro de unidades por tipo y estado**: `ProductosController` y `ProductosAgroController` filtran unidades por `tipo` y `estado` activo.
- **Agro — cantidad e inventario en formulario**: actualizada lógica del input de cantidad y visualización de stock en `productosAgroFrom`.
- **Unidades — formulario**: mejorado `unidadesform` con estilos, estructura de formulario y validación.
- **Agro — búsqueda de personal UTO**: simplificado input de búsqueda eliminando spinner redundante y ajustando layout; mejorado feedback con spinner de carga y selección de resultado.

---

## 2026-03-27

### Added quick
- **Ventas Agro — numeración independiente**: implementadas 4 secuencias PostgreSQL independientes para ventas agro (por sucursal y tipo de pago). Nuevo método `generarCodigoAgroVenta()` en `VentaModel`. Códigos con formato `AG-{SUCURSAL}-{TIPO}-{6_DÍGITOS}` (ej. `AG-SC-CO-000001`). Migración incluida en `2026-03-26-000001_CreateAgroVentaSequences.php`.
- **Docs — arquitectura y contexto**: agregados `ARCHITECTURE.md`, `PDF_REPORTS.md`, `VENTAS_AGRO.md` y skills de Kiro (`.kiro/skills/`) para facilitar el onboarding y compartir contexto del proyecto.

### Changed
- **`ventasAgroController`**: actualizado para usar la nueva numeración agro en lugar de los códigos genéricos de venta.

### Removed
- **Assets no utilizados — imágenes de plantilla** (896 archivos): eliminadas imágenes del template que no se usan; se conservan únicamente favicon, logos, marca de agua y sidebar.
- **Assets no utilizados — SCSS y librerías JS/CSS** (1633 archivos): eliminadas fuentes SCSS del template y 38 librerías JS/CSS no utilizadas.
- **Reorganización de raíz**: movidos `docs/` y `docker/` a sus carpetas correspondientes; eliminados archivos sueltos innecesarios en la raíz del proyecto.

---

## 2026-03-25

### Added
- **Agro — acceso desde otros roles**: habilitado el acceso al módulo agro para roles adicionales. Actualizados `Routes.php` y `sidebar.php`.

---

## 2026-03-17

### Added
- **`CODIGOS_VENTA.md`**: documentación del esquema de numeración de ventas, secuencias y mapeo por módulo.

### Changed
- **Formato de códigos de venta**: actualizado el formato de generación de códigos en `VentaModel` para todos los módulos.

---

## 2026-03-05

### Added
- **Numeración por secuencias**: implementada función de numeración diferente por secuencias PostgreSQL para ventas e inventarios. Migración SQL en `database/migrations/crear_secuencias_ventas.sql`. Actualizado `ventasController` e `inventariosController` para usar las nuevas secuencias.

---

## 2026-03-25

### Added
- **Agro — Excel report (matricial)**: created `app/Services/Agro/ExcelVentasAgroService.php` with the same matrix format as the lacteos module (products as columns, sales as rows). Replaces the previous flat list export. Title reads "CONDORIRI AGROPECUARIO" and units default to "UND".
- **Agro — PDF report**: created `app/Libraries/Agro/CierreVentaAgroPdf.php` extending `CierreVentaBasePdf`. Same matrix layout as lacteos PDF. Title and signature line updated to "Responsable - Productos Agropecuarios". Includes generating user name.
- **Agro — PDF route**: registered missing `productosagro/exportarPdfVentas` route in `Routes.php`.
- **Agro — product list redesign** (`productosAgroIndex.php`): added 4 stat cards (total, with stock, low stock ≤5, no stock); filter bar with text search, stock filter, category filter, and sort dropdown; column-header click sorting; color dot per product name (palette of 10); semantic stock badges (green/amber/red); `fecha_creacion` column; redesigned action buttons matching agro color scheme; JS-driven filtering and ordering without page reload.
- **Agro — product form redesign** (`productosAgroFrom.php`): two-column layout (identification left in blue / prices+inventory right in green) eliminating vertical scroll; `tabindex` order with Enter-key navigation between fields; automatic uppercase on name and category fields.
- **Agro — quick-register templates**: `create()` now passes the last 5 products registered by the user's branch. A yellow chip bar appears above the form; clicking a chip pre-fills all fields except quantity and moves focus to the quantity input. Includes two explanatory helper texts for non-technical users.
- **Agro — Duplicate button**: added "Duplicar" button per row in the product list. Links to `productosagro/create?from=ID`; controller loads that product as `$base` and pre-fills the form, leaving quantity blank.
- **Agro — sales register view** (`ventasIndex.php`): eliminated static PHP grid in favor of JS-only rendering; added `getProductColor()` with 7-color deterministic palette per product name; cards now have colored left border, gradient background, colored circle with initials, colored price, and stock badge `Stock: X und`; `fecha_creacion` shown below stock badge; null-check on `ci_nit` in `buscarClientes()`.
- **Agro — credit sales view** (`ventasCredito.php`): same card improvements as contado view; personal UTO search now accepts name or CI (minimum 3 characters).
- **Agro — confirm sale modal**: upgraded to `modal-lg` with product list rows (name + quantity × price on left, subtotal on right), `row/col-6` layout for payment details, `fw-bold` intro text.
- **Agro — receipt** (`recibo_print.php`): unified with lacteos receipt format — `font-weight:bold` globally, watermark, `info-line` CSS classes, dashed separators, flex product rows, institutional header (UNIVERSIDAD TECNICA DE ORURO / CENTRO EXP. AGROPECUARIO CONDORIRI), "Atendido por" footer with signature line.
- **Agro — stock filter on sale views**: `register()` and `credito()` in `ventasAgroController` now filter `cantidad_inve > 0`, hiding out-of-stock products from the POS grid.
- **Agro — product creation date on sale cards**: `fecha_creacion` displayed below stock badge in both `ventasIndex.php` and `ventasCredito.php`.

### Changed
- **Agro — `buscarPersonalUto()`**: query updated from `p.dip ILIKE ?%` to `(p.dip ILIKE %?% OR p.nombre ILIKE %?%)` enabling partial search by name or CI.
- **Agro — `exportarExcelVentas()`**: replaced inline flat-list XLS generation with delegation to `ExcelVentasAgroService`.
- **Agro — `exportarPdfVentas()`**: replaced `CierreVentaPdf` with `CierreVentaAgroPdf`; now resolves generating user name from `condoriri.usuarios`.
- **Agro — `create()` controller**: now queries last 5 products for the session branch and reads optional `?from=` parameter for duplication.

### Fixed
- **Agro — PDF 404**: dropdown PDF links in `productosAgro/index.php` pointed to non-existent route `productosagro/cierre/rango`; corrected to `productosagro/exportarPdfVentas`.
- **Agro — confirm modal not opening**: `confirmSaleModalInstance.show()` was accidentally left outside the `finalizeSaleBtn` click handler after a prior edit; restored to correct position.

---

## 2026-03-24

### Added
- `CLAUDE.md`: documentation file describing project architecture, directory structure, roles, domain areas, and available development commands for onboarding new developers.

### Changed
- `.gitignore`: updated to exclude an additional file/directory from version control.

---

## 2026-03-20

### Added
- **PDF standardization**: unified all PDF reports under a single shared base template (`app/Libraries/`), ensuring consistent layout, typography, and branding across all generated documents (invoices, closing reports, lacteos reports, etc.).

### Fixed
- **Excel reports — sale numbers**: sale identifiers were missing from exported Excel files; now included in all relevant report columns.
- **Excel reports — pricing and units of measure**: corrected data mapping that caused incorrect prices and unit labels to appear in exported spreadsheets.
- **Excel reports — row height and product names**: normalized row height for readability and standardized product name formatting across all Excel exports.
- **Excel reports — general formatting**: multiple formatting improvements applied globally to Excel reports (column widths, header styles, cell alignment).
- **PDF reports — final adjustments**: resolved remaining layout and data rendering issues across PDF documents after the template standardization.

---

## 2026-03-19

### Fixed
- **Receipts**: improved print quality and visual fidelity of sale receipts, including better font rendering and layout consistency.

---

## 2026-03-16

### Changed
- **Delivery note (envío)**: updated the content and structure of the delivery note document to reflect revised business requirements.
- **Dashboard — quick access buttons**: updated color scheme of quick-access buttons on the home screen to improve visual hierarchy.

### Added
- **Dashboard — UI colors**: applied color coding to key elements on the initial screen to improve navigation and visual differentiation.

### Fixed
- **Sales view — product images**: changed the method used to resolve and display product images in the sales interface, improving reliability across environments.

### Removed
- **Product images**: removed static product image assets from the repository to reduce repository size; images are now expected to be managed externally or via uploads.

### Fixed
- **Inventory reports (PDF & Excel)**: comprehensive overhaul of generic inventory report generation. Corrected data queries, improved column structure, and standardized output format for both PDF and Excel exports.

---

## 2026-03-13

### Added
- **Quality control PDF report**: implemented a new `app/Libraries/` PDF class to generate quality control reports. Routes registered and accessible via the corresponding controller action.
- **Quality control Excel report**: improved formatting of the existing quality control Excel export (column widths, header styles, data alignment).

### Changed
- **Inventory UI — report buttons**: grouped report generation buttons in the inventory views to reduce visual clutter and improve usability.

---

## 2026-03-11

### Added
- **Shipments (envíos) — grouped PDF report**: added a new PDF report that groups products by category within shipment documents.
- **Inventory reports — initial implementation**: first version of new inventory-specific report generation logic.

### Changed
- **Shipments — stock limit enforcement**: product selection in the shipment form is now disabled once the available stock limit is reached, preventing over-allocation.
- **Shipments — table color scheme**: applied a dynamic color scheme to shipment tables to improve readability and status differentiation.

### Fixed
- **PDF encoding**: resolved character encoding issues causing accented characters (á, é, í, ó, ú, ñ) to render incorrectly in generated PDFs.
- **Products — temporary visibility**: temporarily hidden certain products from the catalog view pending data review.

### Visual
- **Sales cards**: applied color coding to sale cards for quick status identification.
- **Stock view**: added color indicators to the product stock screen to differentiate stock levels.
- **Inventory list**: applied color differentiation to inventory entries to distinguish between branches or categories.
- **Sales list**: applied color coding to the sales list view for improved visual scanning.

---

## 2026-03-10

### Added
- **Raw materials (materias primas)**: implemented a dedicated database table, migration, seeder, and `MateriaPrimaModel` for managing raw materials. Integrated into the product form via a dynamic select input.
- **Edit raw material**: added the ability to edit raw material entries directly from the product form select, with corresponding route and controller updates.
- **Client search by name**: improved credit sale client lookup to support search by customer name in addition to existing identifiers.
- **Sidebar — almacén role**: added navigation access for the `almacen` role in the sidebar.
- **Routes — almacén role**: granted the `almacen` role access to user management routes.

### Fixed
- **Sale receipts (almacén)**: corrected receipt generation for warehouse sales; added customer name field to all existing receipt templates.
- **Sale model — client name variable**: fixed incorrect variable reference causing customer name to not display on receipts.

### Changed
- **Inventory layout**: restructured the inventory view to prioritize the most frequently used actions.
- **Sales (almacén) — color styles**: applied the existing color scheme from the standard sales view to the warehouse sales screen.
- **Sales (contado) — color styles**: applied color corrections to the cash sales view for visual consistency.
- **Product form**: minor field reordering to improve form flow; product form now displays the selected raw material label inline.

---

## 2026-03-05

### Fixed
- **Receipts**: corrected receipt layout issues and added a watermark to printed sale receipts.

---

## 2026-02-26

### Added
- **Inventory sales — PDF controller**: created a dedicated controller for generating PDFs from inventory-based sales, separating concerns from the main sales controller.

### Changed
- **Inventory sales — Excel export**: updated product detail columns in the Excel export to include additional product information.
- **Inventory sales — report buttons**: restyled report generation buttons in the inventory/sales view to match the design of the main sales module.

---

## 2026-02-25

### Added
- **Shipment PDF — user and product detail**: enriched shipment PDF reports with sender/receiver user data and full product line detail.
- **Shipment model — extended query**: updated the shipment database query to retrieve user information and sale detail required for the enhanced PDF.

### Fixed
- **Shipment receptions — variable names**: corrected variable naming inconsistencies that prevented user names from displaying correctly in reception views.

---

## 2026-02-24

### Fixed
- **Duplicate submission prevention**: added client-side submit button state management to prevent duplicate records caused by accidental double-clicks on form submission buttons.

---

## 2026-02-23

### Fixed
- **Sales — time restriction removed**: removed the business-hours time restriction from the main sales module, allowing sales to be registered at any time.
- **Inventory sales — time restriction removed**: removed the same time restriction from the inventory/sales module.
- **Shipments — Excel report**: temporarily disabled the Excel report export in the shipments module pending a fix.
