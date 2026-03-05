-- =====================================================
-- MIGRACIÓN: Crear secuencias para códigos de ventas
-- Fecha: 2025-01-XX
-- Descripción: Crea 4 secuencias independientes para 
--              generar códigos únicos por tipo de venta
-- =====================================================

-- Tienda (sucursal_id = 2) - Contado
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_venta_tco 
    START WITH 1 
    INCREMENT BY 1 
    NO MAXVALUE 
    NO MINVALUE 
    CACHE 1;

-- Tienda (sucursal_id = 2) - Crédito
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_venta_tcr 
    START WITH 1 
    INCREMENT BY 1 
    NO MAXVALUE 
    NO MINVALUE 
    CACHE 1;

-- Planta (sucursal_id = 4) - Contado
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_venta_pco 
    START WITH 1 
    INCREMENT BY 1 
    NO MAXVALUE 
    NO MINVALUE 
    CACHE 1;

-- Planta (sucursal_id = 4) - Crédito
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_venta_pcr 
    START WITH 1 
    INCREMENT BY 1 
    NO MAXVALUE 
    NO MINVALUE 
    CACHE 1;

-- Verificar creación
SELECT 
    sequence_name, 
    start_value, 
    increment_by 
FROM information_schema.sequences 
WHERE sequence_schema = 'condoriri' 
  AND sequence_name LIKE 'seq_venta_%'
ORDER BY sequence_name;

-- =====================================================
-- NOTA: Ejecutar este script en PostgreSQL antes de 
--       usar el nuevo sistema de códigos de venta
-- =====================================================
