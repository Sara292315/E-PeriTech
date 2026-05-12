<?php
$host = "127.0.0.1"; // Conecta al servidor en la misma máquina
$puerto = 8080;

$xml_payload = '<?xml version="1.0" encoding="UTF-8"?>
<peticion>
    <operacion>consulta_producto</operacion>
    <categoria>teclados</categoria>
    <marca>logitech</marca>
</peticion>';

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "Error al crear socket\n";
} else {
    echo "Conectando al servidor E-PeriTech en Windows...\n";
    socket_connect($socket, $host, $puerto);
    
    socket_write($socket, $xml_payload, strlen($xml_payload));
    echo "XML enviado.\n";
    
    $respuesta = socket_read($socket, 2048);
    echo "Respuesta del servidor: " . $respuesta . "\n";
    
    socket_close($socket);
}
?>