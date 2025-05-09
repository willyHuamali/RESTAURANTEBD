<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirectIfNotLoggedIn();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$cliente_id = $_GET['id'];

// Verificar si el cliente existe y está activo
try {
    $query = "SELECT * FROM clientes WHERE cliente_id = :cliente_id AND activo = TRUE";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':cliente_id' => $cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        header("Location: index.php?error=Cliente no encontrado o ya eliminado");
        exit();
    }
} catch(PDOException $e) {
    die("Error al verificar cliente: " . $e->getMessage());
}

// Verificar si tiene reservaciones u órdenes relacionadas
try {
    // Verificar reservaciones
    $query_reservas = "SELECT COUNT(*) as total FROM reservaciones WHERE cliente_id = :cliente_id";
    $stmt = $pdo->prepare($query_reservas);
    $stmt->execute([':cliente_id' => $cliente_id]);
    $reservas = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar órdenes
    $query_ordenes = "SELECT COUNT(*) as total FROM ordenes WHERE cliente_id = :cliente_id";
    $stmt = $pdo->prepare($query_ordenes);
    $stmt->execute([':cliente_id' => $cliente_id]);
    $ordenes = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reservas['total'] > 0 || $ordenes['total'] > 0) {
        header("Location: index.php?error=No se puede eliminar el cliente porque tiene reservaciones u órdenes asociadas");
        exit();
    }
} catch(PDOException $e) {
    die("Error al verificar relaciones: " . $e->getMessage());
}

// Si no hay relaciones, proceder con la eliminación (borrado lógico)
try {
    $query = "UPDATE clientes SET activo = FALSE WHERE cliente_id = :cliente_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':cliente_id' => $cliente_id]);

    header("Location: index.php?success=Cliente eliminado correctamente");
    exit();
} catch(PDOException $e) {
    die("Error al eliminar cliente: " . $e->getMessage());
}
?>