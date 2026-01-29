<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductosTable extends Migration
{
    public function up()
    {
        // SQL DIRECTO siguiendo estructura_DB.sql
        $sql = "CREATE TABLE condoriri.productos (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            precio_credito NUMERIC(10,2) NOT NULL,
            precio_contado NUMERIC(10,2) NOT NULL,
            stock INT DEFAULT 0 NOT NULL,
            imagen VARCHAR(255) NULL,
            estado BOOLEAN DEFAULT TRUE NOT NULL,
            categoria_id INT NOT NULL,
            unidad_id INT NOT NULL,
            fecha_vencimiento DATE NULL,
            cantidad_produccion NUMERIC NULL,
            porocidad VARCHAR(100) NULL,
            ph VARCHAR(100) NULL,
            acides VARCHAR(100) NULL,
            consistencia VARCHAR(100) NULL,
            color VARCHAR(100) NULL,
            olor VARCHAR(100) NULL,
            textura VARCHAR(100) NULL,
            observaciones TEXT NULL,
            inventario_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            cantidad_unidad NUMERIC NULL,
            reserva NUMERIC NULL,
            stock_inve INT NULL,
            observacion TEXT NULL,
            merma INT NULL,
            agrega INT NULL,
            parent_id INT NULL,
            litros FLOAT NULL,
            materia_sub INT NULL,
            suero_lacteo NUMERIC NULL,
            suero_queseria NUMERIC NULL,
            cantidad_devo INT NULL
        )";
        
        $this->db->query($sql);
        
        // Agregar foreign keys después de crear la tabla
        $this->db->query('ALTER TABLE condoriri.productos ADD CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES condoriri.categorias(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE condoriri.productos ADD CONSTRAINT fk_productos_unidad FOREIGN KEY (unidad_id) REFERENCES condoriri.unidades(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE condoriri.productos ADD CONSTRAINT fk_productos_inventario FOREIGN KEY (inventario_id) REFERENCES condoriri.inventarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE condoriri.productos ADD CONSTRAINT fk_productos_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE condoriri.productos ADD CONSTRAINT productos_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES condoriri.productos(id) ON DELETE CASCADE');
    }

    public function down()
    {
        // Eliminar foreign keys primero
        $this->db->query('ALTER TABLE condoriri.productos DROP CONSTRAINT IF EXISTS fk_productos_categoria');
        $this->db->query('ALTER TABLE condoriri.productos DROP CONSTRAINT IF EXISTS fk_productos_unidad');
        $this->db->query('ALTER TABLE condoriri.productos DROP CONSTRAINT IF EXISTS fk_productos_inventario');
        $this->db->query('ALTER TABLE condoriri.productos DROP CONSTRAINT IF EXISTS fk_productos_user');
        $this->db->query('ALTER TABLE condoriri.productos DROP CONSTRAINT IF EXISTS productos_parent_id_fkey');
        
        // Eliminar tabla
        $this->db->query('DROP TABLE IF EXISTS condoriri.productos CASCADE');
    }
}