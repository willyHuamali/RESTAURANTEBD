<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=" . urlencode('ID de categoría no válido'));
    exit();
}

$categoria_id = (int)$_GET['id'];

// Inicializar variables
$nombre = '';
$descripcion = '';
$orden = 0;
$activa = true;
$errores = [];

// Obtener datos actuales de la categoría
try {
    $stmt = $pdo->prepare("SELECT * FROM CategoriasMenu WHERE categoria_id = :id");
    $stmt->execute([':id' => $categoria_id]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        header("Location: index.php?error=" . urlencode('Categoría no encontrada'));
        exit();
    }

    // Asignar valores actuales
    $nombre = $categoria['nombre'];
    $descripcion = $categoria['descripcion'];
    $orden = $categoria['orden'];
    $activa = (bool)$categoria['activa'];
} catch (PDOException $e) {
    $errores[] = 'Error al cargar la categoría: ' . $e->getMessage();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $orden = isset($_POST['orden']) ? (int)$_POST['orden'] : 0;
    $activa = isset($_POST['activa']) && $_POST['activa'] == 'on' ? 1 : 0; 


    // Validaciones básicas
    if (empty($nombre)) {
        $errores[] = 'El nombre de la categoría es obligatorio';
    } elseif (strlen($nombre) > 50) {
        $errores[] = 'El nombre no puede exceder los 50 caracteres';
    }

    if (strlen($descripcion) > 200) {
        $errores[] = 'La descripción no puede exceder los 200 caracteres';
    }

    // Si no hay errores básicos, validar existencia de nombre y orden
    if (empty($errores)) {
        try {
            // Verificar si ya existe otra categoría con el mismo nombre (activa)
            $stmt = $pdo->prepare("SELECT categoria_id FROM CategoriasMenu WHERE nombre = :nombre AND activa = 1 AND categoria_id != :id");
            $stmt->execute([':nombre' => $nombre, ':id' => $categoria_id]);
            if ($stmt->fetch()) {
                $errores[] = 'Ya existe otra categoría activa con ese nombre';
            }

            // Verificar si ya existe otra categoría con el mismo orden (activa)
            if ($orden > 0) {
                $stmt = $pdo->prepare("SELECT categoria_id FROM CategoriasMenu WHERE orden = :orden AND activa = 1 AND categoria_id != :id");
                $stmt->execute([':orden' => $orden, ':id' => $categoria_id]);
                if ($stmt->fetch()) {
                    $errores[] = 'Ya existe otra categoría activa con ese número de orden';
                }
            }

            // Si no hay errores de validación, proceder con la actualización
            if (empty($errores)) {
                $stmt = $pdo->prepare("UPDATE CategoriasMenu SET 
                                      nombre = :nombre, 
                                      descripcion = :descripcion, 
                                      orden = :orden, 
                                      activa = :activa 
                                      WHERE categoria_id = :id");
                
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $descripcion,
                    ':orden' => $orden,
                    ':activa' => $activa,
                    ':id' => $categoria_id
                ]);

                // Redirigir con mensaje de éxito
                header("Location: index.php?success=" . urlencode('Categoría actualizada exitosamente'));
                exit();
            }
        } catch (PDOException $e) {
            $errores[] = 'Error al actualizar la categoría: ' . $e->getMessage();
        }
    }
}

// Incluir templates después de posibles redirecciones
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/categoriasmenu/';
?>

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit me-2"></i>Editar Categoría</h2>
        <a href="<?= $base_url ?>index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al listado
        </a>
    </div>

    <!-- Mostrar errores si existen -->
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong>
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre de la categoría <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= htmlspecialchars($nombre) ?>" required maxlength="50">
                        <div class="form-text">Máximo 50 caracteres</div>
                    </div>

                    <div class="col-md-6">
                        <label for="orden" class="form-label">Orden de visualización</label>
                        <input type="number" class="form-control" id="orden" name="orden" 
                               value="<?= htmlspecialchars($orden) ?>" min="0">
                        <div class="form-text">Número para ordenar las categorías (menor = primero)</div>
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" 
                                  rows="3" maxlength="200"><?= htmlspecialchars($descripcion) ?></textarea>
                        <div class="form-text">Máximo 200 caracteres</div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="activa" name="activa" 
                                   <?= $activa ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activa">Categoría activa</label>
                        </div>
                        <div class="form-text">Las categorías inactivas no se mostrarán en el menú</div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                        <a href="<?= $base_url ?>index.php" class="btn btn-outline-secondary">
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