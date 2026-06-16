# 📚 Documentación de Condoriri SG

Bienvenido a la documentación técnica del Sistema de Gestión Condoriri. Esta documentación está organizada por áreas de interés para facilitar la navegación y el entendimiento del proyecto.

---

## 🗂️ Mapa de la Documentación

### 1. [Arquitectura y Diseño Técnico](arquitectura/)
Documentos que describen las bases técnicas, la infraestructura y convenciones del sistema.
* [**Arquitectura General** (arquitectura/ARCHITECTURE.md)](arquitectura/ARCHITECTURE.md): Stack tecnológico, flujo de peticiones, roles del sistema, estructura de base de datos y control de accesos.
* [**Generación de Reportes PDF** (arquitectura/PDF_REPORTS.md)](arquitectura/PDF_REPORTS.md): Estructura, clases y diseño de los reportes PDF.
* [**Gestión de Códigos de Venta**](arquitectura/codigos_venta/):
  * [Estructura y Generación de Códigos](arquitectura/codigos_venta/CODIGOS_VENTA.md): Lógica de prefijos, numeración y formato de los códigos de venta.
  * [Control de Cambios en Códigos](arquitectura/codigos_venta/CODIGOS_VENTA_CAMBIO.md): Historial de ajustes y modificaciones sobre la lógica de códigos.

### 2. [Módulos de Negocio](modulos/)
Reglas de negocio y especificaciones de módulos especializados del sistema.
* [**Módulo Agropecuario (Agro)** (modulos/VENTAS_AGRO.md)](modulos/VENTAS_AGRO.md): Reglas de negocio del POS de productos agropecuarios, flujo de caja, control de stocks y diferencias con el módulo de lácteos.

### 3. [Gestión de Tareas](tareas/)
Seguimiento y bitácoras del desarrollo.
* [**Tareas Completadas Históricas** (tareas/completadas/)](tareas/completadas/): Carpeta con bitácoras individuales de desarrollo ordenadas por fecha.

### 4. [Historial y Changelogs](historial/)
Registro ordenado de cambios y versiones del sistema.
* [**Changelog del Sistema** (historial/CHANGELOG.md)](historial/CHANGELOG.md): Registro cronológico de versiones y cambios mayores a nivel de código de la aplicación.
* [**Changelog de Tareas Completadas** (historial/CHANGELOG_TAREAS.md)](historial/CHANGELOG_TAREAS.md): Registro cronológico detallado de todas las tareas técnicas completadas.

