<?php
require_once 'config.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Obtener datos del usuario
$user = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$userId]);

// Obtener estadísticas del usuario
$stats = [
    'cotizaciones' => $db->fetch("SELECT COUNT(*) as total FROM cotizaciones WHERE usuario_id = ?", [$userId])['total'],
    'pedidos' => $db->fetch("SELECT COUNT(*) as total FROM pedidos WHERE usuario_id = ?", [$userId])['total'],
    'total_gastado' => $db->fetch("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE usuario_id = ? AND estado_pago = 'pagado'", [$userId])['total']
];

// Obtener cotizaciones recientes
$cotizaciones = $db->fetchAll(
    "SELECT * FROM cotizaciones WHERE usuario_id = ? ORDER BY fecha_solicitud DESC LIMIT 5", 
    [$userId]
);

// Obtener pedidos recientes
$pedidos = $db->fetchAll(
    "SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha_pedido DESC LIMIT 5", 
    [$userId]
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - Yohualli</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: var(--primary-black);
            padding-top: 120px;
        }
        
        .dashboard-header {
            background: var(--secondary-black);
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            border-bottom: 2px solid var(--accent-gray);
        }
        
        .dashboard-header h1 {
            color: var(--white);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .dashboard-header p {
            color: var(--text-gray);
            font-size: 1.1rem;
        }
        
        .dashboard-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
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
            margin-bottom: 1.5rem;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--white);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-gray);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .section-card {
            background: var(--secondary-black);
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            overflow: hidden;
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
        
        .section-link {
            color: var(--neon-blue);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .section-link:hover {
            color: var(--white);
        }
        
        .section-content {
            padding: 2rem;
        }
        
        .item-list {
            list-style: none;
        }
        
        .item-list li {
            padding: 1rem 0;
            border-bottom: 1px solid var(--accent-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item-list li:last-child {
            border-bottom: none;
        }
        
        .item-info h4 {
            color: var(--white);
            margin-bottom: 0.3rem;
            font-size: 1rem;
        }
        
        .item-info p {
            color: var(--text-gray);
            font-size: 0.85rem;
            margin: 0;
        }
        
        .item-status {
            padding: 0.3rem 1rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pendiente {
            background: rgba(255, 170, 0, 0.2);
            color: #ffaa00;
            border: 1px solid rgba(255, 170, 0, 0.3);
        }
        
        .status-en-revision,
        .status-en-produccion {
            background: rgba(0, 212, 255, 0.2);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        
        .status-cotizada,
        .status-pagado {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .status-entregado {
            background: rgba(139, 92, 246, 0.2);
            color: var(--accent-purple);
            border: 1px solid rgba(139, 92, 246, 0.3);
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
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .action-btn {
            background: var(--gradient-1);
            color: var(--white);
            padding: 1.5rem;
            border-radius: 15px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            border-color: var(--neon-blue);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .action-btn i {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .action-btn span {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        @media (max-width: 1200px) {
            .dashboard-sections {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
            
            .section-header {
                padding: 1.5rem;
            }
            
            .section-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navegación -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo" onclick="window.location.href='index.html'">YOHUALLI</div>
            <ul class="nav-links">
                <li><a href="index.html">Inicio</a></li>
                <li><a href="productos.html">Productos</a></li>
                <li><a href="personalizado.html">Personalizado</a></li>
                <li><a href="servicios.html">Servicios</a></li>
                <li><a href="contacto.html">Contacto</a></li>
                <li><a href="dashboard.php" class="active">Mi Panel</a></li>
                <li><a href="logout.php">Salir</a></li>
            </ul>
            <div class="nav-actions">
                <div class="cart-icon" onclick="toggleCart()">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </div>
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div style="max-width: 1400px; margin: 0 auto; padding: 0 2rem;">
                <h1>Bienvenido, <?php echo htmlspecialchars($user['nombre']); ?>!</h1>
                <p>Gestiona tus pedidos, cotizaciones y perfil desde tu panel personal</p>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 2rem;">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['cotizaciones']; ?></div>
                    <div class="stat-label">Cotizaciones</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['pedidos']; ?></div>
                    <div class="stat-label">Pedidos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value"><?php echo formatPrice($stats['total_gastado']); ?></div>
                    <div class="stat-label">Total Gastado</div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="quick-actions">
                <a href="personalizado.html" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Nueva Cotización</span>
                </a>
                
                <a href="productos.html" class="action-btn" style="background: var(--gradient-2);">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Ver Productos</span>
                </a>
                
                <a href="perfil.php" class="action-btn" style="background: var(--gradient-3);">
                    <i class="fas fa-user-edit"></i>
                    <span>Editar Perfil</span>
                </a>
                
                <a href="contacto.html" class="action-btn" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                    <i class="fas fa-headset"></i>
                    <span>Soporte</span>
                </a>
            </div>

            <!-- Secciones principales -->
            <div class="dashboard-sections">
                <!-- Cotizaciones recientes -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Cotizaciones Recientes</h3>
                        <a href="mis-cotizaciones.php" class="section-link">Ver todas</a>
                    </div>
                    <div class="section-content">
                        <?php if (empty($cotizaciones)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calculator"></i>
                                <p>No tienes cotizaciones aún</p>
                                <a href="personalizado.html" style="color: var(--neon-blue); text-decoration: none;">Solicitar primera cotización</a>
                            </div>
                        <?php else: ?>
                            <ul class="item-list">
                                <?php foreach ($cotizaciones as $cotizacion): ?>
                                    <li>
                                        <div class="item-info">
                                            <h4>Cotización #<?php echo $cotizacion['codigo_cotizacion']; ?></h4>
                                            <p><?php echo formatDate($cotizacion['fecha_solicitud']); ?></p>
                                        </div>
                                        <span class="item-status status-<?php echo $cotizacion['estado']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $cotizacion['estado'])); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pedidos recientes -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Pedidos Recientes</h3>
                        <a href="mis-pedidos.php" class="section-link">Ver todos</a>
                    </div>
                    <div class="section-content">
                        <?php if (empty($pedidos)): ?>
                            <div class="empty-state">
                                <i class="fas fa-box"></i>
                                <p>No tienes pedidos aún</p>
                                <a href="productos.html" style="color: var(--neon-blue); text-decoration: none;">Explorar productos</a>
                            </div>
                        <?php else: ?>
                            <ul class="item-list">
                                <?php foreach ($pedidos as $pedido): ?>
                                    <li>
                                        <div class="item-info">
                                            <h4>Pedido #<?php echo $pedido['numero_pedido']; ?></h4>
                                            <p><?php echo formatDate($pedido['fecha_pedido']); ?> - <?php echo formatPrice($pedido['total']); ?></p>
                                        </div>
                                        <span class="item-status status-<?php echo $pedido['estado']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir carrito y scripts -->
    <div class="cart-overlay" onclick="toggleCart()"></div>
    <div class="cart-modal" id="cartModal">
        <div class="cart-header">
            <h2>Carrito de Compras</h2>
            <button class="close-cart" onclick="toggleCart()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="cartItems">
            <div style="text-align: center; color: var(--text-gray); padding: 2rem;">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>Tu carrito está vacío</p>
            </div>
        </div>
        <div class="cart-total">
            <div class="total-amount">Total: $<span id="totalAmount">0</span></div>
            <button class="checkout-btn" onclick="checkout()">Proceder al Pago</button>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>