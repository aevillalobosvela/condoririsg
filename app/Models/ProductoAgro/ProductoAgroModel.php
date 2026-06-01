<?php

namespace App\Models\ProductoAgro;

use CodeIgniter\Model;

class ProductoAgroModel extends Model
{
    protected $table            = 'condoriri.productos_agro';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;  

   
    protected $allowedFields = [
        'code',
        'producto',
        'descripcion',
        'cantidad',
        'categoria',
        'unidad_id',
        'precio_credito',
        'precio_contado',
        'cantidad_inve',
        'imagen',
        'sucursal_id',
        'user_id',
        'estado'
    ];

 
    protected $useTimestamps    = true;
    protected $createdField     = 'fecha_creacion';
    protected $updatedField     = 'fecha_update';
    protected $deletedField     = 'fecha_delete';  

   
    protected $validationRules = [
        
        'producto'          => 'required|max_length[255]',
        'descripcion'       => 'permit_empty|string|max_length[65535]',
        'cantidad'          => 'required|integer|greater_than_equal_to[0]',
        'categoria'         => 'required|max_length[255]',
        'unidad_id'         => 'required|integer|greater_than[0]',
        'precio_credito'    => 'required|decimal|greater_than_equal_to[0]',
        'precio_contado'    => 'required|decimal|greater_than_equal_to[0]',
        'cantidad_inve'     => 'required|integer|greater_than_equal_to[0]',
        'sucursal_id'       => 'required|integer|greater_than[0]',
        'user_id'           => 'required|integer|greater_than[0]',
        'imagen'            => 'permit_empty|max_length[255]',
        
    ];

    // protected $validationMessages = [
    //     'code' => [
    //         'is_unique' => 'El código ya está en uso.',
    //     ],
    // ];

    protected $skipValidation = false;

    
    public function withUnidad()
    {
        return $this->builder()
            ->select('productos_agro.*, unidades.nombre as unidad_nombre')
            ->join('condoriri.unidades', 'unidades.id = productos_agro.unidad_id', 'left');
    }

    public function withSucursal()
    {
        return $this->builder()
            ->select('productos_agro.*, sucursales.nombre as sucursal_nombre')
            ->join('condoriri.sucursales', 'sucursales.id = productos_agro.sucursal_id', 'left');
    }

    public function withUsuario()
    {
        return $this->builder()
            ->select('productos_agro.*, usuarios.nombre as usuario_nombre')
            ->join('rrhh.usuarios', 'usuarios.id = productos_agro.user_id', 'left');
    }
 
     public function getAvailableProducts(): array
    {
        return $this
            ->select([
                'productos_agro.id',
                'productos_agro.producto as nombre',          // ✅ nombre (para la vista)
                'productos_agro.code as inventario_id',       // ✅ inventario_id (para lote)
                'productos_agro.cantidad_inve as stock_inve', // ✅ stock_inve (para compatibilidad)
                'productos_agro.precio_contado',
                'productos_agro.precio_credito',
                'productos_agro.imagen',                      // ✅ imagen (nivel 1 cadena de prioridad)
                'unidades.nombre as unidad'
            ])
            ->join('condoriri.unidades', 'unidades.id = productos_agro.unidad_id', 'left')
            ->where('productos_agro.cantidad_inve >', 0)     // ✅ cantidad_inve (tu campo real)
            ->where('productos_agro.estado', 1)              // ✅ solo activos
            ->orderBy('productos_agro.producto', 'ASC')
            ->findAll();
    }

    /**
     * Versión con más detalles (para historial)
     */
    public function getWithDetails(int $id)
    {
        return $this
            ->select([
                'productos_agro.*',
                'unidades.nombre as unidad_nombre',
                'sucursales.nombre as sucursal_nombre'
            ])
            ->join('condoriri.unidades', 'unidades.id = productos_agro.unidad_id', 'left')
            ->join('condoriri.sucursales', 'sucursales.id = productos_agro.sucursal_id', 'left')
            ->find($id);
    }
}