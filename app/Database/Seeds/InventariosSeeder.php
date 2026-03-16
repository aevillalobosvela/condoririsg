<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class InventariosSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre' => 'Inventario Oruro',
                'code' => 'INV-ORU-001',
                'descripcion' => 'Inventario principal sucursal Oruro',
                'stock' => 0,
                'turno' => 'Mañana',
                'estado' => true,
                'sucursal_id' => 1,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Inventario Condoriri',
                'code' => 'INV-CON-001',
                'descripcion' => 'Inventario producción Condoriri',
                'stock' => 0,
                'turno' => 'Mañana',
                'estado' => true,
                'sucursal_id' => 2,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Inventario Ventas Oruro',
                'code' => 'INV-VEN-001',
                'descripcion' => 'Inventario ventas Oruro',
                'stock' => 0,
                'turno' => 'Mañana',
                'estado' => true,
                'sucursal_id' => 3,
                'user_id' => 1,
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $inventario) {
            $this->db->table('condoriri.inventarios')->insert($inventario);
        }
        echo "Seeder de Inventarios ejecutado correctamente.\n";
    }
}