<?php 
declare(strict_types=1); 
 
// Archivo donde se almacenan los usuarios.
const AUTH_USERS_FILE = __DIR__ . '/../../Data/users.json'; 
 
// Inicia la sesión y configura su cookie de forma segura.
function start_auth_session(): void 
{ 
    if (session_status() !== PHP_SESSION_NONE) { 
        return; 
    } 
 
    session_set_cookie_params([ 
        'lifetime' => 0, 
        'path' => '/', 
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), 
        'httponly' => true, 
        'samesite' => 'Lax', 
    ]); 
    session_start(); 
} 
 
// Lee users.json y convierte su contenido JSON en un array PHP.
function get_auth_users(): array 
{ 
    $contents = file_get_contents(AUTH_USERS_FILE); 
    if ($contents === false) { 
        throw new RuntimeException('No se pudo leer el archivo de usuarios.'); 
    } 
 
    $users = json_decode($contents, true, 512, JSON_THROW_ON_ERROR); 
    if (!is_array($users)) { 
        throw new RuntimeException('El archivo de usuarios no tiene un formato válido.'); 
    } 
 
    return $users; 
} 
 
// Comprueba username/email y contraseña. Devuelve el usuario si es válido.
function authenticate_user(string $identifier, string $password): ?array 
{ 
    $identifier = strtolower(trim($identifier)); 
    if ($identifier === '' || $password === '') { 
        return null; 
    } 
 
    foreach (get_auth_users() as $user) { 
        $username = strtolower((string) ($user['username'] ?? '')); 
        $email = strtolower((string) ($user['email'] ?? '')); 
 
        // Se permite iniciar sesión con username o email.
        if (($identifier === $username || $identifier === $email)
            && isset($user['password_hash'])
            && password_verify($password, $user['password_hash'])) { 
            return $user; 
        } 
    } 
 
    return null; 
} 
 
// Envía al usuario al dashboard correspondiente según su rol.
function redirect_for_role(string $role): never 
{ 
    $destination = $role === 'administrador' 
        ? 'admin/dashboard.php' 
        : 'recepcion/dashboard.php'; 
 
    header('Location: ' . $destination); 
    exit; 
} 
 
// Protege páginas verificando que exista una sesión y que el rol tenga permiso.
function require_role(array $allowedRoles, string $loginUrl): void 
{ 
    start_auth_session(); 
 
    // Si no hay sesión válida, volver al login.
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) { 
        header('Location: ' . $loginUrl); 
        exit; 
    } 
 
    // Si el usuario está logueado pero no tiene el rol permitido, devolver 403.
    if (!in_array($_SESSION['role'], $allowedRoles, true)) { 
        http_response_code(403); 
        require __DIR__ . '/../access-denied.php'; 
        exit; 
    } 
}