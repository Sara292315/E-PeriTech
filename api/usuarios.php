<?php
// ============================================================
//  E-PeriTech — API Usuarios
// ============================================================
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 2: Capa de Logica de Negocio
//  Gestiona autenticacion y usuarios desde el servidor
//  Ubuntu+PHP, parte de la Capa de Logica de Negocio
//  del modelo de 3 capas definido en la Guia #1.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 3: Procesos Remotos del Servidor
//  Validaciones criticas ejecutadas remotamente:
//  registro, login, gestion de sesiones y permisos.
//  Ninguna validacion de seguridad ocurre en el cliente.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 1: Transparencia de Ubicacion
//  El cliente no sabe donde se ejecuta la validacion
//  del login. Todo parece un unico sistema desde la
//  perspectiva del usuario (transparencia de ubicacion).
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 1: Middleware Web Services REST
//  Endpoints via HTTP + JSON:
//    POST   /api/usuarios.php?action=registro
//    POST   /api/usuarios.php?action=login
//    POST   /api/usuarios.php?action=logout
//    GET    /api/usuarios.php?action=sesion
//    GET    /api/usuarios.php?action=listar
//    PUT    /api/usuarios.php?action=actualizar&id=X
//    DELETE /api/usuarios.php?action=eliminar&id=X
// ============================================================


