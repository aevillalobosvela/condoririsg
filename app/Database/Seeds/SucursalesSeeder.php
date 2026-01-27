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
                'nombre'    => 'oruro',
                'descripcion' => 'tineda',
                'direccion'   => '6 de octubre entre aroma',
                'telefono'    => '456789',
                 'estado' => true,
                'user_id'  => '1',
                'created_at' => Time::now()
            ],
            [
                'nombre'    => 'codoriri',
                'descripcion' => 'produccion de leches',
                'direccion'   => '6 de octubre entre aroma',
                'telefono'    => '456789',
                'estado' => true,
                'user_id'  => '1',
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'ventas oruro  ',
                'descripcion' => 'productos ventas',
                'direccion'   => '6 de octubre entre aroma',
                'telefono'    => '456789',
                'estado' => true,
                'user_id'  => '1',
                'created_at' => Time::now()
            ]

        ];

        // USAR EL ESQUEMA CORRECTO: condoriri.usuarios
        $this->db->table('condoriri.sucursales')->insertBatch($data);
        echo "Seeder de Sucursales ejecutado correctamente.\n";
    }
}
