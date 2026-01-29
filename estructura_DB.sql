-- DROP SCHEMA condoriri;

CREATE SCHEMA condoriri AUTHORIZATION postgres;

-- DROP SEQUENCE condoriri.bajas_id_seq;

CREATE SEQUENCE condoriri.bajas_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.categorias_id_seq;

CREATE SEQUENCE condoriri.categorias_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.clientes_id_seq;

CREATE SEQUENCE condoriri.clientes_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.detalle_venta_id_seq;

CREATE SEQUENCE condoriri.detalle_venta_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.envios_id_seq;

CREATE SEQUENCE condoriri.envios_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.estados_id_seq;

CREATE SEQUENCE condoriri.estados_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.inventarios_id_seq;

CREATE SEQUENCE condoriri.inventarios_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.migrations_id_seq;

CREATE SEQUENCE condoriri.migrations_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.productos_agro_id_seq;

CREATE SEQUENCE condoriri.productos_agro_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.productos_id_seq;

CREATE SEQUENCE condoriri.productos_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.roles_id_seq;

CREATE SEQUENCE condoriri.roles_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.stock_sucursales_id_seq;

CREATE SEQUENCE condoriri.stock_sucursales_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.sucursales_id_seq;

CREATE SEQUENCE condoriri.sucursales_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.transferencias_productos_id_seq;

CREATE SEQUENCE condoriri.transferencias_productos_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.unidades_id_seq;

CREATE SEQUENCE condoriri.unidades_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.usuarios_id_seq;

CREATE SEQUENCE condoriri.usuarios_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;
-- DROP SEQUENCE condoriri.ventas_id_seq;

CREATE SEQUENCE condoriri.ventas_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 9223372036854775807
	START 1
	NO CYCLE;-- condoriri.migrations definition

-- Drop table

-- DROP TABLE condoriri.migrations;

CREATE TABLE condoriri.migrations (
	id bigserial NOT NULL,
	"version" varchar(255) NOT NULL,
	"class" varchar(255) NOT NULL,
	"group" varchar(255) NOT NULL,
	"namespace" varchar(255) NOT NULL,
	"time" int4 NOT NULL,
	batch int4 NOT NULL,
	CONSTRAINT pk_migrations PRIMARY KEY (id)
);


-- condoriri.roles definition

-- Drop table

-- DROP TABLE condoriri.roles;

CREATE TABLE condoriri.roles (
	id serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	descripcion text NULL,
	estado bool DEFAULT true NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	CONSTRAINT roles_pkey PRIMARY KEY (id)
);


-- condoriri.sucursales definition

-- Drop table

-- DROP TABLE condoriri.sucursales;

CREATE TABLE condoriri.sucursales (
	id serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	descripcion text NULL,
	direccion varchar(255) NOT NULL,
	telefono varchar(15) NULL,
	estado bool DEFAULT true NOT NULL,
	user_id int4 NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	CONSTRAINT sucursales_pkey PRIMARY KEY (id)
);


-- condoriri.usuarios definition

-- Drop table

-- DROP TABLE condoriri.usuarios;

CREATE TABLE condoriri.usuarios (
	id serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	apellidos varchar(150) NOT NULL,
	usuario varchar(50) NOT NULL,
	correo varchar(150) NOT NULL,
	"password" varchar(255) NOT NULL,
	celular varchar(20) NULL,
	direccion text NULL,
	rol_id int4 NOT NULL,
	sucursal_id int4 NOT NULL,
	estado bool DEFAULT true NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	ci int4 NULL,
	CONSTRAINT usuarios_correo_key UNIQUE (correo),
	CONSTRAINT usuarios_pkey PRIMARY KEY (id),
	CONSTRAINT usuarios_usuario_key UNIQUE (usuario),
	CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES condoriri.roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_usuarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id) ON DELETE CASCADE ON UPDATE CASCADE
);


-- condoriri.categorias definition

-- Drop table

-- DROP TABLE condoriri.categorias;

CREATE TABLE condoriri.categorias (
	id serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	descripcion text NULL,
	estado bool DEFAULT true NOT NULL,
	user_id int4 NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	CONSTRAINT categorias_pkey PRIMARY KEY (id),
	CONSTRAINT fk_categorias_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
);


-- condoriri.clientes definition

-- Drop table

