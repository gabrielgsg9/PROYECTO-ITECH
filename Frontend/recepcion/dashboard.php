<?php
require_once __DIR__ . '/../Helpers/auth.php';

require_role(['administrador', 'recepcionista'], '../login.php');
$userName = htmlspecialchars((string) $_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars((string) $_SESSION['role'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción — Clínica Imagen</title>
    <link rel="stylesheet" href="../STYLES/login.css">
</head>
<body>
    <main class="auth-wrapper">
        <section class="auth-card dashboard-card">
            <p class="role-label"><?= ucfirst($role) ?></p>
            <h1>Panel de recepción</h1>
            <p class="auth-subtitle">Bienvenida, <?= $userName ?>. Tu sesión tiene permisos de recepción.</p>
            <a class="btn-primary button-link" href="../logout.php">Cerrar sesión</a>
        </section>
    </main>
</body>
</html>
