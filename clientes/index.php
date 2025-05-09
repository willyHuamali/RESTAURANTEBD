<?php
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
?>
<?php
require_once '../includes/db.php';
include '../includes/header.php';
include '../includes/navbar.php';

// Manejo de errores para la consulta
try {
    // Consulta para obtener todos los clientes con información del usuario que los registró
    $query = "SELECT c.*, u.username as registrado_por 
              FROM clientes c
              LEFT JOIN usuarios u ON c.usuario_id = u.usuario_id
              WHERE c.activo = TRUE
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
        <a href="registrar.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nuevo Cliente
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
<div class="modal fade" id="confirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar este cliente? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" class="btn btn-danger" id="btn-confirmar-eliminar">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<?php 
include '../includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar modal de eliminación
    const modalEliminar = new bootstrap.Modal(document.getElementById('confirmarEliminar'));
    const btnConfirmar = document.getElementById('btn-confirmar-eliminar');
    
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            btnConfirmar.href = `eliminar.php?id=${id}`;
            modalEliminar.show();
        });
    });
    
    // Mostrar alertas de error/success
    if (window.location.search.includes('error=')) {
        const urlParams = new URLSearchParams(window.location.search);
        const errorMsg = urlParams.get('error');
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${errorMsg}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.querySelector('.container').prepend(alertDiv);
    }
    
    // Inicializar DataTable
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
            },
            responsive: true
        });
    }
});
</script>

