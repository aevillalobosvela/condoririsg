<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDetalleVentaTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.detalle_venta (
            id SERIAL PRIMARY KEY,
            venta_id INT NOT NULL,
            stock_id INT NULL,
            cantidad INT NOT NULL,
            precio_unitario NUMERIC(10, 2) NOT NULL,
            subtotal NUMERIC(10, 2) NOT NULL,
            observaciones TEXT NULL,
            created_at TIMESTAMP DEFAULT NOW() NULL,
            updated_at TIMESTAMP DEFAULT NOW() NULL,
            deleted_at TIMESTAMP NULL,
            producto_id INT NULL,
            producto_agro_id INT NULL,
            CONSTRAINT uk_detalle_stock UNIQUE (venta_id, stock_id)
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign keys
        $this->db->query('ALTER TABLE condoriri.detalle_venta ADD CONSTRAINT fk_stock_item FOREIGN KEY (stock_id) REFERENCES condoriri.stock_sucursales(id)');
        $this->db->query('ALTER TABLE condoriri.detalle_venta ADD CONSTRAINT fk_venta FOREIGN KEY (venta_id) REFERENCES condoriri.ventas(id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE condoriri.detalle_venta DROP CONSTRAINT IF EXISTS fk_stock_item');
        $this->db->query('ALTER TABLE condoriri.detalle_venta DROP CONSTRAINT IF EXISTS fk_venta');
        $this->db->query('DROP TABLE IF EXISTS condoriri.detalle_venta CASCADE');
    }
}