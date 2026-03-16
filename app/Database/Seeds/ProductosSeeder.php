<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class ProductosSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre' => 'Leche Fresca',
                'descripcion' => 'Leche fresca de vaca, pasteurizada',
                'precio_credito' => 3.50,
                'precio_contado' => 3.00,
                'stock' => 100,
                'estado' => true,
                'categoria_id' => 1,
                'unidad_id' => 1, // Litros
                'fecha_vencimiento' => date('Y-m-d', strtotime('+7 days')),
                'cantidad_produccion' => 500,
                'ph' => '6.7',
                'acides' => '0.15',
                'consistencia' => 'Líquida',
                'color' => 'Blanco',
                'olor' => 'Característico',
                'textura' => 'Suave',
                'inventario_id' => 1,
                'user_id' => 3,
                'cantidad_unidad' => 1,
                'litros' => 1.0,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Queso Fresco',
                'descripcion' => 'Queso fresco artesanal',
                'precio_credito' => 25.00,
                'precio_contado' => 22.00,
                'stock' => 50,
                'estado' => true,
                'categoria_id' => 2,
                'unidad_id' => 2, // Kilogramos
                'fecha_vencimiento' => date('Y-m-d', strtotime('+15 days')),
                'cantidad_produccion' => 20,
                'consistencia' => 'Semi-dura',
                'color' => 'Blanco cremoso',
                'olor' => 'Suave',
                'textura' => 'Firme',
                'inventario_id' => 2,
                'user_id' => 3,
                'cantidad_unidad' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Yogurt Natural',
                'descripcion' => 'Yogurt natural sin azúcar',
                'precio_credito' => 4.50,
                'precio_contado' => 4.00,
                'stock' => 80,
                'estado' => true,
                'categoria_id' => 3,
                'unidad_id' => 3, // Unidades
                'fecha_vencimiento' => date('Y-m-d', strtotime('+10 days')),
                'cantidad_produccion' => 200,
                'ph' => '4.2',
                'acides' => '0.9',
                'consistencia' => 'Cremosa',
                'color' => 'Blanco',
                'olor' => 'Ácido suave',
                'textura' => 'Cremosa',
                'inventario_id' => 1,
                'user_id' => 3,
                'cantidad_unidad' => 200,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Mantequilla',
                'descripcion' => 'Mantequilla artesanal sin sal',
                'precio_credito' => 18.00,
                'precio_contado' => 16.00,
                'stock' => 30,
                'estado' => true,
                'categoria_id' => 1,
                'unidad_id' => 4, // Gramos
                'fecha_vencimiento' => date('Y-m-d', strtotime('+30 days')),
                'cantidad_produccion' => 10,
                'consistencia' => 'Sólida',
                'color' => 'Amarillo claro',
                'olor' => 'Lácteo',
                'textura' => 'Cremosa',
                'inventario_id' => 2,
                'user_id' => 3,
                'cantidad_unidad' => 500,
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $producto) {
            $this->db->table('condoriri.productos')->insert($producto);
        }
        echo "Seeder de Productos ejecutado correctamente.\n";
    }
}