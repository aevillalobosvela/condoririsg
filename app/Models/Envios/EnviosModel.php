<?php

namespace App\Models\Envios;

use CodeIgniter\Model;

class EnviosModel extends Model
{
    protected $table      = 'condoriri.envios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'code',
        'sucursal_origen_id',
        'sucursal_destino_id',
        'observacion_origen',
        'observacion_destino',
        'estado_id',
        'fecha_envio',
        'fecha_recepcion',
        'user_transporte_id',
        'user_id',
        'user_recepcion_id',
        'tipo'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'estado_id'          => 'required|integer',
        'fecha_envio'        => 'required|valid_date',
        'user_transporte_id' => 'required|integer',
        'user_id'            => 'required|integer',
        'user_recepcion_id'  => 'permit_empty|integer',
        'sucursal_origen_id' => 'permit_empty|integer',
        'sucursal_destino_id'=> 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'code' => [
            'required'   => 'El código de envío es obligatorio.',
            'is_unique'  => 'Este código de envío ya existe.',
            'max_length' => 'El código no puede tener más de 100 caracteres.',
        ],
        'estado_id' => [
            'required' => 'El ID del estado es obligatorio.',
            'integer'  => 'El ID del estado debe ser un número entero.',
        ],
        'fecha_envio' => [
            'required'   => 'La fecha de envío es obligatoria.',
            'valid_date' => 'La fecha de envío no tiene un formato válido.',
        ],
        'user_transporte_id' => [
            'required' => 'El ID del usuario de transporte es obligatorio.',
            'integer'  => 'El ID del usuario de transporte debe ser un número entero.',
        ],
        'user_id' => [
            'required' => 'El ID del usuario es obligatorio.',
            'integer'  => 'El ID del usuario debe ser un número entero.',
        ],
        'user_recepcion_id' => [
            'integer'  => 'El ID del usuario que recibe debe ser un número entero.',
        ]
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Obtiene un envío con los IDs de inventario de origen y destino
     */
    public function getEnvioWithRelations(int $id): ?array
    {
        return $this->select('
                condoriri.envios.*,
                inv_origen.id as inventario_origen_id,
                inv_dest.id as inventario_destino_id,
                so.nombre as sucursal_origen_nombre,
                sd.nombre as sucursal_destino_nombre
            ')
            ->join('condoriri.inventarios as inv_origen', 'inv_origen.sucursal_id = condoriri.envios.sucursal_origen_id')
            ->join('condoriri.inventarios as inv_dest', 'inv_dest.sucursal_id = condoriri.envios.sucursal_destino_id')
            ->join('condoriri.sucursales as so', 'so.id = condoriri.envios.sucursal_origen_id')
            ->join('condoriri.sucursales as sd', 'sd.id = condoriri.envios.sucursal_destino_id')
            ->where('condoriri.envios.id', $id)
            ->first();
    }
}