# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Development server
php spark serve

# Run tests
composer test
# or
phpunit

# Database migrations
php spark migrate
php spark migrate:rollback
php spark db:seed <SeederName>

# Code formatting
./vendor/bin/php-cs-fixer fix

# Docker (full stack)
docker-compose up
```

## Architecture

**Condoriri SG** is a CodeIgniter 4 (PHP 8.1) ERP system for managing dairy/agricultural business operations. Database: PostgreSQL, schema namespace `condoriri`.

### Request Flow

`public/index.php` → `app/Config/Routes.php` → Controller → Model → View

Auto-routing is **disabled** (`$routes->setAutoRoute(false)`). All routes are explicitly defined in `Routes.php` with role-based access control enforced via `app/Filters/`.

### Key Directories

- **`app/Controllers/`** — Organized by domain (ventas, inventarios, productos, contabilidad, devoluciones, reportes, usuarios, admin, etc.)
- **`app/Models/`** — One model per domain entity; `UsuarioModel.php` handles auth logic
- **`app/Libraries/`** — Custom PDF generation classes (FacturaPdf, CierreVentaPdf, CierreVentaAdmin, ReporteLacteos, etc.) built on top of a PDF library
- **`app/Services/`** — Business logic separated from controllers (`Inventarios/`, `Shared/`)
- **`app/Filters/`** — Middleware for authentication and role-based authorization
- **`app/Database/Migrations/`** — Schema versioning (15+ migration files)
- **`app/Config/Routes.php`** — Central route file; all role/permission rules are here

### Roles

The system enforces 7 roles: `admin`, `vendedor`, `almacen`, `contabilidad`, `agropecuario`, `ganaderia`, `dev`. Route-level access control is defined per role in `Routes.php`.

### Domain Areas

| Domain | Purpose |
|---|---|
| ventas | Sales transactions and detail |
| inventarios | Stock management per branch (sucursal) |
| productos / productosAgro | Product catalog (dairy vs agro) |
| clientes | Customer management |
| devoluciones | Returns processing |
| contabilidad | Accounting entries |
| reportes | PDF/Excel report generation |
| transferencias | Stock transfers between branches |

### Database

PostgreSQL — connection config in `app/Config/Database.php`, environment variables in `.env` (see `.env.example`). All tables live under the `condoriri` schema.

### PDF Reports

Custom PDF classes live in `app/Libraries/`. They are instantiated directly in controllers to generate invoices, daily closing reports, and lacteos reports.

## Documentation

Extended documentation lives in `docs/`:

- `docs/ARCHITECTURE.md` — conventions, roles, controller/model patterns, route definitions
- `docs/PDF_REPORTS.md` — how to create PDF/Excel reports, base class methods, examples
- `docs/VENTAS_AGRO.md` — agro sales module, differences vs lacteos, routes, DB tables
- `docs/CODIGOS_VENTA.md` — sale code format, PostgreSQL sequences, SQL scripts
- `docs/CHANGELOG.md` — full change history

Docker files are in `docker/`.
