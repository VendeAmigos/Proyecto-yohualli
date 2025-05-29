<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Obtener datos para reportes básicos
$reportData = [
    'ventas_mes_actual' => $db->fetch("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE()) AND estado_pago = 'pagado'")['total'],
    'ventas_mes_anterior' => $db->fetch("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH) AND estado_pago = 'pagado'")['total'],
    'pedidos_mes' => $db->fetch("SELECT COUNT(*) as total FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE())")['total'],
    'usuarios_nuevos_mes' => $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())")['total'],
    'cotizaciones_mes' => $db->fetch("SELECT COUNT(*) as total FROM cotizaciones WHERE MONTH(fecha_solicitud) = MONTH(CURRENT_DATE()) AND YEAR(fecha_solicitud) = YEAR(CURRENT_DATE())")['total']
];

// Calcular crecimiento
$crecimiento_ventas = 0;
if ($reportData['ventas_mes_anterior'] > 0) {
    $crecimiento_ventas = (($reportData['ventas_mes_actual'] - $reportData['ventas_mes_anterior']) / $reportData['ventas_mes_anterior']) * 100;
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Yohualli Admin</title>
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
        }
        
        .page-title {
            color: var(--white);
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .report-card {
            background: var(--secondary-black);
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .report-card:hover {
            border-color: var(--neon-blue);
            transform: translateY(-2px);
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .report-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
        }
        
        .report-title {
            color: var(--white);
            font-size: 1.3rem;
            font-weight: bold;
            margin: 0;
        }
        
        .report-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--neon-blue);
            margin-bottom: 0.5rem;
        }
        
        .report-description {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .report-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .trend-positive {
            color: #48bb78;
        }
        
        .trend-negative {
            color: #f56565;
        }
        
        .quick-reports {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .quick-report {
            background: var(--secondary-black);
            border-radius: 15px;
            border: 1px solid var(--accent-gray);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .quick-report:hover {
            border-color: var(--neon-blue);
            transform: translateY(-2px);
        }
        
        .quick-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--neon-blue);
            margin-bottom: 0.5rem;
        }
        
        .quick-label {
            color: var(--text-gray);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .coming-soon {
            background: var(--secondary-black);
            padding: 3rem 2rem;
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            text-align: center;
            margin-top: 2rem;
        }
        
        .coming-soon i {
            font-size: 3rem;
            color: var(--neon-blue);
            margin-bottom: 1rem;
        }
        
        .coming-soon h3 {
            color: var(--white);
            margin-bottom: 1rem;
        }
        
        .coming-soon p {
            color: var(--text-gray);
            max-width: 500px;
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
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="productos.php"><i class="fas fa-cube"></i> Productos</a></li>
                <li><a href="cotizaciones.php"><i class="fas fa-calculator"></i> Cotizaciones</a></li>
                <li><a href="pedidos.php"><i class="fas fa-box"></i> Pedidos</a></li>
                <li><a href="contactos.php"><i class="fas fa-envelope"></i> Contactos</a></li>
                <li><a href="materiales.php"><i class="fas fa-industry"></i> Materiales</a></li>
                <li><a href="servicios.php"><i class="fas fa-cogs"></i> Servicios</a></li>
                <li><a href="reportes.php" class="active"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                <li><a href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="admin-main">
            <div class="page-header">
                <h1 class="page-title">Reportes y Analytics</h1>
                <p style="color: var(--text-gray);">Métricas y estadísticas del negocio</p>
            </div>

            <!-- Reportes rápidos -->
            <div class="quick-reports">
                <div class="quick-report">
                    <div class="quick-number"><?php echo formatPrice($reportData['ventas_mes_actual']); ?></div>
                    <div class="quick-label">Ventas Este Mes</div>
                </div>
                <div class="quick-report">
                    <div class="quick-number"><?php echo $reportData['pedidos_mes']; ?></div>
                    <div class="quick-label">Pedidos Este Mes</div>
                </div>
                <div class="quick-report">
                    <div class="quick-number"><?php echo $reportData['usuarios_nuevos_mes']; ?></div>
                    <div class="quick-label">Usuarios Nuevos</div>
                </div>
                <div class="quick-report">
                    <div class="quick-number"><?php echo $reportData['cotizaciones_mes']; ?></div>
                    <div class="quick-label">Cotizaciones</div>
                </div>
            </div>

            <!-- Reportes principales -->
            <div class="reports-grid">
                <div class="report-card">
                    <div class="report-header">
                        <h3 class="report-title">Ventas Mensuales</h3>
                        <div class="report-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="report-value"><?php echo formatPrice($reportData['ventas_mes_actual']); ?></div>
                    <div class="report-description">Ingresos generados este mes</div>
                    <div class="report-trend <?php echo $crecimiento_ventas >= 0 ? 'trend-positive' : 'trend-negative'; ?>">
                        <i class="fas fa-arrow-<?php echo $crecimiento_ventas >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs(round($crecimiento_ventas, 1)); ?>% vs mes anterior
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-header">
                        <h3 class="report-title">Rendimiento de Pedidos</h3>
                        <div class="report-icon">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <div class="report-value"><?php echo $reportData['pedidos_mes']; ?></div>
                    <div class="report-description">Pedidos procesados este mes</div>
                    <div class="report-trend trend-positive">
                        <i class="fas fa-info-circle"></i>
                        Promedio mensual
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-header">
                        <h3 class="report-title">Crecimiento de Usuarios</h3>
                        <div class="report-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="report-value"><?php echo $reportData['usuarios_nuevos_mes']; ?></div>
                    <div class="report-description">Nuevos registros este mes</div>
                    <div class="report-trend trend-positive">
                        <i class="fas fa-user-plus"></i>
                        Usuarios activos
                    </div>
                </div>
            </div>

            <!-- Próximamente -->
            <div class="coming-soon">
                <i class="fas fa-chart-line"></i>
                <h3>Reportes Avanzados Próximamente</h3>
                <p>
                    Próximamente tendrás acceso a reportes más detallados incluyendo gráficos interactivos, 
                    análisis de tendencias, reportes de productos más vendidos, análisis de satisfacción del cliente y mucho más.
                </p>
            </div>
        </main>
    </div>
</body>
</html>