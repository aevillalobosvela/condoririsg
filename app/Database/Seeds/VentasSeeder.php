<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class VentasSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'code' => 'VEN-001-' . date('Y'),
                'cliente_id' => 2, // Juan Pérez
                'sucursal_id' => 1,
                'tipo_pago' => 'Contado',
                'monto_total' => 15.00,
                'estado' => 'Pagada',
                'observaciones' => 'Venta regular de leche',
                'user_id' => 2, // Vendedor
                'created_at' => Time::now()->subDays(5)
            ],
            [
                'code' => 'VEN-002-' . date('Y'),
                'cliente_id' => 3, // María González
                'sucursal_id' => 1,
                'tipo_pago' => 'Crédito',
                'monto_total' => 29.50,
                'estado' => 'Pagada',
                'observaciones' => 'Compra de queso y yogurt',
                'user_id' => 2,
                'created_at' => Time::now()->subDays(3)
            ],
            [
                'code' => 'VEN-003-' . date('Y'),
                'cliente_id' => 1, // Cliente General
                'sucursal_id' => 3,
                'tipo_pago' => 'Contado',
                'monto_total' => 12.00,
                'estado' => 'Pagada',
                'observaciones' => 'Venta al por menor',
                'user_id' => 2,
                'created_at' => Time::now()->subDays(2)
            ],
            [
                'code' => 'VEN-004-' . date('Y'),
                'cliente_id' => 2,
                'sucursal_id' => 1,
                'tipo_pago' => 'Contado',
                'monto_total' => 38.00,
                'estado' => 'Pagada',
                'observaciones' => 'Compra semanal completa',
                'user_id' => 2,
                'created_at' => Time::now()->subDays(1)
            ],
            [
                'code' => 'VEN-005-' . date('Y'),
                'cliente_id' => 3,
                'sucursal_id' => 1,
                'tipo_pago' => 'Crédito',
                'monto_total' => 22.00,
                'estado' => 'Pendiente',
                'observaciones' => 'Pago pendiente - cliente frecuente',
                'user_id' => 2,
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $venta) {
            $this->db->table('condoriri.ventas')->insert($venta);
        }
        echo "Seeder de Ventas ejecutado correctamente.\n";
    }
}