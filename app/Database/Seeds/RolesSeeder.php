<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre' => 'admin',
                'descripcion' => 'Sistema',
                'estado' => true,
                'created_at' => Time::now()
            ],
             [
                'nombre' => 'vendedor',
                'descripcion' => 'venta de productos',
                'estado' => true,
                'created_at' => Time::now()
             ],
              [
                'nombre' => 'almacen',
                'descripcion' => 'productos lacteos',
                'estado' => true,
                'created_at' => Time::now()
            ]

        ];

        // USAR EL ESQUEMA CORRECTO: condoriri.usuarios
        $this->db->table('condoriri.roles')->insertBatch($data);
        echo "Seeder de Roles ejecutado correctamente.\n";
    }
}
