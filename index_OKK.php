<?php
// Asegurar que config.php esté incluido primero
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

redirectIfNotLoggedIn();

// Incluir archivos de la plantilla
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container mt-5 pt-4">
    <div class="p-5 mb-4 bg-light rounded-3">
        <div class="container-fluid py-5">
            <h1 class="display-5 fw-bold">Bienvenido al Sistema del Restaurante</h1>
            <p class="col-md-8 fs-4">Gestión completa del restaurante: clientes, menú, pedidos, mesas, finanzas y más.</p>
            <hr class="my-4">
            <div class="row g-4">
                <!-- Módulo de Clientes -->
                <div class="col-md-3">
                    <div class="card h-100 border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Clientes</h5>
                            <p class="card-text">Registro y gestión de clientes del restaurante.</p>
                            <a href="clientes/" class="btn btn-outline-primary">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Platos -->
                <div class="col-md-3">
                    <div class="card h-100 border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-utensils fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Platos</h5>
                            <p class="card-text">Gestión del menú y platos disponibles.</p>
                            <a href="platos/" class="btn btn-outline-success">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Pedidos -->
                <div class="col-md-3">
                    <div class="card h-100 border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-clipboard-list fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">Pedidos</h5>
                            <p class="card-text">Registro y seguimiento de pedidos.</p>
                            <a href="pedidos/" class="btn btn-outline-warning">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Mesas -->
                <div class="col-md-3">
                    <div class="card h-100 border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-chair fa-3x text-info mb-3"></i>
                            <h5 class="card-title">Mesas</h5>
                            <p class="card-text">Gestión de mesas y disponibilidad.</p>
                            <a href="mesas/" class="btn btn-outline-info">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Tesorería -->
                <div class="col-md-3">
                    <div class="card h-100 border-danger">
                        <div class="card-body text-center">
                            <i class="fas fa-cash-register fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">Tesorería</h5>
                            <p class="card-text">Gestión de pagos, facturas y caja.</p>
                            <a href="tesoreria/" class="btn btn-outline-danger">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Reportes -->
                <div class="col-md-3">
                    <div class="card h-100 border-secondary">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-3x text-secondary mb-3"></i>
                            <h5 class="card-title">Reportes</h5>
                            <p class="card-text">Reportes de ventas, clientes y más.</p>
                            <a href="reportes/" class="btn btn-outline-secondary">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Mantenimiento -->
                <div class="col-md-3">
                    <div class="card h-100 border-dark">
                        <div class="card-body text-center">
                            <i class="fas fa-tools fa-3x text-dark mb-3"></i>
                            <h5 class="card-title">Mantenimiento</h5>
                            <p class="card-text">Configuración y mantenimiento del sistema.</p>
                            <a href="mantenimiento/" class="btn btn-outline-dark">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Espacio para futuro módulo (opcional) -->
                <div class="col-md-3">
                    <div class="card h-100 border-light">
                        <div class="card-body text-center">
                            <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
                            <h5 class="card-title">Nuevo Módulo</h5>
                            <p class="card-text">Espacio reservado para futuras funcionalidades.</p>
                            <a href="#" class="btn btn-outline-secondary disabled">Próximamente</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>