<?php
// api/usuarios.php — CRUD de usuarios (admin)
require_once __DIR__ . '/config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    // ─── LISTAR TODOS ─────────────────────────────────────────
    case 'list':
        $role = $_GET['role'] ?? null;
        $pdo  = getDB();
        if ($role) {
            $stmt = $pdo->prepare("
                SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.activo,
                       u.avatar, u.created_at, r.nombre AS role,
                       p.empresa, p.nit, p.verificado
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                LEFT JOIN proveedores p ON p.usuario_id = u.id
                WHERE r.nombre = ?
                ORDER BY u.created_at DESC
            ");
            $stmt->execute([$role]);
        } else {
            $stmt = $pdo->query("
                SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.activo,
                       u.avatar, u.created_at, r.nombre AS role,
                       p.empresa, p.nit, p.verificado
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                LEFT JOIN proveedores p ON p.usuario_id = u.id
                ORDER BY u.created_at DESC
            ");
        }
        respond(['ok' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ─── OBTENER POR ID ───────────────────────────────────────
    case 'get':
        $id   = $_GET['id'] ?? '';
        $pdo  = getDB();
        $stmt = $pdo->prepare("
            SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.direccion,
                   u.activo, u.avatar, u.created_at, r.nombre AS role,
                   p.empresa, p.nit, p.verificado
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            LEFT JOIN proveedores p ON p.usuario_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) respond(['ok' => false, 'msg' => 'Usuario no encontrado.'], 404);
        respond(['ok' => true, 'user' => $user]);
        break;

    // ─── ACTUALIZAR ───────────────────────────────────────────
    case 'update':
        if ($method !== 'POST' && $method !== 'PUT')
            respond(['ok' => false, 'msg' => 'Método no permitido'], 405);

        $body = getBody();
        $id   = $body['id'] ?? '';
        if (!$id) respond(['ok' => false, 'msg' => 'ID requerido.']);

        $pdo    = getDB();
        $fields = [];
        $params = [];

        foreach (['nombre','apellido','telefono','direccion','avatar','activo'] as $f) {
            if (isset($body[$f])) {
                $fields[] = "$f = ?";
                $params[] = $body[$f];
            }
        }

        if (isset($body['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($body['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) respond(['ok' => false, 'msg' => 'Nada que actualizar.']);

        $params[] = $id;
        $pdo->prepare("UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = ?")
            ->execute($params);

        // Actualizar proveedor si aplica
        if (isset($body['verificado'])) {
            $pdo->prepare("UPDATE proveedores SET verificado = ? WHERE usuario_id = ?")
                ->execute([(int)$body['verificado'], $id]);
        }

        respond(['ok' => true, 'msg' => 'Usuario actualizado.']);
        break;

    // ─── ELIMINAR ─────────────────────────────────────────────
    case 'delete':
        if ($method !== 'POST' && $method !== 'DELETE')
            respond(['ok' => false, 'msg' => 'Método no permitido'], 405);

        $body = getBody();
        $id   = $body['id'] ?? $_GET['id'] ?? '';
        if (!$id) respond(['ok' => false, 'msg' => 'ID requerido.']);

        getDB()->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
        respond(['ok' => true, 'msg' => 'Usuario eliminado.']);
        break;

    // ─── STATS (admin dashboard) ──────────────────────────────
    case 'stats':
        $pdo  = getDB();
        $row  = $pdo->query("SELECT * FROM v_stats")->fetch();
        respond(['ok' => true, 'data' => $row]);
        break;

    default:
        respond(['ok' => false, 'msg' => 'Acción no encontrada.'], 404);
}
