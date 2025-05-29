<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido requerido']);
    exit;
}

$pedidoId = (int)$_GET['id'];
$db = Database::getInstance();

try {
    $pedido = $db->fetch("
        SELECT p.*, u.nombre, u.apellidos, u.email, u.telefono, u.empresa 
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.id = ?
    ", [$pedidoId]);
    
    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido no encontrado']);
        exit;
    }
    
    echo json_encode($pedido);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>