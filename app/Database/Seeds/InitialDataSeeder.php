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
                'nombre' => 'Oruro',
                'descripcion' => 'Tienda principal Oruro',
                'direccion' => '6 de octubre entre Aroma',
                'telefono' => '52456789',
                'estado' => true,
                'user_id' => 1, // Temporal, se actualizará después
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Condoriri',
                'descripcion' => 'Planta de producción de lácteos',
                'direccion' => 'Comunidad Condoriri',
                'telefono' => '52456790',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Ventas Oruro',
                'descripcion' => 'Punto de venta Oruro',
                'direccion' => 'Av. Brasil entre 6 de Octubre',
                'telefono' => '52456791',
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
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'rol_id' => 1,
            'sucursal_id' => 1,
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