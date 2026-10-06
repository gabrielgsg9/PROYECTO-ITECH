<?php

class Conectar
{
    public static function conexion()
    {
        $servidor = "localhost";
        $baseDatos = "sistema_clinico";
        $usuario = "root";
        $contrasena = "";

        try {
            $stringConexion = "mysql:host=$servidor;dbname=$baseDatos;charset=utf8mb4";

            $conexion = new PDO(
                $stringConexion,
                $usuario,
                $contrasena
            );

            $conexion->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $conexion->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

            return $conexion;

        } catch (PDOException $e) {

            die(
                "Error de conexión a MySQL: "
                . $e->getMessage()
            );
        }
    }
}
