<?php

session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/* =========================================================
   1. VERIFICAR SESIÓN
   ========================================================= */
if (!isset($_SESSION['id_usuario'])) {
    die('Debe iniciar sesión.');
}

/* =========================================================
   2. VERIFICAR QUE SEA TÉCNICO
   ========================================================= */
if (!isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 3) {
    die('Acceso no permitido.');
}

/* =========================================================
   3. SOLO PERMITIR POST
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido.');
}

/* =========================================================
   4. RECIBIR DATOS
   ========================================================= */
$id_reporte = isset($_POST['id_reporte'])
    ? intval($_POST['id_reporte'])
    : 0;

$comentario = isset($_POST['comentario'])
    ? trim($_POST['comentario'])
    : '';

/* =========================================================
   5. VALIDAR DATOS
   ========================================================= */
if ($id_reporte <= 0) {
    die('El reporte no es válido.');
}

if ($comentario === '') {
    die('Debe escribir un comentario de resolución.');
}

/* =========================================================
   6. CONEXIÓN A LA BASE DE DATOS
   ========================================================= */
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->conectar();

if (!$db) {
    die('No se pudo conectar con la base de datos.');
}

try {

    /* =====================================================
       7. VERIFICAR QUE EL REPORTE ESTÉ ASIGNADO
          AL TÉCNICO ACTUAL Y QUE ESTÉ EN PROCESO
       ===================================================== */
    $sqlReporte = "
        SELECT
            r.id_reporte,
            r.id_estado,
            e.nombre AS estado
        FROM reportes r
        INNER JOIN estados e
            ON r.id_estado = e.id_estado
        INNER JOIN asignaciones a
            ON a.id_reporte = r.id_reporte
        WHERE r.id_reporte = :id_reporte
          AND a.id_tecnico = :id_tecnico
          AND a.activa = 1
        LIMIT 1
    ";

    $stmtReporte = $db->prepare($sqlReporte);

    $stmtReporte->execute([
        ':id_reporte' => $id_reporte,
        ':id_tecnico' => (int)$_SESSION['id_usuario']
    ]);

    $reporte = $stmtReporte->fetch(PDO::FETCH_ASSOC);

    if (!$reporte) {
        die('El reporte no está asignado a este técnico.');
    }

    /* 4 = En proceso */
    if ((int)$reporte['id_estado'] !== 4) {
        die(
            'El reporte no puede resolverse porque su estado actual es: '
            . htmlspecialchars(
                $reporte['estado'],
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    /* =====================================================
       8. INICIAR TRANSACCIÓN
       ===================================================== */
    $db->beginTransaction();

    /* =====================================================
       9. CAMBIAR ESTADO A RESUELTO
          5 = Resuelto
       ===================================================== */
    $sqlEstado = "
        UPDATE reportes
        SET id_estado = 5
        WHERE id_reporte = :id_reporte
          AND id_estado = 4
    ";

    $stmtEstado = $db->prepare($sqlEstado);

    $stmtEstado->execute([
        ':id_reporte' => $id_reporte
    ]);

    if ($stmtEstado->rowCount() !== 1) {
        throw new Exception(
            'No fue posible actualizar el estado del reporte.'
        );
    }

    /* =====================================================
       10. REGISTRAR LA RESOLUCIÓN EN EL HISTORIAL
       ===================================================== */
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
            5,
            :comentario
        )
    ";

    $stmtSeguimiento = $db->prepare($sqlSeguimiento);

    $stmtSeguimiento->execute([
        ':id_reporte' => $id_reporte,
        ':id_usuario' => (int)$_SESSION['id_usuario'],
        ':comentario' => $comentario
    ]);

    /* =====================================================
       11. CONFIRMAR TRANSACCIÓN
       ===================================================== */
    $db->commit();

} catch (PDOException $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    die(
        'Error al resolver el reporte: '
        . htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );

} catch (Exception $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    die(
        'Error al resolver el reporte: '
        . htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

/* =========================================================
   12. PREPARAR DATOS SEGUROS PARA LA CONFIRMACIÓN
   ========================================================= */
$idReporteSeguro = htmlspecialchars(
    (string)$id_reporte,
    ENT_QUOTES,
    'UTF-8'
);

$comentarioSeguro = htmlspecialchars(
    $comentario,
    ENT_QUOTES,
    'UTF-8'
);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reporte resuelto - Sistema de Mantenimiento
    </title>

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
        }

        .menu {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(
                180deg,
                #243b88 0%,
                #2563eb 100%
            );
            color: white;
            padding: 28px 18px;
        }

        .menu h2 {
            text-align: center;
            margin-bottom: 35px;
            font-size: 21px;
        }

        .menu ul {
            list-style: none;
        }

        .menu li {
            margin-bottom: 8px;
        }

        .menu a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 13px 15px;
            border-radius: 9px;
            transition: 0.2s ease;
        }

        .menu a:hover,
        .menu a.activo {
            background: rgba(255, 255, 255, 0.15);
            font-weight: 600;
        }

        .menu a.cerrar {
            background: #dc2626;
            margin-top: 25px;
        }

        .menu a.cerrar:hover {
            background: #b91c1c;
        }

        .contenido {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            padding: 40px;
        }

        .contenedor {
            width: 100%;
            max-width: 850px;
            margin: 0 auto;
        }

        .tarjeta {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 40px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.07);
        }

        .icono-exito {
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
            font-weight: bold;
        }

        h1 {
            text-align: center;
            color: #166534;
            font-size: 28px;
            margin-bottom: 12px;
        }

        .descripcion {
            text-align: center;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .reporte {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .estado {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 700;
            margin-top: 8px;
        }

        .comentario {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 22px;
        }

        .comentario-titulo {
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .comentario-texto {
            color: #374151;
            line-height: 1.6;
            white-space: normal;
            word-wrap: break-word;
        }

        .mensaje {
            text-align: center;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 28px;
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
            border-radius: 9px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .boton-principal {
            background: #2563eb;
            color: white;
        }

        .boton-principal:hover {
            background: #1d4ed8;
        }

        .boton-secundario {
            background: #e5e7eb;
            color: #374151;
        }

        .boton-secundario:hover {
            background: #d1d5db;
        }

        @media (max-width: 800px) {

            .menu {
                position: relative;
                width: 100%;
                height: auto;
            }

            .contenido {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .tarjeta {
                padding: 25px 18px;
            }

        }

    </style>

</head>

<body>

<aside class="menu">

    <h2>🛠️ Mantenimiento</h2>

    <ul>

        <li>
            <a
                href="../../frontend/dashboard/tecnico.php"
            >
                📊 Dashboard
            </a>
        </li>

        <li>
            <a
                href="../../frontend/dashboard/reportes_asignados.php"
                class="activo"
            >
                📋 Mis reportes
            </a>
        </li>

        <li>
            <a
                href="../../backend/auth/logout.php"
                class="cerrar"
            >
                🚪 Cerrar sesión
            </a>
        </li>

    </ul>

</aside>

<main class="contenido">

    <div class="contenedor">

        <div class="tarjeta">

            <div class="icono-exito">
                ✓
            </div>

            <h1>
                Reporte resuelto correctamente
            </h1>

            <p class="descripcion">
                El reporte fue marcado como resuelto correctamente
                y el comentario quedó registrado en el historial.
            </p>

            <div class="reporte">

                El reporte

                <strong>
                    #<?php echo $idReporteSeguro; ?>
                </strong>

                ahora se encuentra en estado:

                <br>

                <span class="estado">
                    ✓ Resuelto
                </span>

            </div>

            <div class="comentario">

                <div class="comentario-titulo">
                    📝 Comentario de resolución
                </div>

                <div class="comentario-texto">
                    <?php echo nl2br($comentarioSeguro); ?>
                </div>

            </div>

            <p class="mensaje">
                El cambio de estado y el comentario quedaron
                registrados en el historial del reporte.
            </p>

            <div class="botones">

                <a
                    href="../../frontend/dashboard/detalle_reporte.php?id=<?php echo $idReporteSeguro; ?>"
                    class="boton boton-principal"
                >
                    📋 Ver reporte
                </a>

                <a
                    href="../../frontend/dashboard/reportes_asignados.php"
                    class="boton boton-secundario"
                >
                    🔧 Mis reportes
                </a>

            </div>

        </div>

    </div>

</main>

</body>

</html>
