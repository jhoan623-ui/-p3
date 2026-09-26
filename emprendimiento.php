<?php
// emprendimiento.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 1. CONTROL DE SESIÓN
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
    die("Error al conectar con la base de datos.");
}

// Variables iniciales de la unidad productiva
$emprendimiento = [
    'nombre_emprendimiento' => '',
    'rif'                   => '',
    'sector_productivo'     => '',
    'descripcion'           => '',
    'direccion_fisica'      => ''
];

$existe_registro = false;

// 2. BUSCAR SI EL EMPRENDEDOR YA TIENE REGISTRADA SU UNIDAD PRODUCTIVA
try {
    // Nota: Asumimos la tabla 'emprendimiento' vinculada por 'cedula_persona'
    $sql_buscar = "SELECT * FROM emprendimiento WHERE cedula_persona = :cedula";
    $stmt_buscar = $conexion_activa->prepare($sql_buscar);
    $stmt_buscar->bindParam(':cedula', $cedula_usuario);
    $stmt_buscar->execute();

    if ($stmt_buscar->rowCount() > 0) {
        $emprendimiento = $stmt_buscar->fetch(PDO::FETCH_ASSOC);
        $existe_registro = true;
    }
} catch (PDOException $e) {
    // Si la tabla aún no existe o hay error de estructura
    $mensaje = "<div class='alert error'>⚠️ Error al consultar el emprendimiento. Compruebe la base de datos.</div>";
}

