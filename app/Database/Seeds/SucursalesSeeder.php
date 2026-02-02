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

        // USAR EL ESQUEMA CORRECTO: condoriri.sucursales
        $this->db->table('condoriri.sucursales')->insertBatch($data);
        echo "Seeder de Sucursales ejecutado correctamente.\n";
    }
}
