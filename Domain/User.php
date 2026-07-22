<?php
class User
{
    public $nombre;
    public $password;
    public $rol;
    public function __construct($nombre, $password, $rol = 'paciente')
    {
        $this->nombre = $nombre;
        $this->password = $password;
        $this->rol = $rol;
    }
}
?>