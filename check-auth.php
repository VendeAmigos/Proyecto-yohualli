<?php
require_once 'config.php';

header('Content-Type: application/json');

echo json_encode([
    'isLoggedIn' => isLoggedIn(),
    'isAdmin' => isAdmin(),
    'userId' => $_SESSION['user_id'] ?? null,
    'userName' => $_SESSION['user_name'] ?? null,
    'userRole' => $_SESSION['user_role'] ?? null
]);
?>