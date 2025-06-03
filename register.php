<?php
require_once 'config.php';

// Si ya está logueado, redirigir al index
if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo('admin/dashboard.php');
    } else {
        redirectTo('index.html');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre'] ?? '');
    $apellidos = sanitize($_POST['apellidos'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $empresa = sanitize($_POST['empresa'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validaciones
    if (empty($nombre) || empty($apellidos) || empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos obligatorios';
    } elseif (!validateEmail($email)) {
        $error = 'El email no tiene un formato válido';
    } elseif (!validatePassword($password)) {
        $error = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (!$terms) {
        $error = 'Debe aceptar los términos y condiciones';
    } else {
        $db = Database::getInstance();
        
        // Verificar si el email ya existe
        $existingUser = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$email]);
        
        if ($existingUser) {
            $error = 'Ya existe una cuenta con este email';
        } else {
            try {
                // Crear nuevo usuario
                $token_verificacion = generateToken();
                $password_hash = hashPassword($password);
                
                $sql = "INSERT INTO usuarios (nombre, apellidos, email, telefono, empresa, password_hash, token_verificacion, fecha_registro) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $db->query($sql, [$nombre, $apellidos, $email, $telefono, $empresa, $password_hash, $token_verificacion]);
                
                $userId = $db->lastInsertId();
                
                // Log del registro
                logActivity($userId, 'registro_usuario', 'usuarios', $userId);
                
                // Enviar email de verificación (opcional)
                // sendVerificationEmail($email, $token_verificacion);
                
                $success = 'Cuenta creada exitosamente. Puedes iniciar sesión ahora.';
                
                // Limpiar formulario
                $nombre = $apellidos = $email = $telefono = $empresa = '';
                
            } catch (Exception $e) {
                $error = 'Error al crear la cuenta. Intente nuevamente.';
                error_log("Error en registro: " . $e->getMessage());
            }
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Yohualli</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-black) 0%, var(--secondary-black) 100%);
            padding: 2rem;
        }
        
        .register-card {
            background: var(--secondary-black);
            padding: 3rem;
            border-radius: 25px;
            border: 1px solid var(--accent-gray);
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }
        
        .register-logo {
            font-size: 2.5rem;
            font-weight: 900;
            background: var(--gradient-3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .register-subtitle {
            color: var(--text-gray);
            font-size: 1.1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-group label {
            display: block;
            color: var(--white);
            margin-bottom: 0.8rem;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1.2rem;
            background: var(--accent-gray);
            border: 2px solid var(--light-gray);
            border-radius: 15px;
            color: var(--white);
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--neon-blue);
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
            background: rgba(42, 42, 42, 0.8);
        }
        
        .password-group {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: var(--neon-blue);
        }
        
        .terms-group {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            margin-bottom: 2rem;
            color: var(--text-gray);
            font-size: 0.9rem;
        }
        
        .terms-group input[type="checkbox"] {
            margin-top: 0.2rem;
            transform: scale(1.2);
        }
        
        .terms-group a {
            color: var(--neon-blue);
            text-decoration: none;
        }
        
        .terms-group a:hover {
            color: var(--white);
        }
        
        .register-btn {
            width: 100%;
            background: var(--gradient-1);
            color: var(--white);
            padding: 1.3rem;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .register-btn::before {
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
        
        .register-btn:hover::before {
            left: 0;
        }
        
        .register-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }
        
        .register-footer {
            text-align: center;
            color: var(--text-gray);
            position: relative;
            z-index: 1;
        }
        
        .register-footer a {
            color: var(--neon-blue);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-footer a:hover {
            color: var(--white);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            color: #ff6b6b;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.1);
            border: 1px solid rgba(72, 187, 120, 0.3);
            color: #68d391;
        }
        
        .alert-info {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.3);
            color: var(--neon-blue);
        }
        
        .back-to-site {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: var(--text-gray);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
        }
        
        .back-to-site:hover {
            color: var(--neon-blue);
        }
        
        @media (max-width: 768px) {
            .register-container {
                padding: 1rem;
            }
            
            .register-card {
                padding: 2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .back-to-site {
                top: 1rem;
                left: 1rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.html" class="back-to-site">
        <i class="fas fa-arrow-left"></i>
        Volver al sitio
    </a>
    
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-logo">YOHUALLI</div>
                <p class="register-subtitle">Crea tu cuenta y comienza a imprimir</p>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">
                            <i class="fas fa-user"></i> Nombre *
                        </label>
                        <input type="text" id="nombre" name="nombre" required 
                               value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                               placeholder="Tu nombre">
                    </div>
                    
                    <div class="form-group">
                        <label for="apellidos">
                            <i class="fas fa-user"></i> Apellidos *
                        </label>
                        <input type="text" id="apellidos" name="apellidos" required 
                               value="<?php echo htmlspecialchars($apellidos ?? ''); ?>"
                               placeholder="Tus apellidos">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Correo electrónico *
                    </label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           placeholder="tu@email.com">
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="telefono">
                            <i class="fas fa-phone"></i> Teléfono
                        </label>
                        <input type="tel" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($telefono ?? ''); ?>"
                               placeholder="123 456 7890">
                    </div>
                    
                    <div class="form-group">
                        <label for="empresa">
                            <i class="fas fa-building"></i> Empresa
                        </label>
                        <input type="text" id="empresa" name="empresa" 
                               value="<?php echo htmlspecialchars($empresa ?? ''); ?>"
                               placeholder="Nombre de la empresa">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Contraseña *
                        </label>
                        <div class="password-group">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres">
                            <span class="toggle-password" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirmar contraseña *
                        </label>
                        <div class="password-group">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Repite tu contraseña">
                            <span class="toggle-password" onclick="togglePassword('confirm_password', this)">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="terms-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        Acepto los <a href="terminos.html" target="_blank">términos y condiciones</a> 
                        y la <a href="privacidad.html" target="_blank">política de privacidad</a>
                    </label>
                </div>
                
                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus"></i> Crear Cuenta
                </button>
            </form>
            
            <div class="register-footer">
                <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId, element) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = element.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                eyeIcon.className = 'fas fa-eye';
            }
        }
        
        // Validación en tiempo real
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Debes aceptar los términos y condiciones');
                return false;
            }
        });
        
        // Validación de contraseña en tiempo real
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#ff6b6b';
            } else {
                this.style.borderColor = 'var(--light-gray)';
            }
        });
    </script>
</body>
</html>