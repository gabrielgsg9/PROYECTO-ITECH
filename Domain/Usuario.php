<?php
class Usuario {
    public $nombre;
    public $password;
    public $error;

    public function __construct($nombre, $password,$rol) {
        $this->nombre = $nombre;
        $this->password = $password;
        $this->rol = $rol;
    }
}
?>
