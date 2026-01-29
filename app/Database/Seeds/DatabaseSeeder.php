<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "=== Iniciando seeders completos del sistema ===\n";
        
        try {
            // 1. Roles
            $this->call('RolesSeeder');
            
            // 2. Datos iniciales
            $this->call('InitialDataSeeder');
            
            // 3. Usuarios completos
            $this->call('UsuariosCompletosSeeder');
            
            // 4. Datos básicos
            $this->call('CategoriasSeeder');
            $this->call('UnidadesSeeder');
            $this->call('EstadosSeeder');
            
            // 5. Inventarios y Clientes
            $this->call('InventariosSeeder');
            $this->call('ClientesSeeder');
            
            // 6. Productos y Stock
            $this->call('ProductosSeeder');
            $this->call('StockSucursalesSeeder');
            
            // 7. Operaciones
            $this->call('VentasSeeder');
            $this->call('DetalleVentaSeeder');
            
            echo "=== Sistema completamente poblado ===\n";
            echo "Usuarios: admin/admin123, vendedor1/vend123, almacen1/alm123, etc.\n";
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "Ejecuta seeders individuales para identificar el problema.\n";
        }
    }
}