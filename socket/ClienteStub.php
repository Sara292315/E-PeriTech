<?php
// ============================================================
//  E-PeriTech — Stub del Cliente
//  GUIA 5 — Actividad 2: Marshaling de objetos
//  El cliente principal llama a este Stub como si fuera local.
//  El Stub serializa el objeto y lo envia por socket.
// ============================================================

require_once 'Producto.php';

class ClienteStub {

    private string $host;
    private int    $port;

    public function __construct(string $host = '127.0.0.1', int $port = 8081) {
        $this->host = $host;
        $this->port = $port;
    }

    public function enviarProducto(Producto $producto): array {
        echo "[STUB] Iniciando conexion con el servidor..." . PHP_EOL;

        // 1. Crear socket cliente
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return ['estado' => 'ERROR', 'mensaje' => 'No se pudo crear socket'];
        }

        // 2. Conectar al servidor en 127.0.0.1:8081
        if (!socket_connect($socket, $this->host, $this->port)) {
            socket_close($socket);
            return ['estado' => 'ERROR', 'mensaje' => 'No se pudo conectar al servidor'];
        }
        echo "[STUB] Conectado. Serializando objeto Producto..." . PHP_EOL;

        // 3. MARSHALING: convertir el objeto a bytes
        $payload = serialize($producto) . '##FIN##';
        echo "[STUB] Payload: " . strlen($payload) . " bytes. Enviando..." . PHP_EOL;

        // 4. Enviar los bytes por el socket
        socket_write($socket, $payload);
        echo "[STUB] Datos enviados. Esperando respuesta..." . PHP_EOL;

        // 5. Leer la respuesta del servidor
        $buffer = '';
        while (true) {
            $chunk = socket_read($socket, 4096);
            if ($chunk === false || $chunk === '') break;
            $buffer .= $chunk;
            if (strpos($buffer, '##FIN##') !== false) break;
        }

        // 6. Deserializar la respuesta del servidor
        $respuesta = unserialize(str_replace('##FIN##', '', $buffer));

        // 7. Cerrar el socket correctamente
        socket_close($socket);
        echo "[STUB] Socket cerrado." . PHP_EOL;

        return is_array($respuesta) ? $respuesta : ['estado' => 'ERROR'];
    }
}
