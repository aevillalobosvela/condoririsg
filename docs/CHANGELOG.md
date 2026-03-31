# CHANGELOG

All notable changes to **Condoriri SG** are documented in this file.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) conventions.

---

## [Unreleased]

---

## 2026-04-02

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

## 2026-04-01

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
