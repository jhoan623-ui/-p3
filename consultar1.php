<?php
// consultar.php
include "conexion.php";

$db = new Conexion();
$conexion_activa = $db->conectar();

// Apuntamos directamente a tu tabla 'persona'
$pgsql = "SELECT * FROM persona";

$resultado = $db->ejecutarSentencia($pgsql);

if ($resultado && $resultado->rowCount() > 0) {
    echo "<h2>Personas Registradas</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: sans-serif;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Cédula</th>";
    echo "<th>Nombres</th>";
    echo "<th>Apellidos</th>";
    echo "<th>Fecha Nacimiento</th>";
    echo "<th>Correo Electrónico</th>";
    echo "<th>Teléfono</th>";
    echo "</tr>";

    // Recorremos las filas de la tabla 'persona'
    while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . (isset($fila['cedula']) ? $fila['cedula'] : 'N/A') . "</td>";
        echo "<td>" . (isset($fila['nombres']) ? $fila['nombres'] : 'N/A') . "</td>";
        echo "<td>" . (isset($fila['apellidos']) ? $fila['apellidos'] : 'N/A') . "</td>";
        
        // CORRECCIÓN AQUÍ: Se cambió 'fech_nacimiento' por 'fech_nacimineto'
        echo "<td>" . (isset($fila['fech_nacimineto']) ? $fila['fech_nacimineto'] : 'N/A') . "</td>";
        
        echo "<td>" . (isset($fila['correo_electronico']) ? $fila['correo_electronico'] : 'N/A') . "</td>";
        echo "<td>" . (isset($fila['telefono_movil']) ? $fila['telefono_movil'] : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se encontraron personas registradas o la tabla 'persona' está vacía.</p>";
}
 
$db->desconectar();
?>