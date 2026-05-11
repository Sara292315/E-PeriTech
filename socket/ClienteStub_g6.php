<?php
// ============================================================
//  E-PeriTech — Stub del Cliente Guia 6
// ============================================================
//  GUIA #6 - Actividad 3: Búsqueda Dinámica (Naming Systems)
//  Este Stub ya NO recibe la IP del servidor como parámetro.
//  En su lugar, recibe la dirección del Registry y el nombre
//  lógico del servicio que necesita.
//
//  Internamente, antes de conectarse al servidor, hace un
//  LOOKUP al Registry para obtener IP y puerto dinámicamente.
//
//  Para el cliente principal (Cliente_g6.php), la llamada
//  sigue siendo igual de simple:
//    $stub->enviarProducto($producto)
//  La transparencia de ubicación se mantiene intacta.
// ============================================================

require_once 'Producto.php';

class ClienteStub_g6 {

    private string $registryIp;
    private int    $registryPort;
    private string $nombreServicio;

    // El constructor ya NO recibe IP del servidor.
    // Solo necesita saber dónde está el Registry y qué servicio buscar.
    public function __construct(
        string $registryIp     = '127.0.0.1',
        int    $registryPort   = 9000,
        string $nombreServicio = 'ProductoService'
    ) {
        $this->registryIp     = $registryIp;
        $this->registryPort   = $registryPort;
        $this->nombreServicio = $nombreServicio;
    }

    // ============================================================
    //  MÉTODO PRIVADO: lookup()
    //  Actividad 3 — Búsqueda dinámica en el Registry
    //
    //  Abre una conexión temporal al Registry, envía un LOOKUP
    //  con el nombre lógico del servicio, y recibe como respuesta
    //  la IP y el puerto donde está corriendo ese servidor.
    //
    //  Retorna un array con 'estado', 'ip' y 'puerto'.
    // ============================================================
    private function lookup(): array {
        echo "[STUB] Consultando Registry en {$this->registryIp}:{$this->registryPort}..." . PHP_EOL;
        echo "[STUB] Buscando servicio: '{$this->nombreServicio}'" . PHP_EOL;

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($sock === false) {
            return ['estado' => 'ERROR', 'mensaje' => 'No se pudo crear socket para Registry'];
        }

        // Conectar al Registry
        if (!socket_connect($sock, $this->registryIp, $this->registryPort)) {
            socket_close($sock);
            return ['estado' => 'ERROR', 'mensaje' => 'No se pudo conectar al Registry'];
        }

        // Enviar la solicitud LOOKUP con el nombre lógico
        $mensaje = serialize([
            'accion' => 'LOOKUP',
            'nombre' => $this->nombreServicio,
        ]) . '##FIN##';

        socket_write($sock, $mensaje);

        // Leer la respuesta del Registry
        $buffer = '';
        while (true) {
            $chunk = socket_read($sock, 4096);
            if ($chunk === false || $chunk === '') break;
            $buffer .= $chunk;
            if (strpos($buffer, '##FIN##') !== false) break;
        }

        socket_close($sock);

        $respuesta = unserialize(str_replace('##FIN##', '', $buffer));
        return is_array($respuesta) ? $respuesta : ['estado' => 'ERROR'];
    }

    // ============================================================
    //  MÉTODO PÚBLICO: enviarProducto()
    //  El cliente lo llama igual que en la Guía #5.
    //  Internamente ahora tiene DOS fases:
    //    Fase 1 — LOOKUP: pregunta al Registry la dirección
    //    Fase 2 — Conexión: se conecta al servidor con esa dirección
    // ============================================================
    public function enviarProducto(Producto $producto): array {

        // ---- FASE 1: Resolver dirección vía Registry ----
        $direccion = $this->lookup();

        if ($direccion['estado'] !== 'OK') {
            echo "[STUB] ERROR: Servicio '{$this->nombreServicio}' no encontrado en el Registry." . PHP_EOL;
            echo "[STUB] ¿Está corriendo server_g6.php?" . PHP_EOL;
            return ['estado' => 'ERROR', 'mensaje' => 'Servicio no encontrado en Registry'];
        }

        $ip     = $direccion['ip'];
        $puerto = $direccion['puerto'];
        echo "[STUB] ¡Servicio encontrado! Dirección obtenida: $ip:$puerto" . PHP_EOL;
        echo "[STUB] Conectando al servidor..." . PHP_EOL;

        // ---- FASE 2: Conectar al servidor con la dirección obtenida ----
        // Desde aquí en adelante es igual a ClienteStub.php de la Guía #5
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return ['estado' => 'ERROR', 'mensaje' => 'No se pudo crear socket'];
        }

        if (!socket_connect($socket, $ip, $puerto)) {
            socket_close($socket);
            return ['estado' => 'ERROR', 'mensaje' => 'No se pudo conectar al servidor en ' . $ip . ':' . $puerto];
        }

        echo "[STUB] Conectado. Serializando objeto Producto..." . PHP_EOL;

        // Marshaling: serializar el objeto y enviarlo
        $payload = serialize($producto) . '##FIN##';
        echo "[STUB] Payload: " . strlen($payload) . " bytes. Enviando..." . PHP_EOL;
        socket_write($socket, $payload);
        echo "[STUB] Datos enviados. Esperando respuesta del servidor..." . PHP_EOL;

        // Leer la respuesta del servidor
        $buffer = '';
        while (true) {
            $chunk = socket_read($socket, 4096);
            if ($chunk === false || $chunk === '') break;
            $buffer .= $chunk;
            if (strpos($buffer, '##FIN##') !== false) break;
        }

        $respuesta = unserialize(str_replace('##FIN##', '', $buffer));
        socket_close($socket);
        echo "[STUB] Socket cerrado." . PHP_EOL;

        return is_array($respuesta) ? $respuesta : ['estado' => 'ERROR'];
    }
}
