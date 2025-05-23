<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$base_url = '/restauranteBD/pedidos/';

// Obtener datos necesarios para los select
try {
    // Clientes
    $stmt_clientes = $pdo->query("SELECT cliente_id, nombre, apellido FROM clientes WHERE activo = TRUE ORDER BY apellido, nombre");
    $clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
    
    // Mesas disponibles
    $stmt_mesas = $pdo->query("SELECT mesa_id, numero, capacidad FROM mesas WHERE estado = 'disponible' ORDER BY numero");
    $mesas = $stmt_mesas->fetchAll(PDO::FETCH_ASSOC);
    
    // Empleados
    $stmt_empleados = $pdo->query("SELECT empleado_id, nombre, apellido FROM empleados WHERE activo = TRUE ORDER BY apellido, nombre");
    $empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

    // Platillos disponibles por categoría
    $stmt_categorias = $pdo->query("SELECT categoria_id, nombre FROM CategoriasMenu ORDER BY nombre");
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

    $platillos_por_categoria = [];
    foreach ($categorias as $categoria) {
        $stmt = $pdo->prepare("SELECT p.platillo_id, p.nombre, p.precio 
                             FROM platillos p
                             WHERE p.categoria_id = ? AND p.activo = TRUE
                             ORDER BY p.nombre");
        $stmt->execute([$categoria['categoria_id']]);
        $platillos_por_categoria[$categoria['nombre']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch(PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Datos de la orden
        $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
        $mesa_id = !empty($_POST['mesa_id']) ? (int)$_POST['mesa_id'] : null;
        $empleado_id = !empty($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;
        $notas = !empty($_POST['notas']) ? trim($_POST['notas']) : null;
        $usuario_id = $_SESSION['usuario_id'];
        
        // Insertar la orden
        $stmt_orden = $pdo->prepare("INSERT INTO ordenes 
            (cliente_id, mesa_id, empleado_id, usuario_id, notas) 
            VALUES (:cliente_id, :mesa_id, :empleado_id, :usuario_id, :notas)");
        
        $stmt_orden->execute([
            ':cliente_id' => $cliente_id,
            ':mesa_id' => $mesa_id,
            ':empleado_id' => $empleado_id,
            ':usuario_id' => $usuario_id,
            ':notas' => $notas
        ]);
        
        $orden_id = $pdo->lastInsertId();
        
        // Insertar detalles de la orden
        $neto = 0;
        $igv = 0;
        $total = 0;
        
        foreach ($_POST['platillos'] as $detalle) {
            $platillo_id = (int)$detalle['platillo_id'];
            $cantidad = (int)$detalle['cantidad'];
            $instrucciones = !empty($detalle['instrucciones']) ? trim($detalle['instrucciones']) : null;
            
            // Obtener precio del platillo
            $stmt_precio = $pdo->prepare("SELECT precio FROM platillos WHERE platillo_id = ?");
            $stmt_precio->execute([$platillo_id]);
            $precio_unitario = $stmt_precio->fetchColumn();
            
            $subtotal = $precio_unitario * $cantidad;
            $igvdet = $subtotal * 0.18; // 18% IGV
            $totaldet = $subtotal + $igvdet;
            
            $neto += $subtotal;
            $igv += $igvdet;
            $total += $totaldet;
            
            $stmt_detalle = $pdo->prepare("INSERT INTO detallesorden 
                (orden_id, platillo_id, cantidad, precio_unitario, igvdet, subtotal, totaldet, instrucciones_especiales) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt_detalle->execute([
                $orden_id, $platillo_id, $cantidad, $precio_unitario, 
                $igvdet, $subtotal, $totaldet, $instrucciones
            ]);
        }
        
        // Actualizar totales de la orden
        $stmt_update = $pdo->prepare("UPDATE ordenes SET 
            neto_ord = ?, igv_ord = ?, total_ord = ? 
            WHERE orden_id = ?");
        
        $stmt_update->execute([$neto, $igv, $total, $orden_id]);
        
        // Actualizar estado de la mesa si se asignó
        if ($mesa_id) {
            $stmt_mesa = $pdo->prepare("UPDATE mesas SET estado = 'ocupada' WHERE mesa_id = ?");
            $stmt_mesa->execute([$mesa_id]);
        }
        
        $pdo->commit();
        
        // Redirigir con mensaje de éxito
        header("Location: $base_url?success=Pedido registrado correctamente");
        exit();
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = "Error al registrar el pedido: " . $e->getMessage();
    }
}
?>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Columna izquierda - Categorías y Productos -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Menú</h5>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" class="form-control" placeholder="Buscar producto..." id="buscarProducto">
                        <button class="btn btn-light" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- Pestañas de categorías -->
                        <ul class="nav nav-tabs" id="categoriasTab" role="tablist">
                            <?php foreach(array_keys($platillos_por_categoria) as $index => $categoria): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                                            id="<?= str_replace(' ', '-', strtolower($categoria)) ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?= str_replace(' ', '-', strtolower($categoria)) ?>" 
                                            type="button" role="tab">
                                        <?= htmlspecialchars($categoria) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- Contenido de categorías -->
                        <div class="tab-content p-3" id="categoriasTabContent" style="max-height: 65vh; overflow-y: auto;">
                            <?php foreach($platillos_por_categoria as $categoria => $platillos): ?>
                                <div class="tab-pane fade <?= array_key_first($platillos_por_categoria) === $categoria ? 'show active' : '' ?>" 
                                     id="<?= str_replace(' ', '-', strtolower($categoria)) ?>" 
                                     role="tabpanel">
                                    <div class="row row-cols-2 g-3">
                                        <?php foreach($platillos as $platillo): ?>
                                            <div class="col">
                                                <button type="button" class="btn btn-outline-primary w-100 h-100 py-3 btn-agregar-platillo"
                                                        data-platillo-id="<?= $platillo['platillo_id'] ?>"
                                                        data-precio="<?= $platillo['precio'] ?>"
                                                        data-nombre="<?= htmlspecialchars($platillo['nombre']) ?>">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold"><?= htmlspecialchars($platillo['nombre']) ?></span>
                                                        <span class="text-muted small">S/ <?= number_format($platillo['precio'], 2) ?></span>
                                                    </div>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Columna derecha - Detalle del pedido -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detalle del Pedido</h5>
                        <div class="d-flex">
                            <div class="me-3">
                                <span class="me-2"><i class="far fa-clock"></i></span>
                                <span id="hora-actual"><?= date('H:i') ?></span>
                            </div>
                            <div>
                                <span class="me-2"><i class="far fa-calendar-alt"></i></span>
                                <span id="fecha-actual"><?= date('d M Y') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <form method="post" id="formPedido">
                        <div class="row g-0">
                            <!-- Información del pedido -->
                            <div class="col-md-4 p-3 border-end">
                                <div class="mb-3">
                                    <label for="cliente_id" class="form-label small fw-bold">Cliente</label>
                                    <select class="form-select form-select-sm" id="cliente_id" name="cliente_id">
                                        <option value="">Seleccione cliente</option>
                                        <?php foreach($clientes as $cliente): ?>
                                            <option value="<?= $cliente['cliente_id'] ?>">
                                                <?= htmlspecialchars($cliente['apellido'] . ', ' . $cliente['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="mesa_id" class="form-label small fw-bold">Mesa</label>
                                    <select class="form-select form-select-sm" id="mesa_id" name="mesa_id">
                                        <option value="">Sin mesa</option>
                                        <?php foreach($mesas as $mesa): ?>
                                            <option value="<?= $mesa['mesa_id'] ?>" data-capacidad="<?= $mesa['capacidad'] ?>">
                                                Mesa <?= htmlspecialchars($mesa['numero']) ?> (<?= $mesa['capacidad'] ?> pers.)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="empleado_id" class="form-label small fw-bold">Mesero</label>
                                    <select class="form-select form-select-sm" id="empleado_id" name="empleado_id">
                                        <option value="">Seleccione mesero</option>
                                        <?php foreach($empleados as $empleado): ?>
                                            <option value="<?= $empleado['empleado_id'] ?>">
                                                <?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notas" class="form-label small fw-bold">Notas</label>
                                    <textarea class="form-control form-control-sm" id="notas" name="notas" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <!-- Lista de productos -->
                            <div class="col-md-5 p-3 border-end">
                                <div class="table-responsive" style="max-height: 45vh; overflow-y: auto;">
                                    <table class="table table-sm">
                                        <thead class="sticky-top bg-light">
                                            <tr>
                                                <th width="5%">Cant</th>
                                                <th width="55%">Producto</th>
                                                <th width="20%">Precio</th>
                                                <th width="20%">Total</th>
                                                <th width="5%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="detalles-pedido">
                                            <tr id="mensaje-vacio">
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    Seleccione productos del menú
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-3 pt-2 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small">Subtotal:</span>
                                        <span class="fw-bold" id="subtotal">S/ 0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small">IGV (18%):</span>
                                        <span class="fw-bold" id="igv">S/ 0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center h5 mt-2">
                                        <span>Total:</span>
                                        <span class="fw-bold text-primary" id="total">S/ 0.00</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="col-md-3 p-3">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg mb-2">
                                        <i class="fas fa-check-circle me-2"></i> Guardar
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-secondary mb-2" id="btn-limpiar">
                                        <i class="fas fa-broom me-2"></i> Limpiar
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-primary mb-2">
                                        <i class="fas fa-print me-2"></i> Imprimir
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-danger mb-2">
                                        <i class="fas fa-times-circle me-2"></i> Cancelar
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-info mb-2">
                                        <i class="fas fa-utensils me-2"></i> Cocina
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template para fila de producto (oculto) -->
<table class="d-none">
    <tbody>
        <tr class="detalle-platillo" id="platillo-template">
            <td>
                <input type="number" class="form-control form-control-sm cantidad" 
                       name="platillos[{{index}}][cantidad]" min="1" value="1" required>
            </td>
            <td>
                <input type="hidden" name="platillos[{{index}}][platillo_id]" value="{{platillo_id}}">
                {{nombre}}
                <div>
                    <input type="text" class="form-control form-control-sm mt-1" 
                           name="platillos[{{index}}][instrucciones]" placeholder="Instrucciones">
                </div>
            </td>
            <td class="precio-unitario">S/ {{precio}}</td>
            <td class="total-item">S/ {{precio}}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-platillo">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>

<script>
$(document).ready(function() {
    let platilloIndex = 0;
    const template = $('#platillo-template').html();
    
    // Actualizar hora y fecha
    function actualizarHora() {
        const ahora = new Date();
        $('#hora-actual').text(ahora.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}));
        $('#fecha-actual').text(ahora.toLocaleDateString('es-ES', {weekday: 'short', day: 'numeric', month: 'short'}));
    }
    setInterval(actualizarHora, 1000);
    
    // Agregar platillo al hacer clic en los botones
    $(document).on('click', '.btn-agregar-platillo', function() {
        const platilloId = $(this).data('platillo-id');
        const precio = parseFloat($(this).data('precio'));
        const nombre = $(this).data('nombre');
        
        // Verificar si el platillo ya está en la lista
        const platilloExistente = $(`input[name^="platillos"][value="${platilloId}"]`).closest('tr');
        
        if (platilloExistente.length > 0) {
            // Incrementar cantidad si ya existe
            const cantidadInput = platilloExistente.find('.cantidad');
            cantidadInput.val(parseInt(cantidadInput.val()) + 1);
            calcularTotales();
            return;
        }
        
        // Agregar nuevo platillo
        const html = template
            .replace(/{{index}}/g, platilloIndex)
            .replace(/{{platillo_id}}/g, platilloId)
            .replace(/{{nombre}}/g, nombre)
            .replace(/{{precio}}/g, precio.toFixed(2));
        
        $('#mensaje-vacio').remove();
        $('#detalles-pedido').append(html);
        
        platilloIndex++;
        calcularTotales();
    });
    
    // Eliminar platillo
    $(document).on('click', '.btn-eliminar-platillo', function() {
        $(this).closest('tr').remove();
        calcularTotales();
        
        if ($('.detalle-platillo').length === 0) {
            $('#detalles-pedido').html('<tr id="mensaje-vacio"><td colspan="5" class="text-center text-muted py-4">Seleccione productos del menú</td></tr>');
        }
    });
    
    // Limpiar todo el pedido
    $('#btn-limpiar').click(function() {
        $('#detalles-pedido').html('<tr id="mensaje-vacio"><td colspan="5" class="text-center text-muted py-4">Seleccione productos del menú</td></tr>');
        $('#cliente_id, #mesa_id, #empleado_id').val('');
        $('#notas').val('');
        platilloIndex = 0;
        calcularTotales();
    });
    
    // Calcular totales cuando cambia cantidad
    $(document).on('change', '.cantidad', function() {
        calcularTotales();
    });
    
    // Función para calcular totales
    function calcularTotales() {
        let subtotal = 0;
        
        $('.detalle-platillo').each(function() {
            const cantidad = parseInt($(this).find('.cantidad').val()) || 0;
            const precio = parseFloat($(this).find('.precio-unitario').text().replace('S/ ', ''));
            const totalItem = cantidad * precio;
            
            $(this).find('.total-item').text('S/ ' + totalItem.toFixed(2));
            subtotal += totalItem;
        });
        
        const igv = subtotal * 0.18;
        const total = subtotal + igv;
        
        $('#subtotal').text('S/ ' + subtotal.toFixed(2));
        $('#igv').text('S/ ' + igv.toFixed(2));
        $('#total').text('S/ ' + total.toFixed(2));
    }
    
    // Validar formulario antes de enviar
    $('#formPedido').submit(function(e) {
        if ($('.detalle-platillo').length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Pedido vacío',
                text: 'Debe agregar al menos un producto al pedido',
            });
            return false;
        }
        
        return true;
    });
    
    // Buscar productos
    $('#buscarProducto').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        if (searchTerm === '') {
            $('.btn-agregar-platillo').show();
            return;
        }
        
        $('.btn-agregar-platillo').each(function() {
            const nombre = $(this).data('nombre').toLowerCase();
            $(this).toggle(nombre.includes(searchTerm));
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>