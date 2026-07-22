<?php
require_once __DIR__ . '/../Service/UserService.php';

$resultHtml = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = new UserService();
    $resultHtml = $service->loginUser(
        $_POST['email'] ?? '',
        $_POST['password'] ?? ''
    );
}
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
            <p class="auth-subtitle">Acceder a tu portal <del></del> paciente</p>

            <?= $resultHtml ?>

            <form method="POST" action="" class="auth-form" novalidate>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="tu@email.com" required>

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required minlength="8">

                <button type="submit" class="btn-primary">Ingresar</button>
            </form>

            <p class="auth-footer">¿No tenés cuenta? <a href="register.php">Registrate</a></p>
        </div>
    </main>
</body>
</html>