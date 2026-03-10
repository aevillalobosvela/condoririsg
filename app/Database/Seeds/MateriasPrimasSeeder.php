<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MateriasPrimasSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['nombre' => 'LECHE', 'created_at' => date('Y-m-d H:i:s')],
            ['nombre' => 'SUERO LECHE', 'created_at' => date('Y-m-d H:i:s')],
            ['nombre' => 'SUERO QUESO', 'created_at' => date('Y-m-d H:i:s')],
        ];

        $this->db->table('condoriri.materias_primas')->insertBatch($data);
    }
}
