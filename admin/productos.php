<?php
require_once '../config.php';
requireAdmin();

$db = Database::getInstance();

// Procesar acciones
$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        // Crear nuevo producto
        $nombre = sanitize($_POST['nombre']);
        $descripcion = sanitize($_POST['descripcion']);
        $categoria = sanitize($_POST['categoria']);
        $precio = (float)$_POST['precio'];
        $material = sanitize($_POST['material']);
        $dimensiones = sanitize($_POST['dimensiones']);
        $tiempo_impresion = sanitize($_POST['tiempo_impresion']);
        $stock = (int)$_POST['stock'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        
        try {
            $sql = "INSERT INTO productos (nombre, descripcion, categoria, precio, material, dimensiones, tiempo_impresion, stock, activo, destacado, fecha_creacion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $db->query($sql, [$nombre, $descripcion, $categoria, $precio, $material, $dimensiones, $tiempo_impresion, $stock, $activo, $destacado]);
            
            $productId = $db->lastInsertId();
            logActivity($_SESSION['user_id'], 'crear_producto', 'productos', $productId);
            showMessage('Producto creado correctamente', 'success');
            header('Location: productos.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al crear producto: ' . $e->getMessage();
        }
    }
    
    if ($action === 'edit' && $productId) {
        // Actualizar producto
        $nombre = sanitize($_POST['nombre']);
        $descripcion = sanitize($_POST['descripcion']);
        $categoria = sanitize($_POST['categoria']);
        $precio = (float)$_POST['precio'];
        $material = sanitize($_POST['material']);
        $dimensiones = sanitize($_POST['dimensiones']);
        $tiempo_impresion = sanitize($_POST['tiempo_impresion']);
        $stock = (int)$_POST['stock'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        
        try {
            $sql = "UPDATE productos SET nombre = ?, descripcion = ?, categoria = ?, precio = ?, material = ?, dimensiones = ?, tiempo_impresion = ?, stock = ?, activo = ?, destacado = ? WHERE id = ?";
            $db->query($sql, [$nombre, $descripcion, $categoria, $precio, $material, $dimensiones, $tiempo_impresion, $stock, $activo, $destacado, $productId]);
            
            logActivity($_SESSION['user_id'], 'actualizar_producto', 'productos', $productId);
            showMessage('Producto actualizado correctamente', 'success');
            header('Location: productos.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al actualizar producto: ' . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && $productId) {
        try {
            $db->query("DELETE FROM productos WHERE id = ?", [$productId]);
            logActivity($_SESSION['user_id'], 'eliminar_producto', 'productos', $productId);
            showMessage('Producto eliminado correctamente', 'success');
            header('Location: productos.php');
            exit;
        } catch (Exception $e) {
            $message = 'Error al eliminar producto: ' . $e->getMessage();
        }
    }
}

// Obtener lista de productos
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$whereClause = 'WHERE 1=1';
$params = [];

if ($search) {
    $whereClause .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($categoria) {
    $whereClause .= " AND categoria = ?";
    $params[] = $categoria;
}

$productos = $db->fetchAll("SELECT * FROM productos $whereClause ORDER BY fecha_creacion DESC LIMIT $limit OFFSET $offset", $params);

// Contar total para paginación
$totalProductos = $db->fetch("SELECT COUNT(*) as total FROM productos $whereClause", $params)['total'];
$totalPages = ceil($totalProductos / $limit);

// Obtener categorías para filtros
$categorias = $db->fetchAll("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL ORDER BY categoria");

// Si es edición, obtener datos del producto
$productoEdit = null;
if ($action === 'edit' && $productId) {
    $productoEdit = $db->fetch("SELECT * FROM productos WHERE id = ?", [$productId]);
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Yohualli Admin</title>
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
        
        .form-group {
            margin-bottom: 0;
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
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .product-card {
            background: var(--secondary-black);
            border-radius: 20px;
            border: 1px solid var(--accent-gray);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            border-color: var(--neon-blue);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.2);
        }
        
        .product-image {
            height: 200px;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--white);
            position: relative;
        }
        
        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .badge-destacado {
            background: rgba(255, 170, 0, 0.9);
            color: var(--white);
        }
        
        .badge-inactivo {
            background: rgba(245, 101, 101, 0.9);
            color: var(--white);
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-title {
            color: var(--white);
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .product-category {
            color: var(--neon-blue);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .product-description {
            color: var(--text-gray);
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.8rem;
        }
        
        .detail-item {
            color: var(--text-gray);
        }
        
        .detail-label {
            font-weight: bold;
            color: var(--white);
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--neon-blue);
            margin-bottom: 1rem;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
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
        
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            background: var(--accent-gray);
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            color: var(--white);
            resize: vertical;
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
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
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
            
            .products-grid {
                grid-template-columns: 1fr;
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
                <li><a href="productos.php" class="active"><i class="fas fa-cube"></i> Productos</a></li>
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
                <h1 class="page-title">Gestión de Productos</h1>
                <div>
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Producto
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
                        <label for="search">Buscar productos</label>
                        <input type="text" id="search" name="search" placeholder="Nombre o descripción..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['categoria']); ?>" 
                                        <?php echo $categoria === $cat['categoria'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($cat['categoria'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <?php if ($search || $categoria): ?>
                        <a href="productos.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Grid de productos -->
            <div class="products-grid">
                <?php foreach ($productos as $producto): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <i class="fas fa-cube"></i>
                            <?php if ($producto['destacado']): ?>
                                <span class="product-badge badge-destacado">Destacado</span>
                            <?php endif; ?>
                            <?php if (!$producto['activo']): ?>
                                <span class="product-badge badge-inactivo">Inactivo</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <div class="product-category"><?php echo htmlspecialchars($producto['categoria']); ?></div>
                            <p class="product-description"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            
                            <div class="product-details">
                                <div class="detail-item">
                                    <span class="detail-label">Material:</span> <?php echo htmlspecialchars($producto['material'] ?? 'N/A'); ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Stock:</span> <?php echo $producto['stock']; ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Dimensiones:</span> <?php echo htmlspecialchars($producto['dimensiones'] ?? 'N/A'); ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tiempo:</span> <?php echo htmlspecialchars($producto['tiempo_impresion'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            
                            <div class="product-price"><?php echo formatPrice($producto['precio']); ?></div>
                            
                            <div class="product-actions">
                                <button onclick="editProduct(<?php echo $producto['id']; ?>)" class="btn btn-primary btn-small">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button onclick="deleteProduct(<?php echo $producto['id']; ?>)" class="btn btn-danger btn-small">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $categoria ? '&categoria=' . urlencode($categoria) : ''; ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal para crear/editar productos -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Nuevo Producto</h2>
                <button type="button" class="close-modal" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="productForm">
                <input type="hidden" name="action" id="formAction" value="create">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre del producto *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="categoria">Categoría *</label>
                        <select id="categoria_form" name="categoria" required>
                            <option value="">Seleccionar categoría</option>
                            <option value="figuras">Figuras</option>
                            <option value="prototipos">Prototipos</option>
                            <option value="decorativos">Decorativos</option>
                            <option value="funcionales">Funcionales</option>
                            <option value="herramientas">Herramientas</option>
                            <option value="repuestos">Repuestos</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción *</label>
                    <textarea id="descripcion" name="descripcion" required placeholder="Descripción detallada del producto..."></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="precio">Precio (MXN) *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <input type="number" id="stock" name="stock" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="material">Material</label>
                        <select id="material" name="material">
                            <option value="">Seleccionar material</option>
                            <option value="PLA">PLA</option>
                            <option value="ABS">ABS</option>
                            <option value="PETG">PETG</option>
                            <option value="TPU">TPU (Flexible)</option>
                            <option value="Resina">Resina</option>
                            <option value="Mixto">Material mixto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dimensiones">Dimensiones</label>
                        <input type="text" id="dimensiones" name="dimensiones" placeholder="ej: 10x8x5 cm">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tiempo_impresion">Tiempo de impresión estimado</label>
                    <input type="text" id="tiempo_impresion" name="tiempo_impresion" placeholder="ej: 2-3 horas">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="activo" name="activo" checked>
                    <label for="activo">Producto activo (visible en la tienda)</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="destacado" name="destacado">
                    <label for="destacado">Producto destacado</label>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: var(--accent-gray);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Producto';
            document.getElementById('formAction').value = 'create';
            document.getElementById('productForm').action = '?action=create';
            document.getElementById('productForm').reset();
            document.getElementById('activo').checked = true;
            document.getElementById('productModal').classList.add('show');
        }
        
        function editProduct(productId) {
            document.getElementById('modalTitle').textContent = 'Editar Producto';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productForm').action = `?action=edit&id=${productId}`;
            
            // Cargar datos del producto
            fetch(`get-product.php?id=${productId}`)
                .then(response => response.json())
                .then(product => {
                    document.getElementById('nombre').value = product.nombre || '';
                    document.getElementById('categoria_form').value = product.categoria || '';
                    document.getElementById('descripcion').value = product.descripcion || '';
                    document.getElementById('precio').value = product.precio || '';
                    document.getElementById('stock').value = product.stock || '';
                    document.getElementById('material').value = product.material || '';
                    document.getElementById('dimensiones').value = product.dimensiones || '';
                    document.getElementById('tiempo_impresion').value = product.tiempo_impresion || '';
                    document.getElementById('activo').checked = product.activo == 1;
                    document.getElementById('destacado').checked = product.destacado == 1;
                    
                    document.getElementById('productModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del producto');
                });
        }
        
        function deleteProduct(productId) {
            if (confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
                window.location.href = `?action=delete&id=${productId}`;
            }
        }
        
        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>