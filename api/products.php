<?php
// API REST simple pour gérer les produits.
// - GET  /api/products.php       : liste les produits
// - POST /api/products.php      : ajoute un produit
// - PUT  /api/products.php      : met à jour un produit
// - DELETE /api/products.php    : supprime un produit

header('Content-Type: application/json; charset=utf-8');

$storageFile = __DIR__ . '/../data/products.json';

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
    $products = readProducts($storageFile);
    echo json_encode(['ok' => true, 'products' => $products]);
    exit;
}

$input = file_get_contents('php://input');
$body = json_decode($input, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Requête invalide']);
    exit;
}

$products = readProducts($storageFile);

if ($method === 'POST') {
    // Création
    $body['id'] = $body['id'] ?? bin2hex(random_bytes(5));
    if (!isset($body['title']) || !isset($body['price'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Titre et prix requis']);
        exit;
    }

    $products[] = $body;
    writeProducts($storageFile, $products);
    echo json_encode(['ok' => true, 'product' => $body]);
    exit;
}

if ($method === 'PUT') {
    if (!isset($body['id'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID requis']);
        exit;
    }

    $found = false;
    foreach ($products as &$product) {
        if ($product['id'] === $body['id']) {
            $product = array_merge($product, $body);
            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Produit introuvable']);
        exit;
    }

    writeProducts($storageFile, $products);
    echo json_encode(['ok' => true, 'product' => $body]);
    exit;
}

if ($method === 'DELETE') {
    if (!isset($body['id'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID requis']);
        exit;
    }

    $products = array_filter($products, fn($p) => $p['id'] !== $body['id']);
    writeProducts($storageFile, array_values($products));

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
