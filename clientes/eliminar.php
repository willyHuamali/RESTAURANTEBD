<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $cliente_id = intval($_POST['id']);
    
    try {
        $stmt = $pdo->prepare("UPDATE clientes SET activo = FALSE WHERE cliente_id = :cliente_id");
        $stmt->execute([':cliente_id' => $cliente_id]);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Solicitud no válida']);
}