<?php
require_once '../Repository/UserRepository.php';
require_once '../Domain/User.php';
class UserService
{
    private $repository;
    public function __construct()
    {
        $this->repository = new UserRepository();
    }
    public function registerUser($nombre, $pass1, $pass2, $rol = 'paciente')
    {
        $errores = [];
        if (empty($nombre) || empty($pass1)) {
            $errores[] = "Todos los campos son requeridos.";
            return $this->generarHtmlErrores($errores);
        }
        if ($pass1 !== $pass2) {
            $errores[] = "Las contraseñas ingresadas no coinciden.";
        }
        if (strlen($pass1) < 4) {
            $errores[] = "La contraseña debe tener una longitud mínima de 4 caracteres.";
        }
        if ($this->repository->findByName($nombre) !== null) {
            $errores[] = "El nombre de usuario o credencial '$nombre' ya existe en el
sistema.";
        }
        if (count($errores) > 0) {
            return $this->generarHtmlErrores($errores);
        }
        $nuevoUsuario = new User($nombre, $pass1, $rol);
        try {
            $this->repository->save($nuevoUsuario);

            return "<div style='background:#d4edda; color:#155724; padding:15px; margin-
bottom:15px; border-radius:5px;'>

¡Usuario registrado correctamente en la base de datos de
Clínica Imagen!
</div>";
        } catch (Exception $e) {
            return $this->generarHtmlErrores(["Error interno: No se pudo persistir el
registro."
            ]);
        }
    }
    public function loginUser($nombre, $password)
    {
        if (empty($nombre) || empty($password)) {
            return $this->generarHtmlErrores(["Debe completar todos los campos del
formulario."
            ]);
        }
        // Recuperamos el objeto de dominio desde el repositorio de MySQL
        $usuario = $this->repository->findByName($nombre);
        // Verificación lógica de existencia y coincidencia de password
        if ($usuario === null || $usuario->password !== $password) {
            return $this->generarHtmlErrores(["Las credenciales ingresadas son
incorrectas."
            ]);
        }
        // Activación del subsistema de sesiones
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Seteo de variables de sesión globales e inviolables
        $_SESSION['usuario_nombre'] = $usuario->nombre;
        $_SESSION['usuario_rol'] = $usuario->rol;
        // Redirección inmediata por cabecera HTTP al dashboard protegido
        header("Location: dashboard.php");
        exit();
    }
    private function generarHtmlErrores($errores)
    {

        $html = "<div style='background:#f8d7da; color:#721c24; padding:15px; margin-
bottom:15px; border-radius:5px;'>";

        $html .= "<b>Se han encontrado errores de validación:</b><ul style='margin-top:
5px; padding-left:20px;'>";
        foreach ($errores as $error) {
            $html .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $html .= "</ul></div>";
        return $html;
    }
}
?>