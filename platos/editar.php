<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';

$base_url = '/restauranteBD/platos/';

// Verificar si se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: {$base_url}index.php?error=ID de platillo no válido");
    exit();
}

$platillo_id = (int)$_GET['id'];

// Inicializar variables
$nombre = '';
$categoria_id = null;
$descripcion = '';
$precio = 0;
$costo = null;
$tiempo_preparacion = null;
$activo = 1;
$imagen_url = null;
$errores = [];

// Obtener categorías para el select
try {
    $stmt = $pdo->query("SELECT categoria_id, nombre FROM CategoriasMenu ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error al obtener categorías: " . $e->getMessage());
}

// Obtener datos del platillo a editar
try {
    $stmt = $pdo->prepare("SELECT * FROM Platillos WHERE platillo_id = :id");
    $stmt->execute([':id' => $platillo_id]);
    $platillo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$platillo) {
        header("Location: {$base_url}index.php?error=Platillo no encontrado");
        exit();
    }
    
    // Asignar valores del platillo a las variables
    $nombre = $platillo['nombre'];
    $categoria_id = $platillo['categoria_id'];
    $descripcion = $platillo['descripcion'];
    $precio = $platillo['precio'];
    $costo = $platillo['costo'];
    $tiempo_preparacion = $platillo['tiempo_preparacion'];
    $activo = $platillo['activo'];
    $imagen_url = $platillo['imagen_url'];
    
} catch(PDOException $e) {
    die("Error al obtener platillo: " . $e->getMessage());
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria_id = $_POST['categoria_id'] ?? null;
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $costo = !empty($_POST['costo']) ? (float)$_POST['costo'] : null;
    $tiempo_preparacion = !empty($_POST['tiempo_preparacion']) ? (int)$_POST['tiempo_preparacion'] : null;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones básicas
    if (empty($nombre)) {
        $errores[] = "El nombre del platillo es requerido";
    }
    
    if (empty($categoria_id)) {
        $errores[] = "Debe seleccionar una categoría";
    }
    
    if ($precio <= 0) {
        $errores[] = "El precio debe ser mayor a cero";
    }
    
    // Validar si ya existe otro platillo activo con el mismo nombre
    if (!empty($nombre) && empty($errores)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Platillos WHERE nombre = :nombre AND activo = 1 AND platillo_id != :id");
            $stmt->execute([
                ':nombre' => $nombre,
                ':id' => $platillo_id
            ]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $errores[] = "Ya existe otro platillo activo con este nombre. Solo puede tener platillos con nombres duplicados si el otro está inactivo.";
            }
        } catch(PDOException $e) {
            $errores[] = "Error al validar el nombre del platillo: " . $e->getMessage();
        }
    }
    
    // Procesar imagen si se subió
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['imagen'];
        
        // Validar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $errores[] = "Solo se permiten imágenes JPG, PNG o GIF";
        }
        
        // Validar tamaño (max 2MB)
        if ($file['size'] > 2097152) {
            $errores[] = "La imagen no debe superar los 2MB";
        }
        
        if (empty($errores)) {
            // Crear directorio si no existe
            $upload_dir = __DIR__ . '/../uploads/platillos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generar nombre único para el archivo
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'platillo_' . time() . '_' . uniqid() . '.' . $ext;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Eliminar imagen anterior si existe
                if (!empty($imagen_url)) {
                    $old_image_path = __DIR__ . '/..' . $imagen_url;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $imagen_url = '/uploads/platillos/' . $filename;
            } else {
                $errores[] = "Error al subir la imagen";
            }
        }
    }

    // Si no hay errores, actualizar en la base de datos
    if (empty($errores)) {
        try {
            // Preparar consulta de actualización
            $sql = "UPDATE Platillos SET 
                    categoria_id = :categoria_id, 
                    nombre = :nombre, 
                    descripcion = :descripcion, 
                    precio = :precio, 
                    costo = :costo, 
                    tiempo_preparacion = :tiempo_preparacion, 
                    activo = :activo";
            
            // Agregar imagen_url solo si se actualizó
            if (!empty($imagen_url)) {
                $sql .= ", imagen_url = :imagen_url";
            }
            
            $sql .= " WHERE platillo_id = :platillo_id";
            
            $stmt = $pdo->prepare($sql);
            
            $params = [
                ':categoria_id' => $categoria_id,
                ':nombre' => $nombre,
                ':descripcion' => !empty($descripcion) ? $descripcion : null,
                ':precio' => $precio,
                ':costo' => !empty($costo) ? $costo : null,
                ':tiempo_preparacion' => !empty($tiempo_preparacion) ? $tiempo_preparacion : null,
                ':activo' => $activo,
                ':platillo_id' => $platillo_id
            ];
            
            if (!empty($imagen_url)) {
                $params[':imagen_url'] = $imagen_url;
            }
            
            $stmt->execute($params);
            
            // Redirigir con mensaje de éxito
            header("Location: {$base_url}index.php?success=" . urlencode('Platillo actualizado correctamente'));
            exit();
        } catch(PDOException $e) {
            $errores[] = "Error al actualizar el platillo: " . $e->getMessage();
        }
    }
}

// Incluir templates después de posibles redirecciones
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit me-2"></i>Editar Platillo</h2>
        <a href="<?= $base_url ?>index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Mostrar errores si existen -->
    <?php if(!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">Errores encontrados:</h5>
            <ul>
                <?php foreach($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <!-- Columna izquierda -->
                    <div class="col-md-6">
                        <!-- Nombre -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Platillo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($nombre) ?>" required>
                        </div>
                        
                        <!-- Categoría -->
                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">-- Seleccione una categoría --</option>
                                <?php foreach($categorias as $categoria): ?>
                                    <option value="<?= $categoria['categoria_id'] ?>" 
                                        <?= ($categoria_id == $categoria['categoria_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Descripción -->                      
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= isset($descripcion) ? htmlspecialchars($descripcion) : '' ?></textarea>
                         </div>
                                            
                        <!-- Precio -->
                        <div class="mb-3">
                            <label for="precio" class="form-label">Precio (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="precio" name="precio" 
                                   value="<?= htmlspecialchars($precio) ?>" required>
                        </div>
                    </div>
                    
                    <!-- Columna derecha -->
                    <div class="col-md-6">
                       <!-- Costo -->
                        <div class="mb-3">
                            <label for="costo" class="form-label">Costo (S/)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="costo" name="costo" 
                                value="<?= isset($costo) ? htmlspecialchars((string)$costo) : '' ?>">
                        </div>

                        <!-- Tiempo de preparación -->
                        <div class="mb-3">
                            <label for="tiempo_preparacion" class="form-label">Tiempo de Preparación (minutos)</label>
                            <input type="number" min="0" class="form-control" id="tiempo_preparacion" name="tiempo_preparacion" 
                                value="<?= isset($tiempo_preparacion) ? htmlspecialchars((string)$tiempo_preparacion) : '' ?>">
                        </div>
                        
                        <!-- Imagen -->
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen del Platillo</label>
                            <?php if(!empty($imagen_url)): ?>
                                <div class="mb-2">
                                    <img src="<?= htmlspecialchars($imagen_url) ?>" alt="Imagen actual" style="max-width: 100px; max-height: 100px;" class="img-thumbnail">
                                    <div class="form-text">Imagen actual</div>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                            <div class="form-text">Formatos aceptados: JPG, PNG, GIF (Máx. 2MB)</div>
                        </div>
                        
                        <!-- Estado -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" 
                                <?= $activo ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="reset" class="btn btn-secondary me-md-2">
                        <i class="fas fa-undo me-1"></i> Restablecer
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>