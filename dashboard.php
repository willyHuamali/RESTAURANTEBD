<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Cargar cabecera
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="card-title">Bienvenido, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="card-text">Este es el panel principal del sistema de gestión del restaurante.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Selecciona una opción del menú superior para comenzar.
                    </div>

                    <!-- Estadísticas rápidas -->
                    <div class="row mt-4">
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-primary h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-users"></i> Clientes</h5>
                                    <p class="card-text"><?php echo obtenerTotalClientes($pdo); ?> registrados</p>
                                    <a href="clientes/" class="text-white">Ver más <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-success h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-utensils"></i> Platos</h5>
                                    <p class="card-text"><?php echo obtenerTotalPlatos($pdo); ?> disponibles</p>
                                    <a href="platos/" class="text-white">Ver más <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-warning h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-clipboard-list"></i> Pedidos</h5>
                                    <p class="card-text"><?php echo obtenerPedidosHoy($pdo); ?> hoy</p>
                                    <a href="pedidos/" class="text-white">Ver más <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-danger h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-dollar-sign"></i> Ventas</h5>
                                    <p class="card-text"><?php echo formatMoney(obtenerVentasHoy($pdo)); ?> hoy</p>
                                    <a href="reportes/ventas" class="text-white">Ver más <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/includes/footer.php';

/**
 * Funciones auxiliares para obtener estadísticas
 */

function obtenerTotalClientes($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM clientes WHERE activo = 1");
    return $stmt->fetchColumn();
}

function obtenerTotalPlatos($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM platos WHERE activo = 1");
    return $stmt->fetchColumn();
}

function obtenerPedidosHoy($pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha) = CURDATE()");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function obtenerVentasHoy($pdo) {
    $stmt = $pdo->prepare("SELECT SUM(total) FROM pedidos WHERE DATE(fecha) = CURDATE() AND estado = 'completado'");
    $stmt->execute();
    return $stmt->fetchColumn() ?? 0;
}

function formatMoney($amount) {
    return '$' . number_format($amount, 2);
}
?>