<?php
require_once 'config.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo('admin/dashboard.php');
    } else {
        redirectTo('dashboard.php');
    }
}

$error = '';
$loginAttempts = $_SESSION['login_attempts'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos';
    } elseif ($loginAttempts >= MAX_LOGIN_ATTEMPTS) {
        $error = 'Demasiados intentos fallidos. Intente más tarde.';
    } else {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, nombre, apellidos, email, password_hash, rol, activo, verificado 
             FROM usuarios WHERE email = ?", 
            [$email]
        );
        
        if ($user && verifyPassword($password, $user['password_hash'])) {
            if (!$user['activo']) {
                $error = 'Su cuenta está desactivada. Contacte al administrador.';
            } elseif (!$user['verificado']) {
                $error = 'Debe verificar su cuenta antes de ingresar.';
            } else {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['login_attempts'] = 0;
                
                // Actualizar último acceso
                $db->query("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?", [$user['id']]);
                
                // Log del login
                logActivity($user['id'], 'login_exitoso');
                
                // Configurar cookie si seleccionó "recordarme"
                if ($remember) {
                    $token = generateToken();
                    // Aquí podrías guardar el token en la base de datos para "remember me"
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                }
                
                // Redirigir según el rol
                if ($user['rol'] === 'admin') {
                    redirectTo('admin/dashboard.php');
                } else {
                    redirectTo('dashboard.php');
                }
            }
        } else {
            $loginAttempts++;
            $_SESSION['login_attempts'] = $loginAttempts;
            $error = 'Email o contraseña incorrectos';
            
            // Log del intento fallido
            if ($user) {
                logActivity($user['id'], 'login_fallido');
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
    <title>Iniciar Sesión - Yohualli</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-black) 0%, var(--secondary-black) 100%);
            padding: 2rem;
        }
        
        .login-card {
            background: var(--secondary-black);
            padding: 3rem;
            border-radius: 25px;
            border: 1px solid var(--accent-gray);
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }
        
        .login-logo {
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
        
        .login-subtitle {
            color: var(--text-gray);
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            color: var(--text-gray);
        }
        
        .remember-me input {
            margin-right: 0.5rem;
            transform: scale(1.2);
        }
        
        .forgot-password {
            color: var(--neon-blue);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .forgot-password:hover {
            color: var(--white);
        }
        
        .login-btn {
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
        
        .login-btn::before {
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
        
        .login-btn:hover::before {
            left: 0;
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }
        
        .login-footer {
            text-align: center;
            color: var(--text-gray);
            position: relative;
            z-index: 1;
        }
        
        .login-footer a {
            color: var(--neon-blue);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
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
            .login-container {
                padding: 1rem;
            }
            
            .login-card {
                padding: 2rem;
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
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">YOHUALLI</div>
                <p class="login-subtitle">Accede a tu cuenta</p>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($loginAttempts > 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Intentos fallidos: <?php echo $loginAttempts; ?>/<?php echo MAX_LOGIN_ATTEMPTS; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Correo electrónico
                    </label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           placeholder="tu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <div class="password-group">
                        <input type="password" id="password" name="password" required 
                               placeholder="Tu contraseña">
                        <span class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        Recordarme
                    </label>
                    <a href="forgot-password.php" class="forgot-password">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
                
                <button type="submit" class="login-btn" <?php echo $loginAttempts >= MAX_LOGIN_ATTEMPTS ? 'disabled' : ''; ?>>
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <div class="login-footer">
                <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                eyeIcon.className = 'fas fa-eye';
            }
        }
        
        // Limpiar intentos después de 30 minutos
        setTimeout(() => {
            fetch('clear-attempts.php', { method: 'POST' });
        }, 30 * 60 * 1000);
    </script>
</body>
</html>