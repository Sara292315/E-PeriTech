<?php
// ============================================================
//  E-PeriTech — API Órdenes
// ============================================================

require_once 'config.php';
session_start();

$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {

        case 'listar':
            $where  = '';
            $params = [];
            if (!empty($_GET['comprador_id'])) {
                $where = "WHERE o.comprador_id = ?";
                $params[] = $_GET['comprador_id'];
            }
            $stmt = $pdo->prepare("
                SELECT o.*, u.nombre, u.apellido, u.email
                FROM ordenes o
                JOIN usuarios u ON u.id = o.comprador_id
                $where
                ORDER BY o.fecha DESC
            ");
            $stmt->execute($params);
            $ordenes = $stmt->fetchAll();

            // Adjuntar items a cada orden
            foreach ($ordenes as &$orden) {
                $s2 = $pdo->prepare("
                    SELECT oi.*, p.nombre AS producto_nombre
                    FROM orden_items oi
                    JOIN productos p ON p.id = oi.producto_id
                    WHERE oi.orden_id = ?
                ");
                $s2->execute([$orden['id']]);
                $orden['items'] = $s2->fetchAll();
            }
            responder(true, $ordenes);

        case 'obtener':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) responder(false, null, 'ID requerido.', 400);
            $stmt = $pdo->prepare("SELECT * FROM ordenes WHERE id = ?");
            $stmt->execute([$id]);
            $orden = $stmt->fetch();
            if (!$orden) responder(false, null, 'Orden no encontrada.', 404);
            $s2 = $pdo->prepare("
                SELECT oi.*, p.nombre AS producto_nombre
                FROM orden_items oi
                JOIN productos p ON p.id = oi.producto_id
                WHERE oi.orden_id = ?
            ");
            $s2->execute([$id]);
            $orden['items'] = $s2->fetchAll();
            responder(true, $orden);

        case 'crear':
            $d = bodyJson();
            if (empty($d['comprador_id']) || empty($d['items'])) {
                responder(false, null, 'comprador_id e items son requeridos.', 400);
            }
            $pdo->beginTransaction();
            $total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $d['items']));
            $stmt = $pdo->prepare("
                INSERT INTO ordenes (comprador_id, total, estado) VALUES (?, ?, 'pendiente')
            ");
            $stmt->execute([$d['comprador_id'], $total]);
            $ordenId = (int)$pdo->lastInsertId();

            $s2 = $pdo->prepare("
                INSERT INTO orden_items (orden_id, producto_id, cantidad, precio_unitario)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($d['items'] as $item) {
                $s2->execute([$ordenId, (int)$item['producto_id'], (int)$item['cantidad'], (float)$item['precio']]);
            }
            $pdo->commit();
            responder(true, ['id' => $ordenId], 'Orden creada.');

        case 'estado':
            $id     = (int)($_GET['id'] ?? 0);
            $d      = bodyJson();
            $estado = $d['estado'] ?? '';
            $validos = ['pendiente','procesando','enviado','entregado','cancelado'];
            if (!$id || !in_array($estado, $validos)) {
                responder(false, null, 'ID o estado inválido.', 400);
            }
            $pdo->prepare("UPDATE ordenes SET estado = ? WHERE id = ?")->execute([$estado, $id]);
            responder(true, null, 'Estado actualizado.');

        default:
            responder(false, null, 'Acción no válida.', 400);
    }

} catch (PDOException $e) {
    responder(false, null, 'Error de base de datos: ' . $e->getMessage(), 500);
}