-- DROP TABLE condoriri.clientes;

CREATE TABLE condoriri.clientes (
	id serial4 NOT NULL,
	nombre_completo varchar(100) NOT NULL,
	ci_nit varchar(20) NULL,
	estado bool DEFAULT true NOT NULL,
	user_id int4 NULL,
	created_at timestamp DEFAULT now() NULL,
	updated_at timestamp DEFAULT now() NULL,
	deleted_at timestamp NULL,
	CONSTRAINT clientes_ci_nit_key UNIQUE (ci_nit),
	CONSTRAINT clientes_pkey PRIMARY KEY (id),
	CONSTRAINT fk_user_cliente FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id)
);


-- condoriri.estados definition

-- Drop table

-- DROP TABLE condoriri.estados;

CREATE TABLE condoriri.estados (
	id serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	estado bool DEFAULT true NOT NULL,
	user_id int4 NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	CONSTRAINT estados_pkey PRIMARY KEY (id),
	CONSTRAINT fk_estados_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
);


-- condoriri.inventarios definition

-- Drop table

-- DROP TABLE condoriri.inventarios;

CREATE TABLE condoriri.inventarios (
	id serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	code varchar(100) NOT NULL,
	descripcion text NULL,
	stock numeric DEFAULT 0 NOT NULL,
	turno varchar(100) NOT NULL,
	estado bool DEFAULT true NOT NULL,
	sucursal_id int4 NOT NULL,
	user_id int4 NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	reserva numeric NULL,
	grasa float4 NULL,
	sng float4 NULL,
	densidad float4 NULL,
	lactosa float4 NULL,
	solidos float4 NULL,
	proteina float4 NULL,
	agua float4 NULL,
	temperatura float4 NULL,
	congelacion float4 NULL,
	ph float4 NULL,
	fecha_calidad timestamp NULL,
	user_cali int4 NULL,
	CONSTRAINT inventarios_pkey PRIMARY KEY (id),
	CONSTRAINT fk_inventarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_inventarios_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
);


-- condoriri.unidades definition

-- Drop table

-- DROP TABLE condoriri.unidades;

CREATE TABLE condoriri.unidades (
	id serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	descripcion text NULL,
	estado bool DEFAULT true NOT NULL,
	user_id int4 NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	CONSTRAINT unidades_pkey PRIMARY KEY (id),
	CONSTRAINT fk_unidades_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
);


-- condoriri.ventas definition

-- Drop table

-- DROP TABLE condoriri.ventas;

CREATE TABLE condoriri.ventas (
	id serial4 NOT NULL,
	code varchar(50) NOT NULL,
	cliente_id int4 NULL,
	sucursal_id int4 NOT NULL,
	tipo_pago varchar(50) NOT NULL,
	monto_total numeric(10, 2) NOT NULL,
	estado varchar(50) DEFAULT 'Pagada'::character varying NOT NULL,
	observaciones text NULL,
	user_id int4 NOT NULL,
	created_at timestamp DEFAULT now() NULL,
	updated_at timestamp DEFAULT now() NULL,
	deleted_at timestamp NULL,
	personal_uto_id int4 NULL,
	CONSTRAINT ventas_code_key UNIQUE (code),
	CONSTRAINT ventas_pkey PRIMARY KEY (id),
	CONSTRAINT fk_cliente FOREIGN KEY (cliente_id) REFERENCES condoriri.clientes(id),
	CONSTRAINT fk_vendedor FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id),
	CONSTRAINT fk_ventas_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id) ON DELETE RESTRICT ON UPDATE CASCADE
);


-- condoriri.envios definition

-- Drop table

-- DROP TABLE condoriri.envios;

