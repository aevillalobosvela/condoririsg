<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductosAgroTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.productos_agro (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            producto VARCHAR(255) NOT NULL,
            descripcion TEXT NULL,
            cantidad INT DEFAULT 0 NOT NULL,
            categoria VARCHAR(255) NOT NULL,
            unidad_id INT NOT NULL,
            precio_credito FLOAT NOT NULL,
            precio_contado FLOAT NOT NULL,
            cantidad_inve INT DEFAULT 0 NOT NULL,
            fecha_creacion TIMESTAMPTZ DEFAULT NOW() NOT NULL,
            fecha_update TIMESTAMPTZ NULL,
            fecha_delete TIMESTAMPTZ NULL,
            sucursal_id INT NOT NULL,
            user_id INT NOT NULL,
            estado BOOLEAN NULL,
            CONSTRAINT productos_agro_cantidad_check CHECK (cantidad >= 0),
            CONSTRAINT productos_agro_cantidad_inve_check CHECK (cantidad_inve >= 0),
            CONSTRAINT productos_agro_precio_contado_check CHECK (precio_contado >= 0),
            CONSTRAINT productos_agro_precio_credito_check CHECK (precio_credito >= 0)
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign keys
        $this->db->query('ALTER TABLE condoriri.productos_agro ADD CONSTRAINT fk_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id) ON DELETE RESTRICT ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE condoriri.productos_agro ADD CONSTRAINT fk_unidad FOREIGN KEY (unidad_id) REFERENCES condoriri.unidades(id) ON DELETE RESTRICT ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE condoriri.productos_agro ADD CONSTRAINT fk_usuario FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE condoriri.productos_agro DROP CONSTRAINT IF EXISTS fk_sucursal');
        $this->db->query('ALTER TABLE condoriri.productos_agro DROP CONSTRAINT IF EXISTS fk_unidad');
        $this->db->query('ALTER TABLE condoriri.productos_agro DROP CONSTRAINT IF EXISTS fk_usuario');
        $this->db->query('DROP TABLE IF EXISTS condoriri.productos_agro CASCADE');
    }
}