<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Obtener estadísticas generales
$stats = [
    'usuarios_total' => $db->fetch("SELECT COUNT(*) as total FROM usuarios")['total'],
    'usuarios_nuevos_mes' => $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())")['total'],
    'cotizaciones_pendientes' => $db->fetch("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'pendiente'")['total'],
    'pedidos_activos' => $db->fetch("SELECT COUNT(*) as total FROM pedidos WHERE estado IN ('pagado', 'en_produccion', 'en_post_proceso')")['total'],
    'ingresos_mes' => $db->fetch("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE()) AND estado_pago = 'pagado'")['total'],
    'contactos_nuevos' => $db->fetch("SELECT COUNT(*) as total FROM contactos WHERE estado = 'nuevo'")['total']
];

// Obtener actividad reciente
$actividad_reciente = $db->fetchAll(
    "SELECT l.*, u.nombre, u.apellidos 
     FROM logs_sistema l 
     LEFT JOIN usuarios u ON l.usuario_id = u.id 
     ORDER BY l.fecha_log DESC 
     LIMIT 10"
);

// Obtener pedidos recientes
$pedidos_recientes = $db->fetchAll(
    "SELECT p.*, u.nombre, u.apellidos 
     FROM pedidos p 
     LEFT JOIN usuarios u ON p.usuario_id = u.id 
     ORDER BY p.fecha_pedido DESC 
     LIMIT 5"
);

