<?php

namespace App\Models\TransferirProducto;

use CodeIgniter\Model;

class TransferirProductosModel extends Model
{
    protected $table = 'condoriri.transferencias_productos';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'envio_id',
        'producto_id',
        'cantidad',
        'precio_contado',
        'precio_credito',
        'inventario_origen_id',
        'observacion_origen',
        'observacion_destino',
        'nota_origen',
        'nota_destino',
        'estado_id',
        'user_id',
        'user_recepcion_id',
        'cantidad_acep'
    ];

    protected $validationRules = [
        'envio_id'              => 'required|integer',
        'producto_id'           => 'required|integer',
        'cantidad'              => 'required|integer|greater_than[0]',
        'precio_contado'        => 'required|decimal|greater_than_equal_to[0]',
        'precio_credito'        => 'required|decimal|greater_than_equal_to[0]',
        // 'inventario_origen_id'  => 'required|integer',
        'estado_id'             => 'required|integer',
        'user_id'               => 'required|integer',
        'user_recepcion_id'     => 'integer',
    ];

    protected $validationMessages = [
        'envio_id' => [
            'required' => 'El ID del envío es obligatorio.',
            'integer'  => 'El ID del envío debe ser un número entero.'
        ],
        'producto_id' => [
            'required' => 'El ID del producto es obligatorio.',
            'integer'  => 'El ID del producto debe ser un número entero.'
        ],
        'cantidad' => [
            'required'      => 'La cantidad es obligatoria.',
            'integer'       => 'La cantidad debe ser un número entero.',
            'greater_than'  => 'La cantidad debe ser mayor que cero.'
        ],
        'precio_contado' => [
            'required'      => 'El precio al contado es obligatorio.',
            'decimal'       => 'El precio al contado debe ser un número decimal.',
            'greater_than_equal_to' => 'El precio al contado debe ser mayor o igual a cero.',
        ],
        'precio_credito' => [
            'required'      => 'El precio al crédito es obligatorio.',
            'decimal'       => 'El precio al crédito debe ser un número decimal.',
            'greater_than_equal_to' => 'El precio al crédito debe ser mayor o igual a cero.',
        ],
        'inventario_origen_id' => [
            // 'required' => 'El ID del inventario de origen es obligatorio.',
            'integer'  => 'El ID del inventario de origen debe ser un número entero.'
        ],
        'estado_id' => [
            'required' => 'El ID del estado es obligatorio.',
            'integer'  => 'El ID del estado debe ser un número entero.'
        ],
        'user_id' => [
            'required' => 'El ID del usuario es obligatorio.',
            'integer'  => 'El ID del usuario debe ser un número entero.'
        ],
        'user_recepcion_id' => [
            'required' => 'El ID del usuario de recepción es obligatorio.',
            'integer'  => 'El ID del usuario de recepción debe ser un número entero.'
        ],
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $skipValidation = false;



    public function getTransfersWithDetails()
    {
        return $this->select('transferencias_productos.*, productos.nombre as producto_nombre, estados.nombre as estado_nombre, usuarios.nombre as user_nombre')
            ->join('condoriri.productos', 'productos.id = transferencias_productos.producto_id', 'left')
            ->join('condoriri.estados', 'estados.id = transferencias_productos.estado_id', 'left')
            ->join('condoriri.usuarios', 'usuarios.id = transferencias_productos.user_id', 'left')
            ->findAll();
    }
    // public function getTransferenciasWithProductData(int $envioId)
    // {
    //     return $this->select('
    //         transferencias_productos.*,
    //         condoriri.productos.nombre as producto_nombre,
    //         condoriri.productos.precio_contado,
    //         condoriri.estados.nombre as estado_nombre
    //     ')
    //         ->join('condoriri.productos', 'condoriri.productos.id = transferencias_productos.producto_id')
    //         ->join('condoriri.estados', 'condoriri.estados.id = transferencias_productos.estado_id')
    //         ->where('transferencias_productos.envio_id', $envioId)
    //         ->get()
    //         ->getResult();
    // }
    public function getTransferenciasWithProductData(int $envioId)
    {
        return $this->select('
            transferencias_productos.*,
            condoriri.productos.nombre as producto_nombre,
            condoriri.productos.precio_contado,
            condoriri.productos.precio_credito, -- <-- Nuevo campo incluido
            condoriri.estados.nombre as estado_nombre
        ')
            ->join('condoriri.productos', 'condoriri.productos.id = transferencias_productos.producto_id')
            ->join('condoriri.estados', 'condoriri.estados.id = transferencias_productos.estado_id')
            ->where('transferencias_productos.envio_id', $envioId)
            ->get()
            ->getResult();
    }
}
