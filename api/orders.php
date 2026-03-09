<?php
// API simple pour stocker et consulter les commandes.
// Usage :
//   POST /api/orders.php -> enregistre une commande JSON
//   GET  /api/orders.php -> renvoie la liste des commandes

header('Content-Type: application/json; charset=utf-8');

$storageFile = __DIR__ . '/../data/orders.json';
$productsFile = __DIR__ . '/../data/products.json';

function readOrders($path) {
    if (!file_exists($path)) {
        return [];
    }

    $content = file_get_contents($path);
    if (!$content) {
        return [];
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function writeOrders($path, $orders) {
    $tmp = tempnam(sys_get_temp_dir(), 'orders_');
    file_put_contents($tmp, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    rename($tmp, $path);
}

function readProducts($path) {
    if (!file_exists($path)) {
        return [];
    }

    $content = file_get_contents($path);
    if (!$content) {
        return [];
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function writeProducts($path, $products) {
    $tmp = tempnam(sys_get_temp_dir(), 'products_');
    file_put_contents($tmp, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    rename($tmp, $path);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $orders = readOrders($storageFile);
    echo json_encode(['ok' => true, 'orders' => $orders]);
    exit;
}

if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $body = json_decode($input, true);

    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Corps invalide']);
        exit;
    }

    $orders = readOrders($storageFile);

    // Mise à jour du stock lors de la commande
    $products = readProducts($productsFile);
    foreach ($body['items'] as $item) {
        foreach ($products as &$prod) {
            if ($prod['id'] === $item['id']) {
                $prod['stock'] = max(0, ($prod['stock'] ?? 0) - $item['qty']);
                break;
            }
        }
    }
    unset($prod);
    writeProducts($productsFile, $products);

    $orders[] = $body;
    writeOrders($storageFile, $orders);

    echo json_encode(['ok' => true, 'order' => $body]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
