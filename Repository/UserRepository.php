<?php
require_once '../Domain/User.php';
class UserRepository
{
    private $pdo;
    public function __construct()
    {
        $host = 'localhost';
        $db = 'clinica_imagenbd';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        try {
            $this->pdo = new PDO($dsn, $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error crítico en la infraestructura de datos: " . $e->getMessage());
        }
    }
    public function findByName($nombre)
    {
        // Consultas preparadas para evitar ataques de inyección SQL
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE nombre = ?");
        $stmt->execute([strtolower(trim($nombre))]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($datos) {
            return new User($datos['nombre'], $datos['password'], $datos['rol']);
        }
        return null;
    }
    public function save(User $usuario)
    {
        $sql = "INSERT INTO usuarios (nombre, password, rol) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            strtolower(trim($usuario->nombre)),
            $usuario->password,
            $usuario->rol
        ]);
    }
}
?>