<?php
// config.php - Configuración de la base de datos y constantes del sistema

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'yohualli_3d');

// Configuración del sitio
define('SITE_URL', 'http://localhost/yohualli');
define('SITE_NAME', 'Yohualli Impresiones 3D');
define('SITE_EMAIL', 'info@yohualli.com');

// Configuración de archivos
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['stl', 'obj', '3mf', 'ply']);

// Configuración de seguridad
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);

// Configuración de email (para envío de notificaciones)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Timezone
date_default_timezone_set('America/Mexico_City');

// Clase para manejo de base de datos
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Método para ejecutar consultas preparadas
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error en consulta SQL: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Obtener un solo registro
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Obtener múltiples registros
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Obtener el último ID insertado
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Iniciar transacción
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    // Confirmar transacción
    public function commit() {
        return $this->connection->commit();
    }
    
    // Revertir transacción
    public function rollback() {
        return $this->connection->rollback();
    }
}

// Funciones de utilidad
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function showMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function formatPrice($price) {
    return '$' . number_format($price, 2, '.', ',');
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function logActivity($userId, $action, $table = null, $recordId = null, $oldData = null, $newData = null) {
    $db = Database::getInstance();
    
    $sql = "INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip_cliente, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $userId,
        $action,
        $table,
        $recordId,
        $oldData ? json_encode($oldData) : null,
        $newData ? json_encode($newData) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    try {
        $db->query($sql, $params);
    } catch(Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

// Función para enviar emails (requiere configurar SMTP)
function sendEmail($to, $subject, $body, $isHTML = true) {
    // Aquí puedes implementar el envío de emails
    // usando PHPMailer o la función mail() de PHP
    return true; // Placeholder
}

// Validaciones
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH;
}

function validateFile($file) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error al subir el archivo';
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'El archivo es demasiado grande';
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Tipo de archivo no permitido';
    }
    
    return $errors;
}

// Configurar reporte de errores según el entorno
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    // Desarrollo
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    // Producción
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', 'logs/php_errors.log');
}
?>
