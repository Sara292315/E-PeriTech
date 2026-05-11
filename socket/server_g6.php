<?php
// ============================================================
//  E-PeriTech — Servidor Guia 6
// ============================================================
//  GUIA #6 - Actividad 2: Protocolo de Registro de Servicios
//  Al iniciar, este servidor se anuncia automáticamente
//  ante el Registry mediante una operación BIND, enviando:
//    - Su nombre lógico: 'ProductoService'
//    - Su IP real: 127.0.0.1
//    - Su puerto: 8082
//
//  Solo DESPUÉS de registrarse exitosamente, el servidor
//  empieza a escuchar conexiones de clientes.
// ============================================================

require_once 'Producto.php';

// Puerto donde este servidor atiende clientes
// (distinto al 8081 de la Guía #5 para no tener conflictos)
define('HOST',            '0.0.0.0');
define('PORT',            8082);

// Dirección del Registry (donde va a hacer el BIND)
define('REGISTRY_IP',     '127.0.0.1');
define('REGISTRY_PORT',   9000);

// Nombre lógico con el que este servicio se identifica
// Es como el "nombre en el directorio"
define('NOMBRE_SERVICIO', 'ProductoService');

echo "=== E-PeriTech | Servidor Guia 6 ===" . PHP_EOL;

// ============================================================
//  FUNCIÓN: registrarEnRegistry()
//  Actividad 2 — Auto-registro al arrancar
//  Abre una conexión temporal al Registry,
//  envía el BIND con nombre+IP+puerto, lee la confirmación
//  y cierra la conexión. Es solo para el registro.
// ============================================================
function registrarEnRegistry(): void {
    echo "[REGISTRO] Conectando al Registry en " . REGISTRY_IP . ":" . REGISTRY_PORT . "..." . PHP_EOL;

    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($sock === false) {
        die("[ERROR] No se pudo crear socket para el Registry." . PHP_EOL);
    }

    // Conectar al Registry
    if (!socket_connect($sock, REGISTRY_IP, REGISTRY_PORT)) {
        die("[ERROR] No se pudo conectar al Registry. ¿Está corriendo registry_g6.php?" . PHP_EOL);
    }

    // Armar el mensaje BIND: nombre lógico + mi IP + mi puerto
    $mensaje = serialize([
        'accion' => 'BIND',
        'nombre' => NOMBRE_SERVICIO,
        'ip'     => '127.0.0.1',   // IP con la que los clientes me alcanzan
        'puerto' => PORT,           // Puerto donde voy a escuchar
    ]) . '##FIN##';

    socket_write($sock, $mensaje);

    // Leer la confirmación del Registry
    $buffer = '';
    while (true) {
        $chunk = socket_read($sock, 4096);
        if ($chunk === false || $chunk === '') break;
        $buffer .= $chunk;
        if (strpos($buffer, '##FIN##') !== false) break;
    }

    socket_close($sock);

    $resp = unserialize(str_replace('##FIN##', '', $buffer));
    echo "[REGISTRO] Registry respondió: " . $resp['estado'] . " — " . ($resp['resultado'] ?? 'sin detalle') . PHP_EOL;
    echo "[REGISTRO] Servicio '" . NOMBRE_SERVICIO . "' disponible en 127.0.0.1:" . PORT . PHP_EOL . PHP_EOL;
}

// ---- Primero: registrarse en el Registry ----
registrarEnRegistry();

// ---- Segundo: ahora sí escuchar clientes ----
// Todo lo que sigue es igual a server_g5.php
echo "Escuchando en " . HOST . ":" . PORT . " ..." . PHP_EOL;
echo "Esperando conexión del cliente..." . PHP_EOL . PHP_EOL;

$servidor = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($servidor, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($servidor, HOST, PORT);
socket_listen($servidor, 5);

$cliente = socket_accept($servidor);
if ($cliente === false) {
    die("[ERROR] Fallo en socket_accept." . PHP_EOL);
}

echo "[CONEXION] Cliente conectado." . PHP_EOL;

// Recibir el payload del cliente (objeto Producto serializado)
$buffer = '';
while (true) {
    $chunk = socket_read($cliente, 4096);
    if ($chunk === false || $chunk === '') break;
    $buffer .= $chunk;
    if (strpos($buffer, '##FIN##') !== false) break;
}

$payload  = str_replace('##FIN##', '', $buffer);
echo "[RECIBIDO] " . strlen($payload) . " bytes." . PHP_EOL;

// UNMARSHALING: igual que Guía #5
$producto = unserialize($payload);

if ($producto instanceof Producto) {
    echo PHP_EOL . "=== OBJETO RECONSTRUIDO EXITOSAMENTE ===" . PHP_EOL;
    echo $producto->mostrar() . PHP_EOL;
    echo "=========================================" . PHP_EOL . PHP_EOL;

    $respuesta = serialize([
        'estado'   => 'OK',
        'mensaje'  => 'Producto recibido y reconstruido vía Registry',
        'producto' => $producto->nombre,
        'precio'   => $producto->precio,
    ]);
    socket_write($cliente, $respuesta . '##FIN##');
    echo "[RESPUESTA] Confirmación enviada al cliente." . PHP_EOL;
} else {
    echo "[ERROR] No se pudo deserializar el objeto." . PHP_EOL;
    socket_write($cliente, serialize(['estado' => 'ERROR']) . '##FIN##');
}

socket_close($cliente);
socket_close($servidor);
echo PHP_EOL . "[FIN] Sockets cerrados correctamente." . PHP_EOL;