require_once 'config.php';
session_start();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── REGISTRO ─────────────────────────────────────────
        case 'registro':
            $d = bodyJson();
            $campos = ['nombre','apellido','email','password','rol'];
            foreach ($campos as $c) {
                if (empty($d[$c])) responder(false, null, "El campo '$c' es requerido.", 400);
            }

            $pdo = getDB();

            // Verificar email duplicado
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([strtolower(trim($d['email']))]);
            if ($stmt->fetch()) responder(false, null, 'El correo ya está registrado.', 409);

            // Obtener rol_id
            $rol = strtolower(trim($d['rol']));
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = ?");
            $stmt->execute([$rol]);
            $rolRow = $stmt->fetch();
            if (!$rolRow) responder(false, null, 'Rol inválido.', 400);

            // Generar ID único
            $prefix = match($rol) {
                'admin'     => 'ADMIN',
                'proveedor' => 'PROV',
                default     => 'COMP',
            };
            $id = 'USR-' . $prefix . '-' . strtoupper(substr(uniqid(), -5));

            $avatar = match($rol) {
                'admin'     => '👨‍💼',
                'proveedor' => '🏭',
                default     => '👤',
            };

            $hash = password_hash($d['password'], PASSWORD_BCRYPT);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (id, rol_id, nombre, apellido, email, password, telefono, direccion, avatar)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $rolRow['id'],
                trim($d['nombre']),
                trim($d['apellido']),
                strtolower(trim($d['email'])),
                $hash,
                $d['telefono'] ?? null,
                $d['direccion'] ?? null,
                $avatar,
            ]);

            // Si es proveedor, insertar en tabla proveedores
            if ($rol === 'proveedor') {
                if (empty($d['empresa']) || empty($d['nit'])) {
                    $pdo->rollBack();
                    responder(false, null, 'Empresa y NIT son requeridos para proveedores.', 400);
                }
                $stmt = $pdo->prepare("INSERT INTO proveedores (usuario_id, empresa, nit) VALUES (?, ?, ?)");
                $stmt->execute([$id, trim($d['empresa']), trim($d['nit'])]);
            }

            $pdo->commit();

            responder(true, ['id' => $id], 'Usuario registrado correctamente.');

        // ── LOGIN CON GOOGLE ──────────────────────────────────
        // Verifica el access_token con la API de Google, luego
        // busca o crea el usuario en la base de datos local.
        case 'loginGoogle':
            $d = bodyJson();
            if (empty($d['access_token'])) {
                responder(false, null, 'Token de Google requerido.', 400);
            }

            // Verificar el token con Google y obtener info del usuario
            $googleRes = @file_get_contents(
                'https://www.googleapis.com/oauth2/v3/userinfo',
                false,
                stream_context_create([
                    'http' => [
                        'header' => 'Authorization: Bearer ' . $d['access_token'],
                        'timeout' => 10,
                    ]
                ])
            );

            if (!$googleRes) {
                responder(false, null, 'No se pudo verificar el token con Google.', 401);
            }

            $gUser = json_decode($googleRes, true);
            if (empty($gUser['email'])) {
                responder(false, null, 'Token de Google inválido o sin permiso de email.', 401);
            }

            $pdo   = getDB();
            $email = strtolower(trim($gUser['email']));

            // Buscar si ya existe el usuario
            $stmt = $pdo->prepare("
                SELECT u.*, r.nombre AS rol
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Registrar nuevo usuario con rol comprador por defecto
                $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'comprador'");
                $stmt->execute();
                $rolRow = $stmt->fetch();
                if (!$rolRow) responder(false, null, 'Rol comprador no encontrado.', 500);

                $id      = 'USR-COMP-' . strtoupper(substr(uniqid(), -5));
                $nombre  = $gUser['given_name']  ?? explode('@', $email)[0];
                $apellido = $gUser['family_name'] ?? '';
                $avatar  = $gUser['picture']      ?? '👤';

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (id, rol_id, nombre, apellido, email, password, avatar)
                    VALUES (?, ?, ?, ?, ?, '', ?)
                ");
                $stmt->execute([$id, $rolRow['id'], $nombre, $apellido, $email, $avatar]);

                // Obtener el usuario recién creado
                $stmt = $pdo->prepare("
                    SELECT u.*, r.nombre AS rol
                    FROM usuarios u
                    JOIN roles r ON r.id = u.rol_id
                    WHERE u.id = ?
                ");
                $stmt->execute([$id]);
                $user = $stmt->fetch();
            }

            if (!$user['activo']) responder(false, null, 'Cuenta desactivada.', 403);

            // Iniciar sesión PHP
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_rol']    = $user['rol'];
            $_SESSION['user_nombre'] = $user['nombre'];

            unset($user['password']);
            responder(true, $user, 'Login con Google exitoso.');

        // ── LOGIN ─────────────────────────────────────────────
        case 'login':
            $d = bodyJson();
            if (empty($d['email']) || empty($d['password'])) {
                responder(false, null, 'Email y contraseña requeridos.', 400);
            }

            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT u.*, r.nombre AS rol
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id
                WHERE u.email = ?
            ");
            $stmt->execute([strtolower(trim($d['email']))]);
            $user = $stmt->fetch();

            if (!$user) responder(false, null, 'Correo no registrado.', 401);
            if (!$user['activo']) responder(false, null, 'Cuenta desactivada.', 403);
            if (!password_verify($d['password'], $user['password'])) {
                responder(false, null, 'Contraseña incorrecta.', 401);
            }

            // Guardar sesión PHP
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['user_rol'] = $user['rol'];
            $_SESSION['user_nombre'] = $user['nombre'];

            unset($user['password']); // No enviar hash al cliente
            responder(true, $user, 'Login exitoso.');

        // ── LOGOUT ────────────────────────────────────────────
        case 'logout':
            session_destroy();
            responder(true, null, 'Sesión cerrada.');

        // ── SESIÓN ACTIVA ─────────────────────────────────────
        case 'sesion':
            if (empty($_SESSION['user_id'])) {
                responder(false, null, 'No hay sesión activa.');
            }
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT u.id, u.nombre, u.apellido, u.email, u.telefono,
                       u.direccion, u.avatar, u.activo, u.created_at,
                       r.nombre AS rol
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id
                WHERE u.id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            responder(true, $user);

        // ── LISTAR ────────────────────────────────────────────
        case 'listar':
            $pdo = getDB();
            $where = '';
            $params = [];
            if (!empty($_GET['rol'])) {
                $where = "WHERE r.nombre = ?";
                $params[] = $_GET['rol'];
            }
            $stmt = $pdo->prepare("
                SELECT u.id, u.nombre, u.apellido, u.email, u.telefono,
                       u.direccion, u.avatar, u.activo, u.created_at,
                       r.nombre AS rol
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id
                $where
                ORDER BY u.created_at DESC
            ");
            $stmt->execute($params);
            responder(true, $stmt->fetchAll());

        // ── OBTENER UNO ───────────────────────────────────────
        case 'obtener':
            $id = $_GET['id'] ?? '';
            if (!$id) responder(false, null, 'ID requerido.', 400);
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT u.id, u.nombre, u.apellido, u.email, u.telefono,
                       u.direccion, u.avatar, u.activo, u.created_at,
                       r.nombre AS rol
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if (!$user) responder(false, null, 'Usuario no encontrado.', 404);
            responder(true, $user);

        // ── ACTUALIZAR ────────────────────────────────────────
        case 'actualizar':
            $id = $_GET['id'] ?? '';
            $d  = bodyJson();
            if (!$id) responder(false, null, 'ID requerido.', 400);

            $pdo = getDB();
            $campos = [];
            $params = [];

            if (!empty($d['nombre']))    { $campos[] = 'nombre = ?';    $params[] = trim($d['nombre']); }
            if (!empty($d['apellido']))  { $campos[] = 'apellido = ?';  $params[] = trim($d['apellido']); }
            if (!empty($d['telefono']))  { $campos[] = 'telefono = ?';  $params[] = $d['telefono']; }
            if (isset($d['direccion']))  { $campos[] = 'direccion = ?'; $params[] = $d['direccion']; }
            if (isset($d['activo']))     { $campos[] = 'activo = ?';    $params[] = (int)$d['activo']; }
            if (!empty($d['password']))  {
                $campos[] = 'password = ?';
                $params[] = password_hash($d['password'], PASSWORD_BCRYPT);
            }

            if (empty($campos)) responder(false, null, 'Nada que actualizar.', 400);

            $params[] = $id;
            $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
            $pdo->prepare($sql)->execute($params);
            responder(true, null, 'Usuario actualizado.');

        // ── ELIMINAR ──────────────────────────────────────────
        case 'eliminar':
            $id = $_GET['id'] ?? '';
            if (!$id) responder(false, null, 'ID requerido.', 400);
            $pdo = getDB();
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
            responder(true, null, 'Usuario eliminado.');

        default:
            responder(false, null, 'Acción no válida.', 400);
    }

} catch (PDOException $e) {
    responder(false, null, 'Error de base de datos: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    responder(false, null, 'Error del servidor: ' . $e->getMessage(), 500);
}
