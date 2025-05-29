<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de producto requerido']);
    exit;
}

$productId = (int)$_GET['id'];
$db = Database::getInstance();

try {
    $product = $db->fetch("SELECT * FROM productos WHERE id = ?", [$productId]);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Producto no encontrado']);
        exit;
    }
    
    echo json_encode($product);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>