<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    die('Debe iniciar sesión.');
}

// 2. Verificar que sea técnico
if ((int)($_SESSION['id_rol'] ?? 0) !== 3) {
    die('Acceso no permitido.');
}

// 3. Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido.');
}

// 4. Recibir datos
$id_reporte = isset($_POST['id_reporte']) ? (int)$_POST['id_reporte'] : 0;
$actividad = trim($_POST['actividad'] ?? '');

if ($id_reporte <= 0) {
    die('El reporte no es válido.');
}

if ($actividad === '') {
    die('Debe escribir la actividad realizada.');
}

// 5. Conectar a la base de datos
require_once '../config/database.php';

$database = new Database();
$db = $database->conectar();

try {
    // 6. Verificar que el reporte esté asignado al técnico actual
    $sql = '
        SELECT id_reporte
        FROM asignaciones
        WHERE id_reporte = :id_reporte
          AND id_tecnico = :id_tecnico
          AND activa = 1
        LIMIT 1
    ';

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':id_reporte' => $id_reporte,
        ':id_tecnico' => (int)$_SESSION['id_usuario']
    ]);

    $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asignacion) {
        die('El reporte no está asignado a este técnico.');
    }

    // 7. Obtener el estado actual del reporte
    $sql = '
        SELECT id_estado
        FROM reportes
        WHERE id_reporte = :id_reporte
        LIMIT 1
    ';

    $stmt = $db->prepare($sql);
    $stmt->execute([':id_reporte' => $id_reporte]);

    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reporte) {
        die('El reporte no existe.');
    }

    // 8. Registrar la actividad en el historial
    $sql = '
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
            :id_estado,
            :comentario
        )
    ';

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':id_reporte' => $id_reporte,
        ':id_usuario' => (int)$_SESSION['id_usuario'],
        ':id_estado' => (int)$reporte['id_estado'],
        ':comentario' => $actividad
    ]);

    // 9. Preparar datos seguros para mostrar
    $id_reporte_seguro = htmlspecialchars((string)$id_reporte, ENT_QUOTES, 'UTF-8');
    $actividad_segura = htmlspecialchars($actividad, ENT_QUOTES, 'UTF-8');

} catch (PDOException $e) {
    die('Error al registrar la actividad: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividad registrada - Sistema de Mantenimiento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f3f6fa;
            color: #1f2937;
            min-height: 100vh;
        }

        .menu {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, #243b88 0%, #2563eb 100%);
            color: white;
            padding: 28px 18px;
        }

        .menu h2 {
            text-align: center;
            margin-bottom: 35px;
            font-size: 22px;
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
            padding: 13px 14px;
            border-radius: 9px;
            transition: 0.2s;
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
            padding: 50px 35px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .tarjeta-confirmacion {
            width: 100%;
            max-width: 800px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
        }

        .icono-exito {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: bold;
        }

        h1 {
            color: #1f2937;
            margin-bottom: 15px;
        }

        .descripcion {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
        }

        .reporte {
            margin-top: 25px;
            padding: 16px;
            background: #eff6ff;
            border-radius: 10px;
            color: #1e3a8a;
        }

        .actividad {
            margin-top: 20px;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            text-align: left;
        }

        .actividad-titulo {
            font-weight: bold;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .actividad-texto {
            color: #4b5563;
            line-height: 1.6;
            white-space: normal;
        }

        .estado {
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            background: #dcfce7;
            color: #166534;
            font-weight: 600;
        }

        .mensaje {
            color: #6b7280;
            line-height: 1.5;
        }

        .botones {
            margin-top: 25px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .boton {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
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
            color: #1f2937;
        }

        .boton-secundario:hover {
            background: #d1d5db;
        }

        @media (max-width: 800px) {
            .menu {
                position: static;
                width: 100%;
                height: auto;
            }

            .contenido {
                margin-left: 0;
                width: 100%;
                padding: 25px 15px;
            }

            .tarjeta-confirmacion {
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
                <a href="../../frontend/dashboard/tecnico.php">
                    📊 Dashboard
                </a>
            </li>
            <li>
                <a href="../../frontend/dashboard/reportes_asignados.php" class="activo">
                    📋 Mis reportes
                </a>
            </li>
            <li>
                <a href="../../backend/auth/logout.php" class="cerrar">
                    🚪 Cerrar sesión
                </a>
            </li>
        </ul>
    </aside>

    <main class="contenido">
        <div class="tarjeta-confirmacion">
            <div class="icono-exito">✓</div>

            <h1>Actividad registrada correctamente</h1>

            <p class="descripcion">
                La actividad realizada fue registrada correctamente en el historial del reporte.
            </p>

            <div class="reporte">
                Actividad registrada para el reporte
                <strong>#<?php echo $id_reporte_seguro; ?></strong>
            </div>

            <div class="actividad">
                <div class="actividad-titulo">
                    🔧 Actividad realizada
                </div>
                <div class="actividad-texto">
                    <?php echo nl2br($actividad_segura); ?>
                </div>
            </div>

            <div class="estado">
                📋 Actividad registrada en el historial
            </div>

            <br><br>

            <p class="mensaje">
                La información quedó almacenada en el historial del reporte.
            </p>

            <div class="botones">
                <a
                    href="../../frontend/dashboard/detalle_reporte.php?id=<?php echo $id_reporte_seguro; ?>"
                    class="boton boton-principal"
                >
                    📋 Volver al reporte
                </a>

                <a
                    href="../../frontend/dashboard/reportes_asignados.php"
                    class="boton boton-secundario"
                >
                    🔧 Mis reportes
                </a>
            </div>
        </div>
    </main>
</body>
</html>
