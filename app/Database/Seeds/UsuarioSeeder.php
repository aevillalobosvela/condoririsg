<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre' => 'Admin',
                'apellidos' => 'Sistema',
                'usuario' => 'admin',
                'correo' => 'admin@condoriri.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'rol_id' => 1,
                'sucursal_id' => 1,
                'estado' => true,
                'created_at' => Time::now()
            ]
        ];

        // USAR EL ESQUEMA CORRECTO: condoriri.usuarios
        $this->db->table('condoriri.usuarios')->insertBatch($data);
        echo "Seeder de Usuarios ejecutado correctamente.\n";
    }
}