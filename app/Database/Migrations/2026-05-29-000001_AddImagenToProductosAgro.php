<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddImagenToProductosAgro extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE condoriri.productos_agro
             ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) NULL DEFAULT NULL"
        );
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE condoriri.productos_agro
             DROP COLUMN IF EXISTS imagen"
        );
    }
}
