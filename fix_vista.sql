-- ============================================================
--  CORRECCIÓN: actualizar v_productos para incluir proveedor_id
--  Ejecutar en phpMyAdmin → pestaña SQL
-- ============================================================

CREATE OR REPLACE VIEW v_productos AS
SELECT
    p.id,
    p.nombre,
    c.slug          AS categoria,
    p.precio,
    p.precio_viejo,
    p.descuento,
    p.icono,
    p.activo,
    p.descripcion,
    p.proveedor_id,
    pr.empresa      AS proveedor,
    p.created_at
FROM productos p
JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN proveedores pr ON p.proveedor_id = pr.usuario_id;
