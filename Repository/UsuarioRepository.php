<?php
require_once '../Domain/Usuario.php';

class UsuarioRepository {
    private $pdo;
    private $listaUsuariosBD;

    public function __construct() {
        $host='localhost';
        $db='clinicaimagenbd';
        $user='root';
        $pass='';
        $charset='utf8mb4';

        $dsn="mysql:host=$host;dbname=$db; charset=$charset";

        try{
            $this->pdo = new PDO($dsn, $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e){
            die("ERROR critoco de coneccion con la BD: ". $e->getMessage());
        }
    }
    // Método para buscar si un usuario ya existe por su nombre
    public function buscarPorNombre($nombre) {
            $stmt= $this->pdo->prepare("SELECT * FROM usuarios WHERE nombre=?");
            $stmt->execute([strtolower($nombre)]);
            $datos= $stmt -> fetch(PDO::FETCH_ASSOC);
            if ($datos){
              return new Usuario($datos['nombre'],$datos['password'],$datos['rol']); 
            }
            return null;
        }
    public function guardar(Usuario $usuario){
        $sql="INSERT INTO usuarios(nombre, password, rol) VALUES(?,?,?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $usuario->nombre,
            $usuario->password,
            $usuario->rol
            ]);
      }
    }
?>
