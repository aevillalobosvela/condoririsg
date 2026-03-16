<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBajasTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.bajas (
            id SERIAL PRIMARY KEY,
            producto_id INT NOT NULL,
            cantidad INT NOT NULL,
            observacion TEXT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT NOW() NULL,
            CONSTRAINT bajas_cantidad_check CHECK (cantidad > 0)
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign keys
        $this->db->query('ALTER TABLE condoriri.bajas ADD CONSTRAINT fk_bajas_producto FOREIGN KEY (producto_id) REFERENCES condoriri.productos(id) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE condoriri.bajas ADD CONSTRAINT fk_bajas_usuario FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE RESTRICT');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE condoriri.bajas DROP CONSTRAINT IF EXISTS fk_bajas_producto');
        $this->db->query('ALTER TABLE condoriri.bajas DROP CONSTRAINT IF EXISTS fk_bajas_usuario');
        $this->db->query('DROP TABLE IF EXISTS condoriri.bajas CASCADE');
    }
}