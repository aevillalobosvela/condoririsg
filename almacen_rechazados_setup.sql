-- Crear sucursal especial para productos rechazados
INSERT INTO condoriri.sucursales (nombre, direccion, telefono, estado, created_at) 
VALUES ('ALMACÉN RECHAZADOS', 'Depósito Central - Área de Cuarentena', '000-0000', true, NOW());

-- Obtener el ID de la sucursal creada
-- SELECT id FROM condoriri.sucursales WHERE nombre = 'ALMACÉN RECHAZADOS';

-- Crear estados específicos para rechazos
INSERT INTO condoriri.estados (nombre, descripcion, created_at) VALUES 
('RECHAZADO_CALIDAD', 'Producto rechazado por problemas de calidad', NOW()),
('RECHAZADO_CANTIDAD', 'Producto rechazado por cantidad incorrecta', NOW()),
('RECHAZADO_VENCIMIENTO', 'Producto rechazado por fecha de vencimiento', NOW()),
('EN_CUARENTENA', 'Producto en almacén de rechazados pendiente de decisión', NOW()),
('DESECHADO', 'Producto desechado definitivamente', NOW()),
('REPROCESADO', 'Producto rechazado que fue reprocesado', NOW());