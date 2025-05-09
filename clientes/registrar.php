<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/clientes/';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $dni = trim($_POST['dni']);
    $ruc = trim($_POST['ruc']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
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
    if (!empty($dni)) {
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
    if (!empty($ruc)) {
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
            header("Location: {$base_url}index.php?success=Cliente registrado correctamente");
            exit();
        } catch(PDOException $e) {
            $errores[] = "Error al registrar el cliente: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-5 pt-4">
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
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre*</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="apellido" class="form-label">Apellido*</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="apellido" name="apellido" 
                                   value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="dni" class="form-label">DNI</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="dni" name="dni" 
                                   value="<?= htmlspecialchars($_POST['dni'] ?? '') ?>"
                                   maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <small class="text-muted">Obligatorio si no ingresa RUC</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="ruc" class="form-label">RUC</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="ruc" name="ruc" 
                                   value="<?= htmlspecialchars($_POST['ruc'] ?? '') ?>"
                                   maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <small class="text-muted">Obligatorio si no ingresa DNI</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="telefono" class="form-label">Teléfono*</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" required
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   >
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="direccion" class="form-label">Dirección</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <input type="text" class="form-control" id="direccion" name="direccion" 
                                   value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>"
                                   placeholder="Ingrese la dirección">
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Guardar Cliente
                        </button>
                        <a href="<?= $base_url ?>index.php" class="btn btn-outline-secondary ms-2 px-4">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>