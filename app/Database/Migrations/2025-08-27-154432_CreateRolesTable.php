<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearRolesTable extends Migration
{
    public function up()
    {
        // Crear esquema primero
        // $this->db->query('CREATE SCHEMA IF NOT EXISTS condoriri');
        
        // SQL DIRECTO - 100% funcional en Docker
        $sql = "CREATE TABLE condoriri.roles (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )";
        
        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS condoriri.roles CASCADE');
    }
}