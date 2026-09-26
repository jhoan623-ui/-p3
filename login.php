<?php
// login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "conexion.php";

$error = "";

// Si el usuario ya tiene sesión activa, lo mandamos directo a actualizar
if (isset($_SESSION['usuario_cedula'])) {
    header("Location: actualizar.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cedula = trim($_POST['cedula']);
    $clave  = trim($_POST['clave']);

    if (!empty($cedula) && !empty($clave)) {
        $db = new Conexion();
        $conexion_activa = $db->conectar();

        if ($conexion_activa) {
            try {
                $sql = "SELECT cedula, nombres, apellidos, clave_v FROM persona WHERE cedula = :cedula";
                $stmt = $conexion_activa->prepare($sql);
                $stmt->bindParam(':cedula', $cedula);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Verificación segura de la contraseña encriptada
                    if (password_verify($clave, $usuario['clave_v'])) {
                        
                        // Guardar datos clave en la sesión
                        $_SESSION['usuario_cedula']    = $usuario['cedula'];
                        $_SESSION['usuario_nombres']   = $usuario['nombres'];
                        $_SESSION['usuario_apellidos'] = $usuario['apellidos'];

                        header("Location: actualizar.php");
                        exit(); 
                    } else {
                        // Mensaje genérico para proteger la privacidad de datos
                        $error = "⚠️ Cédula o contraseña incorrectas.";
                    }
                } else {
                    // Mismo mensaje genérico
                    $error = "⚠️ Cédula o contraseña incorrectas.";
                }
            } catch (PDOException $e) {
                // Ocultamos el detalle técnico del error SQL al usuario final
                $error = "⚠️ Error en el sistema. Por favor, intente más tarde.";
            }
            $db->desconectar();
        } else {
            $error = "⚠️ No se pudo conectar con la base de datos.";
        }
    } else {
        $error = "⚠️ Por favor, llene todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - Sistema Emprendedores</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); width: 100%; max-width: 380px; }
        h2 { text-align: center; color: #0056b3; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #495057; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .btn-login { width: 100%; padding: 12px; background-color: #0056b3; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-login:hover { background-color: #004085; }
        .alert { background-color: #fde8e8; color: #9b1c1c; padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; text-align: center; border: 1px solid #fbd5d5; font-weight: bold; }
        .register-link { text-align: center; margin-top: 20px; font-size: 14px; color: #6c757d; }
        .register-link a { color: #0056b3; text-decoration: none; font-weight: bold; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Ingresar al Sistema</h2>

    <?php if (!empty($error)): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="cedula">Cédula de Identidad</label>
            <input type="text" id="cedula" name="cedula" placeholder="Ej: V-12345678" value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="clave">Contraseña</label>
            <input type="password" id="clave" name="clave" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-login">Iniciar Sesión</button>
    </form>

    <div class="register-link">
        ¿No tienes una cuenta? <a href="registrar5.php">Regístrate aquí</a>
    </div>
</div>

</body>
</html>