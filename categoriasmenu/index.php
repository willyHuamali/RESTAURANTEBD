<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/categoriasmenu/';

// Configuración de paginación
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$criterio = isset($_GET['criterio']) ? $_GET['criterio'] : 'nombre';

// Construcción de la consulta base
$query = "SELECT SQL_CALC_FOUND_ROWS * FROM CategoriasMenu";

// Añadir condiciones de búsqueda si hay criterios
$where = [];
$params = [];

if (!empty($busqueda)) {
    switch ($criterio) {
        case 'nombre':
            $where[] = "nombre LIKE :busqueda";
            $params[':busqueda'] = "%$busqueda%";
            break;
        case 'descripcion':
            $where[] = "descripcion LIKE :busqueda";
            $params[':busqueda'] = "%$busqueda%";
            break;
    }
}

// Unir condiciones WHERE si existen
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Orden y límite para paginación
$query .= " ORDER BY orden ASC, nombre ASC LIMIT :offset, :limit";

try {
    // Preparar consulta
    $stmt = $pdo->prepare($query);
    
    // Bind de parámetros
    if (!empty($busqueda)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener el total de registros para paginación
    $total_registros = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
} catch(PDOException $e) {
    die("Error al obtener categorías: " . $e->getMessage());
}
?>

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-list-alt me-2"></i>Gestión de Categorías de Menú</h2>
        <a href="<?= $base_url ?>registrar.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva Categoría
        </a>
    </div>    

    <?php if(isset($_GET['success']) && !empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars((string)$_GET['success'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Formulario de búsqueda -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-6">
                    <label for="busqueda" class="form-label">Buscar categoría:</label>
                    <div class="input-group">                      
                        <input type="text" class="form-control" id="busqueda" name="busqueda" 
                        value="<?= isset($busqueda) && !empty($busqueda) ? htmlspecialchars((string)$busqueda, ENT_QUOTES, 'UTF-8') : '' ?>" 
                        placeholder="Ingrese término de búsqueda">
                        
                        <select class="form-select" name="criterio" style="max-width: 150px;">
                            <option value="nombre" <?= $criterio === 'nombre' ? 'selected' : '' ?>>Nombre</option>
                            <option value="descripcion" <?= $criterio === 'descripcion' ? 'selected' : '' ?>>Descripción</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover data-table">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($categorias)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No se encontraron categorías</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($categorias as $categoria): ?>
                            <tr>
                                <td><?= htmlspecialchars($categoria['categoria_id']) ?></td>
                                <td><?= htmlspecialchars($categoria['nombre']) ?></td>
                                <td><?= !empty($categoria['descripcion']) ? htmlspecialchars($categoria['descripcion']) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($categoria['orden']) ?></td>
                                <td>
                                    <?php if($categoria['activa']): ?>
                                        <span class="badge bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="editar.php?id=<?= $categoria['categoria_id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger btn-eliminar" title="Eliminar" 
                                                data-id="<?= $categoria['categoria_id'] ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if($total_paginas > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>" aria-label="First">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    // Mostrar números de página
                    $inicio = max(1, $pagina_actual - 2);
                    $fin = min($total_paginas, $pagina_actual + 2);
                    
                    for ($i = $inicio; $i <= $fin; $i++):
                    ?>
                        <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) ?>" aria-label="Last">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="text-muted text-center">
                Mostrando <?= count($categorias) ?> de <?= $total_registros ?> registros
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmarEliminarModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar esta categoría? Esta acción no se puede deshacer.
                <br><br>
                <strong>Nota:</strong> Si la categoría tiene platillos asociados, no podrá ser eliminada.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminar">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

<script>
$(document).ready(function() {
    // Manejar clic en botón eliminar
    $('.btn-eliminar').click(function() {
        var categoriaId = $(this).data('id');
        $('#confirmarEliminarModal').modal('show');
        
        $('#confirmarEliminar').off('click').on('click', function() {
            window.location.href = 'eliminarcategoria.php?id=' + categoriaId;
        });
    });
});
</script>