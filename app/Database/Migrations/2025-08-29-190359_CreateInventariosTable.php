<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInventariosTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO - 100% funcional en Docker
        $sql = "CREATE TABLE condoriri.inventarios (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            code VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            stock INT DEFAULT 0 NOT NULL,
            turno VARCHAR(100) NOT NULL,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            sucursal_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign keys después de crear la tabla
        $this->db->query('ALTER TABLE condoriri.inventarios ADD CONSTRAINT fk_inventarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE condoriri.inventarios ADD CONSTRAINT fk_inventarios_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        // Eliminar foreign keys primero
        $this->db->query('ALTER TABLE condoriri.inventarios DROP CONSTRAINT IF EXISTS fk_inventarios_sucursal');
        $this->db->query('ALTER TABLE condoriri.inventarios DROP CONSTRAINT IF EXISTS fk_inventarios_user');
        
        // Eliminar tabla
        $this->db->query('DROP TABLE IF EXISTS condoriri.inventarios CASCADE');
    }
}