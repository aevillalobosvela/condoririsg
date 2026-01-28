# Condoriri SG - Sistema de Gestión

Sistema de gestión empresarial desarrollado con CodeIgniter 4 y PostgreSQL. Incluye módulos de inventarios, ventas, contabilidad, reportes y administración de usuarios.

## 🚀 Opciones de Ejecución

### Opción 1: Docker (Recomendado)

**Ventajas:**
- Entorno completo preconfigurado
- PostgreSQL incluido
- Todas las extensiones PHP necesarias
- Consistente entre desarrollo y producción

**Requisitos:**
- Docker Desktop
- Contenedor PostgreSQL `postgres-dev` en red `condoriri-network`

**Configuración de Base de Datos:**
- Host: `postgres-dev`
- Base de datos: `devdb`
- Esquema: `condoriri`
- Usuario: `devadmin`
- Contraseña: `devpass`
- Puerto: `5432`

**Ejecutar:**
```bash
docker-compose up -d
```

**Acceder:** http://localhost:8080

### Opción 2: PHP Local (spark serve)

**Ventajas:**
- Desarrollo más rápido (sin rebuild)
- Debugging directo

**Requisitos:**
- PHP 8.1+ con extensiones: `intl`, `mbstring`, `curl`, `openssl`, `pgsql`, `pdo_pgsql`
- Composer
- PostgreSQL local o cambiar a SQLite

**Configuración de Base de Datos:**

Para **PostgreSQL local:**
```env
database.default.hostname = localhost
database.default.DBDriver = Postgre
```

Para **SQLite** (más simple):
```env
database.default.hostname = 
database.default.database = writable/database.db
database.default.DBDriver = SQLite3
```

**Ejecutar:**
```bash
composer install
php spark serve --port=8080
```

**Acceder:** http://localhost:8080

## 📁 Estructura del Proyecto

```
condoririsg/
├── app/
│   ├── Controllers/     # Controladores por módulos
│   ├── Models/         # Modelos de datos
│   ├── Views/          # Vistas del frontend
│   └── Config/         # Configuraciones
├── public/
│   └── assets/         # CSS, JS, imágenes
├── writable/           # Logs, cache, sesiones
├── .env               # Variables de entorno
└── docker-compose.yml # Configuración Docker
```

## 🔧 Configuración

### Variables de Entorno (.env)

Las variables en `.env` tienen **prioridad** sobre `app/Config/Database.php`:

```env
# Desarrollo
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080'

# Base de datos (se sobrescribe según opción elegida)
database.default.hostname = postgres-dev  # Docker
# database.default.hostname = localhost   # PHP local
```

### Cambio entre Entornos

**Para Docker:**
```env
database.default.hostname = postgres-dev
```

**Para PHP local con PostgreSQL:**
```env
database.default.hostname = localhost
```

**Para PHP local con SQLite:**
```env
database.default.hostname = 
database.default.database = writable/database.db
database.default.DBDriver = SQLite3
```

## 🌐 Producción

**Estado:** Sistema en producción activo

**Para desplegar:**
1. Cambiar variables en `.env`:
   ```env
   CI_ENVIRONMENT = production
   app.baseURL = 'https://tu-dominio.com'
   database.default.hostname = servidor-produccion
   ```

2. Configurar base de datos de producción
3. Ejecutar migraciones si es necesario

## 📋 Módulos del Sistema

- **Inventarios** - Control de stock y productos
- **Ventas** - Gestión de ventas y facturación
- **Contabilidad** - Registro contable
- **Reportes** - Informes y estadísticas
- **Usuarios** - Administración de accesos
- **Sucursales** - Gestión multi-sucursal

## 🛠️ Comandos Útiles

### Docker
```bash
# Levantar servicios
docker-compose up -d

# Ver logs
docker logs condoririSG

# Acceder al contenedor
docker exec -it condoririSG bash

# Reconstruir
docker-compose up --build
```

### PHP Local
```bash
# Instalar dependencias
composer install

# Servidor de desarrollo
php spark serve --port=8080

# Limpiar cache
php spark cache:clear
```

## 🔍 Solución de Problemas

### Error de sesiones
Si aparece error de sesiones, verificar que existe:
```
writable/session/
```

### Error de base de datos
- **Docker:** Verificar que `postgres-dev` esté ejecutándose
- **PHP local:** Verificar extensiones PostgreSQL o cambiar a SQLite

### Error de permisos
```bash
chmod -R 755 writable/
```