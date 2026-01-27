<?php

namespace App\Models\StockSucursal;

use CodeIgniter\Model;


class StockSucursalModel extends Model
{
    
    protected $table = 'condoriri.stock_sucursales';
    protected $primaryKey = 'id';
    protected $returnType = 'array'; 
    protected $useSoftDeletes = false; 

   
    protected $allowedFields = [
        'producto_id', 
        'sucursal_id', 
        'cantidad', 
        'stock', 
        'precio_contado', 
        'precio_credito', 
        'estado', 
        'user_id',
        'producto',
        'categoria',
        'unidad'
    ];

  
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = null; 

    
    protected $validationRules = [
        'producto_id'    => 'required|integer',
        'sucursal_id'    => 'required|integer',
        'cantidad'       => 'required|integer',
        'stock'          => 'required|integer',
        'precio_contado' => 'required|numeric', 
        'precio_credito' => 'required|numeric',
       
        'user_id'        => 'permit_empty|integer',
        'producto'       => 'max_length[255]',
        'categoria'      => 'max_length[255]',
        'unidad'         => 'max_length[255]'
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false; 

    /**
     * Get summary statistics for stock
     */
    public function getResumenStock($fecha_inicio = null, $fecha_fin = null)
    {
        $builder = $this->db->table($this->table);
        
        // Apply date filters if provided
        if ($fecha_inicio && $fecha_fin) {
            $builder->where('created_at >=', $fecha_inicio . ' 00:00:00');
            $builder->where('created_at <=', $fecha_fin . ' 23:59:59');
        } elseif ($fecha_inicio) {
            $builder->where('created_at >=', $fecha_inicio . ' 00:00:00');
        } elseif ($fecha_fin) {
            $builder->where('created_at <=', $fecha_fin . ' 23:59:59');
        }
        
        $builder->select('
            COUNT(DISTINCT producto_id) as total_productos,
            SUM(stock) as total_stock,
            SUM(stock * precio_contado) as total_valor_contado,
            SUM(stock * precio_credito) as total_valor_credito
        ');
        
        return $builder->get()->getRow();
    }

    /**
     * Get product-wise breakdown with statistics
     */
    public function getResumenPorProducto($fecha_inicio = null, $fecha_fin = null)
    {
        $builder = $this->db->table($this->table);
        
        // Apply date filters if provided
        if ($fecha_inicio && $fecha_fin) {
            $builder->where($this->table . '.created_at >=', $fecha_inicio . ' 00:00:00');
            $builder->where($this->table . '.created_at <=', $fecha_fin . ' 23:59:59');
        } elseif ($fecha_inicio) {
            $builder->where($this->table . '.created_at >=', $fecha_inicio . ' 00:00:00');
        } elseif ($fecha_fin) {
            $builder->where($this->table . '.created_at <=', $fecha_fin . ' 23:59:59');
        }
        
        $builder->select('
            ' . $this->table . '.producto,
            ' . $this->table . '.categoria,
            ' . $this->table . '.unidad,
            SUM(' . $this->table . '.cantidad) as total_cantidad,
            SUM(' . $this->table . '.stock) as total_stock,
            AVG(' . $this->table . '.precio_contado) as precio_contado,
            AVG(' . $this->table . '.precio_credito) as precio_credito,
            SUM(' . $this->table . '.stock * ' . $this->table . '.precio_contado) as valor_contado,
            SUM(' . $this->table . '.stock * ' . $this->table . '.precio_credito) as valor_credito
        ');
        $builder->groupBy($this->table . '.producto, ' . $this->table . '.categoria, ' . $this->table . '.unidad');
        $builder->orderBy($this->table . '.producto', 'ASC');
        
        $productos = $builder->get()->getResult();
        
        // Calculate totals
        $totales = [
            'total_cantidad' => 0,
            'total_stock' => 0,
            'total_valor_contado' => 0,
            'total_valor_credito' => 0
        ];
        
        foreach ($productos as $producto) {
            $totales['total_cantidad'] += $producto->total_cantidad;
            $totales['total_stock'] += $producto->total_stock;
            $totales['total_valor_contado'] += $producto->valor_contado;
            $totales['total_valor_credito'] += $producto->valor_credito;
        }
        
        return [
            'lista_productos' => $productos,
            'totales_generales' => $totales
        ];
    }

    /**
     * Get stock with full details (for listing)
     */
    public function getStockWithDetails()
    {
        return $this->select('
            ' . $this->table . '.*,
            productos.nombre as producto_nombre,
            categorias.nombre as categoria_nombre,
            sucursales.nombre as sucursal_nombre
        ')
        ->join('productos', 'productos.id = ' . $this->table . '.producto_id', 'left')
        ->join('categorias', 'categorias.id = productos.categoria_id', 'left')
        ->join('sucursales', 'sucursales.id = ' . $this->table . '.sucursal_id', 'left')
        ->orderBy($this->table . '.created_at', 'DESC')
        ->findAll();
    }
    public function getProductosConStockPorSucursal(int $sucursalId): array
    {
        return $this->db->table('condoriri.stock_sucursales ss')
            ->select('
                p.id, 
                ss.id as inventario_id,
                p.nombre, 
                ss.stock as stock_inve,
                p.precio_contado,
                p.precio_credito,
                p.imagen,
                c.nombre as categoria_nombre,
                u.nombre as unidad_nombre
            ')
            ->join('condoriri.productos p', 'p.id = ss.producto_id')
            ->join('condoriri.categorias c', 'c.id = p.categoria_id', 'left')
            ->join('condoriri.unidades u', 'u.id = p.unidad_id', 'left')
            ->where('ss.sucursal_id', $sucursalId)
            // ->where('ss.stock >', 0) // Removed to show all records per user request
            ->get()
            ->getResult();
    }

}