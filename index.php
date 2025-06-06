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

<main class="container my-5">
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Bienvenido al Sistema del Restaurante Will</h1>
            <p class="hero-description">
                Gestión completa de tu restaurante: control de clientes, menú digital, 
                seguimiento de pedidos, administración de mesas y análisis financieros.
            </p>
            
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <!-- Módulo de Clientes -->
                <div class="col">
                    <div class="card h-100 border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Clientes</h5>
                            <p class="card-text">Registro y gestión de clientes del restaurante.</p>
                            <a href="clientes/" class="btn btn-outline-primary mt-auto">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Platos -->
                <div class="col">
                    <div class="card h-100 border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-utensils fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Menú</h5>
                            <p class="card-text">Gestión del menú y platos disponibles.</p>
                            <a href="platos/" class="btn btn-outline-success mt-auto">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Pedidos -->
                <div class="col">
                    <div class="card h-100 border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-clipboard-list fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">Pedidos</h5>
                            <p class="card-text">Registro y seguimiento de pedidos.</p>
                            <a href="pedidos/" class="btn btn-outline-warning mt-auto">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Mesas -->
                <div class="col">
                    <div class="card h-100 border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-chair fa-3x text-info mb-3"></i>
                            <h5 class="card-title">Mesas</h5>
                            <p class="card-text">Gestión de mesas y disponibilidad.</p>
                            <a href="mesas/" class="btn btn-outline-info mt-auto">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Tesorería -->
                <div class="col">
                    <div class="card h-100 border-danger">
                        <div class="card-body text-center">
                            <i class="fas fa-cash-register fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">Tesorería</h5>
                            <p class="card-text">Gestión de pagos, facturas y caja.</p>
                            <a href="tesoreria/" class="btn btn-outline-danger mt-auto">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Reportes -->
                <div class="col">
                    <div class="card h-100 border-secondary">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-3x text-secondary mb-3"></i>
                            <h5 class="card-title">Reportes</h5>
                            <p class="card-text">Reportes de ventas, clientes y más.</p>
                            <a href="reportes/" class="btn btn-outline-secondary mt-auto">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Módulo de Mantenimiento -->
                <div class="col">
                    <div class="card h-100 border-dark">
                        <div class="card-body text-center">
                            <i class="fas fa-tools fa-3x text-dark mb-3"></i>
                            <h5 class="card-title">Mantenimiento</h5>
                            <p class="card-text">Configuración del sistema.</p>
                            <a href="mantenimiento/" class="btn btn-outline-dark mt-auto">Administrar</a>
                        </div>
                    </div>
                </div>
                
                <!-- Espacio para futuro módulo -->
                <div class="col">
                    <div class="card h-100 border-light">
                        <div class="card-body text-center">
                            <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
                            <h5 class="card-title">Nuevo Módulo</h5>
                            <p class="card-text">Próximas funcionalidades.</p>
                            <a href="#" class="btn btn-outline-secondary disabled mt-auto">Próximamente</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
require_once 'includes/footer.php';
?>