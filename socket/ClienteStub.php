<?php
// ============================================================
//  E-PeriTech — Stub del Cliente
// ============================================================
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 1: Analisis de Interoperabilidad
//  El Stub implementa el concepto de transparencia de
//  ubicacion: el cliente no sabe si la operacion es local
//  o remota. Esta es la base del middleware distribuido
//  analizado en la Guia #2 (modelo RMI aplicado a PHP).
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #3 - Actividad 2: Creacion del Descriptor de Socket
//  y Bind. El Stub crea el socket cliente con AF_INET y
//  SOCK_STREAM, y se conecta a la IP y puerto definidos
//  en la topologia de red de la Guia #2.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #3 - Actividad 3: Simulacion de Handshake
//  socket_connect() realiza el apretón de manos inicial
//  con el servidor, validando que el canal de transporte
//  esta correctamente establecido.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 2: Intercambio de Payload
//  El Stub serializa el objeto y lo envia con el
//  delimitador ##FIN## para evitar fragmentacion en el
//  buffer de red. Tambien lee la respuesta del servidor.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 3: Protocolo de Cierre
//  socket_close() libera el descriptor del socket del
//  cliente al finalizar la transmision.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #5 - Actividad 2: Implementacion del Stub
//  (Marshaling de Objetos). Esta clase recibe un objeto
//  Producto, lo convierte a bytes con serialize() y lo
//  inyecta en el socket. El programa principal del cliente
//  llama a enviarProducto() como si fuera un metodo local.
//  GUIA 5 — Actividad 2: Marshaling de objetos
//  El cliente principal llama a este Stub como si fuera local.
//  El Stub serializa el objeto y lo envia por socket.
// ============================================================

require_once 'Producto.php';

class ClienteStub {

    private string $host;
    private int    $port;

    //  --------------CLIENTE SERVIDOR-------------------------
    //  GUIA #2 - Actividad 3: Topologia Logica del MVP
    //  El host 127.0.0.1 y el puerto 8081 corresponden a la
    //  configuracion de red definida en la Guia #2
    //  (NAT + reenvio de puertos en VirtualBox).
    // ---------------------------------------------------------
    public function __construct(string $host = '127.0.0.1', int $port = 8081) {
        $this->host = $host;
        $this->port = $port;
    }

    public function enviarProducto(Producto $producto): array {
        echo "[STUB] Iniciando conexion con el servidor..." . PHP_EOL;

        //  --------------CLIENTE SERVIDOR--------------------------
        //  GUIA #3 - Actividad 2: Creacion del Descriptor de Socket
        //  AF_INET = protocolo IPv4, SOCK_STREAM = TCP confiable,
        //  SOL_TCP = capa de transporte TCP.
        // ---------------------------------------------------------
        // 1. Crear socket cliente
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return ['estado' => 'ERROR', 'mensaje' => 'No se pudo crear socket'];
        }

        //  --------------CLIENTE SERVIDOR-------------------------
        //  GUIA #3 - Actividad 3: Simulacion de Handshake
        //  socket_connect() inicia el apretón de manos TCP con
        //  el servidor en la IP y puerto de la topologia.
        // ---------------------------------------------------------
        // 2. Conectar al servidor en 127.0.0.1:8081
        if (!socket_connect($socket, $this->host, $this->port)) {
            socket_close($socket);
            return ['estado' => 'ERROR', 'mensaje' => 'No se pudo conectar al servidor'];
        }
        echo "[STUB] Conectado. Serializando objeto Producto..." . PHP_EOL;

        //  --------------CLIENTE SERVIDOR-------------------------
        //  GUIA #5 - Actividad 2: MARSHALING de Objetos
        //  serialize() convierte el objeto Producto a una cadena
        //  de bytes lista para viajar por el socket.
        //  ##FIN## es el delimitador de trama definido en la
        //  Guia #1 Actividad 4 (Diseno del Payload).
        // ---------------------------------------------------------
        $payload = serialize($producto) . '##FIN##';
        echo "[STUB] Payload: " . strlen($payload) . " bytes. Enviando..." . PHP_EOL;

        //  --------------CLIENTE SERVIDOR-------------------------
        //  GUIA #4 - Actividad 2: Intercambio de Payload (Escritura)
        //  socket_write() envia la trama serializada al servidor.
        // ---------------------------------------------------------
        socket_write($socket, $payload);
        echo "[STUB] Datos enviados. Esperando respuesta..." . PHP_EOL;

        //  --------------CLIENTE SERVIDOR-------------------------
        //  GUIA #4 - Actividad 2: Intercambio de Payload (Lectura)
        //  Lectura en bucle hasta detectar el delimitador ##FIN##
        //  para evitar que los mensajes se fragmenten en el buffer.
        // ---------------------------------------------------------
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

        //  --------------CLIENTE SERVIDOR-------------------------
        //  GUIA #5 - Actividad 2: Deserializacion de la respuesta
        //  unserialize() reconstruye el array de respuesta
        //  enviado por el servidor.
        // ---------------------------------------------------------
        $respuesta = unserialize(str_replace('##FIN##', '', $buffer));

        //  --------------CLIENTE SERVIDOR-------------------------
        //  GUIA #4 - Actividad 3: Protocolo de Cierre (Socket Close)
        //  socket_close() libera el puerto del cliente correctamente
        //  para ejecuciones posteriores del sistema distribuido.
        // ---------------------------------------------------------
        // 6. Deserializar la respuesta del servidor
        $respuesta = unserialize(str_replace('##FIN##', '', $buffer));

        // 7. Cerrar el socket correctamente
        socket_close($socket);
        echo "[STUB] Socket cerrado." . PHP_EOL;

        return is_array($respuesta) ? $respuesta : ['estado' => 'ERROR'];
    }
}
