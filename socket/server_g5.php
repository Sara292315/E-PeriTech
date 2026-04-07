<?php
// ============================================================
//  E-PeriTech — Servidor Guia 5
// ============================================================
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 2: Capa de Logica de Negocio
//  Este servidor representa la Capa de Logica de Negocio
//  del modelo de 3 capas definido en la Guia #1:
//  (Ubuntu Server + PHP) que procesa las solicitudes
//  remotas del cliente web.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 3: Procesos Remotos del Servidor
//  El servidor ejecuta los procesos criticos: validacion
//  del objeto recibido y procesamiento de la solicitud,
//  tal como se definio en la Guia #1 Actividad 3.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 3: Topologia Logica del MVP
//  HOST 0.0.0.0 = escucha en todas las interfaces (igual
//  al "Apache escuchando en 0.0.0.0" de la Guia #2).
//  PORT 8081 = puerto exclusivo del sistema definido en
//  la tabla de puertos de la Guia #2 Actividad 3.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #3 - Actividad 1: Habilitacion de PHP-Sockets
//  Requiere la extension sockets activa en Ubuntu Server
//  (verificada con: php -m | grep sockets).
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #3 - Actividad 2: Creacion del Descriptor y Bind
//  Crea el socket servidor con AF_INET y lo enlaza a la
//  IP y puerto definidos en la topologia de la Guia #2.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 1: Algoritmos de Escucha
//  socket_listen() y socket_accept() implementan la logica
//  de espera y aceptacion de conexiones entrantes.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 2: Intercambio de Payload
//  Lee la trama serializada con delimitador ##FIN## y
//  procesa el objeto segun las reglas de negocio.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 3: Protocolo de Cierre
//  socket_close() libera ambos descriptores (cliente y
//  servidor) al finalizar la transmision.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #5 - Actividad 3: Reconstruccion en el Servidor
//  (Unmarshaling). unserialize() convierte los bytes de
//  vuelta al objeto Producto original, validando que
//  el estado se mantiene igual al enviado por el cliente.
// ============================================================

require_once 'Producto.php';

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 3: Puertos exclusivos del sistema
//  HOST 0.0.0.0 = acepta conexiones de cualquier interfaz
//  PORT 8081    = puerto exclusivo Guia 5 (tabla de puertos)
// ---------------------------------------------------------
define('HOST', '0.0.0.0');
define('PORT', 8081);

echo "=== E-PeriTech | Servidor Guia 5 ===" . PHP_EOL;

// - --------------CLIENTE SERVIDOR-------------------------
//  GUIA #3 - Actividad 2: Creacion del Descriptor de Socket
//  AF_INET = IPv4, SOCK_STREAM = TCP, SOL_TCP = transporte.
//  Se valida el retorno — si falla, reporta el error tecnico.
// ---------------------------------------------------------
$servidor = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($servidor === false) {
    die("Error al crear socket: " . socket_strerror(socket_last_error()) . PHP_EOL);
}

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 3: Prevencion de errores de Binding
//  SO_REUSEADDR permite reutilizar el puerto si quedo
//  ocupado de una ejecucion anterior (binding seguro).
// ---------------------------------------------------------
socket_set_option($servidor, SOL_SOCKET, SO_REUSEADDR, 1);

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #3 - Actividad 2: Bind del socket
//  Enlaza el socket a la IP y puerto de la topologia.
// ---------------------------------------------------------
if (!socket_bind($servidor, HOST, PORT)) {
    die("Error en bind: " . socket_strerror(socket_last_error($servidor)) . PHP_EOL);
}

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 1: Algoritmo de Escucha (Listen)
//  socket_listen() pone el servidor en estado de espera.
//  El parametro 5 = cola maxima de conexiones pendientes.
// ---------------------------------------------------------
socket_listen($servidor, 5);
echo "Escuchando en " . HOST . ":" . PORT . " ..." . PHP_EOL;
echo "Esperando conexion del cliente..." . PHP_EOL . PHP_EOL;

// - --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 1: Algoritmo de Aceptacion (Accept)
//  socket_accept() bloquea el servidor hasta que un cliente
//  se conecte, implementando el handshake de la Guia #3.
// ---------------------------------------------------------
$cliente = socket_accept($servidor);
if ($cliente === false) {
    die("Error en accept: " . socket_strerror(socket_last_error($servidor)) . PHP_EOL);
}

echo "[CONEXION] Cliente conectado." . PHP_EOL;

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 2: Lectura del Payload
//  Lectura en bucle con chunks de 4096 bytes hasta detectar
//  el delimitador ##FIN## que evita fragmentacion del mensaje
//  en el buffer de red (definido en Guia #1 Actividad 4).
// ---------------------------------------------------------
$buffer = '';
while (true) {
    $chunk = socket_read($cliente, 4096);
    if ($chunk === false || $chunk === '') break;
    $buffer .= $chunk;
    if (strpos($buffer, '##FIN##') !== false) break;
}

$payload = str_replace('##FIN##', '', $buffer);
echo "[RECIBIDO] " . strlen($payload) . " bytes." . PHP_EOL;

// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #5 - Actividad 3: UNMARSHALING
//  unserialize() convierte los bytes recibidos de vuelta
//  al objeto Producto original. instanceof valida que la
//  reconstruccion fue exitosa y el estado se mantiene.
// ---------------------------------------------------------
$producto = unserialize($payload);

if ($producto instanceof Producto) {
    echo PHP_EOL . "=== OBJETO RECONSTRUIDO EXITOSAMENTE ===" . PHP_EOL;
    //  --------------CLIENTE SERVIDOR-------------------------
    //  GUIA #5 - Actividad 3: Inspeccion del objeto reconstruido
    //  mostrar() valida que los datos de las propiedades son
    //  identicos a los enviados desde el cliente (Marshaling).
    // ---------------------------------------------------------
    echo $producto->mostrar() . PHP_EOL;
    echo "=========================================" . PHP_EOL . PHP_EOL;

    //  --------------CLIENTE SERVIDOR-------------------------
    //  GUIA #4 - Actividad 2: Escritura del Payload de respuesta
    //  El servidor serializa su respuesta y la envia con ##FIN##
    //  siguiendo el flujo Request/Response de la Guia #1 Act. 4.
    // ---------------------------------------------------------
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

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #4 - Actividad 3: Protocolo de Cierre (Socket Close)
//  Se cierran ambos descriptores: el socket del cliente
//  conectado y el socket servidor principal. Esto libera
//  los puertos en Ubuntu para ejecuciones posteriores.
// ---------------------------------------------------------
socket_close($cliente);
socket_close($servidor);
echo PHP_EOL . "[FIN] Sockets cerrados correctamente." . PHP_EOL;
