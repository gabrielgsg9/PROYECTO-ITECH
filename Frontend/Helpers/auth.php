<?php
declare(strict_types=1);

const AUTH_USERS_FILE = __DIR__ . '/../../Data/users.json';

// iniciar sesion y cookie
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

function register_auth_user(string $email, string $password, string $passwordConfirmation): array
{
    $email = strtolower(trim($email));
    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresá un email válido.';
    }
    if (strlen($password) < 4) {
        $errors[] = 'La contraseña debe tener al menos 4 caracteres.';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'Las contraseñas ingresadas no coinciden.';
    }
    if ($errors !== []) {
        return ['user' => null, 'errors' => $errors];
    }

    $lock = fopen(AUTH_USERS_FILE . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('No se pudo bloquear el archivo de usuarios.');
    }

    try {
        $users = get_auth_users();
        $maxId = 0;
        $username = strstr($email, '@', true);
        foreach ($users as $existingUser) {
            $maxId = max($maxId, (int) ($existingUser['id'] ?? 0));
            $existingEmail = strtolower((string) ($existingUser['email'] ?? ''));
            $existingUsername = strtolower((string) ($existingUser['username'] ?? ''));
            if ($existingEmail === $email || $existingUsername === $username) {
                return ['user' => null, 'errors' => ['Ya existe una cuenta con ese email o nombre de usuario.']];
            }
        }

        $user = [
            'id' => $maxId + 1,
            'name' => $username,
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'paciente',
        ];
        $users[] = $user;

        $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
        $temporaryFile = AUTH_USERS_FILE . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (file_put_contents($temporaryFile, $json, LOCK_EX) === false || !rename($temporaryFile, AUTH_USERS_FILE)) {
            @unlink($temporaryFile);
            throw new RuntimeException('No se pudo guardar el archivo de usuarios.');
        }

        return ['user' => $user, 'errors' => []];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
function authenticate_user(string $identifier, string $password): ?array
{
    $identifier = strtolower(trim($identifier));
    if ($identifier === '' || $password === '') {
        return null;
    }

    foreach (get_auth_users() as $user) {
        $username = strtolower((string) ($user['username'] ?? ''));
        $email = strtolower((string) ($user['email'] ?? ''));

        //para  permite iniciar sesión con username o email.
        if (
            ($identifier === $username || $identifier === $email)
            && isset($user['password_hash'])
            && password_verify($password, $user['password_hash'])
        ) {
            return $user;
        }
    }

    return null;
}

function get_current_patient(): ?array
{
    start_auth_session();
    $userId = $_SESSION['user_id'] ?? null;

    if (($_SESSION['role'] ?? '') !== 'paciente' || $userId === null) {
        return null;
    }

    foreach (get_auth_users() as $user) {
        if ((int) ($user['id'] ?? 0) === (int) $userId && ($user['role'] ?? '') === 'paciente') {
            return [
                'id' => $user['id'],
                'nombre' => $user['name'],
                'email' => $user['email'],
            ];
        }
    }

    return null;
}

//  usuario al dashboard correspondiente segun rol.
function redirect_for_role(string $role): never
{
    $destination = match ($role) {
        'administrador' => 'admin/dashboard.php',
        'recepcionista' => 'recepcion/dashboard.php',
        default => 'home.php',
    };

    header('Location: ' . $destination);
    exit;
}

//verificar q haya sesion y rol permitido 
function require_role(array $allowedRoles, string $loginUrl): void
{
    start_auth_session();

    // Si sesion no valid volver al login.
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header('Location: ' . $loginUrl);
        exit;
    }

    // usuario logueao + rol no permitido = 403
    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        http_response_code(403);
        require __DIR__ . '/../access-denied.php';
        exit;
    }
}
