-- Script SQL para crear la tabla devoluciones (Estilo Envios)

-- Eliminar la tabla si existe (Opcional)
DROP TABLE IF EXISTS condoriri.devoluciones CASCADE;

-- Crear la tabla
CREATE TABLE condoriri.devoluciones (
    id SERIAL PRIMARY KEY,
    code VARCHAR(100) NULL, -- Código tipo Envío (ej: DEV-20251127-1234)
    cantidad INT NOT NULL,
    observacion TEXT NULL,
    
    envio_id INT NULL, -- Enlace opcional a un envío original
    
    user_id INT NOT NULL, -- Usuario que crea la devolución
    
    observacion_recepcion TEXT NULL,
    user_recepcion_id INT NULL,
    
    estado_id INT DEFAULT 1, -- 1: Pendiente, 2: Enviado/Confirmado
    
    stock_sucursales_id INT NULL, -- ID del stock descontado
    producto_id INT NOT NULL,     -- ID del producto
    
    sucursales_id INT NOT NULL,         -- Sucursal Origen
    sucursales_destino_id INT NOT NULL, -- Sucursal Destino
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Agregar Claves Foráneas (Relaciones)

-- Relación con Sucursal Origen
ALTER TABLE condoriri.devoluciones 
    ADD CONSTRAINT fk_devoluciones_sucursal_origen 
    FOREIGN KEY (sucursales_id) REFERENCES condoriri.sucursales(id) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Relación con Sucursal Destino
ALTER TABLE condoriri.devoluciones 
    ADD CONSTRAINT fk_devoluciones_sucursal_destino 
    FOREIGN KEY (sucursales_destino_id) REFERENCES condoriri.sucursales(id) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Relación con Productos
ALTER TABLE condoriri.devoluciones 
    ADD CONSTRAINT fk_devoluciones_producto 
    FOREIGN KEY (producto_id) REFERENCES condoriri.productos(id) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Relación con Usuario Creador
ALTER TABLE condoriri.devoluciones 
    ADD CONSTRAINT fk_devoluciones_user 
    FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Relación con Usuario Recepción
ALTER TABLE condoriri.devoluciones 
    ADD CONSTRAINT fk_devoluciones_user_recepcion 
    FOREIGN KEY (user_recepcion_id) REFERENCES condoriri.usuarios(id) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Relación con Estados
ALTER TABLE condoriri.devoluciones 
    ADD CONSTRAINT fk_devoluciones_estado 
    FOREIGN KEY (estado_id) REFERENCES condoriri.estados(id) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Relación con Envios (Opcional)
ALTER TABLE condoriri.devoluciones 
    ADD CONSTRAINT fk_devoluciones_envio 
    FOREIGN KEY (envio_id) REFERENCES condoriri.envios(id) 
    ON DELETE SET NULL ON UPDATE CASCADE;