CREATE TABLE condoriri.envios (
	id serial4 NOT NULL,
	code varchar(100) NOT NULL,
	sucursal_origen_id int4 NULL,
	sucursal_destino_id int4 NULL,
	observacion_origen text NULL,
	observacion_destino text NULL,
	estado_id int4 NOT NULL,
	fecha_envio date NOT NULL,
	fecha_recepcion date NULL,
	user_transporte_id int4 NOT NULL,
	user_id int4 NOT NULL,
	user_recepcion_id int4 NULL,
	created_at timestamp DEFAULT now() NULL,
	updated_at timestamp DEFAULT now() NULL,
	deleted_at timestamp NULL,
	tipo varchar NULL,
	CONSTRAINT envios_code_key UNIQUE (code),
	CONSTRAINT envios_pkey PRIMARY KEY (id),
	CONSTRAINT fk_envios_estado FOREIGN KEY (estado_id) REFERENCES condoriri.estados(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_envios_sucursal_destino FOREIGN KEY (sucursal_destino_id) REFERENCES condoriri.sucursales(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_envios_sucursal_origen FOREIGN KEY (sucursal_origen_id) REFERENCES condoriri.sucursales(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_envios_user_creador FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_envios_user_recepcion FOREIGN KEY (user_recepcion_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_envios_user_transporte FOREIGN KEY (user_transporte_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
);


-- condoriri.productos definition

-- Drop table

-- DROP TABLE condoriri.productos;

CREATE TABLE condoriri.productos (
	id serial4 NOT NULL,
	nombre varchar(100) NOT NULL,
	descripcion text NULL,
	precio_credito numeric(10, 2) NOT NULL,
	precio_contado numeric(10, 2) NOT NULL,
	stock int4 DEFAULT 0 NOT NULL,
	imagen varchar(255) NULL,
	estado bool DEFAULT true NOT NULL,
	categoria_id int4 NOT NULL,
	unidad_id int4 NOT NULL,
	fecha_vencimiento date NULL,
	cantidad_produccion numeric NULL,
	porocidad varchar(100) NULL,
	ph varchar(100) NULL,
	acides varchar(100) NULL,
	consistencia varchar(100) NULL,
	color varchar(100) NULL,
	olor varchar(100) NULL,
	textura varchar(100) NULL,
	observaciones text NULL,
	inventario_id int4 NOT NULL,
	user_id int4 NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	cantidad_unidad numeric NULL,
	reserva numeric NULL,
	stock_inve int4 NULL,
	observacion text NULL,
	merma int4 NULL,
	agrega int4 NULL,
	parent_id int4 NULL,
	litros float4 NULL,
	materia_sub int4 NULL,
	suero_lacteo numeric NULL,
	suero_queseria numeric NULL,
	cantidad_devo int4 NULL,
	CONSTRAINT productos_pkey PRIMARY KEY (id),
	CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES condoriri.categorias(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_productos_inventario FOREIGN KEY (inventario_id) REFERENCES condoriri.inventarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_productos_unidad FOREIGN KEY (unidad_id) REFERENCES condoriri.unidades(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_productos_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT productos_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES condoriri.productos(id) ON DELETE CASCADE
);


-- condoriri.productos_agro definition

-- Drop table

-- DROP TABLE condoriri.productos_agro;

CREATE TABLE condoriri.productos_agro (
	id serial4 NOT NULL,
	code varchar(50) NOT NULL,
	producto varchar(255) NOT NULL,
	descripcion text NULL,
	cantidad int4 DEFAULT 0 NOT NULL,
	categoria varchar(255) NOT NULL,
	unidad_id int4 NOT NULL,
	precio_credito float4 NOT NULL,
	precio_contado float4 NOT NULL,
	cantidad_inve int4 DEFAULT 0 NOT NULL,
	fecha_creacion timestamptz DEFAULT now() NOT NULL,
	fecha_update timestamptz NULL,
	fecha_delete timestamptz NULL,
	sucursal_id int4 NOT NULL,
	user_id int4 NOT NULL,
	estado bool NULL,
	CONSTRAINT productos_agro_cantidad_check CHECK ((cantidad >= 0)),
	CONSTRAINT productos_agro_cantidad_inve_check CHECK ((cantidad_inve >= 0)),
	CONSTRAINT productos_agro_code_key UNIQUE (code),
	CONSTRAINT productos_agro_pkey PRIMARY KEY (id),
	CONSTRAINT productos_agro_precio_contado_check CHECK ((precio_contado >= ((0)::numeric)::double precision)),
	CONSTRAINT productos_agro_precio_credito_check CHECK ((precio_credito >= ((0)::numeric)::double precision)),
	CONSTRAINT fk_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	CONSTRAINT fk_unidad FOREIGN KEY (unidad_id) REFERENCES condoriri.unidades(id) ON DELETE RESTRICT ON UPDATE CASCADE,
	CONSTRAINT fk_usuario FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
);


-- condoriri.stock_sucursales definition

-- Drop table

-- DROP TABLE condoriri.stock_sucursales;

CREATE TABLE condoriri.stock_sucursales (
	id serial4 NOT NULL,
	producto_id int4 NOT NULL,
	sucursal_id int4 NOT NULL,
	cantidad int4 DEFAULT 0 NOT NULL,
	stock int4 DEFAULT 0 NOT NULL,
	precio_contado numeric(10, 2) NOT NULL,
	precio_credito numeric(10, 2) NOT NULL,
	estado bool DEFAULT true NOT NULL,
	user_id int4 NULL,
	created_at timestamp DEFAULT now() NULL,
	updated_at timestamp DEFAULT now() NULL,
	producto varchar NULL,
	categoria varchar NULL,
	unidad varchar NULL,
	CONSTRAINT stock_sucursales_pkey PRIMARY KEY (id),
	CONSTRAINT uk_producto_sucursal UNIQUE (producto_id, sucursal_id),
	CONSTRAINT fk_producto FOREIGN KEY (producto_id) REFERENCES condoriri.productos(id),
	CONSTRAINT fk_sucursal FOREIGN KEY (sucursal_id) REFERENCES condoriri.sucursales(id),
	CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id)
);


-- condoriri.transferencias_productos definition

-- Drop table

-- DROP TABLE condoriri.transferencias_productos;

CREATE TABLE condoriri.transferencias_productos (
	id serial4 NOT NULL,
	envio_id int4 NOT NULL,
	producto_id int4 NOT NULL,
	cantidad int4 NOT NULL,
	precio_contado numeric NOT NULL,
	precio_credito numeric NOT NULL,
	inventario_origen_id int4 NULL,
	observacion_origen text NULL,
	observacion_destino text NULL,
	nota_origen text NULL,
	nota_destino text NULL,
	estado_id int4 NOT NULL,
	user_id int4 NOT NULL,
	user_recepcion_id int4 NOT NULL,
	created_at timestamp NULL,
	updated_at timestamp NULL,
	deleted_at timestamp NULL,
	cantidad_acep int4 NULL,
	CONSTRAINT transferencias_productos_pkey PRIMARY KEY (id),
	CONSTRAINT fk_tp_producto FOREIGN KEY (producto_id) REFERENCES condoriri.productos(id) ON DELETE RESTRICT ON UPDATE CASCADE
);


-- condoriri.bajas definition

-- Drop table

-- DROP TABLE condoriri.bajas;

CREATE TABLE condoriri.bajas (
	id serial4 NOT NULL,
	producto_id int4 NOT NULL,
	cantidad int4 NOT NULL,
	observacion text NULL,
	user_id int4 NOT NULL,
	created_at timestamp DEFAULT now() NULL,
	CONSTRAINT bajas_cantidad_check CHECK ((cantidad > 0)),
	CONSTRAINT bajas_pkey PRIMARY KEY (id),
	CONSTRAINT fk_bajas_producto FOREIGN KEY (producto_id) REFERENCES condoriri.productos(id) ON DELETE CASCADE,
	CONSTRAINT fk_bajas_usuario FOREIGN KEY (user_id) REFERENCES condoriri.usuarios(id) ON DELETE RESTRICT
);


-- condoriri.detalle_venta definition

-- Drop table

-- DROP TABLE condoriri.detalle_venta;

CREATE TABLE condoriri.detalle_venta (
	id serial4 NOT NULL,
	venta_id int4 NOT NULL,
	stock_id int4 NULL,
	cantidad int4 NOT NULL,
	precio_unitario numeric(10, 2) NOT NULL,
	subtotal numeric(10, 2) NOT NULL,
	observaciones text NULL,
	created_at timestamp DEFAULT now() NULL,
	updated_at timestamp DEFAULT now() NULL,
	deleted_at timestamp NULL,
	producto_id int4 NULL,
	producto_agro_id int4 NULL,
	CONSTRAINT detalle_venta_pkey PRIMARY KEY (id),
	CONSTRAINT uk_detalle_stock UNIQUE (venta_id, stock_id),
	CONSTRAINT fk_stock_item FOREIGN KEY (stock_id) REFERENCES condoriri.stock_sucursales(id),
	CONSTRAINT fk_venta FOREIGN KEY (venta_id) REFERENCES condoriri.ventas(id)
);