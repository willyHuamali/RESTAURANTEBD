<?php
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();

// Si no está logueado, redirigir al login
if(!$auth->isLoggedIn()) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title">Bienvenido, <?php echo $_SESSION['username']; ?></h2>
                <p class="card-text">Este es el panel principal del sistema de gestión del restaurante.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Selecciona una opción del menú superior para comenzar.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>