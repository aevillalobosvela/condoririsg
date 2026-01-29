<?php

namespace App\Database\Seeds;

use App\Models\Sucursal\SucursalModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class SucursalesSeeder extends Seeder
{
    public function run()
    {

   


        $data = [
            [
                'nombre'    => 'Oruro',
                'descripcion' => 'Tienda principal Oruro',
                'direccion'   => '6 de octubre entre Aroma',
                'telefono'    => '52456789',
                'estado' => true,
                'user_id'  => 1, // Se actualizará después de crear usuarios
                'created_at' => Time::now()
            ],
            [
                'nombre'    => 'Condoriri',
                'descripcion' => 'Planta de producción de lácteos',
                'direccion'   => 'Comunidad Condoriri',
                'telefono'    => '52456790',
                'estado' => true,
                'user_id'  => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Ventas Oruro',
                'descripcion' => 'Punto de venta Oruro',
                'direccion'   => 'Av. Brasil entre 6 de Octubre',
                'telefono'    => '52456791',
                'estado' => true,
                'user_id'  => 1,
                'created_at' => Time::now()
            ]
        ];

        // USAR EL ESQUEMA CORRECTO: condoriri.sucursales
        $this->db->table('condoriri.sucursales')->insertBatch($data);
        echo "Seeder de Sucursales ejecutado correctamente.\n";
    }
}
