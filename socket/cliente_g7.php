<?php
// ==========================================
// GUÍA 7 - ACTIVIDAD 1: CALLBACKS (CLIENTE)
// ==========================================

$host_servidor = "127.0.0.1"; // Si usas la máquina virtual, pon la IP de Ubuntu aquí
$puerto = 8080;

// 1. Creamos el socket del cliente
$socket_cliente = socket_create(AF_INET, SOCK_STREAM, 0);

echo "Conectando al servidor...\n";
$conexion = socket_connect($socket_cliente, $host_servidor, $puerto);

if ($conexion) {
    // 2. MODIFICAMOS EL STUB (Payload): Creamos la referencia de retorno (callback reference) 
    // Le decimos al servidor quiénes somos para que nos avise.
    $payload_callback = [
        "tipo" => "registro_callback",
        "referencia" => "Cliente_Bryan_Sarah_IP_192.168.1.100" // Esta es nuestra "referencia de retorno" 
    ];
    
    $mensaje_json = json_encode($payload_callback);
    
    // 3. Enviamos nuestra referencia al servidor
    echo "Enviando referencia de callback al servidor...\n";
    socket_write($socket_cliente, $mensaje_json, strlen($mensaje_json));
    
    // 4. Esperamos asíncronamente a que el servidor nos avise (Notificación) 
    echo "Esperando notificación del servidor...\n";
    $respuesta_servidor = socket_read($socket_cliente, 1024);
    
    echo "¡BIP BIP! Mensaje del servidor recibido: \n";
    echo "--> " . $respuesta_servidor . "\n";
    
} else {
    echo "Error al conectar con el servidor.\n";
}

// Cerramos nuestro socket
socket_close($socket_cliente);
?>