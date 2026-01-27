<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSucursalesTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO - 100% funcional en Docker
        $sql = "CREATE TABLE condoriri.sucursales (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            direccion VARCHAR(255) NOT NULL,
            telefono VARCHAR(15) NULL,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )";
        
        $this->db->query($sql);
        
        // Opcional: agregar foreign key después
        // $this->db->query('ALTER TABLE condoriri.sucursales ADD CONSTRAINT fk_sucursales_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id)');
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS condoriri.sucursales CASCADE');
    }
}