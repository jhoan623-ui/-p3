<?php
// enlistar.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// SEGURIDAD: Solo usuarios logueados pueden ver el listado
if (!isset($_SESSION['usuario_cedula'])) {
    header("Location: login.php");
    exit();
}

include "conexion.php";
$db = new Conexion();
$conexion_activa = $db->conectar();

$buscar = "";
$condicion = "";

// Lógica del Buscador
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $buscar = trim($_GET['buscar']);
    // Filtra por cédula, nombres o apellidos
    $condicion = " WHERE cedula LIKE :buscar OR nombres ILIKE :buscar OR apellidos ILIKE :buscar";
}

$sql = "SELECT cedula, nombres, apellidos, fech_nacimineto, correo_electronico, telefono_movil FROM persona" . $condicion . " ORDER BY cedula ASC";

try {
    $stmt = $conexion_activa->prepare($sql);
    if (!empty($condicion)) {
        $buscar_param = "%" . $buscar . "%";
        $stmt->bindParam(':buscar', $buscar_param);
    }
    $stmt->execute();
    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar la lista: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Emprendedores</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin: 0; padding: 30px; }
        .container { background-color: #ffffff; max-width: 1100px; margin: 0 auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eef2f5; padding-bottom: 20px; margin-bottom: 20px; }
        .header-section h2 { color: #0056b3; margin: 0; }
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { padding: 10px; border: 1px solid #ced4da; border-radius: 6px; width: 300px; font-size: 14px; }
        .btn { padding: 10px 15px; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-buscar { background-color: #0056b3; color: white; }
        .btn-limpiar { background-color: #6c757d; color: white; }
        .btn-editar { background-color: #ffc107; color: #212529; padding: 5px 10px; font-size: 12px; }
        .btn-editar:hover { background-color: #e0a800; }
        .btn-salir { background-color: #dc3545; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eef2f5; }
        th { background-color: #f8f9fa; color: #495057; font-weight: 600; }
        tr:hover { background-color: #f8f9fa; }
        .no-data { text-align: center; color: #6c757d; padding: 30px; font-size: 16px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <h2>Panel de Control: Emprendedores Parroquia San Juan</h2>
        <a href="login.php" class="btn btn-salir">Cerrar Sesión</a>
    </div>

    <form method="GET" action="enlistar.php" class="search-box">
        <input type="text" name="buscar" placeholder="Buscar por Cédula, Nombre o Apellido..." value="<?php echo htmlspecialchars($buscar); ?>">
        <button type="submit" class="btn btn-buscar">Buscar</button>
        <?php if (!empty($buscar)): ?>
            <a href="enlistar.php" class="btn btn-limpiar">Limpiar Filtro</a>
        <?php endif; ?>
    </form>

    <?php if (count($personas) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Fecha Nacimiento</th>
                    <th>Correo Electrónico</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personas as $fila): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($fila['cedula']); ?></strong></td>
                        <td><?php echo htmlspecialchars($fila['nombres']); ?></td>
                        <td><?php echo htmlspecialchars($fila['apellidos']); ?></td>
                        <td><?php echo htmlspecialchars($fila['fech_nacimineto']); ?></td>
                        <td><?php echo htmlspecialchars($fila['correo_electronico']); ?></td>
                        <td><?php echo htmlspecialchars($fila['telefono_movil']); ?></td>
                        <td>
                            <a href="actualizar.php" class="btn btn-editar">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">No se encontraron emprendedores registrados que coincidan con la búsqueda.</div>
    <?php endif; ?>
</div>

</body>
</html>