<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Procesar acciones
$action = $_GET['action'] ?? 'list';
$contactoId = $_GET['id'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_status' && $contactoId) {
        $estado = sanitize($_POST['estado']);
        $notas_admin = sanitize($_POST['notas_admin']);
        
        try {
            $sql = "UPDATE contactos SET estado = ?, notas_admin = ?, fecha_respuesta = NOW() WHERE id = ?";
            $db->query($sql, [$estado, $notas_admin, $contactoId]);
            
            logActivity($_SESSION['user_id'], 'actualizar_contacto', 'contactos', $contactoId);
            showMessage('Contacto actualizado correctamente', 'success');
            header('Location: contactos.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al actualizar contacto: ' . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && $contactoId) {
        try {
            $db->query("DELETE FROM contactos WHERE id = ?", [$contactoId]);
            logActivity($_SESSION['user_id'], 'eliminar_contacto', 'contactos', $contactoId);
            showMessage('Contacto eliminado correctamente', 'success');
            header('Location: contactos.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al eliminar contacto: ' . $e->getMessage();
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
    $whereClause .= " AND estado = ?";
    $params[] = $status;
}

if ($search) {
    $whereClause .= " AND (nombre LIKE ? OR email LIKE ? OR asunto LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Obtener contactos
$contactos = $db->fetchAll("
    SELECT * FROM contactos 
    $whereClause 
    ORDER BY fecha_mensaje DESC 
    LIMIT $limit OFFSET $offset
", $params);

// Contar total para paginación
$totalContactos = $db->fetch("
    SELECT COUNT(*) as total FROM contactos $whereClause
", $params)['total'];

$totalPages = ceil($totalContactos / $limit);

// Estadísticas rápidas
$stats = [
    'nuevos' => $db->fetch("SELECT COUNT(*) as total FROM contactos WHERE estado = 'nuevo'")['total'],
    'en_proceso' => $db->fetch("SELECT COUNT(*) as total FROM contactos WHERE estado = 'en_proceso'")['total'],
    'resueltos' => $db->fetch("SELECT COUNT(*) as total FROM contactos WHERE estado = 'resuelto'")['total']
];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Contactos - Yohualli Admin</title>
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
        
        .contactos-table {
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
        
        .status-nuevo {
            background: rgba(255, 170, 0, 0.2);
            color: #ffaa00;
            border: 1px solid rgba(255, 170, 0, 0.3);
        }
        
        .status-en-proceso {
            background: rgba(0, 212, 255, 0.2);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        
        .status-resuelto {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
            border: 1px solid rgba(72, 187, 120, 0.3);
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
        
        .contact-details {
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
        
        .message-content {
            background: var(--accent-gray);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .message-content h4 {
            color: var(--white);
            margin-bottom: 1rem;
        }
        
        .message-content p {
            color: var(--text-gray);
            line-height: 1.6;
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
            .contact-details {
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
                <li><a href="pedidos.php"><i class="fas fa-box"></i> Pedidos</a></li>
                <li><a href="contactos.php" class="active"><i class="fas fa-envelope"></i> Contactos</a></li>
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
                <h1 class="page-title">Gestión de Contactos</h1>
                <div>
                    <span style="color: var(--text-gray);">Total: <?php echo $totalContactos; ?> mensajes</span>
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
                    <div class="stat-number"><?php echo $stats['nuevos']; ?></div>
                    <div class="stat-label">Nuevos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['en_proceso']; ?></div>
                    <div class="stat-label">En Proceso</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['resueltos']; ?></div>
                    <div class="stat-label">Resueltos</div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters-bar">
                <form method="GET" class="filters-form">
                    <div class="form-group">
                        <label for="search">Buscar contactos</label>
                        <input type="text" id="search" name="search" placeholder="Nombre, email o asunto..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="">Todos los estados</option>
                            <option value="nuevo" <?php echo $status === 'nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                            <option value="en_proceso" <?php echo $status === 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="resuelto" <?php echo $status === 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <?php if ($search || $status): ?>
                        <a href="contactos.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de contactos -->
            <div class="contactos-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contactos as $contacto): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--white);">
                                        <?php echo htmlspecialchars($contacto['nombre']); ?>
                                    </strong>
                                    <?php if ($contacto['empresa']): ?>
                                        <br><small><?php echo htmlspecialchars($contacto['empresa']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($contacto['email']); ?>
                                    <?php if ($contacto['telefono']): ?>
                                        <br><small><?php echo htmlspecialchars($contacto['telefono']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: var(--white);">
                                        <?php echo htmlspecialchars($contacto['asunto']); ?>
                                    </strong>
                                    <?php if ($contacto['tipo_proyecto']): ?>
                                        <br><small style="color: var(--neon-blue);">
                                            <?php echo ucfirst(htmlspecialchars($contacto['tipo_proyecto'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $contacto['estado']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $contacto['estado'])); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($contacto['fecha_mensaje']); ?></td>
                                <td>
                                    <button onclick="viewContacto(<?php echo $contacto['id']; ?>)" 
                                            class="btn btn-info" style="padding: 0.5rem; margin-right: 0.5rem;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updateStatus(<?php echo $contacto['id']; ?>)" 
                                            class="btn btn-primary" style="padding: 0.5rem; margin-right: 0.5rem;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteContacto(<?php echo $contacto['id']; ?>)" 
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
                <h2 class="modal-title">Detalles del Contacto</h2>
                <button type="button" class="close-modal" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="contactoDetails">
                <!-- Los detalles se cargarán aquí -->
            </div>
        </div>
    </div>

    <!-- Modal para actualizar estado -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Actualizar Contacto</h2>
                <button type="button" class="close-modal" onclick="closeUpdateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="updateForm">
                <input type="hidden" name="action" value="update_status">
                
                <div class="form-group">
                    <label for="estado">Estado del contacto</label>
                    <select id="estado" name="estado" required>
                        <option value="nuevo">Nuevo</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="resuelto">Resuelto</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notas_admin">Notas administrativas</label>
                    <textarea id="notas_admin" name="notas_admin" rows="4" 
                              placeholder="Notas internas sobre el contacto..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeUpdateModal()" class="btn" style="background: var(--accent-gray);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Actualizar Contacto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewContacto(id) {
            fetch(`get-contacto.php?id=${id}`)
                .then(response => response.json())
                .then(contacto => {
                    const details = `
                        <div class="contact-details">
                            <div class="detail-section">
                                <h4>Información del Cliente</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Nombre:</span>
                                    <span class="detail-value">${contacto.nombre}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value">${contacto.email}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Teléfono:</span>
                                    <span class="detail-value">${contacto.telefono || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Empresa:</span>
                                    <span class="detail-value">${contacto.empresa || 'N/A'}</span>
                                </div>
                            </div>
                            <div class="detail-section">
                                <h4>Información del Mensaje</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Asunto:</span>
                                    <span class="detail-value">${contacto.asunto}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tipo:</span>
                                    <span class="detail-value">${contacto.tipo_proyecto || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Estado:</span>
                                    <span class="status-badge status-${contacto.estado}">${contacto.estado.replace('_', ' ')}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Fecha:</span>
                                    <span class="detail-value">${new Date(contacto.fecha_mensaje).toLocaleDateString('es-MX')}</span>
                                </div>
                            </div>
                        </div>
                        <div class="message-content">
                            <h4>Mensaje</h4>
                            <p>${contacto.mensaje.replace(/\n/g, '<br>')}</p>
                        </div>
                        ${contacto.notas_admin ? `
                        <div class="message-content">
                            <h4>Notas Administrativas</h4>
                            <p>${contacto.notas_admin.replace(/\n/g, '<br>')}</p>
                        </div>
                        ` : ''}
                        <div style="text-align: center; margin-top: 2rem;">
                            <button onclick="updateStatus(${contacto.id}); closeViewModal();" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Actualizar Estado
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('contactoDetails').innerHTML = details;
                    document.getElementById('viewModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los detalles del contacto');
                });
        }
        
        function updateStatus(id) {
            fetch(`get-contacto.php?id=${id}`)
                .then(response => response.json())
                .then(contacto => {
                    document.getElementById('updateForm').action = `?action=update_status&id=${id}`;
                    document.getElementById('estado').value = contacto.estado;
                    document.getElementById('notas_admin').value = contacto.notas_admin || '';
                    
                    document.getElementById('updateModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del contacto');
                });
        }
        
        function deleteContacto(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este contacto? Esta acción no se puede deshacer.')) {
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