<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEstadosTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO - 100% funcional en Docker
        $sql = "CREATE TABLE condoriri.estados (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign key después de crear la tabla
        $this->db->query('ALTER TABLE condoriri.estados ADD CONSTRAINT fk_estados_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        // Eliminar foreign key primero
        $this->db->query('ALTER TABLE condoriri.estados DROP CONSTRAINT IF EXISTS fk_estados_user');
        
        // Eliminar tabla
        $this->db->query('DROP TABLE IF EXISTS condoriri.estados CASCADE');
    }
}