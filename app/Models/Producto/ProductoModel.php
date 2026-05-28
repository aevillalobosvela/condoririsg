<?php

namespace App\Models\Producto;

use CodeIgniter\Model;

class ProductoModel extends Model
{
    protected $table = 'condoriri.productos';
    protected $primaryKey = 'id';
   
    protected $returnType = 'object'; 
    
    protected $allowedFields = [
        'nombre', 
        'descripcion', 
        'precio_credito', 
        'precio_contado', 
        'stock', 
        'imagen', 
        'estado', 
        'categoria_id', 
        'unidad_id', 
        'fecha_vencimiento', 
        'cantidad_produccion', 
        'porocidad', 
        'ph', 
        'acides', 
        'consistencia', 
        'color', 
        'olor', 
        'textura', 
        'observaciones', 
        'inventario_id', 
        'user_id',
        'cantidad_unidad',
        'reserva',
        'stock_inve',
        'observacion',
        'merma',
        'agrega',
        'parent_id',
        'litros',
        'materia_sub',
        'suero_lacteo',
        'suero_queseria',
        'cantidad_devo'
        
        

    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
   
    /**
     * Reglas de validación para creación de productos
     */
    public function getValidationRulesForCreate()
    {
        return [
            'nombre'              => 'required|min_length[3]|max_length[255]',
            'descripcion'         => 'permit_empty|max_length[500]',
            'precio_credito'      => 'required|numeric|greater_than[0]',
            'precio_contado'      => 'required|numeric|greater_than[0]',
            'stock'               => 'required|integer|greater_than_equal_to[0]',
            'categoria_id'        => 'required|integer',
            'unidad_id'           => 'required|integer',
            'inventario_id'       => 'required|integer',
            'user_id'             => 'required|integer',
            'cantidad_unidad'     => 'required|numeric|greater_than[0]',
            'cantidad_produccion' => 'required|numeric|greater_than[0]',
            'fecha_vencimiento'   => 'permit_empty|valid_date',
            'porocidad'           => 'permit_empty|max_length[100]',
            'ph'                  => 'permit_empty|max_length[100]',
            'acides'              => 'permit_empty|max_length[100]',
            'consistencia'        => 'permit_empty|max_length[100]',
            'color'               => 'permit_empty|max_length[100]',
            'olor'                => 'permit_empty|max_length[100]',
            'textura'             => 'permit_empty|max_length[100]',
            'observaciones'       => 'permit_empty|max_length[500]',
        ];
    }

    /**
     * Reglas de validación para actualización de productos
     */
    public function getValidationRulesForUpdate()
    {
        return [
            'nombre'              => 'required|min_length[3]|max_length[255]',
            'descripcion'         => 'permit_empty|max_length[500]',
            'precio_credito'      => 'required|numeric|greater_than[0]',
            'precio_contado'      => 'required|numeric|greater_than[0]',
            'categoria_id'        => 'required|integer',
            'unidad_id'           => 'required|integer',
            'fecha_vencimiento'   => 'permit_empty|valid_date',
            'porocidad'           => 'permit_empty|max_length[100]',
            'ph'                  => 'permit_empty|max_length[100]',
            'acides'              => 'permit_empty|max_length[100]',
            'consistencia'        => 'permit_empty|max_length[100]',
            'color'               => 'permit_empty|max_length[100]',
            'olor'                => 'permit_empty|max_length[100]',
            'textura'             => 'permit_empty|max_length[100]',
            'observaciones'       => 'permit_empty|max_length[500]',
        ];
    }
    
    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre del producto es obligatorio.',
            'min_length' => 'El nombre debe tener al menos 3 caracteres.',
            'max_length' => 'El nombre no puede exceder los 255 caracteres.',
        ],
        'precio_credito' => [
            'required' => 'El precio a crédito es obligatorio.',
            'numeric'  => 'El precio a crédito debe ser un número.',
            'greater_than' => 'El precio a crédito debe ser mayor a 0.',
        ],
        'precio_contado' => [
            'required' => 'El precio al contado es obligatorio.',
            'numeric'  => 'El precio al contado debe ser un número.',
            'greater_than' => 'El precio al contado debe ser mayor a 0.',
        ],
        'stock' => [
            'required'              => 'El stock es obligatorio.',
            'integer'               => 'El stock debe ser un número entero.',
            'greater_than_equal_to' => 'El stock no puede ser negativo.',
        ],
        'categoria_id' => [
            'required' => 'Debe seleccionar una categoría.',
            'integer'  => 'La categoría seleccionada no es válida.',
        ],
        'unidad_id' => [
            'required' => 'Debe seleccionar una unidad.',
            'integer'  => 'La unidad seleccionada no es válida.',
        ],
        'fecha_vencimiento' => [
            'valid_date' => 'La fecha de vencimiento no es una fecha válida.',
        ],
        'cantidad_produccion' => [
            'required' => 'La cantidad de producción es obligatoria.',
            'numeric' => 'La cantidad de producción debe ser un valor numérico.',
            'greater_than' => 'La cantidad de producción debe ser mayor a 0.',
        ],
        'cantidad_unidad' => [
            'required' => 'Los litros por unidad son obligatorios.',
            'numeric' => 'Los litros por unidad deben ser un valor numérico.',
            'greater_than' => 'Los litros por unidad deben ser mayor a 0.',
        ],
        'inventario_id' => [
            'required' => 'Debe seleccionar un inventario.',
            'integer'  => 'El inventario seleccionado no es válido.',
        ],
        'user_id' => [
            'required' => 'El ID de usuario es obligatorio.',
            'integer'  => 'El ID de usuario no es válido.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    
    public function getAllProductosWithRelations()
    {
        return $this->select('condoriri.productos.*, categorias.nombre as categoria_nombre, unidades.nombre as unidad_nombre, inventarios.nombre as inventario_nombre')
                    ->join('condoriri.categorias', 'condoriri.categorias.id = condoriri.productos.categoria_id')
                    ->join('condoriri.unidades', 'condoriri.unidades.id = condoriri.productos.unidad_id')
                    ->join('condoriri.inventarios', 'condoriri.inventarios.id = condoriri.productos.inventario_id')
                    ->findAll();
    }
    
    public function getProductoWithRelations(int $id)
    {
        return $this->select('condoriri.productos.*, categorias.nombre as categoria_nombre, unidades.nombre as unidad_nombre, inventarios.nombre as inventario_nombre, usuarios.nombre_completo as user_nombre')
                    ->join('condoriri.categorias', 'condoriri.categorias.id = condoriri.productos.categoria_id')
                    ->join('condoriri.unidades', 'condoriri.unidades.id = condoriri.productos.unidad_id')
                    ->join('condoriri.inventarios', 'condoriri.inventarios.id = condoriri.productos.inventario_id')
                    ->join('condoriri.usuarios', 'condoriri.usuarios.id = condoriri.productos.user_id')
                    ->where('condoriri.productos.id', $id)
                    ->first();
    }
    
    public function actualizarStock(int $id, int $cantidad)
    {
        return $this->update($id, ['stock' => $cantidad]);
    }
    
    public function getByCategoria(int $categoriaId)
    {
        return $this->where('categoria_id', $categoriaId)->findAll();
    }
    
    public function getByInventario(int $inventarioId)
    {
        return $this->where('inventario_id', $inventarioId)->findAll();
    }

    public function getProductosProximosAVencer(int $dias)
    {
        $fechaLimite = date('Y-m-d', strtotime("+$dias days"));
        return $this->where('fecha_vencimiento <=', $fechaLimite)
                    ->where('fecha_vencimiento >=', date('Y-m-d'))
                    ->findAll();
    }
    
    public function getProductosVencidos()
    {
        return $this->where('fecha_vencimiento <', date('Y-m-d'))
                    ->findAll();
    }

    public function getFilteredProductos(array $filters)
    {
        $this->select('condoriri.productos.*, categorias.nombre as categoria_nombre, unidades.nombre as unidad_nombre, inventarios.nombre as inventario_nombre')
             ->join('condoriri.categorias', 'condoriri.categorias.id = condoriri.productos.categoria_id')
             ->join('condoriri.unidades', 'condoriri.unidades.id = condoriri.productos.unidad_id')
             ->join('condoriri.inventarios', 'condoriri.inventarios.id = condoriri.productos.inventario_id');

        if (!empty($filters['categoria_id'])) {
            $this->where('productos.categoria_id', $filters['categoria_id']);
        }

        if (!empty($filters['inventario_id'])) {
            $this->where('productos.inventario_id', $filters['inventario_id']);
        }
        
        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $this->where('productos.estado', $filters['estado']);
        }

        if (!empty($filters['search'])) {
            $this->groupStart()
                 ->like('productos.nombre', $filters['search'])
                 ->orLike('productos.descripcion', $filters['search'])
                 ->groupEnd();
        }

        return $this->findAll();
    }
// public function getProductosBySucursalAndSearch(int $sucursalId, string $query = null)
// {
//     // The key is to explicitly select the 'code' column
//     $builder = $this->select('condoriri.productos.*, condoriri.productos.code, inventarios.stock as inventario_stock, inventarios.id as inventario_id')
//                     ->join('condoriri.inventarios', 'condoriri.inventarios.producto_id = condoriri.productos.id')
//                     ->where('condoriri.inventarios.sucursal_id', $sucursalId);

//     // If a search query is provided, apply the search filter
//     if ($query) {
//         $builder->groupStart()
//                 ->like('condoriri.productos.nombre', $query)
//                 ->orLike('condoriri.productos.code', $query)
//                 ->groupEnd();
//     }

//     // Execute the query and return the results
//     return $builder->findAll();
// }
    public function getProductosBySucursalAndSearch(int $sucursalId, string $query = null)
{
    $builder = $this->db->table('condoriri.inventarios as i')
        ->select('
            p.id as id,                   -- ✅ Forzamos id de productos
            p.nombre,
            p.descripcion,
            p.precio_credito,
            p.precio_contado,
            p.stock,
            p.stock_inve,
            p.inventario_id as producto_inventario_id,
            i.id as inventario_id,        -- ID del inventario (lote)
            i.stock as inventario_stock,
            i.sucursal_id,
            p.categoria_id,
            p.unidad_id
        ')
        ->join('condoriri.productos p', 'p.id = i.producto_id')
        ->where('i.sucursal_id', $sucursalId);

    if ($query) {
        $builder->groupStart()
            ->like('p.nombre', $query)
            ->orLike('p.code', $query)
            ->groupEnd();
    }

    return $builder->get()->getResult();
}

    public function getProductoWithRelations1($id)
{
    try {
        return $this->select('condoriri.productos.*, categorias.nombre as categoria_nombre, unidades.nombre as unidad_nombre, inventarios.nombre as inventario_nombre, usuarios.nombre_completo as user_nombre')
                    ->join('condoriri.categorias', 'condoriri.categorias.id = condoriri.productos.categoria_id')
                    ->join('condoriri.unidades', 'condoriri.unidades.id = condoriri.productos.unidad_id')
                    ->join('condoriri.inventarios', 'condoriri.inventarios.id = condoriri.productos.inventario_id')
                    ->join('condoriri.usuarios', 'condoriri.usuarios.id = condoriri.productos.user_id')
                    ->where('condoriri.productos.id', $id)
                    ->first();
    } catch (\Exception $e) {
        log_message('error', 'Error al obtener producto con relaciones: ' . $e->getMessage());
        return null;
    }
}


public function buildTree(array $productos, $parentId = null): array
{
    $branch = [];

    foreach ($productos as $producto) {
        if ($producto->parent_id == $parentId) {
            $children = $this->buildTree($productos, $producto->id);
            if (!empty($children)) {
                $producto->subproductos = $children;
            }
            $branch[] = $producto;
        }
    }

    return $branch;
}
    public function getResumen($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->builder();
        $builder->select('COUNT(*) as total_productos, SUM(cantidad_produccion) as total_produccion, SUM(stock_inve) as total_stock_actual');

        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $builder->where('created_at >=', $fechaInicio)
                    ->where('created_at <=', $fechaFin . ' 23:59:59');
        }

        return $builder->get()->getRow();
    }




