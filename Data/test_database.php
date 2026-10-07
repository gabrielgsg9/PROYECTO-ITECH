<?php

require_once "conexion.php";

try {

    $conexion = Conectar::conexion();

    echo "<h1>CONEXIÓN CORRECTA</h1>";

    $sql = "SELECT DATABASE() AS BaseDatos";
    $stmt = $conexion->query($sql);

    $resultado = $stmt->fetch();

    echo "<p>Base de datos conectada: ";
    echo htmlspecialchars($resultado["BaseDatos"]);
    echo "</p>";

    $sql = "SHOW TABLES";
    $stmt = $conexion->query($sql);

    echo "<h2>Tablas encontradas:</h2>";
    echo "<ul>";

    while ($tabla = $stmt->fetch(PDO::FETCH_NUM)) {

        echo "<li>";
        echo htmlspecialchars($tabla[0]);
        echo "</li>";
    }

    echo "</ul>";

} catch (PDOException $e) {

    echo "<h1>ERROR DE CONEXIÓN</h1>";

    echo "<p>";
    echo htmlspecialchars($e->getMessage());
    echo "</p>";
}
