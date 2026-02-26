<?php
// api/productos.php — CRUD de productos
require_once __DIR__ . '/config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    // ─── LISTAR ───────────────────────────────────────────────
    case 'list':
        $pdo        = getDB();
        $soloActivos = $_GET['activos'] ?? '1';
        $categoria   = $_GET['categoria'] ?? null;
        $proveedorId = $_GET['proveedor_id'] ?? null;

        $sql    = "SELECT * FROM v_productos WHERE 1=1";
        $params = [];

        if ($soloActivos === '1') { $sql .= " AND activo = 1"; }
        if ($categoria)           { $sql .= " AND categoria = ?"; $params[] = $categoria; }
        if ($proveedorId)         { $sql .= " AND proveedor = (SELECT empresa FROM proveedores WHERE usuario_id = ?)"; }

        // Búsqueda por nombre
        if (!empty($_GET['q'])) {
            $sql .= " AND nombre LIKE ?";
            $params[] = '%' . $_GET['q'] . '%';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond(['ok' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ─── OBTENER POR ID ───────────────────────────────────────
    case 'get':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = getDB()->prepare("SELECT * FROM v_productos WHERE id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod) respond(['ok' => false, 'msg' => 'Producto no encontrado.'], 404);
        respond(['ok' => true, 'product' => $prod]);
        break;

    // ─── CREAR ────────────────────────────────────────────────
    case 'create':
        if ($method !== 'POST') respond(['ok' => false, 'msg' => 'Método no permitido'], 405);

        $body       = getBody();
        $nombre     = trim($body['name'] ?? $body['nombre'] ?? '');
        $categoria  = trim($body['category'] ?? $body['categoria'] ?? '');
        $precio     = (float)($body['price'] ?? $body['precio'] ?? 0);
        $precioViejo = $body['oldPrice'] ?? $body['precio_viejo'] ?? null;
        $icono      = $body['icon'] ?? $body['icono'] ?? '📦';
        $descripcion = $body['description'] ?? $body['descripcion'] ?? '';
        $proveedorId = $body['proveedorId'] ?? $body['proveedor_id'] ?? null;

        if (!$nombre || !$categoria || $precio <= 0)
            respond(['ok' => false, 'msg' => 'Nombre, categoría y precio son obligatorios.']);

        $pdo = getDB();

        // Obtener categoria_id
        $catStmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = ?");
        $catStmt->execute([$categoria]);
        $cat = $catStmt->fetch();
        if (!$cat) respond(['ok' => false, 'msg' => "Categoría '$categoria' no existe."]);

        $descuento = 0;
        if ($precioViejo && $precioViejo > $precio) {
            $descuento = (int)round((1 - $precio / $precioViejo) * 100);
        }

        $pdo->prepare("
            INSERT INTO productos (nombre, categoria_id, precio, precio_viejo, descuento, icono, descripcion, proveedor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$nombre, $cat['id'], $precio, $precioViejo ?: null, $descuento, $icono, $descripcion, $proveedorId]);

        $newId = $pdo->lastInsertId();
        respond(['ok' => true, 'id' => $newId, 'msg' => 'Producto creado.']);
        break;

    // ─── ACTUALIZAR ───────────────────────────────────────────
    case 'update':
        if ($method !== 'POST' && $method !== 'PUT')
            respond(['ok' => false, 'msg' => 'Método no permitido'], 405);

        $body  = getBody();
        $id    = (int)($body['id'] ?? 0);
        if (!$id) respond(['ok' => false, 'msg' => 'ID requerido.']);

        $pdo    = getDB();
        $fields = [];
        $params = [];

        if (isset($body['name']) || isset($body['nombre'])) {
            $fields[] = "nombre = ?";
            $params[] = trim($body['name'] ?? $body['nombre']);
        }
        if (isset($body['category']) || isset($body['categoria'])) {
            $slug    = $body['category'] ?? $body['categoria'];
            $catStmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = ?");
            $catStmt->execute([$slug]);
            $cat = $catStmt->fetch();
            if ($cat) { $fields[] = "categoria_id = ?"; $params[] = $cat['id']; }
        }
        if (isset($body['price']) || isset($body['precio'])) {
            $fields[] = "precio = ?";
            $params[] = (float)($body['price'] ?? $body['precio']);
        }
        if (array_key_exists('oldPrice', $body) || array_key_exists('precio_viejo', $body)) {
            $pv = $body['oldPrice'] ?? $body['precio_viejo'];
            $fields[] = "precio_viejo = ?";
            $params[] = $pv ?: null;
        }
        foreach (['icono' => ['icon','icono'], 'descripcion' => ['description','descripcion']] as $col => $keys) {
            foreach ($keys as $k) {
                if (isset($body[$k])) { $fields[] = "$col = ?"; $params[] = $body[$k]; break; }
            }
        }
        if (isset($body['activo'])) { $fields[] = "activo = ?"; $params[] = (int)$body['activo']; }

        if (empty($fields)) respond(['ok' => false, 'msg' => 'Nada que actualizar.']);

        $params[] = $id;
        $pdo->prepare("UPDATE productos SET " . implode(', ', $fields) . " WHERE id = ?")
            ->execute($params);

        respond(['ok' => true, 'msg' => 'Producto actualizado.']);
        break;

    // ─── ELIMINAR ─────────────────────────────────────────────
    case 'delete':
        $body = getBody();
        $id   = (int)($body['id'] ?? $_GET['id'] ?? 0);
        if (!$id) respond(['ok' => false, 'msg' => 'ID requerido.']);
        getDB()->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
        respond(['ok' => true, 'msg' => 'Producto eliminado.']);
        break;

    default:
        respond(['ok' => false, 'msg' => 'Acción no encontrada.'], 404);
}
