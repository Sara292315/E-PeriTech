<?php
// ============================================================
//  E-PeriTech — Servidor Guia 5
//  GUIA 5 — Actividad 3: Unmarshaling
//  Uso: php server_g5.php
// ============================================================

require_once 'Producto.php';

define('HOST', '0.0.0.0');
define('PORT', 8081);

echo "=== E-PeriTech | Servidor Guia 5 ===" . PHP_EOL;

// Crear socket
$servidor = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($servidor === false) {
    die("Error al crear socket: " . socket_strerror(socket_last_error()) . PHP_EOL);
}

socket_set_option($servidor, SOL_SOCKET, SO_REUSEADDR, 1);

if (!socket_bind($servidor, HOST, PORT)) {
    die("Error en bind: " . socket_strerror(socket_last_error($servidor)) . PHP_EOL);
}

socket_listen($servidor, 5);
echo "Escuchando en " . HOST . ":" . PORT . " ..." . PHP_EOL;
echo "Esperando conexion del cliente..." . PHP_EOL . PHP_EOL;

// Aceptar conexion entrante
$cliente = socket_accept($servidor);
if ($cliente === false) {
    die("Error en accept: " . socket_strerror(socket_last_error($servidor)) . PHP_EOL);
}

echo "[CONEXION] Cliente conectado." . PHP_EOL;

// Leer los bytes que llegan por el socket
$buffer = '';
while (true) {
    $chunk = socket_read($cliente, 4096);
    if ($chunk === false || $chunk === '') break;
    $buffer .= $chunk;
    if (strpos($buffer, '##FIN##') !== false) break;
}

// Quitar el delimitador del final
$payload = str_replace('##FIN##', '', $buffer);
echo "[RECIBIDO] " . strlen($payload) . " bytes." . PHP_EOL;

// UNMARSHALING: convertir bytes de vuelta a objeto PHP
$producto = unserialize($payload);

if ($producto instanceof Producto) {
    echo PHP_EOL . "=== OBJETO RECONSTRUIDO EXITOSAMENTE ===" . PHP_EOL;
    echo $producto->mostrar() . PHP_EOL;
    echo "=========================================" . PHP_EOL . PHP_EOL;

    // Enviar respuesta serializada de vuelta al cliente
    $respuesta = serialize([
        'estado'   => 'OK',
        'mensaje'  => 'Producto recibido y reconstruido',
        'producto' => $producto->nombre,
        'precio'   => $producto->precio,
    ]);
    socket_write($cliente, $respuesta . '##FIN##');
    echo "[RESPUESTA] Confirmacion enviada al cliente." . PHP_EOL;
} else {
    echo "[ERROR] No se pudo deserializar el objeto." . PHP_EOL;
    socket_write($cliente, serialize(['estado' => 'ERROR']) . '##FIN##');
}

// Cierre seguro de ambos sockets
socket_close($cliente);
socket_close($servidor);
echo PHP_EOL . "[FIN] Sockets cerrados correctamente." . PHP_EOL;
