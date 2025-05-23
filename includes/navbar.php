<?php
// includes/navbar.php
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Restaurante</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL;?>index.php"><i class="fas fa-home"></i> Inicio</a>
                </li>
                
                <!-- Módulo de Clientes -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL;?>clientes/"><i class="fas fa-users"></i> Clientes</a>
                </li>
                
                <!--Módulo de Platos con submenú -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="<?php echo BASE_URL;?>" id="platosDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-utensils"></i> Platos
                    </a>
                    <ul class="dropdown-menu">
                     <!--   <li><a class="dropdown-item" href="platos/"><i class="fas fa-list"></i> Lista de Platos</a></li>-->
                        <li><a class="dropdown-item" href="<?php echo BASE_URL;?>platos/"><i class="fas fa-list"></i> Lista de Platos</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL;?>categoriasmenu/"><i class="fas fa-tags"></i> Categorías</a></li>
                    </ul>
                </li>  

               <!-- <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="< ?php echo BASE_URL;?>" id="platosDropdown" role="button" 
                    data-bs-toggle="dropdown" aria-expanded="false" onclick="event.preventDefault()">
                        <i class="fas fa-utensils"></i> Platos
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="platosDropdown">
                        <li><a class="dropdown-item" href="<php echo BASE_URL;?>platos/"><i class="fas fa-list"></i> Lista de Platos</a></li>
                        <li><a class="dropdown-item" href="<php echo BASE_URL;?>categoriasmenu/"><i class="fas fa-tags"></i> Categorías</a></li>
                    </ul>
                </li>   -->
                
                <!-- Módulo de Pedidos -->
                <li class="nav-item">
                    <a class="nav-link" href="pedidos/"><i class="fas fa-clipboard-list"></i> Pedidos</a>
                </li>
                
                <!-- Módulo de Mesas -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL;?>mesas/"><i class="fas fa-chair"></i> Mesas</a>
                </li>
                
                <!-- Módulo de Tesorería con submenú -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="tesoreriaDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cash-register"></i> Tesorería
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="pagos/"><i class="fas fa-money-bill-wave"></i> Pagos</a></li>
                        <li><a class="dropdown-item" href="cobros/"><i class="fas fa-hand-holding-usd"></i> Cobros</a></li>
                    </ul>
                </li>
                
                <!-- Módulo de Reportes con submenú -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportesDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="reportes/ventas"><i class="fas fa-chart-line"></i> Ventas</a></li>
                        <li><a class="dropdown-item" href="reportes/inventario"><i class="fas fa-boxes"></i> Inventario</a></li>
                        <li><a class="dropdown-item" href="reportes/pagos"><i class="fas fa-file-invoice-dollar"></i> Pagos</a></li>
                    </ul>
                </li>
                
                <!-- Módulo de Mantenimiento con submenú -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="mantenimientoDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cogs"></i> Mantenimiento
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header"><i class="fas fa-handshake"></i> Socios de Negocio</li>
                        <li><a class="dropdown-item" href="empleados/"><i class="fas fa-user-tie"></i> Empleados</a></li>
                        <li><a class="dropdown-item" href="clientes/"><i class="fas fa-users"></i> Clientes</a></li>
                        <li><a class="dropdown-item" href="proveedores/"><i class="fas fa-truck"></i> Proveedores</a></li>
                        <li class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="configuracion/"><i class="fas fa-tools"></i> Configuración</a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <span class="nav-link">Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">Cerrar Sesión</a>                        
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Iniciar Sesión</a>                        
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Registrarse</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>