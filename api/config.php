<?php
// ============================================================
//  E-PeriTech — Configuracion de conexion a MariaDB
// ============================================================
//--------------CLIENTE SERVIDOR--------
//  GUIA #1 - Actividad 2: Capa de Datos (3 Capas)
//  Este archivo configura la conexion a la Capa de Datos
//  del modelo de 3 capas definido en la Guia #1:
//  MySQL/MariaDB corriendo en el servidor Ubuntu.
//--------------CLIENTE SERVIDOR--------
//  GUIA #1 - Actividad 3: Procesos Remotos del Servidor
//  La conexion a la base de datos es un proceso critico
//  que se ejecuta unicamente en el servidor (Ubuntu+PHP),
//  nunca en el cliente (navegador web).
//--------------CLIENTE SERVIDOR---------
//  GUIA #2 - Actividad 3: Topologia Logica del MVP
//  DB_HOST localhost = la BD corre en la misma maquina
//  que el servidor PHP (IP 192.168.1.50 segun Guia #2).
//  DB_PORT 3306 = puerto MySQL de la tabla de puertos
//  exclusivos del sistema definida en la Guia #2.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // usuario de phpMyAdmin
<<<<<<< Updated upstream
define('DB_PASS', '');           // contrasena (vacia por defecto en XAMPP)
=======
define('DB_PASS', '1234');           // contraseña (vacía por defecto en XAMPP)
>>>>>>> Stashed changes
define('DB_NAME', 'eperitech');
define('DB_PORT', 3306);

// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 2: Capa de Logica de Negocio
//  getDB() implementa el patron Singleton para la conexion
//  PDO. Las capas estan desacopladas: la interfaz no accede
//  directamente a la BD, sino a traves de este servidor.
// ---------------------------------------------------------
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 4: Payload de Comunicacion
//  Cabeceras HTTP que permiten el intercambio de datos
//  en formato JSON entre cliente y servidor, tal como
//  se definio en el payload de la Guia #1 Actividad 4.
//
//  GUIA #2 - Actividad 1: Middleware / Web Services
//  Estas cabeceras implementan el modelo de Web Services
//  REST analizado en la Guia #2 como middleware para
//  E-PeriTech: comunicacion via HTTP + JSON.
// ---------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function responder(bool $ok, mixed $data = null, string $msg = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'data' => $data, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function bodyJson(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
