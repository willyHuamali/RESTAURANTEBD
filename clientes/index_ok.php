<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/clientes/';

// Manejo de errores para la consulta
try {
    // Consulta para obtener todos los clientes con información del usuario que los registró
    $query = "SELECT c.*, u.username as registrado_por 
              FROM clientes c
              LEFT JOIN usuarios u ON c.usuario_id = u.usuario_id
              /*WHERE c.activo = TRUE    // para mostrar solo los activos*/   
              ORDER BY c.fecha_registro DESC";
    $stmt = $pdo->query($query);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error al obtener clientes: " . $e->getMessage());
}
?>

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users me-2"></i>Gestión de Clientes</h2>
        <a href="<?= $base_url ?>registrar.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nuevo Clientesss
        </a>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover data-table">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>DNI</th>
                            <th>RUC</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Dirección</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clientes as $cliente): ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['cliente_id']) ?></td>
                            <td><?= htmlspecialchars($cliente['nombre']) . ' ' . htmlspecialchars($cliente['apellido']) ?></td>
                            <td><?= !empty($cliente['dni']) ? htmlspecialchars($cliente['dni']) : 'N/A' ?></td>
                            <td><?= !empty($cliente['ruc']) ? htmlspecialchars($cliente['ruc']) : 'N/A' ?></td>
                            <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                            <td><?= htmlspecialchars($cliente['email']) ?></td>
                            <td><?= !empty($cliente['direccion']) ? htmlspecialchars($cliente['direccion']) : 'N/A' ?></td>
                            <td>
                                <?php if($cliente['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?></td>
                            <td><?= !empty($cliente['registrado_por']) ? htmlspecialchars($cliente['registrado_por']) : 'Sistema' ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="editar.php?id=<?= $cliente['cliente_id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-eliminar" title="Eliminar" 
                                            data-id="<?= $cliente['cliente_id'] ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                ¿Está seguro que desea eliminar este cliente? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminar">Eliminar</button>
            </div>
        </div>
    </div>
</div>



<!--  scrip para elimiar datos corregir Al final del archivo clientes/index.php, antes del footer -->
<script>
$(document).ready(function() {
    $('.btn-eliminar').click(function() {
        const clienteId = $(this).data('id');
        const btn = $(this);
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'eliminar.php',
                    method: 'POST',
                    data: { id: clienteId },
                    dataType: 'json'
                })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Eliminado!',
                            response.message,
                            'success'
                        ).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                })
                .fail(function() {
                    Swal.fire(
                        'Error!',
                        'Ocurrió un error al intentar eliminar el cliente',
                        'error'
                    );
                });
            }
        });
    });
});
</script>