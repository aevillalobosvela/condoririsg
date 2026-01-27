<?php

namespace App\Models\Devoluciones;

use CodeIgniter\Model;

class DevolucionesModel extends Model
{
    protected $table      = 'condoriri.devoluciones';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'code',
        'cantidad',
        'observacion',
        'envio_id',
        'user_id',
        'observacion_recepcion',
        'user_recepcion_id',
        'estado_id',
        'stock_sucursales_id',
        'producto_id',
        'sucursales_id',
        'sucursales_destino_id'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'cantidad'              => 'required|integer|greater_than[0]',
        'observacion'           => 'permit_empty|string',
        'user_id'               => 'required|integer',
        'producto_id'           => 'required|integer',
        'sucursales_id'         => 'required|integer',
        'sucursales_destino_id' => 'required|integer',
        'estado_id'             => 'permit_empty|integer'
    ];

    protected $validationMessages = [
        'cantidad' => [
            'required' => 'La cantidad es obligatoria.',
            'greater_than' => 'La cantidad debe ser mayor a 0.'
        ],
        'sucursales_destino_id' => [
            'required' => 'Debe seleccionar una sucursal de destino.'
        ]
    ];

    /**
     * Get devoluciones with details
     */
    public function getDevolucionesWithDetails()
    {
        return $this->select('
                condoriri.devoluciones.*,
                so.nombre as sucursal_origen_nombre,
                sd.nombre as sucursal_destino_nombre,
                p.nombre as producto_nombre,
                u.usuario as usuario_nombre,
                e.nombre as estado_nombre
            ')
            ->join('condoriri.sucursales as so', 'so.id = condoriri.devoluciones.sucursales_id')
            ->join('condoriri.sucursales as sd', 'sd.id = condoriri.devoluciones.sucursales_destino_id')
            ->join('condoriri.productos as p', 'p.id = condoriri.devoluciones.producto_id')
            ->join('condoriri.usuarios as u', 'u.id = condoriri.devoluciones.user_id')
            ->join('condoriri.estados as e', 'e.id = condoriri.devoluciones.estado_id', 'left')
            ->orderBy('condoriri.devoluciones.created_at', 'DESC')
            ->findAll();
    }
    
    public function getDevolucionWithDetails($id)
    {
         return $this->select('
                condoriri.devoluciones.*,
                so.nombre as sucursal_origen_nombre,
                sd.nombre as sucursal_destino_nombre,
                p.nombre as producto_nombre,
                u.usuario as usuario_nombre,
                e.nombre as estado_nombre
            ')
            ->join('condoriri.sucursales as so', 'so.id = condoriri.devoluciones.sucursales_id')
            ->join('condoriri.sucursales as sd', 'sd.id = condoriri.devoluciones.sucursales_destino_id')
            ->join('condoriri.productos as p', 'p.id = condoriri.devoluciones.producto_id')
            ->join('condoriri.usuarios as u', 'u.id = condoriri.devoluciones.user_id')
            ->join('condoriri.estados as e', 'e.id = condoriri.devoluciones.estado_id', 'left')
            ->where('condoriri.devoluciones.id', $id)
            ->first();
    }
}
