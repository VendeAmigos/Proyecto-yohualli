<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Procesar acciones
$action = $_GET['action'] ?? 'list';
$pedidoId = $_GET['id'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_status' && $pedidoId) {
        $estado = sanitize($_POST['estado']);
        $estado_pago = sanitize($_POST['estado_pago']);
        $fecha_entrega = $_POST['fecha_entrega'] ? sanitize($_POST['fecha_entrega']) : null;
        $notas_admin = sanitize($_POST['notas_admin']);
        
        try {
            $sql = "UPDATE pedidos SET estado = ?, estado_pago = ?, fecha_entrega_estimada = ?, notas_admin = ? WHERE id = ?";
            $db->query($sql, [$estado, $estado_pago, $fecha_entrega, $notas_admin, $pedidoId]);
            
            logActivity($_SESSION['user_id'], 'actualizar_pedido', 'pedidos', $pedidoId);
            showMessage('Pedido actualizado correctamente', 'success');
            header('Location: pedidos.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al actualizar pedido: ' . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && $pedidoId) {
        try {
            $db->query("DELETE FROM pedidos WHERE id = ?", [$pedidoId]);
            logActivity($_SESSION['user_id'], 'eliminar_pedido', 'pedidos', $pedidoId);
            showMessage('Pedido eliminado correctamente', 'success');
            header('Location: pedidos.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al eliminar pedido: ' . $e->getMessage();
        }
    }
}

// Filtros
$status = $_GET['status'] ?? '';
$estado_pago = $_GET['estado_pago'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = 'WHERE 1=1';
$params = [];

if ($status) {
    $whereClause .= " AND p.estado = ?";
    $params[] = $status;
}

if ($estado_pago) {
    $whereClause .= " AND p.estado_pago = ?";
    $params[] = $estado_pago;
}

if ($search) {
    $whereClause .= " AND (p.numero_pedido LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Obtener pedidos con datos del usuario
$pedidos = $db->fetchAll("
    SELECT p.*, u.nombre, u.apellidos, u.email, u.telefono 
    FROM pedidos p 
    LEFT JOIN usuarios u ON p.usuario_id = u.id 
    $whereClause 
    ORDER BY p.fecha_pedido DESC 
    LIMIT $limit OFFSET $offset
", $params);

// Contar total para paginación
$totalPedidos = $db->fetch("
    SELECT COUNT(*) as total 
    FROM pedidos p 
    LEFT JOIN usuarios u ON p.usuario_id = u.id 
    $whereClause
", $params)['total'];

$totalPages = ceil($totalPedidos / $limit);

// Estadísticas rápidas
$stats = [
    'pendientes' => $db->fetch("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'")['total'],
    'en_produccion' => $db->fetch("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'en_produccion'")['total'],
    'completados' => $db->fetch("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'completado'")['total'],
    'pagados' => $db->fetch("SELECT COUNT(*) as total FROM pedidos WHERE estado_pago = 'pagado'")['total'],
    'ingresos_mes' => $db->fetch("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE()) AND estado_pago = 'pagado'")['total']
];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Yohualli Admin</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            color: var(--white);
            font-size: 2rem;
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--secondary-black);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid var(--accent-gray);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: var(--neon-blue);
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--neon-blue);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-gray);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .filters-bar {
            background: var(--secondary-black);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid var(--accent-gray);
            margin-bottom: 2rem;
        }
        
        .filters-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto auto;
            gap: 1rem;
            align-items: end;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary { background: var(--gradient-1); color: var(--white); }
        .btn-success { background: #48bb78; color: var(--white); }
        .btn-danger { background: #f56565; color: var(--white); }
        .btn-warning { background: #ffaa00; color: var(--white); }
        .btn-info { background: var(--neon-blue); color: var(--white); }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            color: var(--white);
            margin-bottom: 0.5rem;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            background: var(--accent-gray);
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            color: var(--white);
        }
        
        .pedidos-table {
            background: var(--secondary-black);
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--accent-gray);
        }
        
        .table th {
            background: var(--accent-gray);
            color: var(--white);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .table td {
            color: var(--text-gray);
        }
        
        .table tr:hover {
            background: rgba(0, 212, 255, 0.05);
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
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
        
        .status-pagado {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .status-en-produccion {
            background: rgba(0, 212, 255, 0.2);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        
        .status-completado {
            background: rgba(139, 92, 246, 0.2);
            color: var(--accent-purple);
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        
        .status-cancelado {
            background: rgba(245, 101, 101, 0.2);
            color: #f56565;
            border: 1px solid rgba(245, 101, 101, 0.3);
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 2rem;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--secondary-black);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--accent-gray);
        }
        
        .modal-title {
            color: var(--white);
            font-size: 1.5rem;
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.8rem 1rem;
            background: var(--accent-gray);
            color: var(--text-gray);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover,
        .pagination .active {
            background: var(--gradient-1);
            color: var(--white);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.2);
            border: 1px solid rgba(72, 187, 120, 0.3);
            color: #48bb78;
        }
        
        .alert-error {
            background: rgba(245, 101, 101, 0.2);
            border: 1px solid rgba(245, 101, 101, 0.3);
            color: #f56565;
        }
        
        @media (max-width: 1200px) {
            .admin-sidebar {
                width: 250px;
            }
            .admin-main {
                margin-left: 250px;
            }
            .filters-form {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
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
            .filters-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .form-grid {
                grid-template-columns: 1fr;
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="productos.php"><i class="fas fa-cube"></i> Productos</a></li>
                <li><a href="cotizaciones.php"><i class="fas fa-calculator"></i> Cotizaciones</a></li>
                <li><a href="pedidos.php" class="active"><i class="fas fa-box"></i> Pedidos</a></li>
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
            <div class="page-header">
                <h1 class="page-title">Gestión de Pedidos</h1>
                <div>
                    <span style="color: var(--text-gray);">Total: <?php echo $totalPedidos; ?> pedidos</span>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pendientes']; ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['en_produccion']; ?></div>
                    <div class="stat-label">En Producción</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completados']; ?></div>
                    <div class="stat-label">Completados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pagados']; ?></div>
                    <div class="stat-label">Pagados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatPrice($stats['ingresos_mes']); ?></div>
                    <div class="stat-label">Ingresos del Mes</div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters-bar">
                <form method="GET" class="filters-form">
                    <div class="form-group">
                        <label for="search">Buscar pedidos</label>
                        <input type="text" id="search" name="search" placeholder="Número de pedido, cliente..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Estado del pedido</label>
                        <select id="status" name="status">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?php echo $status === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="pagado" <?php echo $status === 'pagado' ? 'selected' : ''; ?>>Pagado</option>
                            <option value="en_produccion" <?php echo $status === 'en_produccion' ? 'selected' : ''; ?>>En Producción</option>
                            <option value="completado" <?php echo $status === 'completado' ? 'selected' : ''; ?>>Completado</option>
                            <option value="cancelado" <?php echo $status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estado_pago">Estado de pago</label>
                        <select id="estado_pago" name="estado_pago">
                            <option value="">Todos</option>
                            <option value="pendiente" <?php echo $estado_pago === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="pagado" <?php echo $estado_pago === 'pagado' ? 'selected' : ''; ?>>Pagado</option>
                            <option value="cancelado" <?php echo $estado_pago === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <?php if ($search || $status || $estado_pago): ?>
                        <a href="pedidos.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de pedidos -->
            <div class="pedidos-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Estado Pago</th>
                            <th>Fecha Pedido</th>
                            <th>Entrega Est.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--white);">
                                        #<?php echo htmlspecialchars($pedido['numero_pedido']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong style="color: var(--white);">
                                        <?php echo htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellidos']); ?>
                                    </strong>
                                    <br>
                                    <small><?php echo htmlspecialchars($pedido['email']); ?></small>
                                </td>
                                <td>
                                    <strong style="color: var(--neon-blue);">
                                        <?php echo formatPrice($pedido['total']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $pedido['estado']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $pedido['estado_pago']; ?>">
                                        <?php echo ucfirst($pedido['estado_pago']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($pedido['fecha_pedido']); ?></td>
                                <td>
                                    <?php if ($pedido['fecha_entrega_estimada']): ?>
                                        <?php echo formatDate($pedido['fecha_entrega_estimada']); ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-gray);">Sin definir</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="viewPedido(<?php echo $pedido['id']; ?>)" 
                                            class="btn btn-info" style="padding: 0.5rem; margin-right: 0.5rem;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updatePedido(<?php echo $pedido['id']; ?>)" 
                                            class="btn btn-primary" style="padding: 0.5rem; margin-right: 0.5rem;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deletePedido(<?php echo $pedido['id']; ?>)" 
                                            class="btn btn-danger" style="padding: 0.5rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $estado_pago ? '&estado_pago=' . urlencode($estado_pago) : ''; ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal para ver detalles del pedido -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Detalles del Pedido</h2>
                <button type="button" class="close-modal" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="pedidoDetails">
                <!-- Los detalles se cargarán aquí -->
            </div>
        </div>
    </div>

    <!-- Modal para actualizar pedido -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Actualizar Pedido</h2>
                <button type="button" class="close-modal" onclick="closeUpdateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="updateForm">
                <input type="hidden" name="action" value="update_status">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="estado">Estado del pedido</label>
                        <select id="estado" name="estado" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="pagado">Pagado</option>
                            <option value="en_produccion">En Producción</option>
                            <option value="en_post_proceso">En Post-proceso</option>
                            <option value="completado">Completado</option>
                            <option value="entregado">Entregado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estado_pago">Estado de pago</label>
                        <select id="estado_pago" name="estado_pago" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="pagado">Pagado</option>
                            <option value="cancelado">Cancelado</option>
                            <option value="reembolsado">Reembolsado</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fecha_entrega">Fecha de entrega estimada</label>
                    <input type="date" id="fecha_entrega" name="fecha_entrega">
                </div>
                
                <div class="form-group">
                    <label for="notas_admin">Notas administrativas</label>
                    <textarea id="notas_admin" name="notas_admin" rows="4" 
                              placeholder="Notas internas sobre el pedido..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeUpdateModal()" class="btn" style="background: var(--accent-gray);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Actualizar Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewPedido(id) {
            fetch(`get-pedido.php?id=${id}`)
                .then(response => response.json())
                .then(pedido => {
                    const details = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                            <div style="background: var(--accent-gray); padding: 1.5rem; border-radius: 15px;">
                                <h4 style="color: var(--white); margin-bottom: 1rem;">Información del Cliente</h4>
                                <div style="margin-bottom: 0.8rem;">
                                    <span style="color: var(--text-gray); font-weight: bold;">Nombre:</span>
                                    <span style="color: var(--white); margin-left: 0.5rem;">${pedido.nombre} ${pedido.apellidos}</span>
                                </div>
                                <div style="margin-bottom: 0.8rem;">
                                    <span style="color: var(--text-gray); font-weight: bold;">Email:</span>
                                    <span style="color: var(--white); margin-left: 0.5rem;">${pedido.email}</span>
                                </div>
                                <div style="margin-bottom: 0.8rem;">
                                    <span style="color: var(--text-gray); font-weight: bold;">Teléfono:</span>
                                    <span style="color: var(--white); margin-left: 0.5rem;">${pedido.telefono || 'N/A'}</span>
                                </div>
                                <div>
                                    <span style="color: var(--text-gray); font-weight: bold;">Dirección:</span>
                                    <span style="color: var(--white); margin-left: 0.5rem;">${pedido.direccion_envio || 'N/A'}</span>
                                </div>
                            </div>
                            <div style="background: var(--accent-gray); padding: 1.5rem; border-radius: 15px;">
                                <h4 style="color: var(--white); margin-bottom: 1rem;">Información del Pedido</h4>
                                <div style="margin-bottom: 0.8rem;">
                                    <span style="color: var(--text-gray); font-weight: bold;">Número:</span>
                                    <span style="color: var(--white); margin-left: 0.5rem;">#${pedido.numero_pedido}</span>
                                </div>
                                <div style="margin-bottom: 0.8rem;">
                                    <span style="color: var(--text-gray); font-weight: bold;">Estado:</span>
                                    <span class="status-badge status-${pedido.estado}" style="margin-left: 0.5rem;">${pedido.estado.replace('_', ' ')}</span>
                                </div>
                                <div style="margin-bottom: 0.8rem;">
                                    <span style="color: var(--text-gray); font-weight: bold;">Estado Pago:</span>
                                    <span class="status-badge status-${pedido.estado_pago}" style="margin-left: 0.5rem;">${pedido.estado_pago}</span>
                                </div>
                                <div style="margin-bottom: 0.8rem;">
                                    <span style="color: var(--text-gray); font-weight: bold;">Total:</span>
                                    <span style="color: var(--neon-blue); margin-left: 0.5rem; font-weight: bold;">${parseFloat(pedido.total).toLocaleString('es-MX', {minimumFractionDigits: 2})}</span>
                                </div>
                                <div style="margin-bottom: 0.8rem;">
                                    <span style="color: var(--text-gray); font-weight: bold;">Fecha Pedido:</span>
                                    <span style="color: var(--white); margin-left: 0.5rem;">${new Date(pedido.fecha_pedido).toLocaleDateString('es-MX')}</span>
                                </div>
                                <div>
                                    <span style="color: var(--text-gray); font-weight: bold;">Entrega Est.:</span>
                                    <span style="color: var(--white); margin-left: 0.5rem;">${pedido.fecha_entrega_estimada ? new Date(pedido.fecha_entrega_estimada).toLocaleDateString('es-MX') : 'Sin definir'}</span>
                                </div>
                            </div>
                        </div>
                        ${pedido.notas_admin ? `
                        <div style="background: var(--accent-gray); padding: 1.5rem; border-radius: 15px; margin-bottom: 2rem;">
                            <h4 style="color: var(--white); margin-bottom: 1rem;">Notas Administrativas</h4>
                            <p style="color: var(--text-gray); line-height: 1.6; white-space: pre-wrap;">${pedido.notas_admin}</p>
                        </div>
                        ` : ''}
                        <div style="text-align: center; margin-top: 2rem;">
                            <button onclick="updatePedido(${pedido.id}); closeViewModal();" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Actualizar Pedido
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('pedidoDetails').innerHTML = details;
                    document.getElementById('viewModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los detalles del pedido');
                });
        }
        
        function updatePedido(id) {
            fetch(`get-pedido.php?id=${id}`)
                .then(response => response.json())
                .then(pedido => {
                    document.getElementById('updateForm').action = `?action=update_status&id=${id}`;
                    document.getElementById('estado').value = pedido.estado;
                    document.getElementById('estado_pago').value = pedido.estado_pago;
                    document.getElementById('fecha_entrega').value = pedido.fecha_entrega_estimada || '';
                    document.getElementById('notas_admin').value = pedido.notas_admin || '';
                    
                    document.getElementById('updateModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del pedido');
                });
        }
        
        function deletePedido(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este pedido? Esta acción no se puede deshacer.')) {
                window.location.href = `?action=delete&id=${id}`;
            }
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('show');
        }
        
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.remove('show');
        }
        
        // Cerrar modales al hacer clic fuera
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeViewModal();
            }
        });
        
        document.getElementById('updateModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUpdateModal();
            }
        });
    </script>
</body>
</html>