// 3. PROCESAR GUARDADO / ACTUALIZACIÓN (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_emprendimiento = trim($_POST['nombre_emprendimiento']);
    $rif                   = strtoupper(trim($_POST['rif']));
    $sector_productivo     = trim($_POST['sector_productivo']);
    $descripcion           = trim($_POST['descripcion']);
    $direccion_fisica      = trim($_POST['direccion_fisica']);

    // VALIDACIONES DEL SERVIDOR
    if (empty($nombre_emprendimiento) || empty($sector_productivo) || empty($descripcion) || empty($direccion_fisica)) {
        $mensaje = "<div class='alert error'>⚠️ Por favor, complete todos los campos obligatorios.</div>";

    } elseif (strlen($nombre_emprendimiento) < 3 || strlen($nombre_emprendimiento) > 100) {
        $mensaje = "<div class='alert error'>⚠️ El nombre del emprendimiento debe tener entre 3 y 100 caracteres.</div>";

    } elseif (strlen($descripcion) < 15) {
        $mensaje = "<div class='alert error'>⚠️ Indique una descripción más detallada (mínimo 15 caracteres).</div>";

    // Validar formato de RIF si fue ingresado (Ej: J-123456789 o V-123456789)
    } elseif (!empty($rif) && !preg_match('/^[JVEGGL]-?[0-9]{8,9}$/', $rif)) {
        $mensaje = "<div class='alert error'>⚠️ El formato del RIF no es válido (Ej: J-123456789).</div>";

    } else {
        try {
            if ($existe_registro) {
                // ACTUALIZAR REGISTRO EXISTENTE
                $sql_action = "UPDATE emprendimiento 
                               SET nombre_emprendimiento = :nombre, 
                                   rif = :rif, 
                                   sector_productivo = :sector, 
                                   descripcion = :descripcion, 
                                   direccion_fisica = :direccion 
                               WHERE cedula_persona = :cedula";
            } else {
                // INSERTAR NUEVO REGISTRO
                $sql_action = "INSERT INTO emprendimiento (cedula_persona, nombre_emprendimiento, rif, sector_productivo, descripcion, direccion_fisica) 
                               VALUES (:cedula, :nombre, :rif, :sector, :descripcion, :direccion)";
            }

            $stmt_action = $conexion_activa->prepare($sql_action);
            $stmt_action->bindParam(':cedula', $cedula_usuario);
            $stmt_action->bindParam(':nombre', $nombre_emprendimiento);
            $stmt_action->bindParam(':rif', $rif);
            $stmt_action->bindParam(':sector', $sector_productivo);
            $stmt_action->bindParam(':descripcion', $descripcion);
            $stmt_action->bindParam(':direccion', $direccion_fisica);

            $stmt_action->execute();

            // Actualizar datos locales
            $emprendimiento['nombre_emprendimiento'] = $nombre_emprendimiento;
            $emprendimiento['rif']                   = $rif;
            $emprendimiento['sector_productivo']     = $sector_productivo;
            $emprendimiento['descripcion']           = $descripcion;
            $emprendimiento['direccion_fisica']      = $direccion_fisica;
            $existe_registro = true;

            $mensaje = "<div class='alert exito'>¡La información de su emprendimiento se ha guardado con éxito!</div>";

        } catch (PDOException $e) {
            $mensaje = "<div class='alert error'>⚠️ Error al guardar los datos del emprendimiento.</div>";
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
    <title>Registro de Emprendimiento</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px 0; }
        .emp-container { background-color: #ffffff; width: 100%; max-width: 550px; padding: 35px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); box-sizing: border-box; }
        h2 { text-align: center; color: #0056b3; margin-bottom: 25px; border-bottom: 2px solid #eef2f5; padding-bottom: 15px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; font-weight: 600; font-size: 14px; color: #495057; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 11px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .form-group textarea { resize: vertical; height: 90px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #0056b3; outline: none; }
        .btn-group { display: flex; justify-content: space-between; gap: 15px; margin-top: 25px; }
        .btn { flex: 1; padding: 12px; font-size: 15px; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; text-align: center; text-decoration: none; }
        .btn-regresar { background-color: #6c757d; color: #ffffff; }
        .btn-guardar { background-color: #28a745; color: #ffffff; }
        .alert { padding: 12px 15px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-bottom: 20px; text-align: center; }
        .exito { background-color: #def7ec; color: #03543f; border: 1px solid #bcf0da; }
        .error { background-color: #fde8e8; color: #9b1c1c; border: 1px solid #fbd5d5; }
    </style>
</head>
<body>

<div class="emp-container">
    <h2>Datos del Emprendimiento</h2>

    <?php if(!empty($mensaje)) { echo $mensaje; } ?>

    <form action="emprendimiento.php" method="POST">
        
        <div class="form-group">
            <label for="nombre_emprendimiento">Nombre de la Iniciativa / Empresa *</label>
            <input type="text" id="nombre_emprendimiento" name="nombre_emprendimiento" placeholder="Ej: Inversiones El Sol" required value="<?php echo htmlspecialchars($emprendimiento['nombre_emprendimiento']); ?>">
        </div>

        <div class="form-group">
            <label for="rif">RIF (Opcional)</label>
            <input type="text" id="rif" name="rif" placeholder="Ej: J-123456789" value="<?php echo htmlspecialchars($emprendimiento['rif']); ?>">
        </div>

        <div class="form-group">
            <label for="sector_productivo">Sector Productivo *</label>
            <select id="sector_productivo" name="sector_productivo" required>
                <option value="">-- Seleccione un sector --</option>
                <option value="Agroalimentario" <?php echo ($emprendimiento['sector_productivo'] == 'Agroalimentario') ? 'selected' : ''; ?>>Agroalimentario / Alimentos</option>
                <option value="Textil y Calzado" <?php echo ($emprendimiento['sector_productivo'] == 'Textil y Calzado') ? 'selected' : ''; ?>>Textil y Calzado</option>
                <option value="Artesanía y Manualidades" <?php echo ($emprendimiento['sector_productivo'] == 'Artesanía y Manualidades') ? 'selected' : ''; ?>>Artesanía y Manualidades</option>
                <option value="Servicios y Comercialización" <?php echo ($emprendimiento['sector_productivo'] == 'Servicios y Comercialización') ? 'selected' : ''; ?>>Servicios y Comercialización</option>
                <option value="Tecnología e Innovación" <?php echo ($emprendimiento['sector_productivo'] == 'Tecnología e Innovación') ? 'selected' : ''; ?>>Tecnología e Innovación</option>
                <option value="Otros" <?php echo ($emprendimiento['sector_productivo'] == 'Otros') ? 'selected' : ''; ?>>Otros</option>
            </select>
        </div>

        <div class="form-group">
            <label for="descripcion">Breve Descripción de la Actividad *</label>
            <textarea id="descripcion" name="descripcion" placeholder="Describa qué productos o servicios elabora..." required><?php echo htmlspecialchars($emprendimiento['descripcion']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="direccion_fisica">Dirección Física de la Unidad Productiva *</label>
            <input type="text" id="direccion_fisica" name="direccion_fisica" placeholder="Comuna, Sector, Calle, Local o Casa" required value="<?php echo htmlspecialchars($emprendimiento['direccion_fisica']); ?>">
        </div>

        <div class="btn-group">
            <a href="actualizar.php" class="btn btn-regresar">Atrás</a>
            <button type="submit" class="btn btn-guardar"><?php echo $existe_registro ? 'Actualizar Datos' : 'Guardar Emprendimiento'; ?></button>
        </div>

    </form>
</div>

</body>
</html>