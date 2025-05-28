<?php
require_once 'config.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'nombre' => sanitize($_POST['nombre'] ?? ''),
        'apellidos' => sanitize($_POST['apellidos'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'telefono' => sanitize($_POST['telefono'] ?? ''),
        'empresa' => sanitize($_POST['empresa'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // Validaciones
    if (empty($formData['nombre'])) {
        $errors[] = 'El nombre es requerido';
    }
    
    if (empty($formData['apellidos'])) {
        $errors[] = 'Los apellidos son requeridos';
    }
    
    if (empty($formData['email']) || !validateEmail($formData['email'])) {
        $errors[] = 'Email válido es requerido';
    }
    
    if (empty($formData['password'])) {
        $errors[] = 'La contraseña es requerida';
    } elseif (!validatePassword($formData['password'])) {
        $errors[] = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres';
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    // Verificar si el email ya existe
    if (empty($errors)) {
        $db = Database::getInstance();
        $existingUser = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$formData['email']]);
        
        if ($existingUser) {
            $errors[] = 'Ya existe una cuenta con este email';
        }
    }
    
    // Si no hay errores, crear el usuario
    if (empty($errors)) {
        try {
            $passwordHash = hashPassword($formData['password']);
            $verificationToken = generateToken();
            
            $sql = "INSERT INTO usuarios (nombre, apellidos, email, telefono, empresa, password_hash, token_verificacion, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $formData['nombre'],
                $formData['apellidos'],
                $formData['email'],
                $formData['telefono'],
                $formData['empresa'],
                $passwordHash,
                $verificationToken
            ];
            
            $db->query($sql, $params);
            $userId = $db->lastInsertId();
            
            // Log de registro
            logActivity($userId, 'registro_usuario');
            
            // Enviar email de verificación (implementar según necesidades)
            // sendVerificationEmail($formData['email'], $verificationToken);
            
            showMessage('Registro exitoso. Revisa tu email para verificar tu cuenta.', 'success');
            redirectTo('login.php');
            
        } catch (Exception $e) {
            $errors[] = 'Error al crear la cuenta. Intenta nuevamente.';
            error_log("Error en registro: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Yohualli</title>
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
            background: radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
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
            background: var(--gradient-2);
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
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
        
        .form-group input,
        .form-group select {
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
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--neon-blue);
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
            background: rgba(42, 42, 42, 0.8);
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }
        
        .strength-weak { color: #ff6b6b; }
        .strength-medium { color: #ffd93d; }
        .strength-strong { color: #6bcf7f; }
        
        .register-btn {
            width: 100%;
            background: var(--gradient-2);
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
            background: var(--gradient-1);
            transition: left 0.4s ease;
            z-index: -1;
        }
        
        .register-btn:hover::before {
            left: 0;
        }
        
        .register-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(245, 87, 108, 0.4);
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
        
        .alert ul {
            margin: 0;
            padding-left: 1.5rem;
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
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
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
                <p class="register-subtitle">Crea tu cuenta</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">
                            <i class="fas fa-user"></i> Nombre *
                        </label>
                        <input type="text" id="nombre" name="nombre" required 
                               value="<?php echo htmlspecialchars($formData['nombre'] ?? ''); ?>"
                               placeholder="Tu nombre">
                    </div>
                    
                    <div class="form-group">
                        <label for="apellidos">
                            <i class="fas fa-user"></i> Apellidos *
                        </label>
                        <input type="text" id="apellidos" name="apellidos" required 
                               value="<?php echo htmlspecialchars($formData['apellidos'] ?? ''); ?>"
                               placeholder="Tus apellidos">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Correo electrónico *
                    </label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                           placeholder="tu@email.com">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">
                            <i class="fas fa-phone"></i> Teléfono
                        </label>
                        <input type="tel" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($formData['telefono'] ?? ''); ?>"
                               placeholder="+52 449 123 4567">
                    </div>
                    
                    <div class="form-group">
                        <label for="empresa">
                            <i class="fas fa-building"></i> Empresa
                        </label>
                        <input type="text" id="empresa" name="empresa" 
                               value="<?php echo htmlspecialchars($formData['empresa'] ?? ''); ?>"
                               placeholder="Nombre de tu empresa">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Contraseña *
                        </label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Mínimo 8 caracteres"
                               onkeyup="checkPasswordStrength()">
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirmar contraseña *
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirma tu contraseña"
                               onkeyup="checkPasswordMatch()">
                        <div id="passwordMatch" class="password-strength"></div>
                    </div>
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
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            const checks = [
                password.length >= 8,
                /[a-z]/.test(password),
                /[A-Z]/.test(password),
                /[0-9]/.test(password),
                /[^A-Za-z0-9]/.test(password)
            ];
            
            strength = checks.filter(check => check).length;
            
            if (strength < 3) {
                strengthDiv.innerHTML = '<span class="strength-weak">Débil</span>';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength < 4) {
                strengthDiv.innerHTML = '<span class="strength-medium">Media</span>';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.innerHTML = '<span class="strength-strong">Fuerte</span>';
                strengthDiv.className = 'password-strength strength-strong';
            }
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<span class="strength-strong">✓ Las contraseñas coinciden</span>';
                matchDiv.className = 'password-strength strength-strong';
            } else {
                matchDiv.innerHTML = '<span class="strength-weak">✗ Las contraseñas no coinciden</span>';
                matchDiv.className = 'password-strength strength-weak';
            }
        }
    </script>
</body>
</html>