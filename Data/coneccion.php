<?php
class Conectar {
    public static function conexion() {
        $nombreServidor = "(localdb)\\MSSQLLocalDB"; 
        $baseDatos = "sistema_clinico";

        try {
            $stringConexion = "sqlsrv:Server=$nombreServidor;Database=$baseDatos;Trusted_Connection=yes;Encrypt=Optional";
             $conexion = new PDO($stringConexion);
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conexion;
        } catch (Exception $e) {
            die("Error crítico de conexión a LocalDB: " . $e->getMessage());
        }
    }
}
