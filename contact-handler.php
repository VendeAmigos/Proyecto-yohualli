<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Validar y sanitizar datos
    $data = [
        'nombre' => sanitize($_POST['name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'telefono' => sanitize($_POST['phone'] ?? ''),
        'empresa' => sanitize($_POST['company'] ?? ''),
        'asunto' => sanitize($_POST['subject'] ?? ''),
        'tipo_proyecto' => sanitize($_POST['project_type'] ?? ''),
        'presupuesto' => sanitize($_POST['budget'] ?? ''),
        'timeline' => sanitize($_POST['timeline'] ?? ''),
        'mensaje' => sanitize($_POST['message'] ?? ''),
        'newsletter' => isset($_POST['newsletter']) ? 1 : 0
    ];
    
    $errors = [];
    
    // Validaciones
    if (empty($data['nombre'])) {
        $errors[] = 'El nombre es requerido';
    }
    
    if (empty($data['email']) || !validateEmail($data['email'])) {
        $errors[] = 'Email válido es requerido';
    }
    
    if (empty($data['asunto'])) {
        $errors[] = 'El asunto es requerido';
    }
    
    if (empty($data['mensaje'])) {
        $errors[] = 'El mensaje es requerido';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    // Insertar en base de datos
    $db = Database::getInstance();
    
    $sql = "INSERT INTO contactos (nombre, email, telefono, empresa, asunto, tipo_proyecto, presupuesto, timeline, mensaje, ip_cliente, user_agent, fecha_mensaje) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $params = [
        $data['nombre'],
        $data['email'],
        $data['telefono'],
        $data['empresa'],
        $data['asunto'],
        $data['tipo_proyecto'],
        $data['presupuesto'],
        $data['timeline'],
        $data['mensaje'],
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    $db->query($sql, $params);
    $contactId = $db->lastInsertId();
    
    // Si se suscribió al newsletter, agregar a lista (implementar según necesidades)
    if ($data['newsletter']) {
        // Aquí puedes agregar lógica para newsletter
    }
    
    // Enviar email de notificación al admin (opcional)
    $adminNotification = "
        <h2>Nuevo contacto desde el sitio web</h2>
        <p><strong>Nombre:</strong> {$data['nombre']}</p>
        <p><strong>Email:</strong> {$data['email']}</p>
        <p><strong>Teléfono:</strong> {$data['telefono']}</p>
        <p><strong>Empresa:</strong> {$data['empresa']}</p>
        <p><strong>Asunto:</strong> {$data['asunto']}</p>
        <p><strong>Tipo de proyecto:</strong> {$data['tipo_proyecto']}</p>
        <p><strong>Presupuesto:</strong> {$data['presupuesto']}</p>
        <p><strong>Timeline:</strong> {$data['timeline']}</p>
        <p><strong>Mensaje:</strong></p>
        <p>{$data['mensaje']}</p>
    ";
    
    // sendEmail(SITE_EMAIL, 'Nuevo contacto - Yohualli', $adminNotification);
    
    // Respuesta de confirmación al usuario (opcional)
    $userConfirmation = "
        <h2>Gracias por contactarnos, {$data['nombre']}</h2>
        <p>Hemos recibido tu mensaje sobre: <strong>{$data['asunto']}</strong></p>
        <p>Nos pondremos en contacto contigo en menos de 24 horas.</p>
        <p>Saludos,<br>Equipo Yohualli</p>
    ";
    
    // sendEmail($data['email'], 'Confirmación de contacto - Yohualli', $userConfirmation);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Mensaje enviado correctamente. Te contactaremos pronto.',
        'contact_id' => $contactId
    ]);
    
} catch (Exception $e) {
    error_log("Error en contact-handler: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor. Intenta nuevamente.'
    ]);
}
?>