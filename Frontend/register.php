<?php
require_once '../Service/UserService.php';

$resultHtml = "";

// Si viene por POST, invocamos a la capa de Lógica (Servicio)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service = new UserService();
    $resultHtml = $service->registerUser(
        $_POST['email'] ?? '',
        $_POST['password'] ?? '',
        $_POST['confirmPassword'] ?? ''
    );
}
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

            <?php echo $resultHtml; ?>

            <form action="register.php" method="POST" class="auth-form" novalidate>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="tu@email.com" required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" minlength="4" required>

                <label for="confirmPassword">Repetir contraseña</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="••••••••" minlength="4"
                    required>

                <button type="submit" class="btn-primary">Crear cuenta</button>
            </form>

            <p class="auth-footer">¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a></p>
        </div>
    </main>

</body>

</html>