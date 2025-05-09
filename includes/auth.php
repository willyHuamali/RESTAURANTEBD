<?php
// includes/auth.php

// Iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Registra un nuevo usuario con validación de parámetros
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param string $username Nombre de usuario
 * @param string $email Correo electrónico
 * @param string $password Contraseña
 * @param int|null $empleado_id ID de empleado asociado (opcional)
 * @return bool True si el registro fue exitoso, false en caso contrario
 * @throws PDOException Si ocurre un error en la base de datos
 */
function registrarUsuario($pdo, $username, $email, $password, $empleado_id = null) {
    // Validación de parámetros
    if (empty(trim($username)) || empty(trim($email)) || empty(trim($password))) {
        throw new InvalidArgumentException("Todos los campos son obligatorios");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("El formato del email no es válido");
    }

    if (strlen($password) < 7) {
        throw new InvalidArgumentException("La contraseña debe tener al menos 8 caracteres");
    }

    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE username = :username OR email = :email");
    $stmt->execute([':username' => $username, ':email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        throw new RuntimeException("El nombre de usuario o email ya está en uso");
    }

    // Crear hash de contraseña
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    if ($password_hash === false) {
        throw new RuntimeException("Error al generar el hash de la contraseña");
    }

    // Insertar nuevo usuario
    $sql = "INSERT INTO Usuarios (username, password_hash, email, empleado_id) 
            VALUES (:username, :password_hash, :email, :empleado_id)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    
    if ($empleado_id !== null) {
        $stmt->bindParam(':empleado_id', $empleado_id, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(':empleado_id', null, PDO::PARAM_NULL);
    }
    
    return $stmt->execute();
}

/**
 * Autentica a un usuario
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param string $username Nombre de usuario
 * @param string $password Contraseña
 * @return bool True si la autenticación fue exitosa, false en caso contrario
 */
function login($pdo, $username, $password) {
    // Validación básica
    if (empty(trim($username)) || empty(trim($password))) {
        return false;
    }

    // Consulta preparada para evitar inyección SQL
    $sql = "SELECT * FROM Usuarios WHERE username = :username AND activo = TRUE";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    
    if (!$stmt->execute()) {
        return false;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerar ID de sesión para prevenir fixation
        session_regenerate_id(true);
        
        // Actualizar último login
        $update_sql = "UPDATE Usuarios SET ultimo_login = NOW() WHERE usuario_id = :usuario_id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindParam(':usuario_id', $user['usuario_id'], PDO::PARAM_INT);
        $update_stmt->execute();
        
        // Establecer datos de sesión
        $_SESSION['user_id'] = $user['usuario_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    return false;
}

/**
 * Verifica si el usuario está autenticado
 * 
 * @return bool True si el usuario está autenticado, false en caso contrario
 */
function isLoggedIn() {
    // Verificación básica de sesión
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Verificación de seguridad adicional
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        return false;
    }
    
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        return false;
    }
    
    // Tiempo de inactividad (30 minutos)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        return false;
    }
    
    // Actualizar tiempo de última actividad
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Redirige al usuario a la página de login si no está autenticado
 * 
 * @param string $redirectUrl URL a la que redirigir (opcional)
 */
function redirectIfNotLoggedIn($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        // Guardar la URL actual para redirigir después del login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        header("Location: " . $redirectUrl);
        exit();
    }
}

/**
 * Cierra la sesión del usuario
 */
function logout() {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Borrar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}
?>