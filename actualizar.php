<?php
// actualizar.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['usuario_cedula'])) {
    header("Location: login.php");
    exit();
}

include "conexion.php";

$mensaje = "";
$cedula_usuario = $_SESSION['usuario_cedula'];

$db = new Conexion();
$conexion_activa = $db->conectar();

if (!$conexion_activa) {
    die("No se pudo conectar con la base de datos.");
}

// 1. CARGAR DATOS ACTUALES
try {
    $sql_buscar = "SELECT * FROM persona WHERE cedula = :cedula";
    $stmt_buscar = $conexion_activa->prepare($sql_buscar);
    $stmt_buscar->bindParam(':cedula', $cedula_usuario);
    $stmt_buscar->execute();
    
    if ($stmt_buscar->rowCount() > 0) {
        $usuario = $stmt_buscar->fetch(PDO::FETCH_ASSOC);
    } else {
        die("Usuario no encontrado en el sistema.");
    }
} catch (PDOException $e) {
    die("Error al cargar datos: " . $e->getMessage());
}

// 2. PROCESAR LA ACTUALIZACIÓN
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombres            = trim($_POST['nombres']);
    $apellidos          = trim($_POST['apellidos']);
    $correo_electronico = strtolower(trim($_POST['correo_electronico']));
    $telefono_movil     = trim($_POST['telefono_movil']);
    $fecha_nacimiento   = !empty($_POST['fecha_nacimiento']) ? trim($_POST['fecha_nacimiento']) : null;

    // VALIDACIONES DEL SERVIDOR
    if (empty($nombres) || empty($apellidos) || empty($correo_electronico) || empty($telefono_movil)) {
        $mensaje = "<div class='alert error'>⚠️ Por favor, complete todos los campos obligatorios.</div>";

    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombres) || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellidos)) {
        $mensaje = "<div class='alert error'>⚠️ Los nombres y apellidos solo pueden contener letras.</div>";

    } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "<div class='alert error'>⚠️ El correo electrónico no tiene un formato válido.</div>";

    } elseif (!ctype_digit($telefono_movil)) {
        $mensaje = "<div class='alert error'>⚠️ El teléfono móvil solo debe contener números. No se permiten letras ni símbolos.</div>";

    } elseif (strlen($telefono_movil) < 10 || strlen($telefono_movil) > 11) {
        $mensaje = "<div class='alert error'>⚠️ El teléfono móvil debe contener entre 10 y 11 dígitos numéricos (Ej: 04141234567).</div>";

    } else {
        try {
            $sql_verificar_correo = "SELECT cedula FROM persona WHERE correo_electronico = :correo AND cedula != :cedula";
            $stmt_correo = $conexion_activa->prepare($sql_verificar_correo);
            $stmt_correo->bindParam(':correo', $correo_electronico);
            $stmt_correo->bindParam(':cedula', $cedula_usuario);
            $stmt_correo->execute();

            if ($stmt_correo->rowCount() > 0) {
                $mensaje = "<div class='alert error'>⚠️ El correo <b>$correo_electronico</b> ya pertenece a otra cuenta registrada.</div>";
            } else {
                $sql_update = "UPDATE persona 
                               SET nombres = :nombres, 
                                   apellidos = :apellidos, 
                                   fecha_nacimiento = :fecha_nacimiento, 
                                   correo_electronico = :correo_electronico, 
                                   telefono_movil = :telefono_movil 
                               WHERE cedula = :cedula";

                $stmt_up = $conexion_activa->prepare($sql_update);
                
                $stmt_up->bindParam(':nombres', $nombres);
                $stmt_up->bindParam(':apellidos', $apellidos);
                
                if ($fecha_nacimiento === null) {
                    $stmt_up->bindValue(':fecha_nacimiento', null, PDO::PARAM_NULL);
                } else {
                    $stmt_up->bindValue(':fecha_nacimiento', $fecha_nacimiento);
                }

                $stmt_up->bindParam(':correo_electronico', $correo_electronico);
                $stmt_up->bindParam(':telefono_movil', $telefono_movil);
                $stmt_up->bindParam(':cedula', $cedula_usuario);

                $stmt_up->execute();
                
                $_SESSION['usuario_nombres'] = $nombres;
                $_SESSION['usuario_apellidos'] = $apellidos;
                
                $usuario['nombres'] = $nombres;
                $usuario['apellidos'] = $apellidos;
                $usuario['fecha_nacimiento'] = $fecha_nacimiento;
                $usuario['correo_electronico'] = $correo_electronico;
                $usuario['telefono_movil'] = $telefono_movil;

                $mensaje = "<div class='alert exito'>¡Tus datos han sido actualizados con éxito!</div>";
            }

        } catch (PDOException $e) {
            $mensaje = "<div class='alert error'>⚠️ Error al actualizar los datos: " . $e->getMessage() . "</div>";
        }
    }
}

