<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransferenciasProductosTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.transferencias_productos (
            id SERIAL PRIMARY KEY,
            envio_id INT NOT NULL,
            producto_id INT NOT NULL,
            cantidad INT NOT NULL,
            precio_contado NUMERIC NOT NULL,
            precio_credito NUMERIC NOT NULL,
            inventario_origen_id INT NULL,
            observacion_origen TEXT NULL,
            observacion_destino TEXT NULL,
            nota_origen TEXT NULL,
            nota_destino TEXT NULL,
            estado_id INT NOT NULL,
            user_id INT NOT NULL,
            user_recepcion_id INT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            cantidad_acep INT NULL
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign key (solo la que existe en estructura_DB.sql)
        $this->db->query('ALTER TABLE condoriri.transferencias_productos ADD CONSTRAINT fk_tp_producto FOREIGN KEY (producto_id) REFERENCES condoriri.productos(id) ON DELETE RESTRICT ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE condoriri.transferencias_productos DROP CONSTRAINT IF EXISTS fk_tp_producto');
        $this->db->query('DROP TABLE IF EXISTS condoriri.transferencias_productos CASCADE');
    }
}