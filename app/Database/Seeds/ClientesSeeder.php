<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class ClientesSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre_completo' => 'Cliente General',
                'ci_nit' => '0',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre_completo' => 'Juan Pérez',
                'ci_nit' => '12345678',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre_completo' => 'María González',
                'ci_nit' => '87654321',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $cliente) {
            $this->db->table('condoriri.clientes')->insert($cliente);
        }
        echo "Seeder de Clientes ejecutado correctamente.\n";
    }
}