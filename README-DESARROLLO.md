# Condoriri SG - Configuración de Desarrollo

## 🏗️ Entorno de Desarrollo Local

Este proyecto está configurado para desarrollo local usando Docker con PostgreSQL separado.

### Prerequisitos

- Docker Desktop
- Contenedor PostgreSQL (`postgres-dev`) ejecutándose en red `condoriri-network`

### Configuración Actual

#### Base de Datos (Desarrollo)
- **Contenedor:** `postgres-dev`
- **Host:** `postgres-dev` (dentro de la red Docker)
- **Base de datos:** `devdb`
- **Esquema:** `condoriri`
- **Usuario:** `devadmin`
- **Contraseña:** `devpass`
- **Puerto:** `5432`

#### Aplicación
- **URL:** `http://localhost:8080`
- **Contenedor:** `condoririSG`
- **Framework:** CodeIgniter 4
- **PHP:** 8.1

### Levantar el Entorno

1. **Asegurar PostgreSQL activo:**
   ```bash
   docker-compose up -d postgres-dev
   ```

2. **Crear esquema (primera vez):**
   ```sql
   CREATE SCHEMA IF NOT EXISTS condoriri;
   ```

3. **Ejecutar script de base de datos** (tablas y datos iniciales)

4. **Levantar aplicación:**
   ```bash
   docker-compose up --build
   ```

### Archivos de Configuración

- **`.env`** - Variables de entorno (configurado para desarrollo local)
- **`docker-compose.yml`** - Configuración de contenedores
- **`app/Config/Database.php`** - Configuración de BD (usa variables de .env)

### Para Producción

⚠️ **IMPORTANTE:** Antes de subir a producción, el encargado debe:

1. Cambiar configuración de BD en `.env`:
   - `database.default.hostname` → servidor de producción
   - `database.default.database` → base de datos de producción
   - `database.default.username` → usuario de producción
   - `database.default.password` → contraseña de producción
   - `database.default.port` → puerto de producción

2. Cambiar `app.baseURL` en `.env` a la URL de producción

3. Cambiar `CI_ENVIRONMENT` a `production`

### Estructura del Proyecto

- `app/Controllers/` - Controladores por módulos
- `app/Models/` - Modelos de datos
- `app/Views/` - Vistas del frontend
- `public/assets/` - Recursos estáticos
- `writable/` - Logs, cache, sesiones

### Comandos Útiles

```bash
# Ver logs de la aplicación
docker logs condoririSG

# Ejecutar comandos dentro del contenedor
docker exec -it condoririSG bash

# Instalar dependencias PHP
docker exec -it condoririSG composer install

# Ejecutar tests
docker exec -it condoririSG vendor/bin/phpunit
```