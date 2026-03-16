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
                'id' => 1,
                'nombre' => 'activo',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'id' => 2,
                'nombre' => 'enviado',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'id' => 10,
                'nombre' => 'proceso',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'id' => 11,
                'nombre' => 'observado',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'id' => 9,
                'nombre' => 'aceptado',
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