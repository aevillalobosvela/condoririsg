<?php

namespace App\Models\Inventario;

use CodeIgniter\Model;

class InventarioModel extends Model
{
    protected $table = 'condoriri.inventarios';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'nombre',
        'code',
        'descripcion',
        'stock',
        'turno',
        'estado',
        'sucursal_id',
        'user_id',
        'reserva',
        'grasa',
        'sng',
        'densidad',
        'lactosa',
        'solidos',
        'proteina',
        'agua',
        'temperatura',
        'congelacion',
        'ph',
        'fecha_calidad',
        'user_cali',
        
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'nombre'      => 'required|max_length[100]',
        'code'        => 'required|max_length[100]|is_unique[inventarios.code,id,{id}]',
        'descripcion' => 'permit_empty',
        'stock'       => 'required|greater_than_equal_to[0]',
        'turno'       => 'required|in_list[AM,PM]',
        'estado'      => 'required|in_list[0,1]',
        'sucursal_id' => 'required|integer',
        'user_id'     => 'required|integer',
    ];

    protected $validationMessages = [
        'code' => [
            'is_unique' => 'El código ingresado ya existe. Por favor, ingrese uno diferente.'
        ],
        'nombre' => [
            'required' => 'El campo "nombre" es obligatorio.'
        ],
        'stock' => [
            'required' => 'El campo "cantidad" es obligatorio.',
           
            'greater_than_equal_to' => 'El campo "cantidad" debe ser mayor o igual a cero.'
        ],
        'turno' => [
            'required' => 'El campo "turno" es obligatorio.'
        ],
       
        'sucursal_id' => [
            'required' => 'El campo "sucursal" es obligatorio.',
            'integer' => 'El campo "sucursal" debe ser un número entero.'
        ],
        'user_id' => [
            'required' => 'El campo "usuario" es obligatorio.',
            'integer' => 'El campo "usuario" debe ser un número entero.'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Filtra los inventarios por nombre, código y rango de fechas.
     *
     * @param string $nombre
     * @param string $fecha_inicio
     * @param string $fecha_fin
     * @return array
     */
    public function getFilteredInventarios(string $nombre = '', string $fecha_inicio = '', string $fecha_fin = '')
    {
        $builder = $this->builder();

        if (!empty($nombre)) {
            $builder->groupStart()
                ->like('nombre', $nombre)
                ->orLike('code', $nombre)
                ->groupEnd();
        }

        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $builder->where('created_at >=', $fecha_inicio)
                ->where('created_at <=', $fecha_fin . ' 23:59:59');
        }

        return $builder->orderBy('created_at', 'DESC')->get()->getResult();
    }


    public function getResumen($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->builder();
        $builder->select('COUNT(*) as total_registros, SUM(stock) as total_stock_producido');

        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $builder->where('created_at >=', $fechaInicio)
                    ->where('created_at <=', $fechaFin . ' 23:59:59');
        }

        return $builder->get()->getRow();
    }

    public function getResumenPorNombre1($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table('condoriri.productos');
        $builder->select('nombre, COUNT(*) as cantidad, SUM(stock) as total_producido, SUM(stock_inve) as total_stock_actual')
                ->groupBy('nombre');

        if (!empty($fechaInicio) && !empty($fechaFin)) {
            $builder->where('created_at >=', $fechaInicio)
                    ->where('created_at <=', $fechaFin . ' 23:59:59');
        }

        return $builder->get()->getResult();
    }
    
    
    public function getResumenPorNombre($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table('condoriri.productos');
        $builder->select('nombre, COUNT(*) as cantidad, SUM(stock) as total_producido, SUM(stock_inve) as total_stock_actual')
                ->groupBy('nombre');

        if (!empty($fechaInicio)) {
            $builder->where('created_at >=', $fechaInicio);
        }
        if (!empty($fechaFin)) {
            $builder->where('created_at <=', $fechaFin . ' 23:59:59');
        }    

    
    $resultados = $builder->get()->getResult();

  
    $sumaCantidad = 0;
    $sumaProducido = 0;
    $sumaStockActual = 0;

    
    foreach ($resultados as $fila) {
       
        $sumaCantidad += (int) $fila->cantidad;
        $sumaProducido += (int) $fila->total_producido;
        $sumaStockActual += (int) $fila->total_stock_actual;
    }

   
    return [
        'lista_productos' => $resultados,
        'totales_generales' => [
            'gran_total_cantidad'  => $sumaCantidad,
            'gran_total_producido' => $sumaProducido,
            'gran_total_stock'     => $sumaStockActual,
            'total_tipos_productos' => count($resultados)
        ]
    ];
}



    
}
