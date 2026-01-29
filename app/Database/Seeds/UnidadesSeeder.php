<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class UnidadesSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre' => 'Litros',
                'descripcion' => 'Unidad de medida para líquidos',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Kilogramos',
                'descripcion' => 'Unidad de medida para peso',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Unidades',
                'descripcion' => 'Productos por unidad individual',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Gramos',
                'descripcion' => 'Unidad de medida para peso pequeño',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $unidad) {
            $this->db->table('condoriri.unidades')->insert($unidad);
        }
        echo "Seeder de Unidades ejecutado correctamente.\n";
    }
}