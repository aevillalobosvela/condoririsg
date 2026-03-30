<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTipoToUnidades extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE condoriri.unidades ADD COLUMN IF NOT EXISTS tipo VARCHAR(20) NOT NULL DEFAULT 'lacteo'");

        $this->db->query("UPDATE condoriri.unidades SET tipo = 'lacteo' WHERE tipo = 'lacteo'");
    }

    public function down()
    {
        $this->db->query("DELETE FROM condoriri.unidades WHERE tipo = 'agro'");
        $this->db->query("ALTER TABLE condoriri.unidades DROP COLUMN IF EXISTS tipo");
    }
}
