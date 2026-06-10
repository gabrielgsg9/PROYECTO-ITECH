<?php
require_once '../Service/UsuarioService.php';


$resultadoHtml = "";

// Si viene por POST, invocamos a la capa de Lógica (Servicio)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service = new UsuarioService();
    $resultadoHtml = $service->registrarUsuario($_POST['usuario'] ?? '', $_POST['pass1'] ?? '', $_POST['pass2'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Profesional en Capas</title>
    <link rel="stylesheet" href="STYLES/registro.css">

</head>
<body>

    <div class="form-card">
        <h2>Crear Cuenta</h2>
        
        <?php echo $resultadoHtml; ?>

        <form action="registro.php" method="POST">
            <label>Nombre de Usuario:</label>
            <input type="text" name="usuario" placeholder="Ej: joel123">

            <label>Contraseña:</label>
            <input type="password" name="pass1" placeholder="Mínimo 4 caracteres">

            <label>Repetir Contraseña:</label>
            <input type="password" name="pass2" placeholder="Confirma tu contraseña">

            <button type="submit">Registrar Usuario</button>
        </form>
    </div>

</body>
</html>
