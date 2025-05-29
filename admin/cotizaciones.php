<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Procesar acciones
$action = $_GET['action'] ?? 'list';
$cotizacionId = $_GET['id'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_status' && $cotizacionId) {
        $estado = sanitize($_POST['estado']);
        $precio_cotizado = $_POST['precio_cotizado'] ? (float)$_POST['precio_cotizado'] : null;
        $notas_admin = sanitize($_POST['notas_admin']);
        
        try {
            $sql = "UPDATE cotizaciones SET estado = ?, precio_cotizado = ?, notas_admin = ?, fecha_respuesta = NOW() WHERE id = ?";
            $db->query($sql, [$estado, $precio_cotizado, $notas_admin, $cotizacionId]);
            
            logActivity($_SESSION['user_id'], 'actualizar_cotizacion', 'cotizaciones', $cotizacionId);
            showMessage('Cotización actualizada correctamente', 'success');
            header('Location: cotizaciones.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al actualizar cotización: ' . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && $cotizacionId) {
        try {
            $db->query("DELETE FROM cotizaciones WHERE id = ?", [$cotizacionId]);
            logActivity($_SESSION['user_id'], 'eliminar_cotizacion', 'cotizaciones', $cotizacionId);
            showMessage('Cotización eliminada correctamente', 'success');
            header('Location: cotizaciones.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al eliminar cotización: ' . $e->getMessage();
        }
    }
}

// Filtros
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = 'WHERE 1=1';
$params = [];

if ($status) {
    $whereClause .= " AND c.estado = ?";
    $params[] = $status;
}

if ($search) {
    $whereClause .= " AND (c.codigo_cotizacion LIKE ? OR c.descripcion LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Obtener cotizaciones con datos del usuario
$cotizaciones = $db->fetchAll("
    SELECT c.*, u.nombre, u.apellidos, u.email, u.telefono 
    FROM cotizaciones c 
    LEFT JOIN usuarios u ON c.usuario_id = u.id 
    $whereClause 
    ORDER BY c.fecha_solicitud DESC 
    LIMIT $limit OFFSET $offset
", $params);

// Contar total para paginación
$totalCotizaciones = $db->fetch("
    SELECT COUNT(*) as total 
    FROM cotizaciones c 
    LEFT JOIN usuarios u ON c.usuario_id = u.id 
    $whereClause
", $params)['total'];

$totalPages = ceil($totalCotizaciones / $limit);

// Estadísticas rápidas
$stats = [
    'pendientes' => $db->fetch("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'pendiente'")['total'],
    'en_revision' => $db->fetch("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'en_revision'")['total'],
    'cotizadas' => $db->fetch("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'cotizada'")['total'],
    'rechazadas' => $db->fetch("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'rechazada'")['total']
];

// Si es vista detallada, obtener datos de la cotización
$cotizacionDetalle = null;
if ($action === 'view' && $cotizacionId) {
    $cotizacionDetalle = $db->fetch("
        SELECT c.*, u.nombre, u.apellidos, u.email, u.telefono, u.empresa 
        FROM cotizaciones c 
        LEFT JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.id = ?
    ", [$cotizacionId]);
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cotizaciones - Yohualli Admin</title>
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
        
        .stats-quick {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card-small {
            background: var(--secondary-black);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid var(--accent-gray);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card-small:hover {
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
            grid-template-columns: 2fr 1fr auto auto;
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
        
        .cotizaciones-table {
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
        
        .status-en-revision {
            background: rgba(0, 212, 255, 0.2);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        
        .status-cotizada {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .status-rechazada {
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
        
        .cotizacion-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .detail-section {
            background: var(--accent-gray);
            padding: 1.5rem;
            border-radius: 15px;
        }
        
        .detail-section h4 {
            color: var(--white);
            margin-bottom: 1rem;
            text-transform: uppercase;
            font-size: 1rem;
        }
        
        .detail-item {
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
        }
        
        .detail-label {
            color: var(--text-gray);
            font-weight: bold;
        }
        
        .detail-value {
            color: var(--white);
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
            .stats-quick {
                grid-template-columns: repeat(2, 1fr);
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
            .cotizacion-details {
                grid-template-columns: 1fr;
            }
            .stats-quick {
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
                <li><a href="cotizaciones.php" class="active"><i class="fas fa-calculator"></i> Cotizaciones</a></li>
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
            <div class="page-header">
                <h1 class="page-title">Gestión de Cotizaciones</h1>
                <div>
                    <span style="color: var(--text-gray);">Total: <?php echo $totalCotizaciones; ?> cotizaciones</span>
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

            <!-- Estadísticas rápidas -->
            <div class="stats-quick">
                <div class="stat-card-small">
                    <div class="stat-number"><?php echo $stats['pendientes']; ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-number"><?php echo $stats['en_revision']; ?></div>
                    <div class="stat-label">En Revisión</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-number"><?php echo $stats['cotizadas']; ?></div>
                    <div class="stat-label">Cotizadas</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-number"><?php echo $stats['rechazadas']; ?></div>
                    <div class="stat-label">Rechazadas</div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters-bar">
                <form method="GET" class="filters-form">
                    <div class="form-group">
                        <label for="search">Buscar cotizaciones</label>
                        <input type="text" id="search" name="search" placeholder="Código, descripción, cliente..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?php echo $status === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="en_revision" <?php echo $status === 'en_revision' ? 'selected' : ''; ?>>En Revisión</option>
                            <option value="cotizada" <?php echo $status === 'cotizada' ? 'selected' : ''; ?>>Cotizada</option>
                            <option value="rechazada" <?php echo $status === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <?php if ($search || $status): ?>
                        <a href="cotizaciones.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de cotizaciones -->
            <div class="cotizaciones-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Precio Cotizado</th>
                            <th>Fecha Solicitud</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotizaciones as $cotizacion): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--white);">
                                        #<?php echo htmlspecialchars($cotizacion['codigo_cotizacion']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong style="color: var(--white);">
                                        <?php echo htmlspecialchars($cotizacion['nombre'] . ' ' . $cotizacion['apellidos']); ?>
                                    </strong>
                                    <br>
                                    <small><?php echo htmlspecialchars($cotizacion['email']); ?></small>
                                </td>
                                <td><?php echo ucfirst(htmlspecialchars($cotizacion['tipo'])); ?></td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($cotizacion['descripcion'], 0, 100)); ?>
                                        <?php if (strlen($cotizacion['descripcion']) > 100): ?>...<?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $cotizacion['estado']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $cotizacion['estado'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($cotizacion['precio_cotizado']): ?>
                                        <strong style="color: var(--neon-blue);">
                                            <?php echo formatPrice($cotizacion['precio_cotizado']); ?>
                                        </strong>
                                    <?php else: ?>
                                        <span style="color: var(--text-gray);">Sin cotizar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($cotizacion['fecha_solicitud']); ?></td>
                                <td>
                                    <button onclick="viewCotizacion(<?php echo $cotizacion['id']; ?>)" 
                                            class="btn btn-info" style="padding: 0.5rem; margin-right: 0.5rem;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updateStatus(<?php echo $cotizacion['id']; ?>)" 
                                            class="btn btn-primary" style="padding: 0.5rem; margin-right: 0.5rem;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteCotizacion(<?php echo $cotizacion['id']; ?>)" 
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
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal para ver detalles -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Detalles de la Cotización</h2>
                <button type="button" class="close-modal" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="cotizacionDetails">
                <!-- Los detalles se cargarán aquí -->
            </div>
        </div>
    </div>

    <!-- Modal para actualizar estado -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Actualizar Cotización</h2>
                <button type="button" class="close-modal" onclick="closeUpdateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="updateForm">
                <input type="hidden" name="action" value="update_status">
                
                <div class="form-group">
                    <label for="estado">Estado de la cotización</label>
                    <select id="estado" name="estado" required>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_revision">En Revisión</option>
                        <option value="cotizada">Cotizada</option>
                        <option value="rechazada">Rechazada</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="precio_cotizado">Precio cotizado (MXN)</label>
                    <input type="number" id="precio_cotizado" name="precio_cotizado" step="0.01" min="0" 
                           placeholder="Dejar vacío si no aplica">
                </div>
                
                <div class="form-group">
                    <label for="notas_admin">Notas internas</label>
                    <textarea id="notas_admin" name="notas_admin" rows="4" 
                              placeholder="Notas para el equipo administrativo..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeUpdateModal()" class="btn" style="background: var(--accent-gray);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Actualizar Cotización
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewCotizacion(id) {
            fetch(`get-cotizacion.php?id=${id}`)
                .then(response => response.json())
                .then(cotizacion => {
                    const details = `
                        <div class="cotizacion-details">
                            <div class="detail-section">
                                <h4>Información del Cliente</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Nombre:</span>
                                    <span class="detail-value">${cotizacion.nombre} ${cotizacion.apellidos}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value">${cotizacion.email}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Teléfono:</span>
                                    <span class="detail-value">${cotizacion.telefono || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Empresa:</span>
                                    <span class="detail-value">${cotizacion.empresa || 'N/A'}</span>