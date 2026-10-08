<?php

session_start();

// Mostrar errores durante el desarrollo
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// ======================================================
// 1. VERIFICAR SESIÓN
// ======================================================
if (!isset($_SESSION['id_usuario'])) {
    die('Debe iniciar sesión.');
}

// ======================================================
// 2. VERIFICAR QUE SEA ADMINISTRADOR
// ======================================================
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    die('Acceso no autorizado.');
}

// ======================================================
// 3. SOLO PERMITIR POST
// ======================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido.');
}

// ======================================================
// 4. CONEXIÓN A LA BASE DE DATOS
// ======================================================
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->conectar();

if (!$db) {
    die('No se pudo conectar con la base de datos.');
}

// ======================================================
// 5. RECIBIR DATOS
// ======================================================
$id_reporte = isset($_POST['id_reporte']) ? intval($_POST['id_reporte']) : 0;
$id_tecnico = isset($_POST['id_tecnico']) ? intval($_POST['id_tecnico']) : 0;
$observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';

// ======================================================
// 6. VALIDAR DATOS
// ======================================================
if ($id_reporte <= 0) {
    die('El reporte no es válido.');
}

if ($id_tecnico <= 0) {
    die('Debe seleccionar un técnico.');
}

try {
    // ==================================================
    // 7. VERIFICAR QUE EL REPORTE EXISTA
    // ==================================================
    $sqlReporte = "
        SELECT
            r.id_reporte,
            r.id_estado,
            e.nombre AS estado
        FROM reportes r
        INNER JOIN estados e
            ON r.id_estado = e.id_estado
        WHERE r.id_reporte = :id_reporte
    ";

    $stmtReporte = $db->prepare($sqlReporte);
    $stmtReporte->execute([
        ':id_reporte' => $id_reporte
    ]);

    $reporte = $stmtReporte->fetch(PDO::FETCH_ASSOC);

    if (!$reporte) {
        die('El reporte no existe.');
    }

    // ==================================================
    // 8. VERIFICAR QUE EL TÉCNICO EXISTA Y ESTÉ ACTIVO
    // ==================================================
    $sqlTecnico = "
        SELECT
            id_usuario,
            nombre,
            apellido,
            correo
        FROM usuarios
        WHERE id_usuario = :id_tecnico
          AND id_rol = 3
          AND estado = 1
    ";

    $stmtTecnico = $db->prepare($sqlTecnico);
    $stmtTecnico->execute([
        ':id_tecnico' => $id_tecnico
    ]);

    $tecnico = $stmtTecnico->fetch(PDO::FETCH_ASSOC);

    if (!$tecnico) {
        die('El técnico seleccionado no existe o está inactivo.');
    }

    // ==================================================
    // 9. INICIAR TRANSACCIÓN
    // ==================================================
    $db->beginTransaction();

    // ==================================================
    // 10. DESACTIVAR ASIGNACIÓN ANTERIOR
    // ==================================================
    $sqlDesactivar = "
        UPDATE asignaciones
        SET activa = 0
        WHERE id_reporte = :id_reporte
          AND activa = 1
    ";

    $stmtDesactivar = $db->prepare($sqlDesactivar);
    $stmtDesactivar->execute([
        ':id_reporte' => $id_reporte
    ]);

    // ==================================================
    // 11. CREAR NUEVA ASIGNACIÓN
    // ==================================================
    $sqlAsignacion = "
        INSERT INTO asignaciones
        (
            id_reporte,
            id_tecnico,
            observacion,
            activa
        )
        VALUES
        (
            :id_reporte,
            :id_tecnico,
            :observacion,
            1
        )
    ";

    $stmtAsignacion = $db->prepare($sqlAsignacion);
    $stmtAsignacion->execute([
        ':id_reporte' => $id_reporte,
        ':id_tecnico' => $id_tecnico,
        ':observacion' => $observacion
    ]);

    // ==================================================
    // 12. CAMBIAR ESTADO A ASIGNADO
    // 1 = Pendiente
    // 3 = Asignado
    // ==================================================
    $sqlEstado = "
        UPDATE reportes
        SET id_estado = 3
        WHERE id_reporte = :id_reporte
    ";

    $stmtEstado = $db->prepare($sqlEstado);
    $stmtEstado->execute([
        ':id_reporte' => $id_reporte
    ]);

    // ==================================================
    // 13. REGISTRAR EN EL HISTORIAL
    // ==================================================
    $nombreTecnico = $tecnico['nombre'] . ' ' . $tecnico['apellido'];

    $comentario = 'El administrador asignó el reporte al técnico: ' . $nombreTecnico;

    if ($observacion !== '') {
        $comentario .= '. Observación: ' . $observacion;
    }

    $sqlSeguimiento = "
        INSERT INTO seguimientos
        (
            id_reporte,
            id_usuario,
            id_estado,
            comentario
        )
        VALUES
        (
            :id_reporte,
            :id_usuario,
            3,
            :comentario
        )
    ";

    $stmtSeguimiento = $db->prepare($sqlSeguimiento);
    $stmtSeguimiento->execute([
        ':id_reporte' => $id_reporte,
        ':id_usuario' => $_SESSION['id_usuario'],
        ':comentario' => $comentario
    ]);

    // ==================================================
    // 14. CONFIRMAR TRANSACCIÓN
    // ==================================================
    $db->commit();

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    die('Error al asignar el reporte: ' . htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    die('Error al asignar el reporte: ' . htmlspecialchars($e->getMessage()));
}

$nombreTecnicoSeguro = htmlspecialchars($nombreTecnico, ENT_QUOTES, 'UTF-8');
$idReporteSeguro = htmlspecialchars((string)$id_reporte, ENT_QUOTES, 'UTF-8');
$observacionSegura = htmlspecialchars($observacion, ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte asignado - Sistema de Mantenimiento</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f3f6fa;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .contenedor {
            width: 100%;
            max-width: 720px;
        }

        .tarjeta {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .icono {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #dcfce7;
            color: #15803d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }

        h1 {
            color: #166534;
            margin-bottom: 12px;
            font-size: 28px;
        }

        .mensaje {
            color: #4b5563;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .detalle {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            text-align: left;
            margin-bottom: 25px;
        }

        .detalle p {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .detalle p:last-child {
            margin-bottom: 0;
        }

        .botones {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        .boton {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .boton-azul {
            background: #2563eb;
            color: #ffffff;
        }

        .boton-azul:hover {
            background: #1d4ed8;
        }

        .boton-gris {
            background: #e5e7eb;
            color: #374151;
        }

        .boton-gris:hover {
            background: #d1d5db;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px;
            }

            .tarjeta {
                padding: 25px 18px;
            }

            .boton {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="contenedor">
    <div class="tarjeta">
        <div class="icono">✓</div>

        <h1>Reporte asignado correctamente</h1>

        <p class="mensaje">
            El reporte fue asignado correctamente al técnico seleccionado
            y el cambio quedó registrado en el historial.
        </p>

        <div class="detalle">
            <p>
                <strong>Reporte:</strong>
                #<?php echo $idReporteSeguro; ?>
            </p>

            <p>
                <strong>Técnico asignado:</strong>
                <?php echo $nombreTecnicoSeguro; ?>
            </p>

            <p>
                <strong>Nuevo estado:</strong>
                Asignado
            </p>

            <?php if ($observacion !== ''): ?>
                <p>
                    <strong>Observación:</strong>
                    <?php echo $observacionSegura; ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="botones">
            <a
                class="boton boton-azul"
                href="../../frontend/dashboard/reportes_admin.php"
            >
                📋 Volver a reportes
            </a>

            <a
                class="boton boton-gris"
                href="../../frontend/dashboard/administrador.php"
            >
                📊 Ir al dashboard
            </a>
        </div>
    </div>
</div>

</body>
</html>
