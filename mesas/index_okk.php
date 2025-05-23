<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/mesas/';

// Configuración de paginación
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$criterio = isset($_GET['criterio']) ? $_GET['criterio'] : 'numero';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construcción de la consulta base
$query = "SELECT SQL_CALC_FOUND_ROWS * FROM Mesas";

// Añadir condiciones de búsqueda si hay criterios
$where = [];
$params = [];

if (!empty($busqueda)) {
    switch ($criterio) {
        case 'numero':
            $where[] = "numero LIKE :busqueda";
            $params[':busqueda'] = "%$busqueda%";
            break;
        case 'capacidad':
            $where[] = "capacidad = :busqueda";
            $params[':busqueda'] = (int)$busqueda;
            break;
        case 'ubicacion':
            $where[] = "ubicacion LIKE :busqueda";
            $params[':busqueda'] = "%$busqueda%";
            break;
    }
}

// Filtro por estado
if (!empty($filtro_estado)) {
    $where[] = "estado = :estado";
    $params[':estado'] = $filtro_estado;
}

// Unir condiciones WHERE si existen
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Orden y límite para paginación
$query .= " ORDER BY numero ASC LIMIT :offset, :limit";

try {
    // Preparar consulta
    $stmt = $pdo->prepare($query);
    
    // Bind de parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    
    $stmt->execute();
    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener el total de registros para paginación
    $total_registros = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
} catch(PDOException $e) {
    die("Error al obtener mesas: " . $e->getMessage());
}

// Función para obtener clase CSS según estado
function getCardClassByEstado($estado) {
    switch($estado) {
        case 'disponible':
            return 'border-success';
        case 'ocupada':
            return 'border-danger';
        case 'reservada':
            return 'border-warning';
        case 'mantenimiento':
            return 'border-secondary';
        default:
            return '';
    }
}

// Función para obtener icono según estado
function getIconByEstado($estado) {
    switch($estado) {
        case 'disponible':
            return 'fa-check-circle';
        case 'ocupada':
            return 'fa-utensils';
        case 'reservada':
            return 'fa-calendar-check';
        case 'mantenimiento':
            return 'fa-tools';
        default:
            return 'fa-question-circle';
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-table me-2"></i> Gestión de Mesas</h2>
        <a href="<?= $base_url ?>registrar.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva Mesa
        </a>
    </div>    

    <?php if(isset($_GET['success']) && !empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars((string)$_GET['success'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Filtros y búsqueda -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-6">
                    <label for="busqueda" class="form-label">Buscar mesa:</label>
                    <div class="input-group">                      
                        <input type="text" class="form-control" id="busqueda" name="busqueda" 
                               value="<?= htmlspecialchars((string)$busqueda, ENT_QUOTES, 'UTF-8') ?>" 
                               placeholder="Ingrese término de búsqueda">
                        <select class="form-select" name="criterio" style="max-width: 150px;">
                            <option value="numero" <?= $criterio === 'numero' ? 'selected' : '' ?>>Número</option>
                            <option value="capacidad" <?= $criterio === 'capacidad' ? 'selected' : '' ?>>Capacidad</option>
                            <option value="ubicacion" <?= $criterio === 'ubicacion' ? 'selected' : '' ?>>Ubicación</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="estado" class="form-label">Filtrar por estado:</label>
                    <div class="input-group">
                        <select class="form-select" name="estado" id="estado">
                            <option value="">Todos los estados</option>
                            <option value="disponible" <?= $filtro_estado === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                            <option value="ocupada" <?= $filtro_estado === 'ocupada' ? 'selected' : '' ?>>Ocupada</option>
                            <option value="reservada" <?= $filtro_estado === 'reservada' ? 'selected' : '' ?>>Reservada</option>
                            <option value="mantenimiento" <?= $filtro_estado === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Visualización en tarjetas -->
    <?php if(empty($mesas)): ?>
        <div class="alert alert-info">
            No se encontraron mesas con los criterios seleccionados.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach($mesas as $mesa): ?>
                <?php 
                $card_class = getCardClassByEstado($mesa['estado']);
                $icon_class = getIconByEstado($mesa['estado']);
                ?>
                <div class="col">
                    <div class="card h-100 border-3 <?= $card_class ?> shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-table me-2"></i>Mesa #<?= htmlspecialchars($mesa['numero']) ?>
                            </h5>
                            <span class="badge bg-<?= 
                                $mesa['estado'] === 'disponible' ? 'success' : 
                                ($mesa['estado'] === 'ocupada' ? 'danger' : 
                                ($mesa['estado'] === 'reservada' ? 'warning' : 'secondary')) ?>">
                                <?= ucfirst($mesa['estado']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas <?= $icon_class ?> fa-2x me-3 text-<?= 
                                    $mesa['estado'] === 'disponible' ? 'success' : 
                                    ($mesa['estado'] === 'ocupada' ? 'danger' : 
                                    ($mesa['estado'] === 'reservada' ? 'warning' : 'secondary')) ?>"></i>
                                <div>
                                    <h6 class="mb-1">Capacidad: <?= htmlspecialchars($mesa['capacidad']) ?> personas</h6>
                                    <small class="text-muted">Ubicación: <?= !empty($mesa['ubicacion']) ? htmlspecialchars($mesa['ubicacion']) : 'N/A' ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <?php if($mesa['estado'] === 'disponible'): ?>
                                <a href="/restauranteBD/pedidos/registrar.php?mesa_id=<?= $mesa['mesa_id'] ?>" 
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-plus-circle me-1"></i> Nuevo Pedido
                                </a>
                            <?php elseif($mesa['estado'] === 'ocupada'): ?>
                                <a href="/restauranteBD/pedidos/?mesa_id=<?= $mesa['mesa_id'] ?>" 
                                   class="btn btn-info btn-sm">
                                    <i class="fas fa-eye me-1"></i> Ver Pedido
                                </a>
                            <?php endif; ?>
                            
                            <div class="btn-group">
                                <a href="editar.php?id=<?= $mesa['mesa_id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger btn-eliminar" title="Eliminar" 
                                        data-id="<?= $mesa['mesa_id'] ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
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
    
    <div class="text-muted text-center mt-3">
        Mostrando <?= count($mesas) ?> de <?= $total_registros ?> mesas
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
                ¿Está seguro que desea eliminar esta mesa? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminar">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- Script para manejar la eliminación -->
<script>
$(document).ready(function() {
    let mesaIdAEliminar = null;
    
    // Manejar clic en botón eliminar
    $('.btn-eliminar').click(function() {
        mesaIdAEliminar = $(this).data('id');
        $('#confirmarEliminarModal').modal('show');
    });
    
    // Confirmar eliminación
    $('#confirmarEliminar').click(function() {
        if (mesaIdAEliminar) {
            window.location.href = 'eliminar.php?id=' + mesaIdAEliminar;
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>