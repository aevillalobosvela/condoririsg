# Condoriri SG — Arquitectura y Convenciones

## Stack
- **Framework**: CodeIgniter 4 (PHP 8.1)
- **Base de datos**: PostgreSQL, schema `condoriri`
- **Schemas adicionales**: `public` (personas/UTO), `rrhh` (empleados, cargos, secciones)

## Flujo de una request
```
public/index.php → app/Config/Routes.php → Controller → Model → View
```
Auto-routing **deshabilitado**. Todas las rutas son explícitas en `Routes.php`.

## Estructura de directorios clave
```
app/Controllers/{dominio}/     # Un subdirectorio por dominio
app/Models/{Entidad}/          # Un modelo por entidad
app/Services/                  # Lógica de negocio separada del controller
app/Libraries/                 # Clases PDF y utilidades custom
app/Filters/                   # Middleware de auth y roles
app/Config/Routes.php          # Todas las rutas y permisos
```

## Roles del sistema
| Rol | Acceso principal |
|---|---|
| `admin` | Todo |
| `vendedor` | Ventas lácteos, clientes, envíos |
| `almacen` | Inventarios, productos, usuarios |
| `contabilidad` | Reportes, consultas |
| `agropecuario` | Productos agro, ventas agro |
| `ganaderia` | Productos agro, envíos |
| `dev` | Recepciones, acceso técnico |

## Tipos de receptor en ventas a crédito
Una venta a crédito puede tener exactamente uno de estos tres campos poblado en `condoriri.ventas`:

| Campo | Tabla origen | Descripción |
|---|---|---|
| `cliente_id` | `condoriri.clientes` | Venta al contado con cliente registrado |
| `personal_uto_id` | `public.personas` | Crédito a empleado UTO activo |
| `cliente_externo_id` | `condoriri.clientes_externos` | Crédito a persona externa vinculada (Seguro Univ., Spectrolab, etc.) |

Segmentos disponibles en `clientes_externos.segmento`: `SEGURO_UNIV`, `SPECTROLAB`.

## Cómo se definen rutas y permisos
```php
// Patrón estándar en Routes.php
$routes->group('dominio', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'dominio\Controller::index', ['filter' => 'role:admin,vendedor']);
    $routes->post('create', 'dominio\Controller::create', ['filter' => 'role:admin,vendedor']);
});
```
- Siempre `['filter' => 'auth']` en el grupo
- Roles específicos por ruta con `['filter' => 'role:rol1,rol2']`
- `RoleFilter` lee `session()->get('rol_nombre')` para validar

## Sesión del usuario
```php
session()->get('id')           // ID del usuario
session()->get('rol_nombre')   // Rol: 'admin', 'vendedor', etc.
session()->get('sucursal_id')  // ID de la sucursal asignada
```

## Convenciones de Controllers
```php
namespace App\Controllers\dominio;
use App\Controllers\BaseController;

class miController extends BaseController
{
    protected $miModel;

    public function __construct()
    {
        $this->miModel = new MiModel();
    }

    public function index()     { /* lista */ }
    public function register()  { /* formulario crear */ }
    public function create()    { /* POST guardar */ }
    public function edit($id)   { /* formulario editar */ }
    public function update($id) { /* POST actualizar */ }
    public function delete($id) { /* eliminar */ }
}
```

## Convenciones de Models
```php
namespace App\Models\Entidad;
use CodeIgniter\Model;

class MiModel extends Model
{
    protected $table      = 'condoriri.tabla';
    protected $primaryKey = 'id';
    protected $returnType = 'object';  // o 'array'

    protected $allowedFields = ['campo1', 'campo2'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    protected $skipValidation = true;
}
```

## Queries SQL directas (patrón preferido para consultas complejas)
```php
$db = \Config\Database::connect();
$query = $db->query("SELECT ... WHERE campo = ?", [$param]);
$results = $query->getResult();   // array de objetos
$row     = $query->getRow();      // un objeto
```

## Transacciones
```php
$db->transBegin();
try {
    // operaciones...
    $db->transCommit();
    return redirect()->to('/ruta')->with('success', 'OK');
} catch (\Exception $e) {
    $db->transRollback();
    return redirect()->back()->with('error', $e->getMessage());
}
```

## Dominios del sistema
| Dominio | URL base | Propósito |
|---|---|---|
| ventas | `/ventas` | Ventas lácteos (sucursal_id=2) |
| inventarios | `/inventarios` | Materia prima + ventas planta (sucursal_id=4) |
| productosagro | `/productosagro` | Productos y ventas agropecuarios |
| productos | `/productos` | Catálogo productos terminados |
| envios | `/envios` | Envíos entre sucursales |
| contabilidad | `/contabilidad` | Reportes y dashboard contable |
| reportes | `/reportes` | Reportes generales |

## Sucursales conocidas
| ID | Nombre | Prefijo código |
|---|---|---|
| 2 | Sucursal Centro (Tienda) | `SC` |
| 4 | Planta Producción | `PP` |
| 10, 11 | Sucursales agro | — |
