<?php
// ==========================================
// GUÍA 7 - ACTIVIDAD 1: CALLBACKS (SERVIDOR)
// ==========================================

// 1. Creamos la lista de clientes activos donde guardaremos sus "números" (referencias) 
$clientes_activos = []; 

// 2. Configuramos el servidor básico (como en la guía 4 y 5)
$host = "0.0.0.0";
$puerto = 8080;
$servidor = socket_create(AF_INET, SOCK_STREAM, 0);
socket_bind($servidor, $host, $puerto);
socket_listen($servidor);

echo "Servidor Guia 7 escuchando en $host:$puerto...\n";

// 3. Mantenemos el servidor encendido esperando clientes
while (true) {
    // Aceptamos la conexión del cliente
    $conexion_cliente = socket_accept($servidor);
    
    // Leemos lo que nos manda el cliente
    $mensaje_recibido = socket_read($conexion_cliente, 1024);
    $datos = json_decode($mensaje_recibido, true); // Convertimos el JSON a arreglo
    
    // 4. Verificamos si el cliente nos está enviando su "referencia de callback" 
    if (isset($datos['tipo']) && $datos['tipo'] == 'registro_callback') {
        
        $referencia_cliente = $datos['referencia'];
        
        // Guardamos la referencia en nuestra lista de clientes activos 
        $clientes_activos[] = $referencia_cliente;
        echo "Nuevo cliente registrado para notificaciones. Referencia: $referencia_cliente\n";
        
        // 5. SIMULAMOS UN EVENTO DE NEGOCIO ("Stock actualizado") 
        echo "Simulando evento: ¡Stock actualizado de Teclados Mecánicos!\n";
        
        // El servidor ejecuta la función de respuesta enviándole la notificación al cliente
        $notificacion = "ALERTA DEL SERVIDOR: El stock ha sido actualizado. ¡Hay teclados!";
        socket_write($conexion_cliente, $notificacion, strlen($notificacion));
        
    } else {
        echo "Mensaje normal recibido.\n";
    }
    
    // Cerramos la conexión con este cliente
    socket_close($conexion_cliente);
}
// Cerramos el servidor (aunque el while(true) hace que nunca llegue aquí)
socket_close($servidor);
?>