<?php
require_once __DIR__ . '/../Helpers/auth.php';

require_role(['administrador'], '../login.php');
$userName = htmlspecialchars((string) $_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración — Clínica Imagen</title>
    <link rel="stylesheet" href="../STYLES/login.css">
</head>
<body>
    <main class="auth-wrapper">
        <section class="auth-card dashboard-card">
            <p class="role-label">Administrador</p>
            <h1>Panel de administración</h1>
            <p class="auth-subtitle">Bienvenida, <?= $userName ?>. Tu sesión tiene permisos de administración.</p>
            <a class="btn-primary button-link" href="../logout.php">Cerrar sesión</a>
        </section>
    </main>
</body>
</html>
