<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class EstadosSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre' => 'Activo',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Inactivo',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Pendiente',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Completado',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $estado) {
            $this->db->table('condoriri.estados')->insert($estado);
        }
        echo "Seeder de Estados ejecutado correctamente.\n";
    }
}