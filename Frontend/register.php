<?php
require_once __DIR__ . '/../Service/UserService.php';

$resultHtml = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

            <?= $resultHtml ?>

            <form method="POST" action="" class="auth-form" novalidate>
                <div class="auth-row">
                    <div class="auth-field">
                        <label for="firstName">Nombre</label>
                        <input type="text" id="firstName" name="firstName" placeholder="Ana" minlength="2" required
                            value="<?= htmlspecialchars($_POST['firstName'] ?? '') ?>">
                    </div>
                    <div class="auth-field">
                        <label for="lastName">Apellido</label>
                        <input type="text" id="lastName" name="lastName" placeholder="Gómez" minlength="2" required
                            value="<?= htmlspecialchars($_POST['lastName'] ?? '') ?>">
                    </div>
                </div>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="tu@email.com" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

                <div class="auth-row">
                    <div class="auth-field">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" minlength="8"
                            required>
                    </div>
                    <div class="auth-field">
                        <label for="confirmPassword">Repetir contraseña</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="••••••••"
                            minlength="8" required>
                    </div>
                </div>

                <div class="auth-row">
                    <div class="auth-field">
                        <label for="phoneNumber">Teléfono <span class="optional">(opcional)</span></label>
                        <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="099 123 456"
                            value="<?= htmlspecialchars($_POST['phoneNumber'] ?? '') ?>">
                    </div>
                    <div class="auth-field">
                        <label for="idNumber">Cédula <span class="optional">(opcional)</span></label>
                        <input type="text" id="idNumber" name="idNumber" placeholder="4.123.456-7"
                            value="<?= htmlspecialchars($_POST['idNumber'] ?? '') ?>">
                    </div>
                </div>

                <label for="birthDate">Fecha de nacimiento <span class="optional">(opcional)</span></label>
                <input type="date" id="birthDate" name="birthDate"
                    value="<?= htmlspecialchars($_POST['birthDate'] ?? '') ?>">

                <label class="auth-checkbox">
                    <input type="checkbox" name="acceptedTerms" value="1" <?= isset($_POST['acceptedTerms']) ? 'checked' : '' ?>>
                    <span>Acepto los <a href="terms.php" target="_blank">términos y condiciones</a></span>
                </label>

                <button type="submit" class="btn-primary">Crear cuenta</button>
            </form>

            <p class="auth-footer">¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a></p>
        </div>
    </main>
</body>

</html>