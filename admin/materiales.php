<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Procesar acciones
$action = $_GET['action'] ?? 'list';
$materialId = $_GET['id'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $nombre = sanitize($_POST['nombre']);
        $tipo = sanitize($_POST['tipo']);
        $color = sanitize($_POST['color']);
        $precio_kg = (float)$_POST['precio_kg'];
        $stock_kg = (float)$_POST['stock_kg'];
        $proveedor = sanitize($_POST['proveedor']);
        $descripcion = sanitize($_POST['descripcion']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        try {
            $sql = "INSERT INTO materiales (nombre, tipo, color, precio_kg, stock_kg, proveedor, descripcion, activo, fecha_creacion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $db->query($sql, [$nombre, $tipo, $color, $precio_kg, $stock_kg, $proveedor, $descripcion, $activo]);
            
            logActivity($_SESSION['user_id'], 'crear_material', 'materiales', $db->lastInsertId());
            showMessage('Material creado correctamente', 'success');
            header('Location: materiales.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al crear material: ' . $e->getMessage();
        }
    }
    
    if ($action === 'edit' && $materialId) {
        $nombre = sanitize($_POST['nombre']);
        $tipo = sanitize($_POST['tipo']);
        $color = sanitize($_POST['color']);
        $precio_kg = (float)$_POST['precio_kg'];
        $stock_kg = (float)$_POST['stock_kg'];
        $proveedor = sanitize($_POST['proveedor']);
        $descripcion = sanitize($_POST['descripcion']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        try {
            $sql = "UPDATE materiales SET nombre = ?, tipo = ?, color = ?, precio_kg = ?, stock_kg = ?, proveedor = ?, descripcion = ?, activo = ? WHERE id = ?";
            $db->query($sql, [$nombre, $tipo, $color, $precio_kg, $stock_kg, $proveedor, $descripcion, $activo, $materialId]);
            
            logActivity($_SESSION['user_id'], 'actualizar_material', 'materiales', $materialId);
            showMessage('Material actualizado correctamente', 'success');
            header('Location: materiales.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al actualizar material: ' . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && $materialId) {
        try {
            $db->query("DELETE FROM materiales WHERE id = ?", [$materialId]);
            logActivity($_SESSION['user_id'], 'eliminar_material', 'materiales', $materialId);
            showMessage('Material eliminado correctamente', 'success');
            header('Location: materiales.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al eliminar material: ' . $e->getMessage();
        }
    }
}

// Obtener materiales
$search = $_GET['search'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = 'WHERE 1=1';
$params = [];

if ($search) {
    $whereClause .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($tipo) {
    $whereClause .= " AND tipo = ?";
    $params[] = $tipo;
}

$materiales = $db->fetchAll("SELECT * FROM materiales $whereClause ORDER BY nombre ASC LIMIT $limit OFFSET $offset", $params);

// Contar total para paginación
$totalMateriales = $db->fetch("SELECT COUNT(*) as total FROM materiales $whereClause", $params)['total'];
$totalPages = ceil($totalMateriales / $limit);

// Obtener tipos para filtros
$tipos = $db->fetchAll("SELECT DISTINCT tipo FROM materiales WHERE tipo IS NOT NULL ORDER BY tipo");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Materiales - Yohualli Admin</title>
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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
        
        .form-group label {
            display: block;
            color: var(--white);
            margin-bottom: 0.5rem;
            font-weight: bold;
            font-size: 0.9rem;
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
        
        .materials-table {
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
        
        .material-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid var(--white);
            margin-right: 0.5rem;
        }
        
        .stock-indicator {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .stock-alto {
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
        }
        
        .stock-medio {
            background: rgba(255, 170, 0, 0.2);
            color: #ffaa00;
        }
        
        .stock-bajo {
            background: rgba(245, 101, 101, 0.2);
            color: #f56565;
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
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="productos.php"><i class="fas fa-cube"></i> Productos</a></li>
                <li><a href="cotizaciones.php"><i class="fas fa-calculator"></i> Cotizaciones</a></li>
                <li><a href="pedidos.php"><i class="fas fa-box"></i> Pedidos</a></li>
                <li><a href="contactos.php"><i class="fas fa-envelope"></i> Contactos</a></li>
                <li><a href="materiales.php" class="active"><i class="fas fa-industry"></i> Materiales</a></li>
                <li><a href="servicios.php"><i class="fas fa-cogs"></i> Servicios</a></li>
                <li><a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                <li><a href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="admin-main">
            <div class="page-header">
                <h1 class="page-title">Gestión de Materiales</h1>
                <div>
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Material
                    </button>
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

            <!-- Filtros -->
            <div class="filters-bar">
                <form method="GET" class="filters-form">
                    <div class="form-group">
                        <label for="search">Buscar materiales</label>
                        <input type="text" id="search" name="search" placeholder="Nombre o descripción..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo">
                            <option value="">Todos los tipos</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?php echo htmlspecialchars($t['tipo']); ?>" 
                                        <?php echo $tipo === $t['tipo'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($t['tipo'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <?php if ($search || $tipo): ?>
                        <a href="materiales.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de materiales -->
            <div class="materials-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Tipo</th>
                            <th>Color</th>
                            <th>Precio/kg</th>
                            <th>Stock</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiales as $material): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--white);">
                                        <?php echo htmlspecialchars($material['nombre']); ?>
                                    </strong>
                                </td>
                                <td><?php echo ucfirst(htmlspecialchars($material['tipo'])); ?></td>
                                <td>
                                    <?php if ($material['color']): ?>
                                        <span class="material-color" style="background-color: <?php echo htmlspecialchars($material['color']); ?>"></span>
                                        <?php echo htmlspecialchars($material['color']); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: var(--neon-blue);">
                                        <?php echo formatPrice($material['precio_kg']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php 
                                    $stockLevel = 'alto';
                                    if ($material['stock_kg'] < 1) $stockLevel = 'bajo';
                                    elseif ($material['stock_kg'] < 5) $stockLevel = 'medio';
                                    ?>
                                    <span class="stock-indicator stock-<?php echo $stockLevel; ?>">
                                        <?php echo number_format($material['stock_kg'], 2); ?> kg
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($material['proveedor'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($material['activo']): ?>
                                        <span style="color: #48bb78;">Activo</span>
                                    <?php else: ?>
                                        <span style="color: #f56565;">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="editMaterial(<?php echo $material['id']; ?>)" 
                                            class="btn btn-primary" style="padding: 0.5rem; margin-right: 0.5rem;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteMaterial(<?php echo $material['id']; ?>)" 
                                            class="btn btn-danger" style="padding: 0.5rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function openCreateModal() {
            // Implementar modal de creación
            alert('Funcionalidad de crear material - Por implementar');
        }
        
        function editMaterial(materialId) {
            // Implementar modal de edición
            alert('Funcionalidad de editar material - Por implementar');
        }
        
        function deleteMaterial(materialId) {
            if (confirm('¿Estás seguro de que deseas eliminar este material?')) {
                window.location.href = `?action=delete&id=${materialId}`;
            }
        }
    </script>
</body>
</html>