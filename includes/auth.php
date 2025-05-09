<?php
// includes/auth.php
session_start();

function registrarUsuario($pdo, $username, $email, $password, $empleado_id = null) {
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO Usuarios (username, password_hash, email, empleado_id) 
            VALUES (:username, :password_hash, :email, :empleado_id)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':empleado_id', $empleado_id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

function login($pdo, $username, $password) {
    $sql = "SELECT * FROM Usuarios WHERE username = :username AND activo = TRUE";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Actualizar último login
        $update_sql = "UPDATE Usuarios SET ultimo_login = NOW() WHERE usuario_id = :usuario_id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindParam(':usuario_id', $user['usuario_id'], PDO::PARAM_INT);
        $update_stmt->execute();
        
        $_SESSION['user_id'] = $user['usuario_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
        
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}


?>