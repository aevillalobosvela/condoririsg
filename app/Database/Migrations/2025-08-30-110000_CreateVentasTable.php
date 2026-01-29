<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVentasTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.ventas (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            cliente_id INT NULL,
            sucursal_id INT NOT NULL,
            tipo_pago VARCHAR(50) NOT NULL,
            monto_total NUMERIC(10, 2) NOT NULL,
            estado VARCHAR(50) DEFAULT 'Pagada' NOT NULL,
            observaciones TEXT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT NOW() NULL,
            updated_at TIMESTAMP DEFAULT NOW() NULL,
            deleted_at TIMESTAMP NULL,
            personal_uto_id INT NULL
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign keys
        $this->db->query('ALTER TABLE condoriri.ventas ADD CONSTRAINT fk_cliente FOREIGN KEY (cliente_id) REFERENCES condoriri.clientes(id)');
        $this->db->query('ALTER TABLE condoriri.ventas ADD CONSTRAINT fk_vendedor FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id)');
        $this->db->query('ALTER TABLE condoriri.ventas ADD CONSTRAINT fk_ventas_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id) ON DELETE RESTRICT ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE condoriri.ventas DROP CONSTRAINT IF EXISTS fk_cliente');
        $this->db->query('ALTER TABLE condoriri.ventas DROP CONSTRAINT IF EXISTS fk_vendedor');
        $this->db->query('ALTER TABLE condoriri.ventas DROP CONSTRAINT IF EXISTS fk_ventas_sucursal');
        $this->db->query('DROP TABLE IF EXISTS condoriri.ventas CASCADE');
    }
}