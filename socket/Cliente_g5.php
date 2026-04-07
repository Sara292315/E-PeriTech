<?php
// ============================================================
//  E-PeriTech — Cliente Principal Guia 5
// ============================================================
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 3: Delimitacion de Procesos Remotos
//  Este archivo representa la Logica de Cliente definida
//  en la Guia #1: captura el objeto de negocio (Producto)
//  antes de enviarlo al servidor para su procesamiento.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 1: Transparencia de Ubicacion
//  El cliente NO sabe si enviarProducto() es local o
//  remoto. Esta es la transparencia de ubicacion que
//  define el middleware analizado en la Guia #2.
//  Desde la perspectiva del cliente, todo parece un
//  unico sistema aunque el procesamiento sea remoto.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #5 - Actividad 2: Implementacion del Stub
//  El programa principal llama a $stub->enviarProducto()
//  como si fuera un metodo local. El Stub se encarga
//  de empaquetar (Marshaling) el objeto y enviarlo,
//  otorgando la primera capa de transparencia al sistema.
//  GUIA 5 — Actividad 2
//  Este archivo solo maneja la logica de negocio.
//  No sabe nada de sockets — delega todo al Stub.
// ============================================================

require_once 'Producto.php';
require_once 'ClienteStub.php';

echo "=== E-PeriTech | Cliente Guia 5 ===" . PHP_EOL . PHP_EOL;

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 1: Escenario de Software
//  Se instancia un Producto real del catalogo E-PeriTech,
//  igual a los que se gestionan en la base de datos
//  MariaDB definida en la Guia #1 Actividad 2.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #5 - Actividad 1: Clase Serializable
//  El objeto Producto creado aqui es el mismo que
//  sera serializado y enviado al servidor.
// ---------------------------------------------------------
// Crear el objeto de negocio
// (igual que un producto real de la base de datos E-PeriTech)
$producto = new Producto(
    2,
    'Teclado Mecanico RGB Gaming',
    'teclado',
    225000.00,
    'Teclado mecanico con iluminacion RGB para gaming'
);

echo "Objeto a enviar:" . PHP_EOL;
echo $producto->mostrar() . PHP_EOL . PHP_EOL;

// - --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 3: Topologia Logica del MVP
//  127.0.0.1 y puerto 8081 corresponden a la configuracion
//  NAT + reenvio de puertos definida en la Guia #2.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #5 - Actividad 2: Uso del Stub
//  El cliente NO toca el socket directamente.
//  Llama a enviarProducto() como metodo local y es
//  el Stub quien gestiona toda la comunicacion remota.
// ---------------------------------------------------------
$stub = new ClienteStub('127.0.0.1', 8081);
$respuesta = $stub->enviarProducto($producto);

//  --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 4: Payload de Respuesta
//  Se procesa la respuesta JSON-like del servidor,
//  equivalente al flujo Request/Response del diagrama
//  de secuencia definido en la Guia #1 Actividad 4.
// ---------------------------------------------------------
// Usar el Stub — el cliente NO toca el socket directamente
// Para el cliente es como llamar a un metodo local normal
$stub = new ClienteStub('127.0.0.1', 8081);
$respuesta = $stub->enviarProducto($producto);

// Mostrar la respuesta que vino del servidor
echo PHP_EOL . "=== RESPUESTA DEL SERVIDOR ===" . PHP_EOL;
foreach ($respuesta as $clave => $valor) {
    echo "  $clave: $valor" . PHP_EOL;
}
echo "==============================" . PHP_EOL;
echo PHP_EOL . "[FIN] Comunicacion completada exitosamente." . PHP_EOL;
