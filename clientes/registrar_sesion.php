<?php
// Iniciar sesión al principio del script
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Verificar y establecer usuario_id antes de cualquier salida
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /restauranteBD/login.php");
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $usuario_id = $_SESSION['usuario_id']; // ID del usuario que está registrando

    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($apellido)) {
        $errores[] = "El apellido es obligatorio";
    }
    
    if (empty($dni) && empty($ruc)) {
        $errores[] = "Debe ingresar al menos DNI o RUC";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido";
    }

    // Validar que DNI no se repita en clientes activos
    if (!empty($dni) && empty($errores)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE dni = :dni AND activo = TRUE");
            $stmt->execute([':dni' => $dni]);
            if ($stmt->fetchColumn() > 0) {
                $errores[] = "El DNI ingresado ya existe en nuestros registros";
            }
        } catch(PDOException $e) {
            $errores[] = "Error al validar DNI: " . $e->getMessage();
        }
    }

    // Validar que RUC no se repita en clientes activos
    if (!empty($ruc) && empty($errores)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE ruc = :ruc AND activo = TRUE");
            $stmt->execute([':ruc' => $ruc]);
            if ($stmt->fetchColumn() > 0) {
                $errores[] = "El RUC ingresado ya existe en nuestros registros";
            }
        } catch(PDOException $e) {
            $errores[] = "Error al validar RUC: " . $e->getMessage();
        }
    }

    // Si no hay errores, insertar en la base de datos
    if (empty($errores)) {
        try {
            $query = "INSERT INTO clientes (nombre, apellido, dni, ruc, telefono, email, direccion, usuario_id, fecha_registro, activo) 
                      VALUES (:nombre, :apellido, :dni, :ruc, :telefono, :email, :direccion, :usuario_id, NOW(), TRUE)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':dni' => empty($dni) ? null : $dni,
                ':ruc' => empty($ruc) ? null : $ruc,
                ':telefono' => $telefono,
                ':email' => empty($email) ? null : $email,
                ':direccion' => empty($direccion) ? null : $direccion,
                ':usuario_id' => $usuario_id
            ]);

            // Redirigir con mensaje de éxito
            header("Location: index.php?success=Cliente registrado correctamente");
            exit();
        } catch(PDOException $e) {
            $errores[] = "Error al registrar el cliente: " . $e->getMessage();
        }
    }
}

// Incluir vistas después de toda la lógica de procesamiento
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/clientes/';
?>

<div class="container mt-5 pt-4">
    <!-- Resto del código HTML permanece igual -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-plus me-2"></i>Registrar Nuevo Cliente</h2>
        <a href="<?= $base_url ?>index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if(!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Errores encontrados:</strong>
            <ul class="mb-0">
                <?php foreach($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" novalidate>
                <!-- Resto del formulario permanece igual -->
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>