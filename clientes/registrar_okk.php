<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
redirectIfNotLoggedIn();

// Verificar que el usuario esté autenticado y tenga un ID válido
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php?error=No autenticado");
    exit();
}

// Inicializar variables
$errores = [];
$valores = [
    'nombre' => '',
    'apellido' => '',
    'telefono' => '',
    'dni' => '',
    'ruc' => '',
    'email' => '',
    'direccion' => ''
];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanitizar los datos del formulario
    $valores = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'dni' => trim($_POST['dni'] ?? ''),
        'ruc' => trim($_POST['ruc'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? '')
    ];
    
    $usuario_id = $_SESSION['usuario_id'];

    // Validaciones básicas
    if (empty($valores['nombre'])) {
        $errores[] = "El nombre es obligatorio";
    }

    if (empty($valores['apellido'])) {
        $errores[] = "El apellido es obligatorio";
    }

    // Validación: Al menos DNI o RUC es obligatorio
    if (empty($valores['dni']) && empty($valores['ruc'])) {
        $errores[] = "Debe ingresar al menos un DNI o RUC";
    }

    // Validación: DNI no debe repetirse en clientes activos
    if (!empty($valores['dni'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE dni = :dni AND activo = 1");
        $stmt->execute([':dni' => $valores['dni']]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "El DNI ya está registrado para otro cliente activo";
        }
    }

    // Validación: RUC no debe repetirse en clientes activos
    if (!empty($valores['ruc'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE ruc = :ruc AND activo = 1");
        $stmt->execute([':ruc' => $valores['ruc']]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "El RUC ya está registrado para otro cliente activo";
        }
    }

    if (!empty($valores['email'])) {
        if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El email no tiene un formato válido";
        } else {
            // Validar que el email no exista en clientes activos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE email = :email AND activo = 1");
            $stmt->execute([':email' => $valores['email']]);
            if ($stmt->fetchColumn() > 0) {
                $errores[] = "El email ya está registrado para otro cliente activo";
            }
        }
    }

    // Si no hay errores, insertar en la base de datos
    if (empty($errores)) {
        try {
            $query = "INSERT INTO clientes (nombre, apellido, telefono, dni, ruc, email, direccion, usuario_id, activo)
                      VALUES (:nombre, :apellido, :telefono, :dni, :ruc, :email, :direccion, :usuario_id, 1)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':nombre' => $valores['nombre'],
                ':apellido' => $valores['apellido'],
                ':telefono' => $valores['telefono'],
                ':dni' => $valores['dni'],
                ':ruc' => $valores['ruc'],
                ':email' => $valores['email'],
                ':direccion' => $valores['direccion'],
                ':usuario_id' => $usuario_id
            ]);

            // Redirigir con mensaje de éxito
            header("Location: index.php?success=Cliente registrado correctamente");
            exit();
        } catch(PDOException $e) {
            error_log("Error al registrar cliente: " . $e->getMessage());
            $errores[] = "Ocurrió un error al registrar el cliente. Por favor intente nuevamente.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Registrar Nuevo Cliente</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Errores encontrados:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errores as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre*</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?= htmlspecialchars($valores['nombre']) ?>" required
                                       maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ\s]+"
                                       title="Solo letras y espacios">
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido*</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" 
                                       value="<?= htmlspecialchars($valores['apellido']) ?>" required
                                       maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ\s]+"
                                       title="Solo letras y espacios">
                            </div>
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                       value="<?= htmlspecialchars($valores['telefono']) ?>"
                                       maxlength="20" pattern="[0-9+()\- ]+" title="Solo números, +, (), - y espacios">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($valores['email']) ?>"
                                       maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control" id="dni" name="dni" 
                                       value="<?= htmlspecialchars($valores['dni']) ?>"
                                       maxlength="15" pattern="[0-9]*" title="Solo números">
                            </div>
                            <div class="col-md-6">
                                <label for="ruc" class="form-label">RUC</label>
                                <input type="text" class="form-control" id="ruc" name="ruc" 
                                       value="<?= htmlspecialchars($valores['ruc']) ?>"
                                       maxlength="25" pattern="[0-9]*" title="Solo números">
                            </div>
                            <div class="col-12">
                                <label for="direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                          maxlength="200"><?= htmlspecialchars($valores['direccion']) ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Guardar Cliente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include __DIR__ . '/../includes/footer.php';
?>