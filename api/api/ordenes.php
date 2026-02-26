<?php
// api/ordenes.php — Órdenes de compra
require_once __DIR__ . '/config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    // ─── LISTAR TODAS (admin) ─────────────────────────────────
    case 'list':
        $pdo    = getDB();
        $userId = $_GET['usuario_id'] ?? null;

        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM v_ordenes WHERE comprador_email =
                (SELECT email FROM usuarios WHERE id = ?) ORDER BY fecha DESC");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM v_ordenes ORDER BY fecha DESC");
        }
        $ordenes = $stmt->fetchAll();

        // Adjuntar items a cada orden
        foreach ($ordenes as &$o) {
            $items = $pdo->prepare("SELECT * FROM orden_items WHERE orden_id = ?");
            $items->execute([$o['id']]);
            $o['productos'] = $items->fetchAll();
        }
        respond(['ok' => true, 'data' => $ordenes]);
        break;

    // ─── ÓRDENES DE UN USUARIO ────────────────────────────────
    case 'byUser':
        $userId = $_GET['usuario_id'] ?? '';
        if (!$userId) respond(['ok' => false, 'msg' => 'usuario_id requerido.']);

        $pdo  = getDB();
        $stmt = $pdo->prepare("
            SELECT o.id, o.total, o.estado, o.fecha
            FROM ordenes o
            WHERE o.comprador_id = ?
            ORDER BY o.fecha DESC
        ");
        $stmt->execute([$userId]);
        $ordenes = $stmt->fetchAll();

        foreach ($ordenes as &$o) {
            $items = $pdo->prepare("SELECT * FROM orden_items WHERE orden_id = ?");
            $items->execute([$o['id']]);
            $o['productos'] = $items->fetchAll();
        }
        respond(['ok' => true, 'data' => $ordenes]);
        break;

    // ─── CREAR ORDEN ──────────────────────────────────────────
    case 'create':
        if ($method !== 'POST') respond(['ok' => false, 'msg' => 'Método no permitido'], 405);

        $body       = getBody();
        $compradorId = $body['compradorId'] ?? $body['comprador_id'] ?? '';
        $productos  = $body['productos'] ?? [];
        $total      = (float)($body['total'] ?? 0);

        if (!$compradorId || empty($productos) || $total <= 0)
            respond(['ok' => false, 'msg' => 'Faltan datos de la orden.']);

        $pdo   = getDB();
        $ordId = 'ORD-' . time();

        $pdo->prepare("INSERT INTO ordenes (id, comprador_id, total) VALUES (?, ?, ?)")
            ->execute([$ordId, $compradorId, $total]);

        $itemStmt = $pdo->prepare("
            INSERT INTO orden_items (orden_id, producto_id, nombre_snap, precio_snap, cantidad)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($productos as $item) {
            $itemStmt->execute([
                $ordId,
                (int)($item['productoId'] ?? $item['producto_id']),
                $item['nombre'] ?? '',
                (float)($item['precio'] ?? 0),
                (int)($item['cantidad'] ?? 1),
            ]);
        }

        respond(['ok' => true, 'order' => ['id' => $ordId, 'estado' => 'pendiente']]);
        break;

    // ─── ACTUALIZAR ESTADO ────────────────────────────────────
    case 'updateStatus':
        if ($method !== 'POST' && $method !== 'PUT')
            respond(['ok' => false, 'msg' => 'Método no permitido'], 405);

        $body   = getBody();
        $ordId  = $body['orderId'] ?? $body['id'] ?? '';
        $estado = $body['estado'] ?? '';

        $validos = ['pendiente','procesando','enviado','entregado','cancelado'];
        if (!$ordId || !in_array($estado, $validos))
            respond(['ok' => false, 'msg' => 'ID u estado inválido.']);

        getDB()->prepare("UPDATE ordenes SET estado = ? WHERE id = ?")
               ->execute([$estado, $ordId]);

        respond(['ok' => true, 'msg' => 'Estado actualizado.']);
        break;

    default:
        respond(['ok' => false, 'msg' => 'Acción no encontrada.'], 404);
}
