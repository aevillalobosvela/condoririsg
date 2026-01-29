<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class StockSucursalesSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // Leche Fresca en sucursales
            [
                'producto_id' => 1,
                'sucursal_id' => 1, // Oruro
                'cantidad' => 50,
                'stock' => 50,
                'precio_contado' => 3.00,
                'precio_credito' => 3.50,
                'estado' => true,
                'user_id' => 3,
                'producto' => 'Leche Fresca',
                'categoria' => 'Lácteos',
                'unidad' => 'Litros',
                'created_at' => Time::now()
            ],
            [
                'producto_id' => 1,
                'sucursal_id' => 3, // Ventas Oruro
                'cantidad' => 30,
                'stock' => 30,
                'precio_contado' => 3.00,
                'precio_credito' => 3.50,
                'estado' => true,
                'user_id' => 3,
                'producto' => 'Leche Fresca',
                'categoria' => 'Lácteos',
                'unidad' => 'Litros',
                'created_at' => Time::now()
            ],
            // Queso Fresco
            [
                'producto_id' => 2,
                'sucursal_id' => 1,
                'cantidad' => 25,
                'stock' => 25,
                'precio_contado' => 22.00,
                'precio_credito' => 25.00,
                'estado' => true,
                'user_id' => 3,
                'producto' => 'Queso Fresco',
                'categoria' => 'Quesos',
                'unidad' => 'Kilogramos',
                'created_at' => Time::now()
            ],
            // Yogurt Natural
            [
                'producto_id' => 3,
                'sucursal_id' => 1,
                'cantidad' => 40,
                'stock' => 40,
                'precio_contado' => 4.00,
                'precio_credito' => 4.50,
                'estado' => true,
                'user_id' => 3,
                'producto' => 'Yogurt Natural',
                'categoria' => 'Yogurt',
                'unidad' => 'Unidades',
                'created_at' => Time::now()
            ],
            // Mantequilla
            [
                'producto_id' => 4,
                'sucursal_id' => 1,
                'cantidad' => 15,
                'stock' => 15,
                'precio_contado' => 16.00,
                'precio_credito' => 18.00,
                'estado' => true,
                'user_id' => 3,
                'producto' => 'Mantequilla',
                'categoria' => 'Lácteos',
                'unidad' => 'Gramos',
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $stock) {
            $this->db->table('condoriri.stock_sucursales')->insert($stock);
        }
        echo "Seeder de Stock Sucursales ejecutado correctamente.\n";
    }
}