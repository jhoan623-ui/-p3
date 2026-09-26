<?php
// consultar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include "conexion.php";

$db = new Conexion();
$conexion_activa = $db->conectar();

$pgsql = "SELECT * FROM persona ORDER BY cedula ASC";
$resultado = $db->ejecutarSentencia($pgsql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Personas Registradas</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        
        /* Estilos de la Barra de Navegación */
        .navbar { background-color: #0056b3; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-links a { color: white; text-decoration: none; margin-right: 20px; font-weight: bold; font-size: 14px; transition: opacity 0.2s; }
        .nav-links a:hover { opacity: 0.8; }
        .nav-user { font-size: 14px; background-color: rgba(255,255,255,0.15); padding: 5px 12px; border-radius: 20px; }

        .container { background-color: #ffffff; max-width: 1000px; margin: 40px auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #0056b3; margin-top: 0; border-bottom: 2px solid #eef2f5; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eef2f5; }
        th { background-color: #f8f9fa; color: #495057; font-weight: 600; }
        tr:hover { background-color: #f8f9fa; }
        .no-data { text-align: center; color: #6c757d; padding: 30px; font-size: 16px; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-links">
        <a href="login.php">🔑 Login</a>
        <a href="registrar5.php">📝 Registrar</a>
        <a href="actualizar.php">⚙️ Actualizar Perfil</a> <a href="consultar.php">👁️ Consultar</a>
        <a href="enlistar.php">📊 Panel Listado</a>
    </div>
    <span class="nav-user">👤 <?php echo isset($_SESSION['usuario_nombres']) ? $_SESSION['usuario_nombres'] : 'Invitado'; ?></span>
</div>

<div class="container">
    <h2>Personas Registradas en el Sistema</h2>

    <?php if ($resultado && $resultado->rowCount() > 0): ?>
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
                <?php while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($fila['cedula']); ?></strong></td>
                        <td><?php echo htmlspecialchars($fila['nombres']); ?></td>
                        <td><?php echo htmlspecialchars($fila['apellidos']); ?></td>
                        <td><?php echo htmlspecialchars(isset($fila['fech_nacimineto']) ? $fila['fech_nacimineto'] : 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($fila['correo_electronico']); ?></td>
                        <td><?php echo htmlspecialchars($fila['telefono_movil']); ?></td>
                    </tr>
                <?php endwhile; ?> </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">No se encontraron personas registradas o la tabla está vacía.</div>
    <?php endif; ?>
</div>

<?php $db->desconectar(); ?>
</body>
</html>