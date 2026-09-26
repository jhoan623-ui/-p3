<?php
// registrar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cedula             = trim($_POST['cedula']);
    $nombres            = trim($_POST['nombres']);
    $apellidos          = trim($_POST['apellidos']);
    $fecha_nacimiento   = !empty($_POST['fecha_nacimiento']) ? trim($_POST['fecha_nacimiento']) : null;
    $correo_electronico = strtolower(trim($_POST['correo_electronico']));
    $telefono_movil     = trim($_POST['telefono_movil']);
    $clave_original     = trim($_POST['clave']);

    if (empty($cedula) || empty($nombres) || empty($apellidos) || empty($correo_electronico) || empty($telefono_movil) || empty($clave_original)) {
        $mensaje = "<div class='alert error'>⚠️ Por favor, complete todos los campos obligatorios.</div>";
    } elseif (!preg_match('/^[VEve]?[-]?[0-9]{6,9}$/', $cedula)) {
        $mensaje = "<div class='alert error'>⚠️ El formato de la Cédula no es válido (Ej: V-12345678 o 12345678).</div>";
    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombres) || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellidos)) {
        $mensaje = "<div class='alert error'>⚠️ Los nombres y apellidos solo deben contener letras.</div>";
    } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "<div class='alert error'>⚠️ El formato del correo electrónico no es válido.</div>";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $telefono_movil)) {
        $mensaje = "<div class='alert error'>⚠️ El teléfono móvil debe contener entre 10 y 11 dígitos numéricos.</div>";
    } elseif (strlen($clave_original) < 8) {
        $mensaje = "<div class='alert error'>⚠️ La contraseña debe tener al menos 8 caracteres.</div>";
    } else {
        $db = new Conexion();
        $conexion_activa = $db->conectar();

        if ($conexion_activa != null) {
            try {
                $sql_verificar = "SELECT cedula, correo_electronico FROM persona WHERE cedula = :cedula OR correo_electronico = :correo";
                $stmt_verificar = $conexion_activa->prepare($sql_verificar);
                $stmt_verificar->bindParam(':cedula', $cedula);
                $stmt_verificar->bindParam(':correo', $correo_electronico);
                $stmt_verificar->execute();

                if ($stmt_verificar->rowCount() > 0) {
                    $usuario_existente = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
                    if ($usuario_existente['cedula'] == $cedula) {
                        $mensaje = "<div class='alert error'>⚠️ La cédula <b>$cedula</b> ya se encuentra registrada.</div>";
                    } else {
                        $mensaje = "<div class='alert error'>⚠️ El correo <b>$correo_electronico</b> ya está asociado a otra cuenta.</div>";
                    }
                } else {
                    $clave_encriptada = password_hash($clave_original, PASSWORD_DEFAULT);

                    $sql = "INSERT INTO persona (cedula, nombres, apellidos, fecha_nacimiento, correo_electronico, telefono_movil, clave, activo) 
                            VALUES (:cedula, :nombres, :apellidos, :fecha_nacimiento, :correo_electronico, :telefono_movil, :clave, true)";

                    $stmt = $conexion_activa->prepare($sql);
                    $stmt->bindParam(':cedula', $cedula);
                    $stmt->bindParam(':nombres', $nombres);
                    $stmt->bindParam(':apellidos', $apellidos);

                    if ($fecha_nacimiento === null) {
                        $stmt->bindValue(':fecha_nacimiento', null, PDO::PARAM_NULL);
                    } else {
                        $stmt->bindValue(':fecha_nacimiento', $fecha_nacimiento);
                    }

                    $stmt->bindParam(':correo_electronico', $correo_electronico);
                    $stmt->bindParam(':telefono_movil', $telefono_movil);
                    $stmt->bindParam(':clave', $clave_encriptada);

                    $stmt->execute();

                    $_SESSION['usuario_cedula']    = $cedula;
                    $_SESSION['usuario_nombres']   = $nombres;
                    $_SESSION['usuario_apellidos'] = $apellidos;

                    header("Location: actualizar.php");
                    exit();
                }

            } catch (PDOException $e) {
                $mensaje = "<div class='alert error'>⚠️ Error de procesamiento en la base de datos: " . $e->getMessage() . "</div>";
            }
            $db->desconectar();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Emprendedores</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px 0; }
        .reg-container { background-color: white; padding: 35px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); width: 100%; max-width: 450px; box-sizing: border-box; }
        h2 { text-align: center; color: #0056b3; margin-bottom: 25px; border-bottom: 2px solid #eef2f5; padding-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #495057; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .btn-reg { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .btn-reg:hover { background-color: #218838; }
        .alert { padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .error { background-color: #fde8e8; color: #9b1c1c; border: 1px solid #fbd5d5; }
        .login-link { text-align: center; margin-top: 20px; font-size: 14px; color: #6c757d; }
        .login-link a { color: #0056b3; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="reg-container">
    <h2>Registro de Emprendedor</h2>

    <?php if (!empty($mensaje)) { echo $mensaje; } ?>

    <form action="registrar.php" method="POST">
        <div class="form-group">
            <label>Cédula de Identidad *</label>
            <input type="text" name="cedula" placeholder="Ej: V-12345678" value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label>Nombres *</label>
            <input type="text" name="nombres" value="<?php echo isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label>Apellidos *</label>
            <input type="text" name="apellidos" value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label>Fecha de Nacimiento</label>
            <input type="date" name="fecha_nacimiento" value="<?php echo isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Correo Electrónico *</label>
            <input type="email" name="correo_electronico" placeholder="ejemplo@correo.com" value="<?php echo isset($_POST['correo_electronico']) ? htmlspecialchars($_POST['correo_electronico']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label>Teléfono Móvil *</label>
            <input type="tel" name="telefono_movil" placeholder="04141234567" value="<?php echo isset($_POST['telefono_movil']) ? htmlspecialchars($_POST['telefono_movil']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label>Contraseña del Sistema * (mínimo 8 caracteres)</label>
            <input type="password" name="clave" placeholder="Cree una contraseña" required>
        </div>

        <button type="submit" class="btn-reg">Registrar y Continuar</button>
    </form>

    <div class="login-link">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
    </div>
</div>

</body>
</html>