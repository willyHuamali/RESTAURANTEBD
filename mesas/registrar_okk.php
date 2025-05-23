<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/mesas/';

// Inicializar variables
$numero = '';
$capacidad = '';
$ubicacion = '';
$estado = 'disponible';
$error = '';
$success = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos del formulario
    $numero = trim($_POST['numero'] ?? '');
    $capacidad = trim($_POST['capacidad'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $estado = $_POST['estado'] ?? 'disponible';

    // Validaciones
    if (empty($numero)) {
        $error = 'El número de mesa es requerido';
    } elseif (!is_numeric($numero) || $numero <= 0) {
        $error = 'El número de mesa debe ser un valor positivo';
    } elseif (empty($capacidad)) {
        $error = 'La capacidad es requerida';
    } elseif (!is_numeric($capacidad) || $capacidad <= 0) {
        $error = 'La capacidad debe ser un número positivo';
    } else {
        try {
            // Verificar si el número de mesa ya existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Mesas WHERE numero = ?");
            $stmt->execute([$numero]);
            $existe = $stmt->fetchColumn();

            if ($existe > 0) {
                $error = 'El número de mesa ya está registrado';
            } else {
                // Insertar nueva mesa
                $stmt = $pdo->prepare("INSERT INTO Mesas (numero, capacidad, ubicacion, estado) VALUES (?, ?, ?, ?)");
                $stmt->execute([$numero, $capacidad, $ubicacion, $estado]);

                $success = 'Mesa registrada correctamente';
                
                // Limpiar campos después de registro exitoso
                $numero = '';
                $capacidad = '';
                $ubicacion = '';
                $estado = 'disponible';
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar la mesa: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Registrar Nueva Mesa
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                        <div class="mb-3">
                            <label for="numero" class="form-label">Número de Mesa *</label>
                            <input type="number" class="form-control" id="numero" name="numero" 
                                   value="<?= htmlspecialchars($numero) ?>" required min="1">
                            <small class="text-muted">Número único que identifica la mesa</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="capacidad" class="form-label">Capacidad *</label>
                            <input type="number" class="form-control" id="capacidad" name="capacidad" 
                                   value="<?= htmlspecialchars($capacidad) ?>" required min="1">
                            <small class="text-muted">Número máximo de personas que caben en la mesa</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                   value="<?= htmlspecialchars($ubicacion) ?>">
                            <small class="text-muted">Ejemplo: Terraza, Interior, Barra, etc.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado *</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="disponible" <?= $estado === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="ocupada" <?= $estado === 'ocupada' ? 'selected' : '' ?>>Ocupada</option>
                                <option value="reservada" <?= $estado === 'reservada' ? 'selected' : '' ?>>Reservada</option>
                                <option value="mantenimiento" <?= $estado === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?= $base_url ?>" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Mesa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>