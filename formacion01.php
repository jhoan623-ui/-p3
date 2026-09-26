<?php
/**
 * Módulo de Formación y Evaluación Diagnóstica
 * Archivo Unificado: formacion.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['usuario_cedula'])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

$mensaje = "";
$cedula_usuario = $_SESSION['usuario_cedula'];

$db = new Conexion();
$conexion_activa = $db->conectar();

if (!$conexion_activa) {
    die("Error crítico: No se pudo conectar a la base de datos.");
}

// -------------------------------------------------------------------------
// 1. REGISTRAR CURSO O TALLER EN EL CATÁLOGO
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'crear_catalogo') {
    $tipo = $_POST['tipo'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $nivel_destinado = $_POST['nivel_destinado'] ?? '';

    if (!empty($nombre) && in_array($tipo, ['TALLER', 'CURSO']) && in_array($nivel_destinado, ['NIVEL_0', 'NIVEL_MEDIO', 'NIVEL_ALTO'])) {
        try {
            $sql_cat = "INSERT INTO catalogo_formacion (tipo, nombre, descripcion, nivel_destinado) 
                        VALUES (:tipo, :nombre, :descripcion, :nivel)";
            $stmt_cat = $conexion_activa->prepare($sql_cat);
            $stmt_cat->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt_cat->bindValue(':nombre', $nombre, PDO::PARAM_STR);
            $stmt_cat->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt_cat->bindValue(':nivel', $nivel_destinado, PDO::PARAM_STR);
            $stmt_cat->execute();

            $mensaje = "<div class='alert exito'>¡Nuevo " . strtolower($tipo) . " agregado al catálogo exitosamente!</div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='alert error'>⚠️ Error al registrar en catálogo: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert error'>⚠️ Complete todos los campos requeridos del catálogo.</div>";
    }
}

// -------------------------------------------------------------------------
// 2. REGISTRAR INSTRUCTOR MANUALMENTE
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'crear_instructor') {
    $cedula_inst = trim($_POST['cedula_inst'] ?? '');
    $nombres_inst = trim($_POST['nombres_inst'] ?? '');
    $apellidos_inst = trim($_POST['apellidos_inst'] ?? '');
    $especialidad_inst = trim($_POST['especialidad_inst'] ?? '');

    if (!empty($cedula_inst) && !empty($nombres_inst) && !empty($apellidos_inst)) {
        try {
            $conexion_activa->beginTransaction();

            $sql_p = "INSERT INTO persona (cedula, nombres, apellidos) VALUES (:ced, :nom, :ape)
                      ON CONFLICT (cedula) DO UPDATE SET nombres = EXCLUDED.nombres, apellidos = EXCLUDED.apellidos";
            $stmt_p = $conexion_activa->prepare($sql_p);
            $stmt_p->bindValue(':ced', $cedula_inst, PDO::PARAM_STR);
            $stmt_p->bindValue(':nom', $nombres_inst, PDO::PARAM_STR);
            $stmt_p->bindValue(':ape', $apellidos_inst, PDO::PARAM_STR);
            $stmt_p->execute();

            $sql_i = "INSERT INTO instructor (cedula, especialidad, estado) VALUES (:ced, :esp, 'ACTIVO')
                      ON CONFLICT (cedula) DO UPDATE SET especialidad = EXCLUDED.especialidad, estado = 'ACTIVO'";
            $stmt_i = $conexion_activa->prepare($sql_i);
            $stmt_i->bindValue(':ced', $cedula_inst, PDO::PARAM_STR);
            $stmt_i->bindValue(':esp', $especialidad_inst, PDO::PARAM_STR);
            $stmt_i->execute();

            $conexion_activa->commit();
            $mensaje = "<div class='alert exito'>¡Instructor registrado correctamente!</div>";
        } catch (PDOException $e) {
            $conexion_activa->rollBack();
            $mensaje = "<div class='alert error'>⚠️ Error al registrar instructor: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert error'>⚠️ Cédula, nombres y apellidos del instructor son obligatorios.</div>";
    }
}

// -------------------------------------------------------------------------
// 3. PROGRAMAR OFERTA FORMATIVA
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'crear_oferta') {
    $id_catalogo = filter_var($_POST['id_catalogo'] ?? null, FILTER_VALIDATE_INT);
    $cedula_instructor = !empty($_POST['cedula_instructor']) ? $_POST['cedula_instructor'] : null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fin = $_POST['hora_fin'] ?? '';
    $lugar = trim($_POST['lugar'] ?? '');
    $capacidad = filter_var($_POST['capacidad'] ?? 30, FILTER_VALIDATE_INT) ?: 30;

    if ($id_catalogo && !empty($fecha_inicio) && !empty($fecha_fin)) {
        try {
            $sql_oferta = "INSERT INTO oferta_formacion 
                            (id_catalogo, cedula_instructor, fecha_inicio, fecha_fin, hora_inicio, hora_fin, lugar, capacidad) 
                           VALUES 
                            (:id_cat, :ins, :f_ini, :f_fin, :h_ini, :h_fin, :lugar, :cap)";
            $stmt_o = $conexion_activa->prepare($sql_oferta);
            $stmt_o->bindValue(':id_cat', $id_catalogo, PDO::PARAM_INT);
            $stmt_o->bindValue(':ins', $cedula_instructor, PDO::PARAM_STR);
            $stmt_o->bindValue(':f_ini', $fecha_inicio, PDO::PARAM_STR);
            $stmt_o->bindValue(':f_fin', $fecha_fin, PDO::PARAM_STR);
            $stmt_o->bindValue(':h_ini', $hora_inicio, PDO::PARAM_STR);
            $stmt_o->bindValue(':h_fin', $hora_fin, PDO::PARAM_STR);
            $stmt_o->bindValue(':lugar', $lugar, PDO::PARAM_STR);
            $stmt_o->bindValue(':cap', $capacidad, PDO::PARAM_INT);
            $stmt_o->execute();

            $mensaje = "<div class='alert exito'>¡Oferta programada y publicada exitosamente!</div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='alert error'>⚠️ Error al publicar la oferta: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert error'>⚠️ Complete la fecha de inicio, fin y seleccione un elemento del catálogo.</div>";
    }
}

// -------------------------------------------------------------------------
// 4. PROCESAR TEST DIAGNÓSTICO
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'guardar_test') {
    $exp = isset($_POST['posee_experiencia']) ? 1 : 0;
    $rec = isset($_POST['posee_recursos']) ? 1 : 0;
    $dist = isset($_POST['distribuye_producto']) ? 1 : 0;
    $anos = filter_var($_POST['anos_experiencia'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

    $puntaje = ($exp * 25) + ($rec * 25) + ($dist * 35);
    if ($anos >= 2) { $puntaje += 15; } elseif ($anos == 1) { $puntaje += 10; }

    if ($puntaje <= 30) { $nivel = 'NIVEL_0'; } 
    elseif ($puntaje <= 70) { $nivel = 'NIVEL_MEDIO'; } 
    else { $nivel = 'NIVEL_ALTO'; }

    try {
        $sql_test = "INSERT INTO test_diagnostico 
                        (cedula_alumno, posee_experiencia, posee_recursos, distribuye_producto, anos_experiencia, puntaje_total, nivel_asignado) 
                     VALUES 
                        (:cedula, :exp, :rec, :dist, :anos, :puntaje, :nivel)";
        $stmt_t = $conexion_activa->prepare($sql_test);
        $stmt_t->bindValue(':cedula', $cedula_usuario, PDO::PARAM_STR);
        $stmt_t->bindValue(':exp', $exp, PDO::PARAM_BOOL);
        $stmt_t->bindValue(':rec', $rec, PDO::PARAM_BOOL);
        $stmt_t->bindValue(':dist', $dist, PDO::PARAM_BOOL);
        $stmt_t->bindValue(':anos', $anos, PDO::PARAM_INT);
        $stmt_t->bindValue(':puntaje', $puntaje, PDO::PARAM_INT);
        $stmt_t->bindValue(':nivel', $nivel, PDO::PARAM_STR);
        $stmt_t->execute();

        $mensaje = "<div class='alert exito'>¡Test procesado! Nivel asignado: <strong>" . str_replace('_', ' ', $nivel) . "</strong></div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='alert error'>⚠️ Error al guardar el test: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// -------------------------------------------------------------------------
// 5. PROCESAR INSCRIPCIÓN DEL ALUMNO
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'inscribir') {
    $id_oferta = filter_var($_POST['id_oferta'] ?? null, FILTER_VALIDATE_INT);
    $id_test = filter_var($_POST['id_test'] ?? null, FILTER_VALIDATE_INT);

    if ($id_oferta && $id_test) {
        try {
            $sql_inscribir = "INSERT INTO alumno_formacion (id_oferta, cedula_alumno, id_test) VALUES (:id_o, :ced, :id_t)";
            $stmt_i = $conexion_activa->prepare($sql_inscribir);
            $stmt_i->bindValue(':id_o', $id_oferta, PDO::PARAM_INT);
            $stmt_i->bindValue(':ced', $cedula_usuario, PDO::PARAM_STR);
            $stmt_i->bindValue(':id_t', $id_test, PDO::PARAM_INT);
            $stmt_i->execute();

            $mensaje = "<div class='alert exito'>¡Inscripción confirmada exitosamente!</div>";
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $mensaje = "<div class='alert error'>⚠️ Ya estás inscrito en esta actividad.</div>";
            } else {
                $mensaje = "<div class='alert error'>⚠️ Error en inscripción: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// -------------------------------------------------------------------------
// 6. CONSULTAS DE DATOS PARA LA VISTA
// -------------------------------------------------------------------------
$test_actual = null;
try {
    $stmt_tu = $conexion_activa->prepare("SELECT * FROM test_diagnostico WHERE cedula_alumno = :ced ORDER BY id_test DESC LIMIT 1");
    $stmt_tu->bindValue(':ced', $cedula_usuario, PDO::PARAM_STR);
    $stmt_tu->execute();
    $test_actual = $stmt_tu->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$catalogos = [];
try {
    $catalogos = $conexion_activa->query("SELECT * FROM catalogo_formacion ORDER BY tipo, nombre")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$instructores = [];
try {
    $sql_inst = "SELECT i.cedula, p.nombres, p.apellidos, i.especialidad 
                 FROM instructor i 
                 INNER JOIN persona p ON i.cedula = p.cedula 
                 WHERE i.estado = 'ACTIVO' ORDER BY p.nombres";
    $instructores = $conexion_activa->query($sql_inst)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$ofertas = [];
try {
    $sql_o_disp = "
        SELECT 
            o.id_oferta, o.fecha_inicio, o.fecha_fin, o.hora_inicio, o.hora_fin, o.lugar, o.capacidad,
            c.tipo, c.nombre AS nombre_curso, c.descripcion, c.nivel_destinado,
            p.nombres AS nom_inst, p.apellidos AS ape_inst,
            (SELECT COUNT(*) FROM alumno_formacion af WHERE af.id_oferta = o.id_oferta AND af.cedula_alumno = :ced) AS mi_inscripcion
        FROM oferta_formacion o
        INNER JOIN catalogo_formacion c ON o.id_catalogo = c.id_catalogo
        LEFT JOIN instructor i ON o.cedula_instructor = i.cedula
        LEFT JOIN persona p ON i.cedula = p.cedula
        WHERE o.estado = 'PROGRAMADO'
        ORDER BY o.fecha_inicio ASC";
    
    $stmt_od = $conexion_activa->prepare($sql_o_disp);
    $stmt_od->bindValue(':ced', $cedula_usuario, PDO::PARAM_STR);
    $stmt_od->execute();
    $ofertas = $stmt_od->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$db->desconectar();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo Integral de Formación</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: #0056b3; color: white; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .btn-volver { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-weight: bold; }

        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; background: #e9ecef; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; color: #495057; }
        .tab-btn.active { background: #0056b3; color: white; }

        .tab-content { display: none; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .tab-content.active { display: block; }

        .alert { padding: 12px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-bottom: 20px; text-align: center; }
        .exito { background: #def7ec; color: #03543f; }
        .error { background: #fde8e8; color: #9b1c1c; }

        .section-box { border: 1px solid #e3e6f0; padding: 18px; border-radius: 6px; margin-bottom: 25px; background: #fafafa; }
        .section-box h3 { margin-top: 0; color: #0056b3; font-size: 16px; margin-bottom: 12px; }

        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }

        .btn-action { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { border: 1px solid #e3e6f0; border-top: 4px solid #0056b3; padding: 15px; border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between; }
        .badge { display: inline-block; padding: 3px 8px; font-size: 11px; font-weight: bold; border-radius: 4px; background: #e2e8f0; margin-bottom: 8px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Módulo de Formación y Evaluación de Emprendedores</h1>
        <a href="dashboard.php" class="btn-volver">Volver al Panel</a>
    </div>

    <?php if (!empty($mensaje)) echo $mensaje; ?>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab(event, 'ofertas')">📚 Cursos y Talleres Disponibles</button>
        <button class="tab-btn" onclick="switchTab(event, 'test')">📋 Test Diagnóstico Emprendedor</button>
        <button class="tab-btn" onclick="switchTab(event, 'admin')">⚙️ Panel de Gestión del Encargado</button>
    </div>

    <!-- PESTAÑA 1: OFERTA DISPONIBLE -->
    <div id="ofertas" class="tab-content active">
        <h2>Cursos y Talleres Programados</h2>
        <?php if ($test_actual): ?>
            <p style="font-size: 14px; color: #28a745;">
                ✔ Nivel Diagnosticado: <strong><?php echo str_replace('_', ' ', $test_actual['nivel_asignado']); ?></strong> (<?php echo $test_actual['puntaje_total']; ?> pts)
            </p>
        <?php else: ?>
            <p style="font-size: 14px; color: #dc3545;">
                ⚠️ Se recomienda realizar el <strong>Test Diagnóstico</strong> para validar tu nivel antes de inscribirte.
            </p>
        <?php endif; ?>

        <?php if (count($ofertas) > 0): ?>
            <div class="grid-cards">
                <?php foreach ($ofertas as $o): ?>
                    <div class="card">
                        <div>
                            <span class="badge"><?php echo $o['tipo']; ?> | Destinado: <?php echo str_replace('_', ' ', $o['nivel_destinado']); ?></span>
                            <h3 style="margin: 5px 0; color: #0056b3;"><?php echo htmlspecialchars($o['nombre_curso']); ?></h3>
                            <p style="font-size: 13px; color: #6c757d;"><?php echo htmlspecialchars($o['descripcion'] ?? ''); ?></p>
                            
                            <div style="font-size: 13px; margin-bottom: 5px;">
                                <strong>Instructor:</strong> <?php echo htmlspecialchars(($o['nom_inst'] ?? 'Por asignar') . ' ' . ($o['ape_inst'] ?? '')); ?>
                            </div>
                            <div style="font-size: 13px; margin-bottom: 5px;">
                                <strong>Fechas:</strong> <?php echo date("d/m/Y", strtotime($o['fecha_inicio'])) . " al " . date("d/m/Y", strtotime($o['fecha_fin'])); ?>
                            </div>
                            <div style="font-size: 13px; margin-bottom: 5px;">
                                <strong>Horario:</strong> <?php echo date("h:i A", strtotime($o['hora_inicio'])) . " - " . date("h:i A", strtotime($o['hora_fin'])); ?>
                            </div>
                            <div style="font-size: 13px;">
                                <strong>Lugar:</strong> <?php echo htmlspecialchars($o['lugar']); ?>
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <?php if ($o['mi_inscripcion'] > 0): ?>
                                <button class="btn-action" style="background: #17a2b8;" disabled>✓ Ya estás inscrito</button>
                            <?php elseif ($test_actual): ?>
                                <form action="formacion.php" method="POST">
                                    <input type="hidden" name="action" value="inscribir">
                                    <input type="hidden" name="id_oferta" value="<?php echo $o['id_oferta']; ?>">
                                    <input type="hidden" name="id_test" value="<?php echo $test_actual['id_test']; ?>">
                                    <button type="submit" class="btn-action">Solicitar Inscripción</button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="tab-btn" onclick="switchTab(event, 'test')" style="width: 100%; background: #ffc107; color: black;">Realizar Test para Inscribirme</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #6c757d; margin-top: 30px;">No hay actividades formativas programadas actualmente.</p>
        <?php endif; ?>
    </div>

    <!-- PESTAÑA 2: TEST DIAGNÓSTICO -->
    <div id="test" class="tab-content">
        <h2>Evaluación Diagnóstica del Emprendedor</h2>
        <form action="formacion.php" method="POST">
            <input type="hidden" name="action" value="guardar_test">
            <div class="form-group">
                <label><input type="checkbox" name="posee_experiencia" value="1"> ¿Posees experiencia práctica en el área de tu emprendimiento?</label>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="posee_recursos" value="1"> ¿Cuentas con maquinaria, herramientas o insumos propios?</label>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="distribuye_producto" value="1"> ¿Actualmente comercializas o distribuyes algún producto/servicio?</label>
            </div>
            <div class="form-group">
                <label>Años de trayectoria operando:</label>
                <select name="anos_experiencia" class="form-control">
                    <option value="0">Menos de 1 año (Fase inicial)</option>
                    <option value="1">1 a 2 años</option>
                    <option value="2">Más de 2 años</option>
                </select>
            </div>
            <button type="submit" class="btn-action" style="margin-top: 15px;">Enviar Evaluación Diagnóstica</button>
        </form>
    </div>

    <!-- PESTAÑA 3: PANEL DE GESTIÓN (ENCARGADO) -->
    <div id="admin" class="tab-content">
        <h2>Panel de Control del Encargado</h2>

        <!-- FORMULARIO 1: REGISTRAR EN EL CATÁLOGO -->
        <div class="section-box">
            <h3>1. Crear Curso o Taller en el Catálogo Base</h3>
            <form action="formacion.php" method="POST">
                <input type="hidden" name="action" value="crear_catalogo">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="tipo" class="form-control" required>
                            <option value="TALLER">Taller (1 a 2 semanas)</option>
                            <option value="CURSO">Curso (1 a 2 meses)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nombre del Curso/Taller *</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Contabilidad Básica">
                    </div>
                    <div class="form-group">
                        <label>Nivel Destinado *</label>
                        <select name="nivel_destinado" class="form-control" required>
                            <option value="NIVEL_0">Nivel 0 (Principiante)</option>
                            <option value="NIVEL_MEDIO">Nivel Medio (En marcha)</option>
                            <option value="NIVEL_ALTO">Nivel Alto (Certificación)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Descripción del contenido</label>
                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles de lo que aprenderá el emprendedor..."></textarea>
                </div>
                <button type="submit" class="btn-action" style="background: #17a2b8;">Guardar en Catálogo</button>
            </form>
        </div>

        <!-- FORMULARIO 2: REGISTRAR INSTRUCTOR -->
        <div class="section-box">
            <h3>2. Registrar Nuevo Instructor</h3>
            <form action="formacion.php" method="POST">
                <input type="hidden" name="action" value="crear_instructor">
                <div class="form-row">
                    <div class="form-group">
                        <label>Cédula del Instructor *</label>
                        <input type="text" name="cedula_inst" class="form-control" required placeholder="V-12345678">
                    </div>
                    <div class="form-group">
                        <label>Nombres *</label>
                        <input type="text" name="nombres_inst" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos *</label>
                        <input type="text" name="apellidos_inst" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Especialidad / Área de Conocimiento</label>
                    <input type="text" name="especialidad_inst" class="form-control" placeholder="Ej: Marketing Digital, Agronomía, Finanzas">
                </div>
                <button type="submit" class="btn-action" style="background: #6c757d;">Registrar Instructor</button>
            </form>
        </div>

        <!-- FORMULARIO 3: PROGRAMAR HORARIOS DE LA OFERTA -->
        <div class="section-box">
            <h3>3. Programar y Publicar Horarios de Oferta</h3>
            
            <!-- FORMULARIO RÁPIDO DESPLEGABLE DE CATÁLOGO -->
            <div id="boxNuevoCatalogo" style="display: none; background: #e9ecef; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #ced4da;">
                <strong style="font-size: 13px; color: #0056b3;">Registrar nuevo ítem rápido en el Catálogo:</strong>
                <form action="formacion.php" method="POST" style="margin-top: 8px;">
                    <input type="hidden" name="action" value="crear_catalogo">
                    <div class="form-row">
                        <div class="form-group">
                            <select name="tipo" class="form-control" required>
                                <option value="TALLER">Taller</option>
                                <option value="CURSO">Curso</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" name="nombre" class="form-control" required placeholder="Nombre del curso/taller">
                        </div>
                        <div class="form-group">
                            <select name="nivel_destinado" class="form-control" required>
                                <option value="NIVEL_0">Nivel 0</option>
                                <option value="NIVEL_MEDIO">Nivel Medio</option>
                                <option value="NIVEL_ALTO">Nivel Alto</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-action" style="background: #17a2b8; padding: 6px; font-size: 12px;">Guardar e Insertar</button>
                </form>
            </div>

            <form action="formacion.php" method="POST">
                <input type="hidden" name="action" value="crear_oferta">
                
                <div class="form-group">
                    <label style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Seleccionar del Catálogo Base *</span>
                        <button type="button" onclick="toggleNuevoCatalogo()" style="background: #28a745; color: white; border: none; padding: 3px 8px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                            + Agregar Nuevo al Catálogo
                        </button>
                    </label>
                    <select name="id_catalogo" id="selectCatalogo" class="form-control" required onchange="validarReglasFecha()">
                        <option value="">-- Seleccione del Catálogo --</option>
                        <?php foreach ($catalogos as $cat): ?>
                            <option value="<?php echo $cat['id_catalogo']; ?>" data-tipo="<?php echo $cat['tipo']; ?>">
                                [<?php echo $cat['tipo']; ?>] <?php echo htmlspecialchars($cat['nombre']); ?> (<?php echo $cat['nivel_destinado']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Asignar Instructor Encargado</label>
                    <select name="cedula_instructor" class="form-control">
                        <option value="">-- Por Asignar --</option>
                        <?php foreach ($instructores as $inst): ?>
                            <option value="<?php echo $inst['cedula']; ?>">
                                <?php echo htmlspecialchars($inst['nombres'] . ' ' . $inst['apellidos']); ?> (Especialidad: <?php echo htmlspecialchars($inst['especialidad']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Inicio *</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required onchange="validarReglasFecha()">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Cierre *</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required onchange="validarReglasFecha()">
                    </div>
                </div>

                <div id="msgValidacionFecha" style="font-size: 12px; font-weight: bold; margin-bottom: 10px;"></div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Hora Inicio *</label>
                        <input type="time" name="hora_inicio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Hora Fin *</label>
                        <input type="time" name="hora_fin" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Lugar / Salón *</label>
                        <input type="text" name="lugar" class="form-control" required placeholder="Ej: Aula 2 - Casa Comunal">
                    </div>
                    <div class="form-group">
                        <label>Capacidad Máxima (Cupos)</label>
                        <input type="number" name="capacidad" class="form-control" value="30" min="1">
                    </div>
                </div>

                <button type="submit" class="btn-action" style="background: #0056b3; margin-top: 10px;">Programar Oferta Formativa</button>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(evt, tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add('active');
    }
    const targetContent = document.getElementById(tabId);
    if (targetContent) {
        targetContent.classList.add('active');
    }
}

function toggleNuevoCatalogo() {
    const box = document.getElementById('boxNuevoCatalogo');
    if (box.style.display === 'none' || box.style.display === '') {
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}

function validarReglasFecha() {
    const select = document.getElementById('selectCatalogo');
    if (!select || select.selectedIndex === -1) return;
    
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    const tipo = selectedOption.getAttribute('data-tipo');
    const fIni = new Date(document.getElementById('fecha_inicio').value);
    const fFin = new Date(document.getElementById('fecha_fin').value);
    const msg = document.getElementById('msgValidacionFecha');

    if (!isNaN(fIni) && !isNaN(fFin) && msg) {
        const diffDays = Math.ceil((fFin - fIni) / (1000 * 60 * 60 * 24));

        if (tipo === 'TALLER') {
            if (diffDays < 7 || diffDays > 14) {
                msg.style.color = '#dc3545';
                msg.innerHTML = '⚠️ Los talleres deben durar entre 1 y 2 semanas (7 a 14 días). Actual: ' + diffDays + ' días.';
            } else {
                msg.style.color = '#28a745';
                msg.innerHTML = '✓ Duración válida para Taller (' + diffDays + ' días).';
            }
        } else if (tipo === 'CURSO') {
            if (diffDays < 30 || diffDays > 60) {
                msg.style.color = '#dc3545';
                msg.innerHTML = '⚠️ Los cursos deben durar entre 1 y 2 meses (30 a 60 días). Actual: ' + diffDays + ' días.';
            } else {
                msg.style.color = '#28a745';
                msg.innerHTML = '✓ Duración válida para Curso (' + diffDays + ' días).';
            }
        }
    }
}
</script>

</body>
</html>