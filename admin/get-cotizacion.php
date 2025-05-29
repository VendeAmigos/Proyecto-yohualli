<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cotización requerido']);
    exit;
}

$cotizacionId = (int)$_GET['id'];
$db = Database::getInstance();

try {
    $cotizacion = $db->fetch("
        SELECT c.*, u.nombre, u.apellidos, u.email, u.telefono, u.empresa 
        FROM cotizaciones c 
        LEFT JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.id = ?
    ", [$cotizacionId]);
    
    if (!$cotizacion) {
        http_response_code(404);
        echo json_encode(['error' => 'Cotización no encontrada']);
        exit;
    }
    
    echo json_encode($cotizacion);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>