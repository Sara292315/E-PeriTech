<?php
// ============================================================
//  E-PeriTech — API Productos
// ============================================================
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 2: Capa de Logica de Negocio
//  Este archivo implementa la Capa de Logica de Negocio
//  del modelo de 3 capas: valida datos, consulta la BD
//  y retorna respuestas JSON al cliente.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 3: Procesos Remotos del Servidor
//  Todos los procesos aqui son ejecutados remotamente
//  en el servidor (Ubuntu+PHP): consulta por categoria,
//  gestion de ofertas, comparacion y administracion
//  del catalogo de productos.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 4: Payload de Comunicacion
//  Recibe y responde con JSON estructurado siguiendo
//  el payload definido en la Guia #1 Actividad 4:
//  {tipo, categoria, marca, usuario, timestamp}.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 1: Middleware Web Services REST
//  Implementa el modelo de Web Services aplicado en
//  E-PeriTech: comunicacion via HTTP, datos en JSON,
//  independencia del lenguaje, arquitectura REST.
//  Endpoints:
//    GET    /api/productos.php?action=listar
//    GET    /api/productos.php?action=obtener&id=1
//    POST   /api/productos.php?action=crear
//    PUT    /api/productos.php?action=actualizar&id=1
//    DELETE /api/productos.php?action=eliminar&id=1
// ============================================================


require_once 'config.php';

$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {

        case 'listar':
            $where  = "WHERE p.activo = 1";
            $params = [];
            if (!empty($_GET['categoria'])) {
                $where .= " AND c.slug = ?";
                $params[] = $_GET['categoria'];
            }
            if (!empty($_GET['proveedor_id'])) {
                $where .= " AND p.proveedor_id = ?";
                $params[] = $_GET['proveedor_id'];
            }
            $stmt = $pdo->prepare("
                SELECT p.*, c.slug AS categoria_slug, c.nombre AS categoria_nombre
                FROM productos p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                $where
                ORDER BY p.id DESC
            ");
            $stmt->execute($params);
            responder(true, $stmt->fetchAll());

        case 'obtener':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) responder(false, null, 'ID requerido.', 400);
            $stmt = $pdo->prepare("
                SELECT p.*, p.precio_viejo AS precio_anterior, c.slug AS categoria_slug, c.nombre AS categoria_nombre
                FROM productos p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $prod = $stmt->fetch();
            if (!$prod) responder(false, null, 'Producto no encontrado.', 404);
            responder(true, $prod);

        case 'crear':
            $d = bodyJson();
            foreach (['nombre','precio','categoria_id'] as $c) {
                if (empty($d[$c])) responder(false, null, "Campo '$c' requerido.", 400);
            }
            
            // Manejar tanto precio_anterior como precio_viejo para compatibilidad
            $precioAnt = null;
            if (isset($d['precio_anterior']) && $d['precio_anterior'] !== null && $d['precio_anterior'] !== '') {
                $precioAnt = (float)$d['precio_anterior'];
            } elseif (isset($d['precio_viejo']) && $d['precio_viejo'] !== null && $d['precio_viejo'] !== '') {
                $precioAnt = (float)$d['precio_viejo'];
            }
            
            $precio = (float)$d['precio'];
            $descuento = ($precioAnt && $precioAnt > 0)
                ? round((1 - $precio / $precioAnt) * 100)
                : 0;
            
            $stmt = $pdo->prepare("
                INSERT INTO productos (nombre, descripcion, precio, precio_viejo, descuento, icono, categoria_id, proveedor_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            try {
                $stmt->execute([
                    trim($d['nombre']),
                    $d['descripcion'] ?? null,
                    $precio,
                    $precioAnt,
                    $descuento,
                    $d['icono'] ?? '📦',
                    (int)$d['categoria_id'],
                    $d['proveedor_id'] ?? null,
                ]);
                responder(true, ['id' => (int)$pdo->lastInsertId()], 'Producto creado.');
            } catch (PDOException $e) {
                responder(false, null, 'Error al crear producto: ' . $e->getMessage(), 500);
            }

        case 'actualizar':
            $id = (int)($_GET['id'] ?? 0);
            $d  = bodyJson();
            if (!$id) responder(false, null, 'ID requerido.', 400);

            $campos = [];
            $params = [];
            $map = ['nombre','descripcion','precio','icono','categoria_id','proveedor_id','activo'];
            foreach ($map as $f) {
                if (array_key_exists($f, $d)) {
                    $campos[] = "$f = ?";
                    $params[] = $d[$f];
                }
            }
            
            // Manejar precio_viejo (nombre correcto en la BD)
            $precioAnt = null;
            if (isset($d['precio_anterior']) && $d['precio_anterior'] !== null && $d['precio_anterior'] !== '') {
                $precioAnt = (float)$d['precio_anterior'];
            } elseif (isset($d['precio_viejo']) && $d['precio_viejo'] !== null && $d['precio_viejo'] !== '') {
                $precioAnt = (float)$d['precio_viejo'];
            }
            
            if ($precioAnt !== null) {
                $campos[] = "precio_viejo = ?";
                $params[] = $precioAnt;
                
                // Calcular descuento si hay precio anterior
                if (isset($d['precio']) && $precioAnt > 0) {
                    $campos[] = "descuento = ?";
                    $params[] = round((1 - (float)$d['precio'] / $precioAnt) * 100);
                }
            }
            
            if (empty($campos)) responder(false, null, 'Nada que actualizar.', 400);
            $params[] = $id;
            
            try {
                $pdo->prepare("UPDATE productos SET " . implode(', ', $campos) . " WHERE id = ?")->execute($params);
                responder(true, null, 'Producto actualizado.');
            } catch (PDOException $e) {
                responder(false, null, 'Error al actualizar producto: ' . $e->getMessage(), 500);
            }

        case 'eliminar':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) responder(false, null, 'ID requerido.', 400);
            $pdo->prepare("UPDATE productos SET activo = 0 WHERE id = ?")->execute([$id]);
            responder(true, null, 'Producto eliminado.');

        default:
            responder(false, null, 'Acción no válida.', 400);
    }

} catch (PDOException $e) {
    responder(false, null, 'Error de base de datos: ' . $e->getMessage(), 500);
}
