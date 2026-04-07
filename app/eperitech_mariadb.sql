-- ============================================================
--  E-PeriTech — Base de Datos MariaDB
-- ============================================================
-- --------------CLIENTE SERVIDOR-------------------------
--  GUIA #1 - Actividad 2: Capa de Datos (3 Capas)
--  Este archivo define la Capa de Datos del sistema:
--  el esquema completo de la base de datos MariaDB que
--  almacena productos, marcas, usuarios y solicitudes.
--  Corresponde al modelo de 3 capas de la Guia #1.
-- --------------CLIENTE SERVIDOR-------------------------
--  GUIA #1 - Actividad 3: Procesos Remotos del Servidor
--  La base de datos solo es accedida por el servidor
--  PHP (Ubuntu), nunca directamente por el cliente web.
--  Puerto 3306 = capa de datos segun Guia #1 Act. 2.
-- --------------CLIENTE SERVIDOR-------------------------
--  GUIA #2 - Actividad 3: Topologia Logica del MVP
--  Corre en localhost del servidor Ubuntu (IP 192.168.1.50)
--  Puerto 3306 TCP segun la tabla de puertos de la Guia #2.
-- ============================================================


CREATE DATABASE IF NOT EXISTS eperitech
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE eperitech;

-- ─────────────────────────────────────────────
--  TABLA: roles
-- ─────────────────────────────────────────────
CREATE TABLE roles (
    id     TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE  -- admin | proveedor | comprador
);

INSERT INTO roles (nombre) VALUES ('admin'), ('proveedor'), ('comprador');


