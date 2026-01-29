<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockSucursalesTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.stock_sucursales (
            id SERIAL PRIMARY KEY,
            producto_id INT NOT NULL,
            sucursal_id INT NOT NULL,
            cantidad INT DEFAULT 0 NOT NULL,
            stock INT DEFAULT 0 NOT NULL,
            precio_contado NUMERIC(10, 2) NOT NULL,
            precio_credito NUMERIC(10, 2) NOT NULL,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            user_id INT NULL,
            created_at TIMESTAMP DEFAULT NOW() NULL,
            updated_at TIMESTAMP DEFAULT NOW() NULL,
            producto VARCHAR NULL,
            categoria VARCHAR NULL,
            unidad VARCHAR NULL,
            CONSTRAINT uk_producto_sucursal UNIQUE (producto_id, sucursal_id)
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign keys
        $this->db->query('ALTER TABLE condoriri.stock_sucursales ADD CONSTRAINT fk_producto FOREIGN KEY (producto_id) REFERENCES condoriri.productos(id)');
        $this->db->query('ALTER TABLE condoriri.stock_sucursales ADD CONSTRAINT fk_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id)');
        $this->db->query('ALTER TABLE condoriri.stock_sucursales ADD CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE condoriri.stock_sucursales DROP CONSTRAINT IF EXISTS fk_producto');
        $this->db->query('ALTER TABLE condoriri.stock_sucursales DROP CONSTRAINT IF EXISTS fk_sucursal');
        $this->db->query('ALTER TABLE condoriri.stock_sucursales DROP CONSTRAINT IF EXISTS fk_user');
        $this->db->query('DROP TABLE IF EXISTS condoriri.stock_sucursales CASCADE');
    }
}