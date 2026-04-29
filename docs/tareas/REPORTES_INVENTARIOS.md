# Tarea: Mejoras a Reportes del Módulo Inventarios

## Contexto

Módulo: `http://localhost:8080/inventarios`  
Vista principal: `app/Views/inventarios/inventariosIndex.php`  
Controller: `app/Controllers/inventarios/inventariosController.php`

Los botones de reporte están en el encabezado de la tabla de inventarios:
- **Reporte General** → dropdown con Excel y PDF
- **Control de Calidad** → dropdown con Excel y PDF

**Estrategia:** los cambios se hacen primero en Excel, se valida, luego se replica al PDF equivalente.

---

## Archivos involucrados

```
app/Services/Inventarios/ExportacionExcelService.php   ← Reporte General Excel (modificar)
app/Libraries/ReporteInventario.php                    ← Reporte General PDF (modificar después)
app/Controllers/inventarios/inventariosController.php  ← nueva ruta + nuevo servicio
app/Views/inventarios/inventariosIndex.php             ← agregar botón nuevo reporte
app/Config/Routes.php                                  ← nueva ruta
# Crear:
app/Services/Inventarios/ExcelSueroInventarioService.php  ← nuevo reporte separado Excel
```

---

## Cambios definidos

### Cambio 1 — Reporte General Excel: fila de totales/promedios por mes

El reporte agrupa registros por mes. Al final de cada mes se debe agregar una fila de cierre con:
- **SUMA** de las columnas numéricas: STOCK (L), RESERVA, AGREGA, MERMA, y columnas dinámicas de productos (Stock + Cant.Prod)
- **PROMEDIO** de esas mismas columnas

La fila de suma y la fila de promedio deben ser visualmente distinguibles del resto (estilo propio).

### Cambio 2 — Reporte General Excel: cambio de esquema de colores

El esquema actual usa rojo `#DC143C` como color dominante en bordes y encabezados, con fondos amarillo `#FFF9E6` y azul `#E6F2FF` para filas alternas. Es visualmente agresivo.

Se debe reemplazar por un esquema más suave pero igualmente distintivo:
- Encabezados: azul institucional suave (ej. `#2E5090` o similar)
- Filas alternas día par: fondo verde muy suave (ej. `#F0F7F0`)
- Filas alternas día impar: fondo gris muy suave (ej. `#F5F5F5`)
- Bordes: gris claro (ej. `#CCCCCC`) en lugar de rojo
- Filas de suma/promedio: fondo diferenciado (ej. amarillo pálido `#FFFDE7` para suma, azul pálido `#E8F4FD` para promedio)

### Cambio 3 — Reporte General Excel: valores vacíos muestran `-` en lugar de `0`

Actualmente cuando un campo numérico es `null` o `0` se escribe `0`. Se debe cambiar para que cuando el valor sea `null` (sin dato registrado) se muestre el carácter `-` como texto, indicando ausencia de dato. Si el valor es genuinamente `0` (dato registrado como cero), se mantiene `0`.

**Aplica a:** columnas dinámicas de productos (Stock y Cant.Prod) cuando el inventario no tiene ese producto.

### Cambio 4 — Nuevo reporte Excel: separación LECHE vs otros

En la columna NOMBRE del reporte general aparecen valores como `LECHE` y `SUERO LECHE` (y potencialmente otros). Se debe crear un **nuevo reporte separado** que divida:
- **Reporte LECHE:** solo registros donde `nombre = 'LECHE'`
- **Reporte OTROS (SUERO y demás):** todos los registros donde `nombre != 'LECHE'`

Ambos sub-reportes en el mismo archivo Excel, en hojas separadas, con el mismo formato que el Reporte General (incluyendo los cambios 1, 2 y 3 ya aplicados).

Se agrega un nuevo botón en la vista para acceder a este reporte.

---

## Plan de implementación (Excel primero, PDF después de validar)

### Paso 1 — Cambio de colores en Reporte General Excel
**Archivo:** `app/Services/Inventarios/ExportacionExcelService.php`  
**Método:** `definirEstilos()`

