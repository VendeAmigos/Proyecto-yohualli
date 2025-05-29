<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de usuario requerido']);
    exit;
}

$userId = (int)$_GET['id'];
$db = Database::getInstance();

try {
    $user = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$userId]);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }
    
    // No enviar datos sensibles
    unset($user['password_hash']);
    unset($user['token_verificacion']);
    unset($user['token_recuperacion']);
    
    echo json_encode($user);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>