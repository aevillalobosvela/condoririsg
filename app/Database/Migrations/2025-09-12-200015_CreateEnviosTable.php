<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEnviosTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.envios (
            id SERIAL PRIMARY KEY,
            code VARCHAR(100) NOT NULL UNIQUE,
            
            sucursal_origen_id INT NULL,
            sucursal_destino_id INT NULL,
            
            observacion_origen TEXT NULL,
            observacion_destino TEXT NULL,
            
            estado_id INT NOT NULL,
            fecha_envio DATE NOT NULL,
            fecha_recepcion DATE NULL,
            
            user_transporte_id INT NOT NULL,
            user_id INT NOT NULL,
            user_recepcion_id INT NULL,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            tipo VARCHAR NULL
        )";
        
        $this->db->query($sql);

        // 🔹 Relaciones con sucursales
        $this->db->query('ALTER TABLE condoriri.envios 
            ADD CONSTRAINT fk_envios_sucursal_origen 
            FOREIGN KEY (sucursal_origen_id) REFERENCES condoriri.sucursales(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE condoriri.envios 
            ADD CONSTRAINT fk_envios_sucursal_destino 
            FOREIGN KEY (sucursal_destino_id) REFERENCES condoriri.sucursales(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        // 🔹 Relaciones con usuarios
        $this->db->query('ALTER TABLE condoriri.envios 
            ADD CONSTRAINT fk_envios_user_transporte 
            FOREIGN KEY (user_transporte_id) REFERENCES condoriri.usuarios(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE condoriri.envios 
            ADD CONSTRAINT fk_envios_user_creador 
            FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE condoriri.envios 
            ADD CONSTRAINT fk_envios_user_recepcion 
            FOREIGN KEY (user_recepcion_id) REFERENCES condoriri.usuarios(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');

        // 🔹 Relación con estados
        $this->db->query('ALTER TABLE condoriri.envios 
            ADD CONSTRAINT fk_envios_estado 
            FOREIGN KEY (estado_id) REFERENCES condoriri.estados(id) 
            ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        // 🔹 Eliminar claves foráneas
        $this->db->query('ALTER TABLE condoriri.envios DROP CONSTRAINT IF EXISTS fk_envios_sucursal_origen');
        $this->db->query('ALTER TABLE condoriri.envios DROP CONSTRAINT IF EXISTS fk_envios_sucursal_destino');
        $this->db->query('ALTER TABLE condoriri.envios DROP CONSTRAINT IF EXISTS fk_envios_user_transporte');
        $this->db->query('ALTER TABLE condoriri.envios DROP CONSTRAINT IF EXISTS fk_envios_user_creador');
        $this->db->query('ALTER TABLE condoriri.envios DROP CONSTRAINT IF EXISTS fk_envios_user_recepcion');
        $this->db->query('ALTER TABLE condoriri.envios DROP CONSTRAINT IF EXISTS fk_envios_estado');

        // 🔹 Eliminar tabla
        $this->db->query('DROP TABLE IF EXISTS condoriri.envios CASCADE');
    }
}
