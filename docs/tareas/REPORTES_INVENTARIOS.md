# Tarea: Mejoras a Reportes del Módulo Inventarios

## Contexto

Módulo: `http://localhost:8080/inventarios`  
Vista principal: `app/Views/inventarios/inventariosIndex.php`  
Controller: `app/Controllers/inventarios/inventariosController.php`

Los botones de reporte están en el encabezado de la tabla de inventarios:
- **Reporte General** → dropdown con Excel y PDF
- **Control de Calidad** → dropdown con Excel y PDF

---

## Resumen de cambios solicitados

| # | Reporte | Formato | Cambio |
|---|---|---|---|
| 1 | Reporte General | Excel | Separar datos de productos (hoja "Productos") a un nuevo reporte independiente |
| 2 | Reporte General | Excel | El reporte general queda solo con datos de inventario (sin hoja de productos) |
| 3 | Reporte General | PDF | Revisar y mejorar (pendiente de definir detalles — ver sección abajo) |
| 4 | Control de Calidad | Excel | Revisar y mejorar (pendiente de definir detalles — ver sección abajo) |
| 5 | Control de Calidad | PDF | Revisar y mejorar (pendiente de definir detalles — ver sección abajo) |
| 6 | Nuevo reporte | Excel/PDF | "Reporte de Productos" separado, con los datos que se extraen del Reporte General |

---

## Estado actual de cada reporte

### Reporte General — Excel
**Archivo:** `app/Services/Inventarios/ExportacionExcelService.php`  
**Método:** `exportarReporteGeneral()`  
**Ruta:** `GET /inventarios/exportarExcel`

**Estructura actual (2 hojas):**
- Hoja 1 `"Reporte General"` — tabla matricial con columnas:
  - FECHA, TURNO, NOMBRE, STOCK (L), RESERVA, AGREGA, MERMA
  - Columnas dinámicas por producto: `{PRODUCTO} Stock` + `{PRODUCTO} Cant.Prod`
  - Agrupado por mes, colores alternados por día (amarillo/azul)
  - Hoja de resumen al final
- Hoja 2 `"Resumen"` — totales globales + resumen por tipo de producto

**Problema identificado:** la hoja de productos dinámicos (columnas por producto) mezcla datos de inventario con datos de producción de productos. El cliente quiere separar esto.

---

### Reporte General — PDF
**Archivo:** `app/Libraries/ReporteInventario.php`  
**Clase:** `ReporteInventario extends FPDF`  
**Método:** `generarReporte(array $inventarios, array $filters)`  
**Ruta:** `GET /inventarios/exportarPdf`  
**Controller:** `exportarPdf()` → usa `getFilteredInventarios()` + `getResumenPorNombre()`

**Estructura actual:**
- Orientación: Portrait A4
- Tabla con 3 columnas: Producto | Total Producido | Stock Actual
- Datos de `$inventarios['lista_productos']` y `$inventarios['totales_generales']`
- Muestra filtros aplicados y usuario generador

---

### Control de Calidad — Excel
**Archivo:** `app/Controllers/inventarios/inventariosController.php`  
**Método:** `exportarCalidadExcel()` (inline, ~200 líneas de XML)  
**Ruta:** `GET /inventarios/exportarCalidadExcel`

**Estructura actual:**
- 1 hoja `"Control de Calidad"`
- Agrupado por mes (título de mes en rojo oscuro)
- Encabezados por mes: FECHA, NOMBRE, TURNO, GRASA, SNG, DENSIDAD, LACTOSA, SOLIDOS, PROTEINA, AGUA, TEMP, PUNTO CON, pH, OBSERVACION
- Colores alternados por día (amarillo `#FFF9E6` / azul `#E6F2FF`)
- Borde superior grueso para separar días
- Punto de congelación con formato `-0.000`
- Columna OBSERVACION siempre vacía (sin datos en BD)

---

### Control de Calidad — PDF
**Archivo:** `app/Libraries/ReporteControlCalidad.php`  
**Clase:** `ReporteControlCalidad extends FPDF`  
**Método:** `generarReporte(array $inventarios)`  
**Ruta:** `GET /inventarios/exportarCalidadPdf`

**Estructura actual:**
- Orientación: Landscape LEGAL
- Agrupado por mes con título en rojo
- Columnas: FECHA, NOMBRE, TURNO, GRASA, SNG, DENSIDAD, LACTOSA, SOLIDOS, PROTEINA, AGUA, TEMP, PUNTO CON, pH, OBSERVACION
- Colores alternados por día (amarillo/azul)
- Columna OBSERVACION siempre vacía

