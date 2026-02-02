<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
        echo "=== Configurando datos iniciales (resolviendo dependencias circulares) ===\n";
        
        // 1. Crear sucursales temporalmente sin user_id válido
        $sucursalesData = [
            [
                'id' => 2,
                'nombre' => 'ORURO-VENTAS',
                'descripcion' => 'produccion de leches',
                'direccion' => '6 de octubre entre aroma',
                'telefono' => '456789',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'id' => 5,
                'nombre' => 'ORURO-CENTRAL',
                'descripcion' => 'ADMINISTRACION ORURO COND',
                'direccion' => 'COCHABAMBA ORURO',
                'telefono' => '690901',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'id' => 11,
                'nombre' => 'AGROPECUARIOS',
                'descripcion' => 'productos de agropecuarios papa',
                'direccion' => 'condoriri',
                'telefono' => '',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'id' => 10,
                'nombre' => 'GANADERIA',
                'descripcion' => 'ganaderia camellos',
                'direccion' => 'condoriri',
                'telefono' => '',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'id' => 4,
                'nombre' => 'LACTEOS',
                'descripcion' => 'PRODUCTOR DE LACTEOS',
                'direccion' => 'CONDORIRI',
                'telefono' => '456789',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ]
        ];
        
        // Deshabilitar temporalmente la foreign key constraint
        $this->db->query('ALTER TABLE condoriri.sucursales DROP CONSTRAINT IF EXISTS fk_sucursales_user');
        
        $this->db->table('condoriri.sucursales')->insertBatch($sucursalesData);
        echo "Sucursales creadas temporalmente.\n";
        
        // 2. Crear usuario administrador
        $usuarioData = [
            'nombre' => 'Admin',
            'apellidos' => 'Sistema',
            'usuario' => 'admin',
            'correo' => 'admin@condoriri.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'rol_id' => 1,
            'sucursal_id' => 5, // ORURO-CENTRAL
            'estado' => true,
            'created_at' => Time::now()
        ];
        
        $this->db->table('condoriri.usuarios')->insert($usuarioData);
        echo "Usuario administrador creado.\n";
        
        // 3. Restaurar la foreign key constraint
        $this->db->query('ALTER TABLE condoriri.sucursales ADD CONSTRAINT fk_sucursales_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
        echo "Constraints restauradas.\n";
        
        echo "=== Datos iniciales configurados correctamente ===\n";
    }
}