# Generación de Códigos de Venta

## Formato actual

```
{PREFIJO_SUCURSAL}-{PREFIJO_TIPO}-{SECUENCIAL_6_DIGITOS}
```

### Ejemplos
| Sucursal | Tipo de Pago | Código generado |
|---|---|---|
| Sucursal Centro (id=2) | Contado | `SC-CO-000001` |
| Sucursal Centro (id=2) | Crédito | `SC-CR-000001` |
| Planta Producción (otro id) | Contado | `PP-CO-000001` |
| Planta Producción (otro id) | Crédito | `PP-CR-000001` |

---

## Lógica de generación

Archivo: `app/Models/Venta/VentaModel.php` — método `generarCodigoVenta()`

- **Prefijo sucursal**: `sucursal_id == 2` → `SC`, cualquier otro → `PP`
- **Prefijo tipo**: `tipo_pago == 'contado'` → `CO`, cualquier otro → `CR`
- **Secuencial**: se obtiene con `nextval()` de la secuencia PostgreSQL correspondiente, rellenado con ceros hasta 6 dígitos

---

## Secuencias en PostgreSQL

Esquema: `condoriri`

| Secuencia | Usada para |
|---|---|
| `seq_venta_sc_co` | Sucursal Centro + Contado |
| `seq_venta_sc_cr` | Sucursal Centro + Crédito |
| `seq_venta_pp_co` | Planta Producción + Contado |
| `seq_venta_pp_cr` | Planta Producción + Crédito |

---
## Agro (numeración independiente)

Agro requiere 4 series independientes (no se reinician):
- por sucursal: `sucursal_id = 2` => `SC`, `sucursal_id = 4` => `PP`
- por tipo de pago: `contado` => `CO`, `credito` => `CR`

Formato del código Agro:
```
AG-{PREFIJO_SUCURSAL}-{PREFIJO_TIPO}-{SECUENCIAL_6_DIGITOS}
```

Secuencias Agro en PostgreSQL (schema `condoriri`):
| Secuencia | Usada para |
|---|---|
| `seq_agro_venta_sc_co` | Sucursal Centro + Contado |
| `seq_agro_venta_sc_cr` | Sucursal Centro + Crédito |
| `seq_agro_venta_pp_co` | Planta Producción + Contado |
| `seq_agro_venta_pp_cr` | Planta Producción + Crédito |

### Script de creación

```sql
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_venta_sc_co START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_venta_sc_cr START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_venta_pp_co START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_venta_pp_cr START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
```

```sql
-- Agro (numeración independiente)
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_agro_venta_sc_co START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_agro_venta_sc_cr START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_agro_venta_pp_co START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
CREATE SEQUENCE IF NOT EXISTS condoriri.seq_agro_venta_pp_cr START 1 INCREMENT 1 MINVALUE 1 NO MAXVALUE;
```

### Consultar valores actuales

```sql
SELECT sequence_name, last_value
FROM information_schema.sequences
WHERE sequence_schema = 'condoriri'
AND sequence_name LIKE 'seq_venta_%';
```

### Reiniciar una secuencia

```sql
ALTER SEQUENCE condoriri.seq_venta_sc_co RESTART WITH 1;
```

### Ajustar valor manualmente

```sql
-- El próximo código generado usará el número 101
SELECT setval('condoriri.seq_venta_sc_co', 100);

-- El próximo código generado usará exactamente el número 100
SELECT setval('condoriri.seq_venta_sc_co', 100, false);
```

---

## Historial de cambios

### v2 — Formato actual
- **Archivo modificado**: `app/Models/Venta/VentaModel.php`
- **Formato anterior**: `VENTATCO-000001`, `VENTATCR-000001`, `VENTAPCO-000001`, `VENTAPCR-000001`
- **Formato nuevo**: `SC-CO-000001`, `SC-CR-000001`, `PP-CO-000001`, `PP-CR-000001`
- **Secuencias anteriores**: `seq_venta_tco`, `seq_venta_tcr`, `seq_venta_pco`, `seq_venta_pcr`
- **Secuencias nuevas**: `seq_venta_sc_co`, `seq_venta_sc_cr`, `seq_venta_pp_co`, `seq_venta_pp_cr`

---

## Consideraciones para futuros cambios

- Si se añade una nueva sucursal, agregar su prefijo en el método `generarCodigoVenta()` y crear la secuencia correspondiente en la BD.
- No modificar el valor de una secuencia a un número menor que los códigos ya existentes en `condoriri.ventas` para evitar duplicados (el campo `code` tiene restricción `UNIQUE`).
- Antes de bajar el valor de una secuencia, verificar el máximo actual:

```sql
SELECT MAX(CAST(SPLIT_PART(code, '-', 3) AS INTEGER))
FROM condoriri.ventas
WHERE code LIKE 'SC-CO-%';
```
