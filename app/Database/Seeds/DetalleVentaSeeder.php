<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class DetalleVentaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // Venta 1: 5 litros de leche
            [
                'venta_id' => 1,
                'stock_id' => 1, // Leche en Oruro
                'cantidad' => 5,
                'precio_unitario' => 3.00,
                'subtotal' => 15.00,
                'producto_id' => 1,
                'created_at' => Time::now()->subDays(5)
            ],
            // Venta 2: 1kg queso + 2 yogurt
            [
                'venta_id' => 2,
                'stock_id' => 3, // Queso en Oruro
                'cantidad' => 1,
                'precio_unitario' => 25.00,
                'subtotal' => 25.00,
                'producto_id' => 2,
                'created_at' => Time::now()->subDays(3)
            ],
            [
                'venta_id' => 2,
                'stock_id' => 4, // Yogurt en Oruro
                'cantidad' => 1,
                'precio_unitario' => 4.50,
                'subtotal' => 4.50,
                'producto_id' => 3,
                'created_at' => Time::now()->subDays(3)
            ],
            // Venta 3: 4 litros de leche
            [
                'venta_id' => 3,
                'stock_id' => 2, // Leche en Ventas Oruro
                'cantidad' => 4,
                'precio_unitario' => 3.00,
                'subtotal' => 12.00,
                'producto_id' => 1,
                'created_at' => Time::now()->subDays(2)
            ],
            // Venta 4: Compra variada
            [
                'venta_id' => 4,
                'stock_id' => 1, // Leche
                'cantidad' => 3,
                'precio_unitario' => 3.00,
                'subtotal' => 9.00,
                'producto_id' => 1,
                'created_at' => Time::now()->subDays(1)
            ],
            [
                'venta_id' => 4,
                'stock_id' => 4, // Yogurt
                'cantidad' => 2,
                'precio_unitario' => 4.00,
                'subtotal' => 8.00,
                'producto_id' => 3,
                'created_at' => Time::now()->subDays(1)
            ],
            [
                'venta_id' => 4,
                'stock_id' => 5, // Mantequilla
                'cantidad' => 1,
                'precio_unitario' => 16.00,
                'subtotal' => 16.00,
                'producto_id' => 4,
                'created_at' => Time::now()->subDays(1)
            ],
            // Venta 5: 1kg queso (pendiente)
            [
                'venta_id' => 5,
                'stock_id' => 3,
                'cantidad' => 1,
                'precio_unitario' => 22.00,
                'subtotal' => 22.00,
                'producto_id' => 2,
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $detalle) {
            $this->db->table('condoriri.detalle_venta')->insert($detalle);
        }
        echo "Seeder de Detalle Venta ejecutado correctamente.\n";
    }
}