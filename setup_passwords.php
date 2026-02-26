<?php
/**
 * setup_passwords.php
 * ─────────────────────────────────────────────────────────────
 * Ejecuta este archivo UNA SOLA VEZ desde el navegador:
 *   http://localhost/E-PeriTech/setup_passwords.php
 *
 * Actualiza las contraseñas de los usuarios seed con hashes
 * bcrypt correctos. Luego ELIMINA este archivo por seguridad.
 * ─────────────────────────────────────────────────────────────
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'eperitech';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die('<h2 style="color:red">❌ Error de conexión: ' . $e->getMessage() . '</h2>');
}

$usuarios = [
    ['email' => 'admin@eperitech.com',       'password' => 'Admin2025!'],
    ['email' => 'proveedor@techgear.com',    'password' => 'Prov2025!'],
    ['email' => 'proveedor@visionmax.com',   'password' => 'Prov2025!'],
    ['email' => 'andres@gmail.com',          'password' => 'User2025!'],
];

$stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
$ok   = [];

foreach ($usuarios as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt->execute([$hash, $u['email']]);
    $ok[] = "✅ <strong>{$u['email']}</strong> → contraseña actualizada.";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>E-PeriTech — Setup</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 60px auto; padding: 20px; }
        h1   { color: #667eea; }
        li   { margin: 8px 0; font-size: 15px; }
        .warn{ background:#fef9c3; border:1px solid #f59e0b; padding:16px; border-radius:8px; margin-top:24px; }
        .btn { display:inline-block; margin-top:20px; padding:12px 24px; background:#667eea; color:white;
               text-decoration:none; border-radius:8px; font-weight:bold; }
    </style>
</head>
<body>
    <h1>🔐 Setup de contraseñas — E-PeriTech</h1>
    <ul>
        <?php foreach ($ok as $msg) echo "<li>$msg</li>"; ?>
    </ul>

    <div class="warn">
        ⚠️ <strong>¡Importante!</strong> Elimina este archivo ahora que ya cumplió su función:<br><br>
        <code>C:\xampp\htdocs\E-PeriTech\setup_passwords.php</code>
    </div>

    <a class="btn" href="index.html">🏠 Ir a la tienda</a>
    &nbsp;
    <a class="btn" style="background:#10b981;" href="login.html">🔑 Ir al login</a>
</body>
</html>
<?php
// Auto-delete this file after running (opcional)
// unlink(__FILE__);
?>
