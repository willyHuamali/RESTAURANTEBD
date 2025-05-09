<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirigir si ya está logueado
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Inicializar variables
$error = '';
$username = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y limpiar entradas
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones
    if (empty($username)) {
        $error = 'Por favor ingrese su nombre de usuario';
    } elseif (empty($password)) {
        $error = 'Por favor ingrese su contraseña';
    } elseif (strlen($username) > 50) {
        $error = 'El nombre de usuario es demasiado largo';
    } else {
        // Intentar autenticación
        if (login($pdo, $username, $password)) {
            // Redirigir a la página solicitada originalmente o al dashboard
            $redirect = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            header("Location: " . $redirect);
            exit();
        } else {
            // Demora para prevenir ataques de fuerza bruta
            sleep(1);
            $error = 'Credenciales incorrectas';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="assets/images/logo.jpg" alt="Logo Restaurante" class="mb-3" style="height: 80px;">
                            <h3 class="card-title">Iniciar Sesión</h3>
                            <p class="text-muted">Ingrese sus credenciales para continuar</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        
                        <form method="POST" action="<?php echo BASE_URL; ?>login.php" autocomplete="off">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($username); ?>" 
                                           required autofocus maxlength="50">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required minlength="8">
                                </div>
                                <div class="form-text">
                                    <a href="forgot-password.php" class="text-decoration-none">¿Olvidó su contraseña?</a>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-1"></i> Ingresar
                                </button>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Recordar mi sesión</label>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        <div class="text-center">
                            <p class="mb-0">¿No tienes una cuenta? <a href="register.php" class="text-decoration-none">Regístrate aquí</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Deshabilitar reenvío de formulario al recargar
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>