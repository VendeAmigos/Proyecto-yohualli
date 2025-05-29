<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Procesar acciones
$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'edit' && $userId) {
        // Actualizar usuario
        $nombre = sanitize($_POST['nombre']);
        $apellidos = sanitize($_POST['apellidos']);
        $email = sanitize($_POST['email']);
        $telefono = sanitize($_POST['telefono']);
        $empresa = sanitize($_POST['empresa']);
        $rol = sanitize($_POST['rol']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $verificado = isset($_POST['verificado']) ? 1 : 0;
        
        try {
            $sql = "UPDATE usuarios SET nombre = ?, apellidos = ?, email = ?, telefono = ?, empresa = ?, rol = ?, activo = ?, verificado = ? WHERE id = ?";
            $db->query($sql, [$nombre, $apellidos, $email, $telefono, $empresa, $rol, $activo, $verificado, $userId]);
            
            logActivity($_SESSION['user_id'], 'actualizar_usuario', 'usuarios', $userId);
            showMessage('Usuario actualizado correctamente', 'success');
            header('Location: usuarios.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al actualizar usuario: ' . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && $userId) {
        try {
            $db->query("DELETE FROM usuarios WHERE id = ?", [$userId]);
            logActivity($_SESSION['user_id'], 'eliminar_usuario', 'usuarios', $userId);
            showMessage('Usuario eliminado correctamente', 'success');
            header('Location: usuarios.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al eliminar usuario: ' . $e->getMessage();
        }
    }
}

// Obtener lista de usuarios
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$whereClause = '';
$params = [];

if ($search) {
    $whereClause = "WHERE nombre LIKE ? OR apellidos LIKE ? OR email LIKE ?";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$usuarios = $db->fetchAll("SELECT * FROM usuarios $whereClause ORDER BY fecha_registro DESC LIMIT $limit OFFSET $offset", $params);

// Contar total para paginación
$totalUsuarios = $db->fetch("SELECT COUNT(*) as total FROM usuarios $whereClause", $params)['total'];
$totalPages = ceil($totalUsuarios / $limit);

// Si es edición, obtener datos del usuario
$usuarioEdit = null;
if ($action === 'edit' && $userId) {
    $usuarioEdit = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$userId]);
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Yohualli Admin</title>
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
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--gradient-1);
            color: var(--white);
        }
        
        .btn-success {
            background: #48bb78;
            color: var(--white);
        }
        
        .btn-danger {
            background: #f56565;
            color: var(--white);
        }
        
        .btn-warning {
            background: #ffaa00;
            color: var(--white);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .search-bar {
            background: var(--secondary-black);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid var(--accent-gray);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 0.8rem;
            background: var(--accent-gray);
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            color: var(--white);
        }
        
        .users-table {
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
        
        .status-activo {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .status-inactivo {
            background: rgba(245, 101, 101, 0.2);
            color: #f56565;
            border: 1px solid rgba(245, 101, 101, 0.3);
        }
        
        .role-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .role-admin {
            background: rgba(139, 92, 246, 0.2);
            color: var(--accent-purple);
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        
        .role-usuario {
            background: rgba(0, 212, 255, 0.2);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a {
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            color: var(--white);
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            background: var(--accent-gray);
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            color: var(--white);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
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
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--secondary-black);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            width: 90%;
            max-width: 600px;
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
                <li><a href="usuarios.php" class="active"><i class="fas fa-users"></i> Usuarios</a></li>
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
            <div class="page-header">
                <h1 class="page-title">Gestión de Usuarios</h1>
                <div>
                    <span style="color: var(--text-gray);">Total: <?php echo $totalUsuarios; ?> usuarios</span>
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

            <!-- Barra de búsqueda -->
            <div class="search-bar">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Buscar por nombre, apellidos o email..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <?php if ($search): ?>
                        <a href="usuarios.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de usuarios -->
            <div class="users-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Empresa</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td>
                                    <strong style="color: var(--white);">
                                        <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?>
                                    </strong>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['telefono'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($usuario['empresa'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $usuario['rol']; ?>">
                                        <?php echo ucfirst($usuario['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $usuario['activo'] ? 'activo' : 'inactivo'; ?>">
                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                    <?php if (!$usuario['verificado']): ?>
                                        <br><small style="color: #ffaa00;">Sin verificar</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($usuario['fecha_registro']); ?></td>
                                <td>
                                    <button onclick="editUser(<?php echo $usuario['id']; ?>)" class="btn btn-primary" style="padding: 0.5rem; margin-right: 0.5rem;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="deleteUser(<?php echo $usuario['id']; ?>)" class="btn btn-danger" style="padding: 0.5rem;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
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
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal de edición -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Usuario</h2>
                <button type="button" class="close-modal" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="apellidos">Apellidos</label>
                        <input type="text" id="apellidos" name="apellidos" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" id="telefono" name="telefono">
                    </div>
                    <div class="form-group">
                        <label for="empresa">Empresa</label>
                        <input type="text" id="empresa" name="empresa">
                    </div>
                </div>
                <div class="form-group">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" required>
                        <option value="usuario">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="activo" name="activo">
                    <label for="activo">Usuario activo</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="verificado" name="verificado">
                    <label for="verificado">Email verificado</label>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: var(--accent-gray);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editUser(userId) {
            // Obtener datos del usuario mediante AJAX o PHP
            fetch(`get-user.php?id=${userId}`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('editForm').action = `?action=edit&id=${userId}`;
                    document.getElementById('nombre').value = user.nombre || '';
                    document.getElementById('apellidos').value = user.apellidos || '';
                    document.getElementById('email').value = user.email || '';
                    document.getElementById('telefono').value = user.telefono || '';
                    document.getElementById('empresa').value = user.empresa || '';
                    document.getElementById('rol').value = user.rol || 'usuario';
                    document.getElementById('activo').checked = user.activo == 1;
                    document.getElementById('verificado').checked = user.verificado == 1;
                    
                    document.getElementById('editModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del usuario');
                });
        }
        
        function deleteUser(userId) {
            if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
                window.location.href = `?action=delete&id=${userId}`;
            }
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.remove('show');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>