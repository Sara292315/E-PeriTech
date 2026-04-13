<?php
// ============================================================
//  E-PeriTech — Cliente Principal Guia 6
// ============================================================
//  GUIA #6 - Actividad 3: IPs hardcoded eliminadas
//  Comparación con Guía #5:
//
//  ANTES (Guía #5):
//    $stub = new ClienteStub('127.0.0.1', 8081);
//    ↑ El cliente sabía dónde estaba el servidor.
//
//  AHORA (Guía #6):
//    $stub = new ClienteStub_g6('127.0.0.1', 9000, 'ProductoService');
//    ↑ El cliente solo sabe dónde está el Registry y el nombre
//      lógico del servicio. La IP del servidor la resuelve el Stub.
//
//  Si el servidor cambia de IP o puerto, el cliente NO necesita
//  modificarse. Solo el servidor actualiza su BIND en el Registry.
// ============================================================

require_once 'Producto.php';
require_once 'ClienteStub_g6.php';

echo "=== E-PeriTech | Cliente Guia 6 ===" . PHP_EOL . PHP_EOL;

// Crear el objeto de negocio (un producto del catálogo E-PeriTech)
$producto = new Producto(
    3,
    'Mouse Inalámbrico Ergonómico',
    'mouse',
    185000.00,
    'Mouse inalámbrico con sensor óptico de alta precisión'
);

echo "Objeto a enviar:" . PHP_EOL;
echo $producto->mostrar() . PHP_EOL . PHP_EOL;

// ---- ACTIVIDAD 3: Sin IPs quemadas ----
// El cliente ÚNICAMENTE conoce:
//   1. La IP del Registry: 127.0.0.1
//   2. El puerto del Registry: 9000
//   3. El nombre lógico del servicio: 'ProductoService'
// NO sabe nada sobre el puerto 8082 ni la IP del servidor.
$stub = new ClienteStub_g6('127.0.0.1', 9000, 'ProductoService');

// Esta llamada internamente:
//   1. Hace LOOKUP al Registry → obtiene 127.0.0.1:8082
//   2. Se conecta al servidor en 127.0.0.1:8082
//   3. Serializa y envía el producto
//   4. Recibe y deserializa la respuesta
$respuesta = $stub->enviarProducto($producto);

// Mostrar la respuesta que llegó desde el servidor
echo PHP_EOL . "=== RESPUESTA DEL SERVIDOR ===" . PHP_EOL;
foreach ($respuesta as $clave => $valor) {
    echo "  $clave: $valor" . PHP_EOL;
}
echo "==============================" . PHP_EOL;
echo PHP_EOL . "[FIN] Comunicación completada exitosamente vía Registry." . PHP_EOL;
