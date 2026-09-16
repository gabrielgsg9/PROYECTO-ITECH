<?php
require_once __DIR__ . '/Helpers/auth.php';

start_auth_session();
$errors = [];
$email = '';
$registered = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $errors[] = 'La solicitud no es válida. Intentá nuevamente.';
    } else {
        try {
            $result = register_auth_user(
                $email,
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['confirmPassword'] ?? '')
            );
            $errors = $result['errors'];
            $registered = $result['user'] !== null;
        } catch (Throwable $exception) {
            $errors[] = 'No fue posible crear la cuenta. Intentá más tarde.';
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
    <title>Crear cuenta — Clínica Imagen</title>
    <link rel="stylesheet" href="STYLES/register.css">
</head>
<body>
    <main class="auth-wrapper">
        <div class="auth-card">
            <h1>Creá tu cuenta</h1>
            <p class="auth-subtitle">Registrate como paciente</p>

            <?php if ($registered): ?>
                <div class="alert alert-ok" role="status">
                    Cuenta creada correctamente. Ya podés <a href="login.php">iniciar sesión</a>.
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="tu@email.com" required
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email">

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" minlength="4" required autocomplete="new-password">

                <label for="confirmPassword">Repetir contraseña</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="••••••••" minlength="4" required autocomplete="new-password">

                <button type="submit" class="btn-primary">Crear cuenta</button>
            </form>

            <p class="auth-footer">¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a></p>
        </div>
    </main>
</body>
</html>