    /**
     * Stock agrupado por nombre de producto — sin filtro de fecha.
     * Incluye precio, lotes activos y fecha del último lote.
     */
    public function getStockTotalAgrupadoPorNombre(): array
    {
        return $this->select('
                nombre,
                COUNT(id)                                        AS cantidad_registros,
                SUM(stock_inve)                                  AS suma_stock_inve,
                AVG(precio_contado)                              AS precio_contado,
                COUNT(CASE WHEN stock_inve > 0 THEN 1 END)       AS lotes_con_stock,
                MAX(created_at)                                  AS ultimo_lote
            ')
            ->where('deleted_at', null)
            ->where('parent_id', null)
            ->groupBy('nombre')
            ->orderBy('suma_stock_inve', 'DESC')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * Stock agrupado por nombre, filtrado por rango de fechas de creación.
     * Incluye precio, lotes activos y fecha del último lote.
     */
    public function getStockTotalFiltradoPorFecha(string $fechaInicio = null, string $fechaFin = null): array
    {
        $builder = $this->select('
                nombre,
                COUNT(id)                                        AS cantidad_registros,
                SUM(stock_inve)                                  AS suma_stock_inve,
                AVG(precio_contado)                              AS precio_contado,
                COUNT(CASE WHEN stock_inve > 0 THEN 1 END)       AS lotes_con_stock,
                MAX(created_at)                                  AS ultimo_lote
            ')
            ->where('deleted_at', null)
            ->where('parent_id', null)
            ->groupBy('nombre');

        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $builder->where('created_at >=', $fechaInicio . ' 00:00:00')
                    ->where('created_at <=', $fechaFin . ' 23:59:59');
        }

        return $builder
            ->orderBy('suma_stock_inve', 'DESC')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }



}
 