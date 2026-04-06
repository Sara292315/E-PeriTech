<?php
// ============================================================
//  E-PeriTech — Cliente Principal Guia 5
//  GUIA 5 — Actividad 2
//  Este archivo solo maneja la logica de negocio.
//  No sabe nada de sockets — delega todo al Stub.
// ============================================================

require_once 'Producto.php';
require_once 'ClienteStub.php';

echo "=== E-PeriTech | Cliente Guia 5 ===" . PHP_EOL . PHP_EOL;

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
