<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de contacto requerido']);
    exit;
}

$contactoId = (int)$_GET['id'];
$db = Database::getInstance();

try {
    $contacto = $db->fetch("SELECT * FROM contactos WHERE id = ?", [$contactoId]);
    
    if (!$contacto) {
        http_response_code(404);
        echo json_encode(['error' => 'Contacto no encontrado']);
        exit;
    }
    
    echo json_encode($contacto);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>