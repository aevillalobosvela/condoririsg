<?php

namespace App\Models\Producto;

use CodeIgniter\Model;

class ProductoModel extends Model
{
    protected $table = 'condoriri.productos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    
    // Lista completa de todos los campos que se pueden insertar o actualizar.
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
        'user_id'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Reglas de validación para todos los campos del formulario.
    protected $validationRules = [
        'nombre'              => 'required|min_length[3]|max_length[255]',
        'descripcion'         => 'permit_empty|max_length[500]',
        'precio_credito'      => 'required|numeric',
        'precio_contado'      => 'required|numeric',
        'stock'               => 'required|integer|greater_than_equal_to[0]',
        'estado'              => 'required|in_list[0,1]',
        'categoria_id'        => 'required|integer',
        'unidad_id'           => 'required|integer',
        'fecha_vencimiento'   => 'permit_empty|valid_date',
        'cantidad_produccion' => 'permit_empty|integer',
        'porocidad'           => 'permit_empty|numeric',
        'ph'                  => 'permit_empty|numeric',
        'acides'              => 'permit_empty|numeric',
        'consistencia'        => 'permit_empty|max_length[100]',
        'color'               => 'permit_empty|max_length[100]',
        'olor'                => 'permit_empty|max_length[100]',
        'textura'             => 'permit_empty|max_length[100]',
        'observaciones'       => 'permit_empty|max_length[500]',
        'inventario_id'       => 'required|integer',
        'user_id'             => 'required|integer',
    ];
    
    // Mensajes de error personalizados. Esto es lo que faltaba.
    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre del producto es obligatorio.',
            'min_length' => 'El nombre debe tener al menos 3 caracteres.',
            'max_length' => 'El nombre no puede exceder los 255 caracteres.',
        ],
        'precio_credito' => [
            'required' => 'El precio a crédito es obligatorio.',
            'numeric'  => 'El precio a crédito debe ser un número.',
        ],
        'precio_contado' => [
            'required' => 'El precio al contado es obligatorio.',
            'numeric'  => 'El precio al contado debe ser un número.',
        ],
        'stock' => [
            'required'            => 'El stock es obligatorio.',
            'integer'             => 'El stock debe ser un número entero.',
            'greater_than_equal_to' => 'El stock no puede ser negativo.',
        ],
        'estado' => [
            'required' => 'El estado del producto es obligatorio.',
            'in_list'  => 'El estado debe ser Activo (1) o Inactivo (0).',
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
            'integer' => 'La cantidad de producción debe ser un número entero.',
        ],
        'porocidad' => [
            'numeric' => 'La porosidad debe ser un valor numérico.',
        ],
        'ph' => [
            'numeric' => 'El PH debe ser un valor numérico.',
        ],
        'acides' => [
            'numeric' => 'La acidez debe ser un valor numérico.',
        ],
        'inventario_id' => [
            'required' => 'El ID del inventario es obligatorio.',
            'integer'  => 'El ID del inventario no es válido.',
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
}   