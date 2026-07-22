<?php
require_once '../Repository/UsuarioRepository.php';
require_once '../Domain/Usuario.php';

class UsuarioService {
    private $repository;

    public function __construct() {
        $this->repository = new UsuarioRepository();
    }

    public function registrarUsuario($nombre, $pass1, $pass2) {
        $errores = [];

        // Regla 1: Validar campos vacíos
        if (empty($nombre) || empty($pass1)) {
            $errores[] = "Todos los campos son obligatorios.";
            return $this->generarHtmlErrores($errores);
        }

        // Regla 2: Verificar que las contraseñas coincidan
        if ($pass1 !== $pass2) {
            $errores[] = "Las contraseñas ingresadas no coinciden.";
        }

        // Regla 3: Validar longitud de contraseña (Desafío)
        if (strlen($pass1) < 4) {
            $errores[] = "La contraseña debe tener al menos 4 caracteres.";
        }

        // Regla 4: Verificar duplicados usando el Repositorio
        if ($this->repository->buscarPorNombre($nombre) !== null) {
            $errores[] = "El nombre de usuario '$nombre' ya está registrado.";
        }

        // Si hay errores, los procesamos todos juntos en HTML
        if (count($errores) > 0) {
            return $this->generarHtmlErrores($errores);
        }

        $nuevoUsuario = new Usuario(strtolower($nombre), $pass1, 'paciente');

        //DECIMOS AL REPOSITORIO QUE HAGA EL INSERT REAL
        try {
            $this->repository->guardar($nuevoUsuario);
            
            return "<div style='background:#d4edda; color:#155724; padding:15px; margin-bottom:15px; border-radius:5px;'>
                         ¡Usuario <b>" . htmlspecialchars($nuevoUsuario->nombre) . "</b> registrado con éxito en la base de datos de la clínica!
                    </div>";
        } catch (Exception $e) {
            // Por si falla la base de datos (servidor caído, etc.)
            return $this->generarHtmlErrores(["Error interno del servidor al guardar el usuario."]);
        }
    }

    // Método privado auxiliar para armar el bloque de errores que querías en una sola variable
    private function generarHtmlErrores($errores) {
        $html = "<div style='background:#f8d7da; color:#721c24; padding:15px; margin-bottom:15px; border-radius:5px;'>";
        $html .= "<h4>Error al procesar:</h4><ul>";
        foreach ($errores as $error) {
            $html .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $html .= "</ul></div>";
        return $html;
    }
}
?>
