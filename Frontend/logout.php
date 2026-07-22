<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 1. Limpieza absoluta de variables en memoria activa
$_SESSION = array();
// 2. Invalidación explícita de la cookie de rastreo en el cliente
if (ini_get("session_use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
// 3. Destrucción del entorno en el servidor
session_destroy();
// 4. Redirección forzada
header("Location: login.php");
exit();
?>