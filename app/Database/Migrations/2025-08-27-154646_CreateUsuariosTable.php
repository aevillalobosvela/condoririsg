<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsuariosTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.usuarios (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellidos VARCHAR(150) NOT NULL,
            usuario VARCHAR(50) UNIQUE NOT NULL,
            correo VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            celular VARCHAR(20) NULL,
            direccion TEXT NULL,
            rol_id INT NOT NULL,
            sucursal_id INT NOT NULL,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            ci INT NULL
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign keys después de crear la tabla
        $this->db->query('ALTER TABLE condoriri.usuarios ADD CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES condoriri.roles(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE condoriri.usuarios ADD CONSTRAINT fk_usuarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        // Eliminar foreign keys primero
        $this->db->query('ALTER TABLE condoriri.usuarios DROP CONSTRAINT IF EXISTS fk_usuarios_rol');
        $this->db->query('ALTER TABLE condoriri.usuarios DROP CONSTRAINT IF EXISTS fk_usuarios_sucursal');
        
        // Eliminar tabla
        $this->db->query('DROP TABLE IF EXISTS condoriri.usuarios CASCADE');
    }
}