<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Para esta página básica, solo mostraremos una interfaz simple
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Servicios - Yohualli Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background: var(--primary-black);
        }
        
        .admin-sidebar {
            width: 280px;
            background: var(--secondary-black);
            border-right: 2px solid var(--accent-gray);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid var(--accent-gray);
            margin-bottom: 2rem;
        }
        
        .sidebar-logo {
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: var(--text-gray);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: var(--white);
            background: rgba(0, 212, 255, 0.1);
            border-left-color: var(--neon-blue);
        }
        
        .sidebar-menu i {
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }
        
        .admin-main {
            margin-left: 280px;
            flex: 1;
            padding: 2rem;
        }
        
        .page-header {
            background: var(--secondary-black);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .page-title {
            color: var(--white);
            font-size: 2rem;
            margin: 0 0 1rem 0;
        }
        
        .coming-soon {
            background: var(--secondary-black);
            padding: 4rem 2rem;
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            text-align: center;
        }
        
        .coming-soon i {
            font-size: 5rem;
            color: var(--neon-blue);
            margin-bottom: 2rem;
        }
        
        .coming-soon h2 {
            color: var(--white);
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .coming-soon p {
            color: var(--text-gray);
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">YOHUALLI ADMIN</div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="usuarios.