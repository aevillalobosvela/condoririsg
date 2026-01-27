<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCategoriasTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO - 100% funcional en Docker
        $sql = "CREATE TABLE condoriri.categorias (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign key después de crear la tabla
        $this->db->query('ALTER TABLE condoriri.categorias ADD CONSTRAINT fk_categorias_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        // Eliminar foreign key primero
        $this->db->query('ALTER TABLE condoriri.categorias DROP CONSTRAINT IF EXISTS fk_categorias_user');
        
        // Eliminar tabla
        $this->db->query('DROP TABLE IF EXISTS condoriri.categorias CASCADE');
    }
}