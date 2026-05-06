<?php
// ============================================================
//  E-PeriTech — API Ordenes
// ============================================================
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 2: Capa de Logica de Negocio
//  Gestiona el proceso de compra desde el servidor.
//  Las ordenes son un proceso critico que se ejecuta
//  remotamente en Ubuntu+PHP, nunca en el cliente.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 3: Procesos Remotos del Servidor
//  Registro de solicitudes de contacto/compra y
//  administracion futura del catalogo, tal como se
//  definio en los procesos remotos de la Guia #1.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #2 - Actividad 1: Middleware Web Services REST
//  Endpoints via HTTP + JSON para gestion de ordenes.
// ============================================================

require_once __DIR__ . '/../app/Core/Logger.php';
use App\Core\Logger;
require_once 'config.php';
session_start();

Logger::info("todo bien");

$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {

        case 'listar':
            $where  = '';
            $params = [];
            if (!empty($_GET['comprador_id'])) {
                $where = "WHERE o.comprador_id = ?";
                $params[] = $_GET['comprador_id'];
            }
            $stmt = $pdo->prepare("
                SELECT o.*, u.nombre, u.apellido, u.email
                FROM ordenes o
                JOIN usuarios u ON u.id = o.comprador_id
                $where
                ORDER BY o.fecha DESC
            ");
            $stmt->execute($params);
            $ordenes = $stmt->fetchAll();

            // Adjuntar items a cada orden
            foreach ($ordenes as &$orden) {
                $s2 = $pdo->prepare("
                    SELECT oi.*, p.nombre AS producto_nombre
                    FROM orden_items oi
                    JOIN productos p ON p.id = oi.producto_id
                    WHERE oi.orden_id = ?
                ");
                $s2->execute([$orden['id']]);
                $orden['items'] = $s2->fetchAll();
            }
            responder(true, $ordenes);

        case 'obtener':
            $id = $_GET['id'] ?? '';
            if (empty($id)) responder(false, null, 'ID requerido.', 400);
            $stmt = $pdo->prepare("SELECT * FROM ordenes WHERE id = ?");
            $stmt->execute([$id]);
            $orden = $stmt->fetch();
            if (!$orden) responder(false, null, 'Orden no encontrada.', 404);
            $s2 = $pdo->prepare("
                SELECT oi.*, p.nombre AS producto_nombre
                FROM orden_items oi
                JOIN productos p ON p.id = oi.producto_id
                WHERE oi.orden_id = ?
            ");
            $s2->execute([$id]);
            $orden['items'] = $s2->fetchAll();
            responder(true, $orden);

        case 'crear':
            $d = bodyJson();
            if (empty($d['comprador_id']) || empty($d['items'])) {
                responder(false, null, 'comprador_id e items son requeridos.', 400);
            }
            
            $pdo->beginTransaction();
            try {
                // Calcular total
                $total = 0;
                foreach ($d['items'] as $item) {
                    $precio = isset($item['precio']) ? (float)$item['precio'] : (float)$item['price'];
                    $cantidad = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
                    $total += $precio * $cantidad;
                }
                
                // Generar ID de orden (ORD-{timestamp})
                $ordenId = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(6)));
                
                // Insertar orden
                $stmt = $pdo->prepare("
                    INSERT INTO ordenes (id, comprador_id, total, estado) 
                    VALUES (?, ?, ?, 'pendiente')
                ");
                $stmt->execute([$ordenId, $d['comprador_id'], $total]);
                
                // Insertar items de la orden
                $s2 = $pdo->prepare("
                    INSERT INTO orden_items (orden_id, producto_id, nombre_snap, precio_snap, cantidad)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                foreach ($d['items'] as $item) {
                    // Manejar diferentes nombres de campos
                    $productoId = isset($item['producto_id']) ? (int)$item['producto_id'] : 
                                 (isset($item['productoId']) ? (int)$item['productoId'] : 
                                 (isset($item['id']) ? (int)$item['id'] : 0));
                    
                    $nombre = isset($item['nombre']) ? $item['nombre'] : 
                             (isset($item['name']) ? $item['name'] : 'Producto');
                    
                    $precio = isset($item['precio']) ? (float)$item['precio'] : 
                             (isset($item['price']) ? (float)$item['price'] : 0);
                    
                    $cantidad = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
                    
                    if ($productoId > 0 && $precio > 0) {
                        $s2->execute([$ordenId, $productoId, $nombre, $precio, $cantidad]);
                    } else {
                        throw new Exception("Item inválido: producto_id={$productoId}, precio={$precio}");
                    }
                }
                
                $pdo->commit();
                responder(true, ['id' => $ordenId], 'Orden creada.');
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        case 'estado':
            $id     = $_GET['id'] ?? '';
            $d      = bodyJson();
            $estado = $d['estado'] ?? '';
            $validos = ['pendiente','procesando','enviado','entregado','cancelado'];
            
            if (empty($id) || !in_array($estado, $validos)) {
                responder(false, null, 'ID o estado inválido. ID: ' . $id . ', Estado: ' . $estado, 400);
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE ordenes SET estado = ? WHERE id = ?");
                $stmt->execute([$estado, $id]);
                responder(true, null, 'Estado actualizado.');
            } catch (PDOException $e) {
                responder(false, null, 'Error al actualizar estado: ' . $e->getMessage(), 500);
            }

        default:
            responder(false, null, 'Acción no válida.', 400);
    }

} catch (PDOException $e) {
    responder(false, null, 'Error de base de datos: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    responder(false, null, 'Error del servidor: ' . $e->getMessage(), 500);
}
