<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'nombre' => 'admin',
                'descripcion' => 'Administrador del sistema',
                'estado' => true,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'vendedor',
                'descripcion' => 'Encargado de ventas',
                'estado' => true,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'almacen',
                'descripcion' => 'Encargado de almacén e inventarios',
                'estado' => true,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'contabilidad',
                'descripcion' => 'Encargado de contabilidad',
                'estado' => true,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'agropecuario',
                'descripcion' => 'Encargado del área agropecuaria',
                'estado' => true,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'ganaderia',
                'descripcion' => 'Encargado del área de ganadería',
                'estado' => true,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'dev',
                'descripcion' => 'Desarrollador del sistema',
                'estado' => true,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'envios',
                'descripcion' => 'Encargado de envíos y transporte',
                'estado' => true,
                'created_at' => Time::now()
            ]
        ];

        foreach ($roles as $rol) {
            $this->db->table('condoriri.roles')->insert($rol);
        }
        
        echo "Seeder de Roles ejecutado correctamente.\n";
    }
}
