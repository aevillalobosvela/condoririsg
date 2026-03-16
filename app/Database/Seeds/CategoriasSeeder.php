<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class CategoriasSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre' => 'Lácteos',
                'descripcion' => 'Productos derivados de la leche',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Quesos',
                'descripcion' => 'Variedad de quesos artesanales',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ],
            [
                'nombre' => 'Yogurt',
                'descripcion' => 'Yogurt natural y con sabores',
                'estado' => true,
                'user_id' => 1,
                'created_at' => Time::now()
            ]
        ];

        foreach ($data as $categoria) {
            $this->db->table('condoriri.categorias')->insert($categoria);
        }
        echo "Seeder de Categorías ejecutado correctamente.\n";
    }
}