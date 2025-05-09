<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirectIfNotLoggedIn();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$cliente_id = $_GET['id'];

// Obtener datos del cliente
try {
    $query = "SELECT * FROM clientes WHERE cliente_id = :cliente_id AND activo = TRUE";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':cliente_id' => $cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        header("Location: index.php?error=Cliente no encontrado");
        exit();
    }
} catch(PDOException $e) {
    die("Error al obtener cliente: " . $e->getMessage());
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanitizar los datos del formulario
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']);
    $dni = trim($_POST['dni']);
    $ruc = trim($_POST['ruc']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);

    // Validaciones básicas
    $errores = [];

    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }

    if (empty($apellido)) {
        $errores[] = "El apellido es obligatorio";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido";
    }

    // Si no hay errores, actualizar en la base de datos
    if (empty($errores)) {
        try {
            $query = "UPDATE clientes 
                      SET nombre = :nombre, apellido = :apellido, telefono = :telefono, 
                          dni = :dni, ruc = :ruc, email = :email, direccion = :direccion
                      WHERE cliente_id = :cliente_id";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':telefono' => $telefono,
                ':dni' => $dni,
                ':ruc' => $ruc,
                ':email' => $email,
                ':direccion' => $direccion,
                ':cliente_id' => $cliente_id
            ]);

            // Redirigir con mensaje de éxito
            header("Location: index.php?success=Cliente actualizado correctamente");
            exit();
        } catch(PDOException $e) {
            // Manejar error de duplicado de email
            if ($e->errorInfo[1] == 1062) {
                $errores[] = "El email ya está registrado para otro cliente";
            } else {
                $errores[] = "Error al actualizar el cliente: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Editar Cliente</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errores as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre*</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido*</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" 
                                       value="<?= htmlspecialchars($cliente['apellido']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                       value="<?= htmlspecialchars($cliente['telefono']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($cliente['email']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control" id="dni" name="dni" 
                                       value="<?= htmlspecialchars($cliente['dni']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="ruc" class="form-label">RUC</label>
                                <input type="text" class="form-control" id="ruc" name="ruc" 
                                       value="<?= htmlspecialchars($cliente['ruc']) ?>">
                            </div>
                            <div class="col-12">
                                <label for="direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="direccion" name="direccion" rows="2"><?= htmlspecialchars($cliente['direccion']) ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Guardar Cambios
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
include '../includes/footer.php';
?>