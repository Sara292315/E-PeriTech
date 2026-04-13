<?php
// ============================================================
//  E-PeriTech — Registry Guia 6
// ============================================================
//  GUIA #6 - Actividad 1: Configuración del Servicio de Directorio
//  Este módulo independiente funciona como tabla de búsqueda.
//  Implementa dos operaciones:
//    - BIND:   un servidor registra su nombre y dirección
//    - LOOKUP: un cliente busca la dirección por nombre lógico
//
//  GUIA #6 - Actividad 2: Validación de disponibilidad
//  Si un servicio ya existe con ese nombre, el Registry
//  actualiza la referencia en lugar de rechazarla, garantizando
//  la interoperabilidad del sistema distribuido.
// ============================================================

// Puerto propio del Registry (distinto al del servidor de negocio)
define('REGISTRY_HOST', '0.0.0.0');
define('REGISTRY_PORT', 9000);

// Esta es la "tabla de búsqueda" de la Actividad 1.
// Es un arreglo asociativo: NombreServicio => [ip, puerto]
// Ejemplo de cómo se ve cuando hay servicios registrados:
// $tabla = [
//   'ProductoService' => ['ip' => '127.0.0.1', 'puerto' => 8082]
// ]
$tabla = [];

echo "=== E-PeriTech | Registry Guia 6 ===" . PHP_EOL;

// Crear el socket del Registry — igual que en Guía #3
$srv = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($srv, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($srv, REGISTRY_HOST, REGISTRY_PORT);
socket_listen($srv, 10);  // Cola de 10: el Registry atiende muchos

echo "Registry escuchando en 0.0.0.0:" . REGISTRY_PORT . PHP_EOL;
echo "Esperando solicitudes BIND o LOOKUP..." . PHP_EOL . PHP_EOL;

// ---- BUCLE INFINITO ----
// A diferencia del servidor de la Guía #5 que atendía UNA sola
// conexión y se cerraba, el Registry debe correr SIEMPRE.
// Atiende múltiples conexiones una por una: primero el servidor
// hace BIND, luego el cliente hace LOOKUP, y así indefinidamente.
while (true) {
    $conn = socket_accept($srv);
    if ($conn === false) continue;

    // Leer el mensaje con el delimitador ##FIN## (igual que Guía #4)
    $buffer = '';
    while (true) {
        $chunk = socket_read($conn, 4096);
        if ($chunk === false || $chunk === '') break;
        $buffer .= $chunk;
        if (strpos($buffer, '##FIN##') !== false) break;
    }

    // Deserializar el mensaje recibido
    $mensaje = unserialize(str_replace('##FIN##', '', $buffer));

    // Validar que el mensaje tenga el campo 'accion'
    if (!is_array($mensaje) || !isset($mensaje['accion'])) {
        socket_write($conn, serialize(['estado' => 'ERROR', 'mensaje' => 'Solicitud inválida']) . '##FIN##');
        socket_close($conn);
        continue;
    }

    // ==========================================
    //  OPERACIÓN BIND — Actividad 1 y 2
    //  El servidor llama a esto al arrancar.
    //  Guarda el nombre del servicio junto con
    //  su IP y puerto en la tabla asociativa.
    // ==========================================
    if ($mensaje['accion'] === 'BIND') {
        $nombre = $mensaje['nombre'];   // Ej: 'ProductoService'
        $ip     = $mensaje['ip'];       // Ej: '127.0.0.1'
        $puerto = $mensaje['puerto'];   // Ej: 8082

        // Actividad 2: detectar si ya existía para actualizar
        // En lugar de rechazar, se actualiza la referencia
        if (isset($tabla[$nombre])) {
            $resultado = 'ACTUALIZADO';  // Ya existía, se pisa el valor
        } else {
            $resultado = 'REGISTRADO';   // Es nuevo, se inserta
        }

        // Guardar en la tabla (arreglo asociativo)
        $tabla[$nombre] = ['ip' => $ip, 'puerto' => $puerto];

        echo "[BIND] Servicio '$nombre' $resultado => $ip:$puerto" . PHP_EOL;

        // Confirmar al servidor que quedó registrado
        $respuesta = ['estado' => 'OK', 'resultado' => $resultado];
        socket_write($conn, serialize($respuesta) . '##FIN##');
    }

    // ==========================================
    //  OPERACIÓN LOOKUP — Actividad 3
    //  El cliente llama a esto antes de conectarse.
    //  Busca el nombre en la tabla y devuelve la IP y puerto.
    //  Si no existe, devuelve NOT_FOUND.
    // ==========================================
    elseif ($mensaje['accion'] === 'LOOKUP') {
        $nombre = $mensaje['nombre'];   // Nombre que busca el cliente

        if (isset($tabla[$nombre])) {
            // ¡Encontrado! Devolver IP y puerto al cliente
            $ip     = $tabla[$nombre]['ip'];
            $puerto = $tabla[$nombre]['puerto'];
            echo "[LOOKUP] Servicio '$nombre' encontrado => $ip:$puerto" . PHP_EOL;

            $respuesta = [
                'estado' => 'OK',
                'ip'     => $ip,
                'puerto' => $puerto,
            ];
        } else {
            // No existe ese servicio en la tabla
            echo "[LOOKUP] Servicio '$nombre' NO encontrado." . PHP_EOL;
            $respuesta = ['estado' => 'NOT_FOUND'];
        }

        socket_write($conn, serialize($respuesta) . '##FIN##');
    }

    else {
        // Acción desconocida
        socket_write($conn, serialize(['estado' => 'ERROR', 'mensaje' => 'Accion desconocida']) . '##FIN##');
    }

    // Cerrar la conexión con quien hizo la consulta
    // (el Registry sigue vivo para la próxima)
    socket_close($conn);
}
