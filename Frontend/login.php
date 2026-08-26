<?php
require_once __DIR__ . '/Helpers/auth.php';

start_auth_session();

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    redirect_for_role((string) $_SESSION['role']);
}

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $errors[] = 'La solicitud no es válida. Intentá nuevamente.';
    } elseif ($identifier === '' || $password === '') {
        $errors[] = 'Completá el usuario o email y la contraseña.';
    } else {
        try {
            $user = authenticate_user($identifier, $password);
        } catch (Throwable $exception) {
            $user = null;
            $errors[] = 'No fue posible validar las credenciales. Intentá más tarde.';
        }

        if ($user === null && $errors === []) {
            $errors[] = 'El usuario o la contraseña son incorrectos.';
        }

        if ($user !== null) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            unset($_SESSION['csrf_token']);
            redirect_for_role($user['role']);
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — Clínica Imagen</title>
    <link rel="stylesheet" href="STYLES/login.css">
</head>

<body>
    <main class="auth-wrapper">
        <div class="auth-card">
            <h1>Iniciar sesión</h1>
            <p class="auth-subtitle">Accedé al sistema de Clínica Imagen</p>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <label for="identifier">Usuario o email</label>
                <input type="text" id="identifier" name="identifier" placeholder="usuario o tu@email.com" required
                    value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>" autocomplete="username">

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">

                <button type="submit" class="btn-primary">Ingresar</button>
            </form>

            <p class="auth-footer">Usá las credenciales asignadas por la clínica.</p>
        </div>
    </main>
</body>

</html>
