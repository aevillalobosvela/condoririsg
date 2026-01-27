<?php

namespace App\Models\Baja;

use CodeIgniter\Model;

class BajaModel extends Model
{
    protected $table = 'condoriri.bajas';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['producto_id', 'cantidad', 'observacion', 'user_id'];
    


    // ✅ PostgreSQL usa timestamps con zona horaria; CodeIgniter lo maneja bien
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null; // no usamos updated_at
    protected $deletedField  = null;

    // ✅ Validación (mejorada para evitar falsos positivos en PostgreSQL)
    protected $validationRules = [
        'producto_id' => [
            'rules'  => 'required|is_natural_no_zero',
            'errors' => [
                'required'          => 'El producto es obligatorio.',
                'is_natural_no_zero' => 'ID de producto inválido.',
            ]
        ],
        'cantidad' => [
            'rules'  => 'required|is_natural_no_zero|less_than_equal_to[1000000]',
            'errors' => [
                'required'            => 'La cantidad es obligatoria.',
                'is_natural_no_zero'  => 'La cantidad debe ser un número entero mayor a 0.',
                'less_than_equal_to'  => 'La cantidad es demasiado grande.',
            ]
        ],
        'user_id' => [
            'rules'  => 'required|is_natural_no_zero',
            'errors' => [
                'required'          => 'Usuario no identificado.',
                'is_natural_no_zero' => 'ID de usuario inválido.',
            ]
        ],
    ];

    /**
     * Obtiene todas las bajas con información del producto y usuario
     */
 public function getBajasConDetalles()
{
    return $this->select('
        condoriri.bajas.*,
        condoriri.productos.nombre as producto_nombre,
        condoriri.productos.code as producto_code,
        condoriri.usuarios.nombre as usuario_nombre,
        condoriri.usuarios.apellidos as usuario_apellidos,
        condoriri.usuarios.ci as usuario_ci
    ')
    ->join('condoriri.productos', 'condoriri.productos.id = condoriri.bajas.producto_id', 'left')
    ->join('condoriri.usuarios', 'condoriri.usuarios.id = condoriri.bajas.user_id', 'left')
    ->orderBy('condoriri.bajas.created_at', 'DESC')
    ->findAll();
}

    /**
     * Registra una baja y actualiza el stock del producto (opcional)
     * 
     * @param int $productoId
     * @param int $cantidad
     * @param string $observacion
     * @param int $userId
     * @param bool $actualizarStock Si true, resta `cantidad` de `stock_inve` del producto
     * @return array ['success' => true|false, 'id' => ?, 'error' => ?]
     */
    public function registrarConStock(int $productoId, int $cantidad, string $observacion, int $userId, bool $actualizarStock = true): array
    {
        // Iniciar transacción
        $db = $this->db;
        $db->transBegin();

        try {
            // 1. Insertar la baja
            $bajaData = [
                'producto_id' => $productoId,
                'cantidad'    => $cantidad,
                'observacion' => $observacion,
                'user_id'     => $userId,
            ];

            $bajaId = $this->insert($bajaData);
            if (!$bajaId) {
                throw new \Exception('Error al registrar la baja: ' . json_encode($this->errors()));
            }

            // 2. Actualizar stock en productos (si se solicita)
            if ($actualizarStock) {
                $productoModel = model('App\Models\Producto\ProductoModel');
                $producto = $productoModel->find($productoId);

                if (!$producto) {
                    throw new \Exception("Producto ID {$productoId} no encontrado.");
                }

                $nuevoStock = ($producto->stock_inve ?? 0) - $cantidad;
                if ($nuevoStock < 0) {
                    throw new \Exception("Stock insuficiente. Disponible: {$producto->stock_inve}.");
                }

                if (!$productoModel->update($productoId, ['stock_inve' => $nuevoStock])) {
                    throw new \Exception('Error al actualizar stock del producto.');
                }
            }

            $db->transCommit();
            return ['success' => true, 'id' => $bajaId];

        } catch (\Exception $e) {
            $db->transRollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}