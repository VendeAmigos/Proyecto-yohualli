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
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.3);
        }
        
        .admin-main {
            margin-left: 280px;
            flex: 1;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .sidebar-header {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid var(--accent-gray);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .sidebar-logo {
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--gradient-3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--white); /* Fallback */
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
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--gradient-1);
            transition: width 0.3s ease;
            z-index: -1;
            opacity: 0.1;
        }
        
        .sidebar-menu a:hover::before,
        .sidebar-menu a.active::before {
            width: 100%;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: var(--white);
            border-left-color: var(--neon-blue);
            transform: translateX(5px);
        }
        
        .sidebar-menu i {
            margin-right: 1rem;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .admin-header {
            background: var(--secondary-black);
            padding: 2rem;
            border-radius: 25px;
            border: 1px solid var(--accent-gray);
            margin-bottom: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .admin-title {
            color: var(--white);
            font-size: 2rem;
            margin: 0;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 900;
            position: relative;
            z-index: 1;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-gray);
            position: relative;
            z-index: 1;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);
            border: 2px solid var(--white);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .stat-card {
            background: var(--secondary-black);
            padding: 2.5rem;
            border-radius: 25px;
            border: 1px solid var(--accent-gray);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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
            transition: opacity 0.4s ease;
            z-index: -1;
        }
        
        .stat-card:hover::before {
            opacity: 0.1;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            border-color: var(--neon-blue);
            box-shadow: 0 20px 40px rgba(0, 212, 255, 0.3);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--white);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);
        }
        
        .stat-value {
            font-size: 2.8rem;
            font-weight: bold;
            color: var(--white);
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
        }
        
        .stat-label {
            color: var(--text-gray);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .stat-change {
            font-size: 0.85rem;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-weight: bold;
        }
        
        .change-positive {
            color: #48bb78;
            background: rgba(72, 187, 120, 0.2);
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .change-negative {
            color: #f56565;
            background: rgba(245, 101, 101, 0.2);
            border: 1px solid rgba(245, 101, 101, 0.3);
        }
        
        .admin-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }
        
        .section-card {
            background: var(--secondary-black);
            border-radius: 25px;
            border: 1px solid var(--accent-gray);
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 212, 255, 0.2);
        }
        
        .section-header {
            padding: 2rem;
            border-bottom: 1px solid var(--accent-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--accent-gray);
        }
        
        .section-title {
            color: var(--white);
            font-size: 1.3rem;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .section-action {
            color: var(--neon-blue);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            padding: 1.2rem 2rem;
            text-align: left;
            border-bottom: 1px solid var(--accent-gray);
        }
        
        .data-table th {
            background: var(--accent-gray);
            color: var(--white);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
        
        .data-table td {
            color: var(--text-gray);
        }
        
        .data-table tr:hover {
            background: rgba(0, 212, 255, 0.05);
        }
        
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--accent-gray);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background: rgba(0, 212, 255, 0.05);
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            background: var(--gradient-3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--white);
            flex-shrink: 0;
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.3);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            color: var(--white);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .activity-time {
            color: var(--text-gray);
            font-size: 0.8rem;
        }
        
        .empty-state {
            text-align: center;
            color: var(--text-gray);
            padding: 4rem 2rem;
        }
        
        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 4rem;
        }
        
        .action-btn {
            background: var(--gradient-1);
            color: var(--white);
            padding: 1.2rem;
            border-radius: 20px;
            text-decoration: none;
            text-align: center;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-2);
            transition: left 0.4s ease;
            z-index: -1;
        }
        
        .action-btn:hover::before {
            left: 0;
        }
        
        .action-btn:hover {
            transform: translateY(-5px);
            border-color: var(--neon-blue);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .action-btn i {
            font-size: 1.8rem;
        }
        
        /* Alertas mejoradas */
        .alert {
            padding: 1.2rem 1.8rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            font-weight: 500;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.1);
            border-color: rgba(72, 187, 120, 0.3);
            color: #68d391;
        }
        
        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            border-color: rgba(255, 68, 68, 0.3);
            color: #ff6b6b;
        }
        
        .alert-info {
            background: rgba(0, 212, 255, 0.1);
            border-color: rgba(0, 212, 255, 0.3);
            color: var(--neon-blue);
        }
        
        /* Responsive Design */
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
                padding: 1rem 0;
            }
            
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 1rem;
            }
            
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                padding: 0 1rem;
            }
            
            .sidebar-menu li {
                margin-bottom: 0;
            }
            
            .sidebar-menu a {
                padding: 0.8rem 1rem;
                border-radius: 10px;
                border-left: none;
                border: 1px solid var(--accent-gray);
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
                font-size: 0.85rem;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.5rem;
            }
            
            .admin-title {
                font-size: 1.5rem;
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
                    <div>
                        <div style="color: var(--white); font-weight: bold;">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                        <div style="font-size: 0.85rem;">Administrador</div>
                    </div>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Acciones rápidas -->
            <div class="quick-actions">
                <a href="productos.php?action=new" class="action-btn">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Producto</span>
                </a>
                <a href="cotizaciones.php?status=pendiente" class="action-btn" style="background: var(--gradient-2);">
                    <i class="fas fa-clock"></i>
                    <span>Cotizaciones Pendientes</span>
                </a>
                <a href="pedidos.php?status=activos" class="action-btn" style="background: var(--gradient-3);">
                    <i class="fas fa-box"></i>
                    <span>Pedidos Activos</span>
                </a>
                <a href="usuarios.php" class="action-btn" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                    <i class="fas fa-users"></i>
                    <span>Gestionar Usuarios</span>
                </a>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
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
</html>>
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
                        </div