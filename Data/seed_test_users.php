<?php
declare(strict_types=1);

/**
 * Crea las cuentas de prueba para los roles del inicio de sesion activo.
 * Puede ejecutarse varias veces sin duplicar ni modificar usuarios existentes.
 */
const USERS_FILE = __DIR__ . '/users.json';

$testUsers = [
    [
        'name' => 'Usuario de Prueba - Administrador',
        'username' => 'admin.test',
        'email' => 'administrador.test@example.com',
        'password' => 'AdminPrueba2026!',
        'role' => 'administrador',
    ],
    [
        'name' => 'Usuario de Prueba - Recepcionista',
        'username' => 'recepcion.test',
        'email' => 'recepcionista.test@example.com',
        'password' => 'RecepcionPrueba2026!',
        'role' => 'recepcionista',
    ],
];

$contents = file_get_contents(USERS_FILE);
if ($contents === false) {
    throw new RuntimeException('No se pudo leer ' . USERS_FILE);
}

$users = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
if (!is_array($users)) {
    throw new RuntimeException('El archivo de usuarios debe contener un arreglo JSON.');
}

$existingIdentifiers = [];
$maxId = 0;
foreach ($users as $user) {
    if (!is_array($user)) {
        throw new RuntimeException('Se encontro un usuario con formato invalido.');
    }

    $maxId = max($maxId, (int) ($user['id'] ?? 0));
    foreach (['username', 'email'] as $field) {
        if (isset($user[$field])) {
            $existingIdentifiers[strtolower((string) $user[$field])] = true;
        }
    }
}

$created = [];
$hasChanges = false;
foreach ($testUsers as $testUser) {
    $username = strtolower($testUser['username']);
    $email = strtolower($testUser['email']);

    if (isset($existingIdentifiers[$username]) || isset($existingIdentifiers[$email])) {
        $created[] = $testUser['role'] . ': ya existia';
        continue;
    }

    $users[] = [
        'id' => ++$maxId,
        'name' => $testUser['name'],
        'username' => $testUser['username'],
        'email' => $testUser['email'],
        'password_hash' => password_hash($testUser['password'], PASSWORD_DEFAULT),
        'role' => $testUser['role'],
    ];
    $existingIdentifiers[$username] = true;
    $existingIdentifiers[$email] = true;
    $created[] = $testUser['role'] . ': creado';
    $hasChanges = true;
}

if (!$hasChanges) {
    echo implode(PHP_EOL, $created) . PHP_EOL;
    exit(0);
}

$json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
$temporaryFile = USERS_FILE . '.tmp';
if (file_put_contents($temporaryFile, $json, LOCK_EX) === false || !rename($temporaryFile, USERS_FILE)) {
    @unlink($temporaryFile);
    throw new RuntimeException('No se pudo guardar el archivo de usuarios.');
}

echo implode(PHP_EOL, $created) . PHP_EOL;
