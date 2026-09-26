<?php
// formacion.php
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

// 2. PROCESAR INSCRIPCIÓN (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_horario'])) {
    $id_horario = intval($_POST['id_horario']);

    try {
        // Verificar disponibilidad de cupos en la tabla formacion_horario
        $sql_cupo = "SELECT capacidad, 
                            (SELECT COUNT(*) FROM alumno_formacion WHERE id_horario = :id_h AND estado != 'CANCELADO') as inscritos 
                     FROM formacion_horario 
                     WHERE id_horario = :id_h AND estado = 'PROGRAMADO'";
                     
        $stmt_cupo = $conexion_activa->prepare($sql_cupo);
        $stmt_cupo->bindParam(':id_h', $id_horario);
        $stmt_cupo->execute();
        $curso = $stmt_cupo->fetch(PDO::FETCH_ASSOC);

        if (!$curso) {
            $mensaje = "<div class='alert error'>⚠️ La formación seleccionada no se encuentra disponible.</div>";
        } elseif ($curso['inscritos'] >= $curso['capacidad']) {
            $mensaje = "<div class='alert error'>⚠️ Lo sentimos, esta oferta ya alcanzó su capacidad máxima de alumnos.</div>";
        } else {
            // Insertar en alumno_formacion según tu esquema
            $sql_inscribir = "INSERT INTO alumno_formacion (id_horario, cedula_alumno, fecha_inscripcion, estado) 
                               VALUES (:id_h, :cedula, CURRENT_DATE, 'INSCRITO')";
            $stmt_inscribir = $conexion_activa->prepare($sql_inscribir);
            $stmt_inscribir->bindParam(':id_h', $id_horario);
            $stmt_inscribir->bindParam(':cedula', $cedula_usuario);
            $stmt_inscribir->execute();

            $mensaje = "<div class='alert exito'>¡Inscripción realizada con éxito en el sistema!</div>";
        }
    } catch (PDOException $e) {
        // Manejar duplicados de inscripción
        if ($e->getCode() == '23505' || strpos($e->getMessage(), 'duplicate key') !== false) {
            $mensaje = "<div class='alert error'>⚠️ Ya te encuentras inscrito en esta oferta formativa.</div>";
        } else {
            $mensaje = "<div class='alert error'>⚠️ Error al procesar la inscripción: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// 3. CONSULTAR FORMACIONES PROGRAMADAS CON JOIN A TUS TABLAS
$formaciones = [];
try {
    $sql_formaciones = "
        SELECT fh.id_horario, fh.fecha_inicio, fh.fecha_fin, fh.hora_inicio, fh.hora_fin, fh.lugar, fh.capacidad,
               f.nombre AS nombre_curso, f.descripcion AS descripcion_curso, f.duracion_horas,
               tf.nombre_tipo AS tipo_formacion,
               p.nombres AS nombre_instructor, p.apellidos AS apellido_instructor, i.especialidad,
               (SELECT COUNT(*) FROM alumno_formacion af WHERE af.id_horario = fh.id_horario AND af.estado != 'CANCELADO') AS inscritos,
               (SELECT COUNT(*) FROM alumno_formacion af WHERE af.id_horario = fh.id_horario AND af.cedula_alumno = :cedula AND af.estado != 'CANCELADO') AS mi_inscripcion
        FROM formacion_horario fh
        INNER JOIN formacion f ON fh.id_formacion = f.id_formacion
        INNER JOIN tipo_formacion tf ON f.id_tipo_formacion = tf.id_tipo_formacion
        INNER JOIN instructor i ON fh.id_instructor = i.id_instructor
        INNER JOIN persona p ON i.cedula = p.cedula
        WHERE fh.estado = 'PROGRAMADO'
        ORDER BY fh.fecha_inicio ASC";
    
    $stmt_f = $conexion_activa->prepare($sql_formaciones);
    $stmt_f->bindParam(':cedula', $cedula_usuario);
    $stmt_f->execute();
    $formaciones = $stmt_f->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensaje = "<div class='alert error'>⚠️ Error al consultar las formaciones programadas.</div>";
}

$db->desconectar();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Formación - Parroquia San Juan</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 950px; margin: 0 auto; }
        .header { background: #0056b3; color: white; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .btn-volver { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-weight: bold; font-size: 14px; }
        
        .alert { padding: 12px 15px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-bottom: 20px; text-align: center; }
        .exito { background-color: #def7ec; color: #03543f; border: 1px solid #bcf0da; }
        .error { background-color: #fde8e8; color: #9b1c1c; border: 1px solid #fbd5d5; }

        .grid-formaciones { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card-curso { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid #0056b3; }
        .card-curso h3 { margin-top: 0; color: #0056b3; font-size: 18px; margin-bottom: 5px; }
        .tipo-tag { display: inline-block; background-color: #e9ecef; color: #495057; font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 4px; margin-bottom: 10px; text-transform: uppercase; }
        .desc-curso { font-size: 13px; color: #6c757d; margin-bottom: 15px; }
        
        .info-line { font-size: 13px; color: #495057; margin-bottom: 6px; }
        .info-line strong { color: #212529; }
        
        .cupo-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-bottom: 15px; margin-top: 8px; }
        .cupo-disponible { background-color: #e1f5fe; color: #0288d1; }
        .cupo-lleno { background-color: #ffebee; color: #c62828; }

        .btn-inscribir { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; }
        .btn-inscribir:hover { background-color: #218838; }
        .btn-inscrito { width: 100%; padding: 10px; background-color: #17a2b8; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; text-align: center; box-sizing: border-box; display: block; }
        .btn-agotado { width: 100%; padding: 10px; background-color: #dc3545; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; text-align: center; cursor: not-allowed; opacity: 0.7; }
        .no-data { background: white; padding: 30px; text-align: center; border-radius: 8px; color: #6c757d; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Oferta de Formación y Capacitación</h1>
        <a href="dashboard.php" class="btn-volver">Volver al Panel</a>
    </div>

    <?php if(!empty($mensaje)) { echo $mensaje; } ?>

    <?php if (count($formaciones) > 0): ?>
        <div class="grid-formaciones">
            <?php foreach ($formaciones as $curso): ?>
                <?php 
                    $cupos_restantes = $curso['capacidad'] - $curso['inscritos'];
                    $ya_inscrito = $curso['mi_inscripcion'] > 0;
                ?>
                <div class="card-curso">
                    <div>
                        <span class="tipo-tag"><?php echo htmlspecialchars($curso['tipo_formacion']); ?></span>
                        <h3><?php echo htmlspecialchars($curso['nombre_curso']); ?></h3>
                        <p class="desc-curso"><?php echo htmlspecialchars($curso['descripcion_curso']); ?></p>
                        
                        <div class="info-line">
                            <strong>Instructor:</strong> <?php echo htmlspecialchars($curso['nombre_instructor'] . ' ' . $curso['apellido_instructor'] . ' (' . $curso['especialidad'] . ')'); ?>
                        </div>
                        <div class="info-line">
                            <strong>Duración:</strong> <?php echo htmlspecialchars($curso['duracion_horas']); ?> horas
                        </div>
                        <div class="info-line">
                            <strong>Lugar:</strong> <?php echo htmlspecialchars($curso['lugar']); ?>
                        </div>
                        <div class="info-line">
                            <strong>Fechas:</strong> <?php echo date("d/m/Y", strtotime($curso['fecha_inicio'])) . " al " . date("d/m/Y", strtotime($curso['fecha_fin'])); ?>
                        </div>
                        <div class="info-line">
                            <strong>Horario:</strong> <?php echo date("h:i A", strtotime($curso['hora_inicio'])) . " - " . date("h:i A", strtotime($curso['hora_fin'])); ?>
                        </div>
                        
                        <div class="cupo-badge <?php echo ($cupos_restantes > 0) ? 'cupo-disponible' : 'cupo-lleno'; ?>">
                            Cupos disponibles: <?php echo max(0, $cupos_restantes); ?> / <?php echo $curso['capacidad']; ?>
                        </div>
                    </div>

                    <div>
                        <?php if ($ya_inscrito): ?>
                            <div class="btn-inscrito">✓ Ya estás inscrito</div>
                        <?php elseif ($cupos_restantes <= 0): ?>
                            <button class="btn-agotado" disabled>Cupos Agotados</button>
                        <?php else: ?>
                            <form action="formacion.php" method="POST">
                                <input type="hidden" name="id_horario" value="<?php echo $curso['id_horario']; ?>">
                                <button type="submit" class="btn-inscribir">Inscribirme a la formación</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-data">
            <h3>No hay formaciones programadas por el momento.</h3>
            <p>Vuelve a consultar periódicamente para conocer los nuevos talleres y cursos.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>