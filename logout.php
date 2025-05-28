<?php
require_once 'config.php';

// Log del logout si hay usuario logueado
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'logout');
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Eliminar cookie de "recordarme" si existe
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirigir a la página de login con mensaje
showMessage('Has cerrado sesión correctamente', 'success');
redirectTo('login.php');
?>