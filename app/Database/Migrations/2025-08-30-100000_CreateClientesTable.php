<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientesTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.clientes (
            id SERIAL PRIMARY KEY,
            nombre_completo VARCHAR(100) NOT NULL,
            ci_nit VARCHAR(20) NULL UNIQUE,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            user_id INT NULL,
            created_at TIMESTAMP DEFAULT NOW() NULL,
            updated_at TIMESTAMP DEFAULT NOW() NULL,
            deleted_at TIMESTAMP NULL
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign key
        $this->db->query('ALTER TABLE condoriri.clientes ADD CONSTRAINT fk_user_cliente FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE condoriri.clientes DROP CONSTRAINT IF EXISTS fk_user_cliente');
        $this->db->query('DROP TABLE IF EXISTS condoriri.clientes CASCADE');
    }
}