---

## Decisiones pendientes de confirmar con el cliente

### 1. Separación del Reporte General Excel

**Propuesta:**
- El **Reporte General Excel** queda con las columnas fijas: FECHA, TURNO, NOMBRE, STOCK (L), RESERVA, AGREGA, MERMA — sin columnas dinámicas de productos
- Se crea un **nuevo reporte "Reporte de Productos"** con las columnas dinámicas por producto (Stock y Cant.Prod por nombre de producto), manteniendo el mismo formato visual (colores, agrupación por mes)

**Preguntas abiertas:**
- ¿El nuevo reporte de productos debe tener también las columnas FECHA, TURNO, NOMBRE, STOCK (L)?
- ¿O solo las columnas de productos dinámicos?
- ¿El nuevo reporte se accede desde un tercer botón en la vista, o reemplaza algo?

### 2. Mejoras al Reporte General PDF

**Preguntas abiertas:**
- ¿Qué datos específicos debe mostrar? ¿La misma tabla matricial que el Excel (por día/turno)?
- ¿O mantener el formato actual (resumen por producto: Total Producido / Stock Actual)?
- ¿Orientación Portrait o Landscape?

### 3. Mejoras al Control de Calidad Excel

**Preguntas abiertas:**
- ¿Qué cambios específicos se requieren? ¿Agregar datos de OBSERVACION?
- ¿Cambiar el orden de columnas?
- ¿Agregar columna de usuario que registró la calidad (`user_cali`)?

### 4. Mejoras al Control de Calidad PDF

**Preguntas abiertas:**
- ¿Qué cambios específicos se requieren?
- ¿Agregar columna de usuario que registró la calidad?
- ¿Cambiar orientación o tamaño de página?

---

## Archivos a modificar / crear

```
# Modificar
app/Services/Inventarios/ExportacionExcelService.php   ← quitar columnas dinámicas de productos
app/Libraries/ReporteInventario.php                    ← mejoras al PDF general (pendiente definir)
app/Libraries/ReporteControlCalidad.php                ← mejoras al PDF calidad (pendiente definir)
app/Controllers/inventarios/inventariosController.php  ← exportarCalidadExcel() + nueva ruta
app/Views/inventarios/inventariosIndex.php             ← agregar botón nuevo reporte productos
app/Config/Routes.php                                  ← nueva ruta reporte productos

# Crear
app/Services/Inventarios/ExcelProductosInventarioService.php  ← nuevo reporte productos Excel
```

---

## Datos disponibles en BD por inventario

### Tabla `condoriri.inventarios` (campos de calidad)
`grasa`, `sng`, `densidad`, `lactosa`, `solidos`, `proteina`, `agua`, `temperatura`, `congelacion`, `ph`, `fecha_calidad`, `user_cali`

### Tabla `condoriri.inventarios` (campos generales)
`code`, `nombre`, `stock`, `reserva`, `turno`, `estado`, `sucursal_id`, `user_id`, `created_at`

### Tabla `condoriri.productos` (vinculada por `inventario_id`)
`nombre`, `stock`, `stock_inve`, `cantidad_produccion`, `agrega`, `merma`, `litros`, `precio_contado`, `precio_credito`, `categoria_id`, `unidad_id`

---

## Rutas actuales relevantes

```php
// Grupo inventarios en Routes.php
GET  inventarios/exportarExcel          → exportarExcel()          → ExportacionExcelService
GET  inventarios/exportarPdf            → exportarPdf()            → ReporteInventario (PDF)
GET  inventarios/exportarCalidadExcel   → exportarCalidadExcel()   → inline XML en controller
GET  inventarios/exportarCalidadPdf     → exportarCalidadPdf()     → ReporteControlCalidad (PDF)
GET  inventarios/reporteInventario      → reporteInventario()      → ReporteInventario (PDF, desde tab Estadísticos)
```

---

## Estado

| Subtarea | Estado |
|---|---|
| Análisis y documentación | ✅ Completado |
| Definir detalles con cliente | ⏳ Pendiente |
| Separar Reporte General Excel (quitar productos) | ⏳ Pendiente |
| Crear nuevo servicio ExcelProductosInventarioService | ⏳ Pendiente |
| Agregar botón en vista para nuevo reporte | ⏳ Pendiente |
| Mejoras Reporte General PDF | ⏳ Pendiente |
| Mejoras Control de Calidad Excel | ⏳ Pendiente |
| Mejoras Control de Calidad PDF | ⏳ Pendiente |
| Registrar nueva ruta en Routes.php | ⏳ Pendiente |
