<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgroVentaSequences extends Migration
{
    public function up()
    {
        // 4 secuencias independientes para Agro:
        // - Sucursal 2 => SC
        // - Sucursal 4 => PP
        // - Tipos: contado => CO, credito => CR
        $sql = <<<SQL
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_agro_venta_sc_co START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_agro_venta_sc_cr START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_agro_venta_pp_co START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_agro_venta_pp_cr START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
SQL;

        $this->db->query($sql);
    }

    public function down()
    {
        // Nota: no es recomendable borrar secuencias en producción.
        // Se incluye por completitud del rollback.
        $sql = <<<SQL
DROP SEQUENCE IF EXISTS condoriri.seq_agro_venta_sc_co;
DROP SEQUENCE IF EXISTS condoriri.seq_agro_venta_sc_cr;
DROP SEQUENCE IF EXISTS condoriri.seq_agro_venta_pp_co;
DROP SEQUENCE IF EXISTS condoriri.seq_agro_venta_pp_cr;
SQL;

        $this->db->query($sql);
    }
}

