<?php
// api/auth.php  — Login, Registro, Logout, Sesión
require_once __DIR__ . '/config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    // ─── LOGIN ────────────────────────────────────────────────
    case 'login':
        if ($method !== 'POST') respond(['ok' => false, 'msg' => 'Método no permitido'], 405);

        $body  = getBody();
        $email = strtolower(trim($body['email'] ?? ''));
        $pass  = $body['password'] ?? '';

        if (!$email || !$pass) respond(['ok' => false, 'msg' => 'Email y contraseña requeridos.']);

        $pdo  = getDB();
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre AS role
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user)              respond(['ok' => false, 'msg' => 'Correo no registrado.']);
        if (!$user['activo'])    respond(['ok' => false, 'msg' => 'Cuenta desactivada.']);
        if (!password_verify($pass, $user['password']))
                                 respond(['ok' => false, 'msg' => 'Contraseña incorrecta.']);

        // Guardar sesión en servidor
        $_SESSION['userId']  = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['nombre']  = $user['nombre'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['loginAt'] = date('c');

        // Registrar en tabla sesiones
        $pdo->prepare("INSERT INTO sesiones (usuario_id, ip) VALUES (?, ?)")
            ->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? null]);

        unset($user['password']);
        respond(['ok' => true, 'session' => $_SESSION, 'user' => $user]);
        break;

    // ─── REGISTRO ─────────────────────────────────────────────
    case 'register':
        if ($method !== 'POST') respond(['ok' => false, 'msg' => 'Método no permitido'], 405);

        $body     = getBody();
        $nombre   = trim($body['nombre']   ?? '');
        $apellido = trim($body['apellido'] ?? '');
        $email    = strtolower(trim($body['email'] ?? ''));
        $pass     = $body['password']  ?? '';
        $telefono = $body['telefono']  ?? '';
        $role     = $body['role']      ?? 'comprador';
        $direccion = $body['direccion'] ?? '';

        if (!$nombre || !$apellido || !$email || !$pass)
            respond(['ok' => false, 'msg' => 'Faltan campos obligatorios.']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            respond(['ok' => false, 'msg' => 'Email inválido.']);

        if (strlen($pass) < 6)
            respond(['ok' => false, 'msg' => 'La contraseña debe tener al menos 6 caracteres.']);

        $pdo = getDB();

        // Verificar email único
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) respond(['ok' => false, 'msg' => 'El correo ya está registrado.']);

        // Obtener rol_id
        $rolStmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = ?");
        $rolStmt->execute([$role]);
        $rolRow = $rolStmt->fetch();
        if (!$rolRow) respond(['ok' => false, 'msg' => 'Rol inválido.']);

        $rolId  = $rolRow['id'];
        $hash   = password_hash($pass, PASSWORD_BCRYPT);
        $avatar = $role === 'proveedor' ? '🏭' : '👤';
        $newId  = 'USR-' . strtoupper(substr($role, 0, 4)) . '-' . substr(time(), -5);

        $pdo->prepare("
            INSERT INTO usuarios (id, rol_id, nombre, apellido, email, password, telefono, direccion, avatar)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$newId, $rolId, $nombre, $apellido, $email, $hash, $telefono, $direccion, $avatar]);

        // Si es proveedor, insertar en tabla proveedores
        if ($role === 'proveedor') {
            $empresa = $body['empresa'] ?? '';
            $nit     = $body['nit']     ?? '';
            $pdo->prepare("INSERT INTO proveedores (usuario_id, empresa, nit) VALUES (?, ?, ?)")
                ->execute([$newId, $empresa, $nit]);
        }

        respond(['ok' => true, 'msg' => 'Usuario registrado exitosamente.', 'id' => $newId]);
        break;

    // ─── LOGOUT ───────────────────────────────────────────────
    case 'logout':
        // Actualizar logout_at en sesiones
        if (!empty($_SESSION['userId'])) {
            try {
                getDB()->prepare("
                    UPDATE sesiones SET logout_at = NOW()
                    WHERE usuario_id = ? AND logout_at IS NULL
                    ORDER BY login_at DESC LIMIT 1
                ")->execute([$_SESSION['userId']]);
            } catch (Exception $e) {}
        }
        session_destroy();
        respond(['ok' => true]);
        break;

    // ─── SESIÓN ACTUAL ────────────────────────────────────────
    case 'session':
        if (empty($_SESSION['userId'])) {
            respond(['ok' => false, 'session' => null]);
        }
        respond(['ok' => true, 'session' => $_SESSION]);
        break;

    default:
        respond(['ok' => false, 'msg' => 'Acción no encontrada.'], 404);
}