Reemplazar el esquema de colores agresivo (rojo dominante) por el esquema suave definido en Cambio 2.
También actualizar los estilos de encabezados de mes (`titulo_mes`) y título principal (`titulo`).

**Validar:** descargar el Excel desde el botón Reporte General → Excel y confirmar que los colores son correctos y la tabla sigue siendo legible.

---

### Paso 2 — Valores vacíos muestran `-` en Reporte General Excel
**Archivo:** `app/Services/Inventarios/ExportacionExcelService.php`  
**Método:** `generarDatos()`

En las columnas dinámicas de productos, cuando `$datosProducto['stock'] === 0` y el inventario no tiene ese producto (es decir, el valor proviene del inicializador en `prepararDatosConProductos` y no de un producto real), mostrar `-` como texto en lugar de `0.00`.

**Estrategia:** en `prepararDatosConProductos()`, marcar con `null` los productos que no existen en ese inventario (en lugar de `0`). En `generarDatos()`, si el valor es `null` → celda de texto con `-`; si es numérico (incluso `0`) → celda numérica normal.

**Validar:** en el Excel, los inventarios que no tienen ciertos productos deben mostrar `-` en esas columnas, no `0.00`.

---

### Paso 3 — Fila de SUMA y PROMEDIO al final de cada mes en Reporte General Excel
**Archivo:** `app/Services/Inventarios/ExportacionExcelService.php`  
**Métodos:** `generarDatos()` + `definirEstilos()` (agregar estilos de fila totales)

Al terminar de renderizar los registros de cada mes, agregar dos filas:
1. Fila **SUMA**: etiqueta "TOTAL MES" en columna FECHA, suma de STOCK(L), RESERVA, AGREGA, MERMA y cada columna de producto
2. Fila **PROMEDIO**: etiqueta "PROMEDIO" en columna FECHA, promedio de las mismas columnas

Las columnas TURNO y NOMBRE quedan vacías en estas filas.  
Los valores `-` (productos sin dato) se excluyen del cálculo (no cuentan como `0` en el promedio).

**Validar:** al final de cada bloque de mes en el Excel deben aparecer las dos filas con los totales correctos.

---

### Paso 4 — Nuevo reporte Excel: LECHE vs OTROS
**Archivos a crear/modificar:**
- Crear `app/Services/Inventarios/ExcelSueroInventarioService.php`
- Modificar `app/Controllers/inventarios/inventariosController.php` → agregar método `exportarExcelSuero()`
- Modificar `app/Config/Routes.php` → agregar ruta `GET inventarios/exportarExcelSuero`
- Modificar `app/Views/inventarios/inventariosIndex.php` → agregar tercer botón de reporte

El nuevo servicio reutiliza la lógica de `ExportacionExcelService` pero genera dos hojas:
- Hoja 1 `"LECHE"`: solo registros con `nombre = 'LECHE'`, mismo formato (con cambios 1-3 ya aplicados)
- Hoja 2 `"SUERO Y OTROS"`: registros con `nombre != 'LECHE'`, mismo formato

**Validar:** el nuevo botón descarga el Excel con las dos hojas correctamente separadas y con el mismo formato visual.

---

### Paso 5 — Replicar cambios 1, 2 y 3 al Reporte General PDF
**Archivo:** `app/Libraries/ReporteInventario.php`  
Solo después de validar los pasos 1-3 en Excel.

---

### Paso 6 — Replicar cambio 4 al PDF (nuevo reporte LECHE vs OTROS en PDF)
**Archivo a crear:** `app/Libraries/PdfSueroInventarioLib.php` (o similar)  
Solo después de validar el paso 4 en Excel.

---

## Estado

| Paso | Descripción | Estado |
|---|---|---|
| 1 | Cambio de colores — Excel | ⏳ Pendiente |
| 2 | Valores vacíos con `-` — Excel | ⏳ Pendiente |
| 3 | Fila SUMA + PROMEDIO por mes — Excel | ⏳ Pendiente |
| 4 | Nuevo reporte LECHE vs OTROS — Excel | ⏳ Pendiente |
| 5 | Replicar cambios 1-3 al PDF | ⏳ Pendiente |
| 6 | Replicar cambio 4 al PDF | ⏳ Pendiente |
