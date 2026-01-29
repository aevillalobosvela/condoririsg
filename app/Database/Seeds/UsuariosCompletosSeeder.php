<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class UsuariosCompletosSeeder extends Seeder
{
    public function run()
    {
        $usuarios = [
            // Saltamos admin porque ya se crea en InitialDataSeeder
            [
                'nombre' => 'María',
                'apellidos' => 'Ventas López',
                'usuario' => 'vendedor1',
                'correo' => 'vendedor@condoriri.com',
                'password' => password_hash('vend123', PASSWORD_DEFAULT),
                'celular' => '70234567',
                'direccion' => 'Calle Brasil #456',
                'rol_id' => 2,
                'sucursal_id' => 1,
                'estado' => true,
                'ci' => 23456789,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Juan',
                'apellidos' => 'Almacén Pérez',
                'usuario' => 'almacen1',
                'correo' => 'almacen@condoriri.com',
                'password' => password_hash('alm123', PASSWORD_DEFAULT),
                'celular' => '70345678',
                'direccion' => 'Zona Norte #789',
                'rol_id' => 3,
                'sucursal_id' => 2,
                'estado' => true,
                'ci' => 34567890,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Ana',
                'apellidos' => 'Contadora Silva',
                'usuario' => 'contador1',
                'correo' => 'contabilidad@condoriri.com',
                'password' => password_hash('cont123', PASSWORD_DEFAULT),
                'celular' => '70456789',
                'direccion' => 'Av. Circunvalación #321',
                'rol_id' => 4,
                'sucursal_id' => 1,
                'estado' => true,
                'ci' => 45678901,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Pedro',
                'apellidos' => 'Campo Rodríguez',
                'usuario' => 'agro1',
                'correo' => 'agropecuario@condoriri.com',
                'password' => password_hash('agro123', PASSWORD_DEFAULT),
                'celular' => '70567890',
                'direccion' => 'Comunidad Condoriri',
                'rol_id' => 5,
                'sucursal_id' => 2,
                'estado' => true,
                'ci' => 56789012,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Luis',
                'apellidos' => 'Ganadero Mamani',
                'usuario' => 'ganadero1',
                'correo' => 'ganaderia@condoriri.com',
                'password' => password_hash('gan123', PASSWORD_DEFAULT),
                'celular' => '70678901',
                'direccion' => 'Estancia Condoriri',
                'rol_id' => 6,
                'sucursal_id' => 2,
                'estado' => true,
                'ci' => 67890123,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Roberto',
                'apellidos' => 'Developer Quispe',
                'usuario' => 'dev1',
                'correo' => 'dev@condoriri.com',
                'password' => password_hash('dev123', PASSWORD_DEFAULT),
                'celular' => '70789012',
                'direccion' => 'Zona Central #654',
                'rol_id' => 7,
                'sucursal_id' => 1,
                'estado' => true,
                'ci' => 78901234,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Miguel',
                'apellidos' => 'Transportista Choque',
                'usuario' => 'envios1',
                'correo' => 'envios@condoriri.com',
                'password' => password_hash('env123', PASSWORD_DEFAULT),
                'celular' => '70890123',
                'direccion' => 'Villa Sebastián Pagador',
                'rol_id' => 8,
                'sucursal_id' => 3,
                'estado' => true,
                'ci' => 89012345,
                'created_at' => Time::now()
            ]
        ];

        foreach ($usuarios as $usuario) {
            $this->db->table('condoriri.usuarios')->insert($usuario);
        }
        
        echo "Seeder de Usuarios Completos ejecutado correctamente.\n";
    }
}