// Obtener cotizaciones pendientes
$cotizaciones_pendientes = $db->fetchAll(
    "SELECT c.*, u.nombre, u.apellidos 
     FROM cotizaciones c 
     LEFT JOIN usuarios u ON c.usuario_id = u.id 
     WHERE c.estado = 'pendiente'
     ORDER BY c.fecha_solicitud ASC 
     LIMIT 5"
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Yohualli</title>
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
        
        .admin-main {
            margin-left: 280px;
            flex: 1;
            padding: 2rem;
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
        
        .admin-header {
            background: var(--secondary-black);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            margin-bottom: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title {
            color: var(--white);
            font-size: 2rem;
            margin: 0;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-gray);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: var(--secondary-black);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-1);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .stat-card:hover::before {
            opacity: 0.1;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--neon-blue);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.2);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--white);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-change {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }
        
        .change-positive {
            color: #48bb78;
            background: rgba(72, 187, 120, 0.2);
        }
        
        .change-negative {
            color: #f56565;
            background: rgba(245, 101, 101, 0.2);
        }
        
        .admin-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }
        
        .section-card {
            background: var(--secondary-black);
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .section-header {
            padding: 2rem;
            border-bottom: 1px solid var(--accent-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            color: var(--white);
            font-size: 1.3rem;
            font-weight: bold;
            margin: 0;
        }
        
        .section-action {
            color: var(--neon-blue);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .section-action:hover {
            color: var(--white);
        }
        
        .section-content {
            padding: 0;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem 2rem;
            text-align: left;
            border-bottom: 1px solid var(--accent-gray);
        }
        
        .data-table th {
            background: var(--accent-gray);
            color: var(--white);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
        
        .data-table td {
            color: var(--text-gray);
        }
        
        .data-table tr:hover {
            background: rgba(0, 212, 255, 0.05);
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pendiente {
            background: rgba(255, 170, 0, 0.2);
            color: #ffaa00;
            border: 1px solid rgba(255, 170, 0, 0.3);
        }
        
        .status-en-revision,
        .status-pagado {
            background: rgba(0, 212, 255, 0.2);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        
        .status-completado {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .activity-item {
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--accent-gray);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--white);
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            color: var(--white);
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .activity-time {
            color: var(--text-gray);
            font-size: 0.8rem;
        }
        
        .empty-state {
            text-align: center;
            color: var(--text-gray);
            padding: 3rem 2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .action-btn {
            background: var(--gradient-1);
            color: var(--white);
            padding: 1rem;
            border-radius: 15px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            border-color: var(--neon-blue);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        @media (max-width: 1200px) {
            .admin-sidebar {
                width: 250px;
            }
            
            .admin-main {
                margin-left: 250px;
            }
            
            .admin-sections {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
        
        @media (max-width: 968px) {
            .admin-layout {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 2px solid var(--accent-gray);
            }
            
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.8rem 1rem;
                font-size: 0.8rem;
            }
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="productos.php"><i class="fas fa-cube"></i> Productos</a></li>
                <li><a href="cotizaciones.php"><i class="fas fa-calculator"></i> Cotizaciones</a></li>
                <li><a href="pedidos.php"><i class="fas fa-box"></i> Pedidos</a></li>
                <li><a href="contactos.php"><i class="fas fa-envelope"></i> Contactos</a></li>
                <li><a href="materiales.php"><i class="fas fa-industry"></i> Materiales</a></li>
                <li><a href="servicios.php"><i class="fas fa-cogs"></i> Servicios</a></li>
                <li><a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                <li><a href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">Panel de Administración</h1>
                <div class="admin-user">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                    </div>
                    <span>Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 2rem;">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Acciones rápidas -->
            <div class="quick-actions">
                <a href="productos.php?action=new" class="action-btn">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </a>
                <a href="cotizaciones.php?status=pendiente" class="action-btn" style="background: var(--gradient-2);">
                    <i class="fas fa-clock"></i> Cotizaciones Pendientes
                </a>
                <a href="pedidos.php?status=activos" class="action-btn" style="background: var(--gradient-3);">
                    <i class="fas fa-box"></i> Pedidos Activos
                </a>
                <a href="usuarios.php" class="action-btn" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                    <i class="fas fa-users"></i> Gestionar Usuarios
                </a>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="stat-change change-positive">+<?php echo $stats['usuarios_nuevos_mes']; ?> este mes</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['usuarios_total']; ?></div>
                    <div class="stat-label">Total Usuarios</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <span class="stat-change change-positive"><?php echo $stats['cotizaciones_pendientes']; ?> pendientes</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['cotizaciones_pendientes']; ?></div>
                    <div class="stat-label">Cotizaciones</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <span class="stat-change change-positive"><?php echo $stats['pedidos_activos']; ?> activos</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['pedidos_activos']; ?></div>
                    <div class="stat-label">Pedidos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <span class="stat-change change-positive">Este mes</span>
                    </div>
                    <div class="stat-value"><?php echo formatPrice($stats['ingresos_mes']); ?></div>
                    <div class="stat-label">Ingresos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <span class="stat-change change-positive"><?php echo $stats['contactos_nuevos']; ?> nuevos</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['contactos_nuevos']; ?></div>
                    <div class="stat-label">Contactos</div>
                </div>
            </div>

            <!-- Secciones principales -->
            <div class="admin-sections">
                <div class="main-section">
                    <!-- Pedidos recientes -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Pedidos Recientes</h3>
                            <a href="pedidos.php" class="section-action">Ver todos</a>
                        </div>
                        <div class="section-content">
                            <?php if (empty($pedidos_recientes)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-box"></i>
                                    <p>No hay pedidos recientes</p>
                                </div>
                            <?php else: ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Cliente</th>
                                            <th>Total</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pedidos_recientes as $pedido): ?>
                                            <tr>
                                                <td>#<?php echo $pedido['numero_pedido']; ?></td>
                                                <td><?php echo htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellidos']); ?></td>
                                                <td><?php echo formatPrice($pedido['total']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $pedido['estado']; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($pedido['fecha_pedido']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Cotizaciones pendientes -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Cotizaciones Pendientes</h3>
                            <a href="cotizaciones.php?status=pendiente" class="section-action">Ver todas</a>
                        </div>
                        <div class="section-content">
                            <?php if (empty($cotizaciones_pendientes)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calculator"></i>
                                    <p>No hay cotizaciones pendientes</p>
                                </div>
                            <?php else: ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Cliente</th>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cotizaciones_pendientes as $cotizacion): ?>
                                            <tr>
                                                <td>#<?php echo $cotizacion['codigo_cotizacion']; ?></td>
                                                <td><?php echo htmlspecialchars($cotizacion['nombre'] . ' ' . $cotizacion['apellidos']); ?></td>
                                                <td><?php echo ucfirst($cotizacion['tipo']); ?></td>
                                                <td><?php echo formatDate($cotizacion['fecha_solicitud']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="sidebar-section">
                    <!-- Actividad reciente -->
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Actividad Reciente</h3>
                        </div>
                        <div class="section-content">
                            <?php if (empty($actividad_reciente)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <p>No hay actividad reciente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($actividad_reciente as $actividad): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-<?php 
                                                echo match($actividad['accion']) {
                                                    'login_exitoso' => 'sign-in-alt',
                                                    'logout' => 'sign-out-alt',
                                                    'registro_usuario' => 'user-plus',
                                                    default => 'circle'
                                                };
                                            ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                <?php if ($actividad['nombre']): ?>
                                                    <?php echo htmlspecialchars($actividad['nombre'] . ' ' . $actividad['apellidos']); ?>
                                                <?php else: ?>
                                                    Usuario desconocido
                                                <?php endif; ?>
                                                - <?php echo str_replace('_', ' ', $actividad['accion']); ?>
                                            </div>
                                            <div class="activity-time">
                                                <?php echo formatDate($actividad['fecha_log']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>