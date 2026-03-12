<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMateriasPrimasTable extends Migration
{
    public function up()
    {
        $sql = "CREATE TABLE condoriri.materias_primas (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )";
        
        $this->db->query($sql);
        
        // Insertar datos iniciales
        $this->db->query("INSERT INTO condoriri.materias_primas (nombre, created_at) VALUES 
            ('LECHE', NOW()),
            ('SUERO LECHE', NOW()),
            ('SUERO QUESO', NOW())
        ");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS condoriri.materias_primas CASCADE');
    }
}
