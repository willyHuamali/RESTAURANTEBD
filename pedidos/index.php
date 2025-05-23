<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/pedidos/';

// Configuración de paginación
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$criterio = isset($_GET['criterio']) ? $_GET['criterio'] : 'cliente';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construcción de la consulta base
$query = "SELECT SQL_CALC_FOUND_ROWS o.*, 
          c.nombre as cliente_nombre, c.apellido as cliente_apellido,
          m.numero as mesa_numero,
          e.nombre as empleado_nombre, e.apellido as empleado_apellido,
          u.username as registrado_por
          FROM ordenes o
          LEFT JOIN clientes c ON o.cliente_id = c.cliente_id
          LEFT JOIN mesas m ON o.mesa_id = m.mesa_id
          LEFT JOIN empleados e ON o.empleado_id = e.empleado_id
          LEFT JOIN usuarios u ON o.usuario_id = u.usuario_id";

// Añadir condiciones de búsqueda si hay criterios
$where = [];
$params = [];

if (!empty($busqueda)) {
    switch ($criterio) {
        case 'cliente':
            $where[] = "(c.nombre LIKE :busqueda_nombre OR c.apellido LIKE :busqueda_apellido)";
            $params[':busqueda_nombre'] = "%$busqueda%";
            $params[':busqueda_apellido'] = "%$busqueda%";
            break;
        case 'orden_id':
            $where[] = "o.orden_id = :busqueda";
            $params[':busqueda'] = $busqueda;
            break;
        case 'mesa':
            $where[] = "m.nombre LIKE :busqueda";
            $params[':busqueda'] = "%$busqueda%";
            break;
    }
}

// Filtro por estado
if (!empty($estado)) {
    $where[] = "o.estado = :estado";
    $params[':estado'] = $estado;
}

// Unir condiciones WHERE si existen
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Orden y límite para paginación
$query .= " ORDER BY o.fecha_hora DESC LIMIT :offset, :limit";

try {
    // Preparar consulta
    $stmt = $pdo->prepare($query);
    
    // Bind de parámetros
    if (!empty($busqueda) || !empty($estado)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    
    $stmt->execute();
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener el total de registros para paginación
    $total_registros = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
} catch(PDOException $e) {
    die("Error al obtener órdenes: " . $e->getMessage());
}
?>

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clipboard-list me-2"></i>Gestión de Pedidos</h2>
        <a href="<?= $base_url ?>registrar.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nuevo Pedido
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
                <div class="col-md-5">
                    <label for="busqueda" class="form-label">Buscar pedido:</label>
                    <div class="input-group">                      
                        <input type="text" class="form-control" id="busqueda" name="busqueda" 
                        value="<?= isset($busqueda) && !empty($busqueda) ? htmlspecialchars((string)$busqueda, ENT_QUOTES, 'UTF-8') : '' ?>" 
                        placeholder="Ingrese término de búsqueda">
                        
                        <select class="form-select" name="criterio" style="max-width: 150px;">
                            <option value="cliente" <?= $criterio === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                            <option value="orden_id" <?= $criterio === 'orden_id' ? 'selected' : '' ?>>N° Orden</option>
                            <option value="mesa" <?= $criterio === 'mesa' ? 'selected' : '' ?>>Mesa</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="estado" class="form-label">Estado:</label>
                    <select class="form-select" name="estado" id="estado">
                        <option value="">Todos</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="en_preparacion" <?= $estado === 'en_preparacion' ? 'selected' : '' ?>>En preparación</option>
                        <option value="listo" <?= $estado === 'listo' ? 'selected' : '' ?>>Listo</option>
                        <option value="entregado" <?= $estado === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                        <option value="pagado" <?= $estado === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                        <option value="cancelado" <?= $estado === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
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
                            <th>N° Orden</th>
                            <th>Cliente</th>
                            <th>Mesa</th>
                            <th>Empleado</th>
                            <th>Fecha/Hora</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($ordenes)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No se encontraron pedidos</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($ordenes as $orden): ?>
                            <tr>
                                <td><?= htmlspecialchars($orden['orden_id']) ?></td>
                                <td>
                                    <?php if($orden['cliente_id']): ?>
                                        <?= htmlspecialchars($orden['cliente_nombre'] . ' ' . $orden['cliente_apellido']) ?>
                                    <?php else: ?>
                                        Cliente no registrado
                                    <?php endif; ?>
                                </td>
                                <td><?= $orden['mesa_id'] ? htmlspecialchars($orden['mesa_nombre']) : 'N/A' ?></td>
                                <td>
                                    <?php if($orden['empleado_id']): ?>
                                        <?= htmlspecialchars($orden['empleado_nombre'] . ' ' . $orden['empleado_apellido']) ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($orden['fecha_hora'])) ?></td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    switch($orden['estado']) {
                                        case 'pendiente': $badge_class = 'bg-secondary'; break;
                                        case 'en_preparacion': $badge_class = 'bg-info'; break;
                                        case 'listo': $badge_class = 'bg-primary'; break;
                                        case 'entregado': $badge_class = 'bg-success'; break;
                                        case 'pagado': $badge_class = 'bg-success'; break;
                                        case 'cancelado': $badge_class = 'bg-danger'; break;
                                        default: $badge_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= ucfirst(str_replace('_', ' ', $orden['estado'])) ?></span>
                                </td>
                                <td>S/ <?= number_format($orden['total_ord'], 2) ?></td>
                                <td><?= !empty($orden['registrado_por']) ? htmlspecialchars($orden['registrado_por']) : 'Sistema' ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="ver.php?id=<?= $orden['orden_id'] ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?= $orden['orden_id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if($orden['estado'] != 'cancelado' && $orden['estado'] != 'pagado'): ?>
                                            <a href="cambiar_estado.php?id=<?= $orden['orden_id'] ?>" class="btn btn-sm btn-primary" title="Cambiar estado">
                                                <i class="fas fa-exchange-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-danger btn-eliminar" title="Cancelar" 
                                                data-id="<?= $orden['orden_id'] ?>">
                                            <i class="fas fa-times"></i>
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
                Mostrando <?= count($ordenes) ?> de <?= $total_registros ?> registros
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para cancelar -->
<div class="modal fade" id="confirmarCancelarModal" tabindex="-1" aria-labelledby="confirmarCancelarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmarCancelarModalLabel">Confirmar Cancelación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea cancelar esta orden? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarCancelar">Sí, Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Manejar clic en botón de eliminar
    $('.btn-eliminar').click(function() {
        var ordenId = $(this).data('id');
        $('#confirmarCancelarModal').modal('show');
        
        $('#confirmarCancelar').off('click').on('click', function() {
            window.location.href = 'cancelar.php?id=' + ordenId;
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>