-- ─────────────────────────────────────────────
--  TABLA: usuarios
-- ─────────────────────────────────────────────
CREATE TABLE usuarios (
    id          VARCHAR(30)  NOT NULL PRIMARY KEY,  -- USR-ADMIN-001, USR-PROV-001 …
    rol_id      TINYINT UNSIGNED NOT NULL,
    nombre      VARCHAR(80)  NOT NULL,
    apellido    VARCHAR(80)  NOT NULL,
    email       VARCHAR(120) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,              -- hash (bcrypt en producción)
    telefono    VARCHAR(30)  DEFAULT NULL,
    direccion   VARCHAR(255) DEFAULT NULL,          -- solo compradores
    avatar      VARCHAR(10)  DEFAULT '👤',
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- ─────────────────────────────────────────────
--  TABLA: proveedores  (extiende usuarios)
-- ─────────────────────────────────────────────
CREATE TABLE proveedores (
    usuario_id  VARCHAR(30)  NOT NULL PRIMARY KEY,
    empresa     VARCHAR(120) NOT NULL,
    nit         VARCHAR(30)  NOT NULL UNIQUE,
    verificado  TINYINT(1)   NOT NULL DEFAULT 0,

    CONSTRAINT fk_prov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- ─────────────────────────────────────────────
--  TABLA: categorias
-- ─────────────────────────────────────────────
CREATE TABLE categorias (
    id     TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug   VARCHAR(40) NOT NULL UNIQUE,  -- mouse, teclado, monitor …
    nombre VARCHAR(60) NOT NULL
);

INSERT INTO categorias (slug, nombre) VALUES
    ('mouse',    'Mouse'),
    ('teclado',  'Teclado'),
    ('monitor',  'Monitor'),
    ('audifonos','Audífonos'),
    ('otros',    'Otros');

-- ─────────────────────────────────────────────
--  TABLA: proveedor_categorias  (N:M)
-- ─────────────────────────────────────────────
CREATE TABLE proveedor_categorias (
    proveedor_id VARCHAR(30)  NOT NULL,
    categoria_id TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (proveedor_id, categoria_id),
    CONSTRAINT fk_pc_prov FOREIGN KEY (proveedor_id) REFERENCES proveedores(usuario_id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_cat  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- ─────────────────────────────────────────────
--  TABLA: productos
-- ─────────────────────────────────────────────
CREATE TABLE productos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(200) NOT NULL,
    categoria_id  TINYINT UNSIGNED NOT NULL,
    precio        DECIMAL(12,2) NOT NULL,
    precio_viejo  DECIMAL(12,2) DEFAULT NULL,
    descuento     TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- % calculado
    icono         VARCHAR(10)  DEFAULT '📦',
    descripcion   TEXT         DEFAULT NULL,
    proveedor_id  VARCHAR(30)  DEFAULT NULL,            -- NULL = producto propio
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_prod_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    CONSTRAINT fk_prod_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(usuario_id)
        ON DELETE SET NULL
);

-- ─────────────────────────────────────────────
--  TABLA: wishlist  (compradores ↔ productos)
-- ─────────────────────────────────────────────
CREATE TABLE wishlist (
    usuario_id  VARCHAR(30)  NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    added_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, producto_id),
    CONSTRAINT fk_wl_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_wl_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────
--  TABLA: ordenes
-- ─────────────────────────────────────────────
CREATE TABLE ordenes (
    id            VARCHAR(30)  NOT NULL PRIMARY KEY,  -- ORD-001, ORD-{timestamp}
    comprador_id  VARCHAR(30)  NOT NULL,
    total         DECIMAL(14,2) NOT NULL,
    estado        ENUM('pendiente','procesando','enviado','entregado','cancelado')
                  NOT NULL DEFAULT 'pendiente',
    fecha         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ord_comprador FOREIGN KEY (comprador_id) REFERENCES usuarios(id)
);

-- ─────────────────────────────────────────────
--  TABLA: orden_items
-- ─────────────────────────────────────────────
CREATE TABLE orden_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id    VARCHAR(30)  NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    nombre_snap VARCHAR(200) NOT NULL,  -- snapshot del nombre al comprar
    precio_snap DECIMAL(12,2) NOT NULL, -- snapshot del precio al comprar
    cantidad    SMALLINT UNSIGNED NOT NULL DEFAULT 1,

    CONSTRAINT fk_oi_orden    FOREIGN KEY (orden_id)    REFERENCES ordenes(id)  ON DELETE CASCADE,
    CONSTRAINT fk_oi_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- ─────────────────────────────────────────────
--  TABLA: sesiones  (opcional — auditoría de login)
-- ─────────────────────────────────────────────
CREATE TABLE sesiones (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id VARCHAR(30)  NOT NULL,
    login_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    logout_at  DATETIME     DEFAULT NULL,
    ip         VARCHAR(45)  DEFAULT NULL,

    CONSTRAINT fk_ses_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────
--  TABLA: solicitudes_catalogo  (proveedor pide agregar producto)
-- ─────────────────────────────────────────────
CREATE TABLE solicitudes_catalogo (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proveedor_id VARCHAR(30)  NOT NULL,
    nombre       VARCHAR(200) NOT NULL,
    categoria_id TINYINT UNSIGNED NOT NULL,
    precio       DECIMAL(12,2) NOT NULL,
    descripcion  TEXT DEFAULT NULL,
    estado       ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisado_at  DATETIME DEFAULT NULL,

    CONSTRAINT fk_sc_prov FOREIGN KEY (proveedor_id) REFERENCES proveedores(usuario_id),
    CONSTRAINT fk_sc_cat  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);


-- ============================================================
--  DATOS INICIALES (seed)
-- ============================================================

-- Usuarios
INSERT INTO usuarios (id, rol_id, nombre, apellido, email, password, telefono, direccion, avatar, activo, created_at) VALUES
('USR-ADMIN-001', 1, 'Administrador', 'E-PeriTech', 'admin@eperitech.com',
 '$2b$10$placeholder_bcrypt_admin',  '+57 3185838072', NULL, '👨‍💼', 1, '2024-01-01'),

('USR-PROV-001',  2, 'Carlos', 'Ramírez', 'proveedor@techgear.com',
 '$2b$10$placeholder_bcrypt_prov1',  '+57 3001234567', 'Cra 5 # 10-20, Cúcuta', '🏭', 1, '2024-02-15'),

('USR-PROV-002',  2, 'Luisa', 'Torres', 'proveedor@visionmax.com',
 '$2b$10$placeholder_bcrypt_prov2',  '+57 3109876543', 'Cl 12 # 3-45, Bogotá',  '🏭', 1, '2024-03-10'),

('USR-COMP-001',  3, 'Andrés', 'Morales', 'andres@gmail.com',
 '$2b$10$placeholder_bcrypt_comp1',  '+57 3156789012', 'Cl 7 # 2-10, Cúcuta',   '👤', 1, '2024-06-01');

-- Proveedores (detalle)
INSERT INTO proveedores (usuario_id, empresa, nit, verificado) VALUES
('USR-PROV-001', 'TechGear Pro', '900.123.456-7', 1),
('USR-PROV-002', 'VisionMax',   '800.987.654-3', 1);

-- Proveedor ↔ categorías
INSERT INTO proveedor_categorias (proveedor_id, categoria_id) VALUES
('USR-PROV-001', 1),  -- mouse
('USR-PROV-001', 2),  -- teclado
('USR-PROV-002', 3);  -- monitor

-- Productos por defecto
INSERT INTO productos (id, nombre, categoria_id, precio, precio_viejo, descuento, icono, proveedor_id, activo) VALUES
(1, 'Mouse Gamer Inalámbrico RGB Pro',  1, 319000, 399000, 20, '🖱️', NULL, 1),
(2, 'Teclado Mecánico RGB Gaming',      2, 225000, NULL,    0, '⌨️', NULL, 1),
(3, 'Monitor Gamer 25\'\' 200Hz IPS',   3, 589000, 635000,  7, '🖥️', NULL, 1),
(4, 'Audífonos Gaming 7.1 Surround',    4, 185000, 220000, 16, '🎧', NULL, 1),
(5, 'Mouse Pad XXL RGB Extended',       1,  89000, NULL,    0, '🎨', NULL, 1),
(6, 'Webcam 4K Pro Streaming',          5, 349000, 420000, 17, '📹', NULL, 1),
(7, 'SSD M.2 NVMe 1TB Ultra Fast',      5, 259000, NULL,    0, '💾', NULL, 1),
(8, 'Silla Gamer Ergonómica Pro',       5, 899000,1100000, 18, '🪑', NULL, 1);

-- Wishlist del comprador de ejemplo
INSERT INTO wishlist (usuario_id, producto_id) VALUES
('USR-COMP-001', 1),
('USR-COMP-001', 3);

-- Orden de ejemplo
INSERT INTO ordenes (id, comprador_id, total, estado, fecha) VALUES
('ORD-001', 'USR-COMP-001', 319000, 'entregado', '2025-03-15');

INSERT INTO orden_items (orden_id, producto_id, nombre_snap, precio_snap, cantidad) VALUES
('ORD-001', 1, 'Mouse Gamer Inalámbrico RGB Pro', 319000, 1);


-- ============================================================
--  VISTAS ÚTILES
-- ============================================================

-- Vista: resumen de productos con categoría y proveedor
CREATE OR REPLACE VIEW v_productos AS
SELECT
    p.id,
    p.nombre,
    c.slug       AS categoria,
    p.precio,
    p.precio_viejo,
    p.descuento,
    p.icono,
    p.activo,
    pr.empresa   AS proveedor,
    p.created_at
FROM productos p
JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN proveedores pr ON p.proveedor_id = pr.usuario_id;

-- Vista: órdenes con datos del comprador
CREATE OR REPLACE VIEW v_ordenes AS
SELECT
    o.id,
    o.estado,
    o.total,
    o.fecha,
    u.nombre  AS comprador_nombre,
    u.apellido AS comprador_apellido,
    u.email   AS comprador_email
FROM ordenes o
JOIN usuarios u ON o.comprador_id = u.id;

-- Vista: estadísticas generales (equivalente a DB.getStats())
CREATE OR REPLACE VIEW v_stats AS
SELECT
    (SELECT COUNT(*) FROM usuarios)                               AS total_usuarios,
    (SELECT COUNT(*) FROM usuarios WHERE rol_id = 3)             AS total_compradores,
    (SELECT COUNT(*) FROM usuarios WHERE rol_id = 2)             AS total_proveedores,
    (SELECT COUNT(*) FROM usuarios WHERE rol_id = 1)             AS total_admins,
    (SELECT COUNT(*) FROM ordenes)                               AS total_ordenes,
    (SELECT COUNT(*) FROM ordenes WHERE estado = 'pendiente')    AS ordenes_pendientes,
    (SELECT IFNULL(SUM(total),0) FROM ordenes)                   AS ingreso_total;