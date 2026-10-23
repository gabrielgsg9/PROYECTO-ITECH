<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Repository/UserRepository.php';
function start_auth_session(): void
{
    if (session_status() !== PHP_SESSION_NONE)
        return;
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
function get_auth_users(): array
{
    return (new UserRepository())->all();
}
function csrf_token(): string
{
    start_auth_session();
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}
function valid_csrf(?string $token): bool
{
    start_auth_session();
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function register_auth_user(string $email, string $password, string $confirmation): array
{
    $email = strtolower(trim($email));
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Ingresá un email válido.';
    if (strlen($password) < 4)
        $errors[] = 'La contraseña debe tener al menos 4 caracteres.';
    if ($password !== $confirmation)
        $errors[] = 'Las contraseñas ingresadas no coinciden.';
    if ($errors)
        return ['user' => null, 'errors' => $errors];
    $username = (string) strstr($email, '@', true);
    try {
        $user = (new UserRepository())->create(['name' => $username, 'username' => $username, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => 'paciente']);
    } catch (DomainException $e) {
        return ['user' => null, 'errors' => ['Ya existe una cuenta con ese email o nombre de usuario.']];
    }
    return ['user' => $user, 'errors' => []];
}
function login_auth_user(array $user): void
{
    start_auth_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    if ($user['role'] === 'paciente') {
        $_SESSION['patient_id'] = $user['id'];
        $_SESSION['patient_name'] = $user['name'];
    } else {
        unset($_SESSION['patient_id'], $_SESSION['patient_name']);
    }
    unset($_SESSION['csrf_token']);
}
function authenticate_user(string $identifier, string $password): ?array
{
    $identifier = strtolower(trim($identifier));
    if ($identifier === '' || $password === '')
        return null;
    foreach (get_auth_users() as $u)
        if (($identifier === strtolower((string) ($u['username'] ?? '')) || $identifier === strtolower((string) ($u['email'] ?? ''))) && isset($u['password_hash']) && password_verify($password, $u['password_hash']))
            return $u;
    return null;
}
function get_current_patient(): ?array
{
    start_auth_session();
    $id = $_SESSION['user_id'] ?? null;
    if ($id === null)
        return null;
    $user = (new UserRepository())->findById((int) $id);
    if (!$user) {
        $_SESSION = [];
        return null;
    }
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    if ($user['role'] !== 'paciente') {
        unset($_SESSION['patient_id'], $_SESSION['patient_name']);
        return null;
    }
    $_SESSION['patient_id'] = $user['id'];
    $_SESSION['patient_name'] = $user['name'];
    return ['id' => $user['id'], 'nombre' => $user['name'], 'email' => $user['email'] ?? ''];
}
function redirect_for_role(string $role): never
{
    $dest = match ($role) { 'administrador' => 'admin/dashboard.php', 'recepcionista' => 'recepcion/dashboard.php', 'tecnico' => 'tecnico/dashboard.php', default => 'home.php'};
    header('Location: ' . $dest);
    exit;
}
function require_role(array $roles, string $loginUrl): void
{
    start_auth_session();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $loginUrl);
        exit;
    }
    $user = (new UserRepository())->findById((int) $_SESSION['user_id']);
    if (!$user) {
        $_SESSION = [];
        header('Location: ' . $loginUrl);
        exit;
    }
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/../access-denied.php';
        exit;
    }
}