$db->desconectar();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Perfil - Emprendedores</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px 0; }
        .form-container { background-color: #ffffff; width: 100%; max-width: 500px; padding: 35px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); box-sizing: border-box; }
        .form-container h2 { text-align: center; color: #0056b3; margin-bottom: 25px; border-bottom: 2px solid #eef2f5; padding-bottom: 15px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; font-weight: 600; font-size: 14px; color: #495057; }
        .form-group input { width: 100%; padding: 11px 13px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px; box-sizing: border-box; background-color: #fff; }
        .form-group input[readonly] { background-color: #e9ecef; color: #6c757d; cursor: not-allowed; }
        .form-group input:focus:not([readonly]) { border-color: #0056b3; outline: none; }
        .btn-group { display: flex; justify-content: space-between; gap: 15px; margin-top: 30px; }
        .btn { flex: 1; padding: 12px; font-size: 15px; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; }
        .btn-regresar { background-color: #e2e8f0; color: #4a5568; text-align: center; text-decoration: none; display: block; line-height: 1.5; }
        .btn-guardar { background-color: #28a745; color: #ffffff; }
        .alert { padding: 12px 15px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-bottom: 20px; text-align: center; }
        .exito { background-color: #def7ec; color: #03543f; border: 1px solid #bcf0da; }
        .error { background-color: #fde8e8; color: #9b1c1c; border: 1px solid #fbd5d5; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Actualizar Mis Datos</h2>

    <?php if(!empty($mensaje)) { echo $mensaje; } ?>

    <form action="actualizar.php" method="POST">
        
        <div class="form-group">
            <label>Cédula de Identidad (No modificable)</label>
            <input type="text" value="<?php echo htmlspecialchars($usuario['cedula']); ?>" readonly>
        </div>

        <div class="form-group">
            <label for="nombres">Nombres *</label>
            <input type="text" id="nombres" name="nombres" required value="<?php echo htmlspecialchars($usuario['nombres']); ?>">
        </div>

        <div class="form-group">
            <label for="apellidos">Apellidos *</label>
            <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars($usuario['apellidos']); ?>">
        </div>

        <div class="form-group">
            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($usuario['fecha_nacimiento'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="correo_electronico">Correo Electrónico *</label>
            <input type="email" id="correo_electronico" name="correo_electronico" required value="<?php echo htmlspecialchars($usuario['correo_electronico']); ?>">
        </div>

        <div class="form-group">
            <label for="telefono_movil">Teléfono Móvil *</label>
            <input type="tel" 
                   id="telefono_movil" 
                   name="telefono_movil" 
                   placeholder="Ej: 04141234567" 
                   pattern="[0-9]{10,11}" 
                   oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                   maxlength="11" 
                   required 
                   value="<?php echo htmlspecialchars($usuario['telefono_movil']); ?>">
        </div>

        <div class="btn-group">
            <a href="login.php" class="btn btn-regresar">Volver</a>
            <button type="submit" class="btn btn-guardar">Guardar Cambios</button>
        </div>

    </form>
</div>

</body>
</html>