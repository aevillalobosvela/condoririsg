<?php

namespace App\Models\Venta;

use CodeIgniter\Model;

class DetalleModel extends Model
{
    // Configuración de la tabla
    protected $table = 'condoriri.detalle_venta';
    protected $primaryKey = 'id';

    // Campos permitidos para inserción y actualización
    protected $allowedFields = [
        'venta_id',
        'stock_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'observaciones',
        'producto_id',
        'producto_agro_id',
    ];

    // --- Manejo de Tiempos y Soft Deletes ---
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    // Habilita el "Soft Delete"
    protected $useSoftDeletes = true;
    protected $deletedField  = 'deleted_at';


}
