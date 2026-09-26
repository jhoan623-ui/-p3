<?php
// consultar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "conexion.php";

$db = new Conexion();
$conexion_activa = $db->conectar();

$personas = [];

if ($conexion_activa) {
    try {
        $sql = "SELECT cedula, nombres, apellidos, fecha_nacimiento, correo_electronico, telefono_movil 
                FROM persona 
                ORDER BY cedula ASC";
        
        $stmt = $conexion_activa->prepare($sql);
        $stmt->execute();
        $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div class='alert error'>⚠️ Error al consultar datos: " . $e->getMessage() . "</div>";
    }
    $db->desconectar();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personas Registradas</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; padding: 30px; margin: 0; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h2 { color: #333; margin-top: 0; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 10px 12px; text-align: left; font-size: 14px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:nth-child(even) { background-color: #fcfcfc; }
    </style>
</head>
<body>

<div class="container">
    <h2>Personas Registradas</h2>

    <table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>Fecha Nacimiento</th>
                <th>Correo Electrónico</th>
                <th>Teléfono</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($persona['cedula']); ?></td>
                        <td><?php echo htmlspecialchars($persona['nombres']); ?></td>
                        <td><?php echo htmlspecialchars($persona['apellidos']); ?></td>
                        <td>
                            <?php 
                                echo !empty($persona['fecha_nacimiento']) 
                                    ? htmlspecialchars($persona['fecha_nacimiento']) 
                                    : 'N/A'; 
                            ?>
                        </td>
                        <td>
                            <?php 
                                echo !empty($persona['correo_electronico']) 
                                    ? htmlspecialchars($persona['correo_electronico']) 
                                    : 'N/A'; 
                            ?>
                        </td>
                        <td>
                            <?php 
                                echo !empty($persona['telefono_movil']) 
                                    ? htmlspecialchars($persona['telefono_movil']) 
                                    : 'N/A'; 
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No hay registros encontrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>