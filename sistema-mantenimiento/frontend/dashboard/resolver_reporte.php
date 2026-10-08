<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
if ((int)($_SESSION['id_rol'] ?? 0) !== 3) {
    die('Acceso no autorizado.');
}

/* =========================================================
   3. OBTENER ID DEL REPORTE
   ========================================================= */
if (!isset($_GET['id'])) {
    die('No se especificó el reporte.');
}

$id_reporte = (int)$_GET['id'];

if ($id_reporte <= 0) {
    die('El reporte no es válido.');
}

/* =========================================================
   4. CONEXIÓN
   ========================================================= */
require_once __DIR__ . '/../../backend/config/database.php';

$database = new Database();
$db = $database->conectar();

/* =========================================================
   5. VERIFICAR QUE EL REPORTE ESTÉ ASIGNADO AL TÉCNICO
   ========================================================= */
$sql = "
    SELECT
        r.id_reporte,
        r.titulo,
        r.descripcion,
        r.id_estado,
        e.nombre AS estado,
        CONCAT(usr.nombre, ' ', usr.apellido) AS reportante,
        c.nombre AS categoria,
        u.bloque,
        u.espacio,
        p.nombre AS prioridad
    FROM reportes r
    INNER JOIN estados e
        ON r.id_estado = e.id_estado
    INNER JOIN usuarios usr
        ON r.id_usuario = usr.id_usuario
    INNER JOIN categorias c
        ON r.id_categoria = c.id_categoria
    INNER JOIN ubicaciones u
        ON r.id_ubicacion = u.id_ubicacion
    INNER JOIN prioridades p
        ON r.id_prioridad = p.id_prioridad
    INNER JOIN asignaciones a
        ON a.id_reporte = r.id_reporte
    WHERE r.id_reporte = :id_reporte
      AND a.id_tecnico = :id_tecnico
      AND a.activa = 1
    LIMIT 1
";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':id_reporte' => $id_reporte,
    ':id_tecnico' => (int)$_SESSION['id_usuario']
]);

$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporte) {
    die('El reporte no está asignado a este técnico.');
}

/* =========================================================
   6. SOLO SE PUEDE RESOLVER SI ESTÁ EN PROCESO
   ========================================================= */
if ((int)$reporte['id_estado'] !== 4) {
    die(
        'El reporte no puede resolverse porque su estado actual es: '
        . htmlspecialchars($reporte['estado'], ENT_QUOTES, 'UTF-8')
    );
}

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolver reporte - Sistema de Mantenimiento</title>

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
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.activo {
            background: rgba(255,255,255,0.15);
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
            max-width: 900px;
            margin: 0 auto;
        }

        .tarjeta {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }

        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }

        .subtitulo {
            color: #6b7280;
            margin-bottom: 25px;
        }

        .datos {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }

        .dato {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
        }

        .dato strong {
            display: block;
            margin-bottom: 6px;
            color: #374151;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 9px;
        }

        textarea {
            width: 100%;
            min-height: 150px;
            resize: vertical;
            padding: 13px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            outline: none;
            font-size: 15px;
        }

        textarea:focus {
            border-color: #2563eb;
        }

        .botones {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .boton {
            display: inline-block;
            padding: 12px 18px;
            border: none;
            border-radius: 9px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
        }

        .principal {
            background: #2563eb;
            color: white;
        }

        .principal:hover {
            background: #1d4ed8;
        }

        .secundario {
            background: #e5e7eb;
            color: #374151;
        }

        .secundario:hover {
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

            .datos {
                grid-template-columns: 1fr;
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
    <div class="contenedor">

        <div class="tarjeta">
            <h1>Resolver reporte #<?= h($reporte['id_reporte']) ?></h1>

            <p class="subtitulo">
                Registra el comentario que describe cómo se solucionó el problema.
            </p>

            <div class="datos">
                <div class="dato">
                    <strong>Título</strong>
                    <?= h($reporte['titulo']) ?>
                </div>

                <div class="dato">
                    <strong>Estado actual</strong>
                    <?= h($reporte['estado']) ?>
                </div>

                <div class="dato">
                    <strong>Reportante</strong>
                    <?= h($reporte['reportante']) ?>
                </div>

                <div class="dato">
                    <strong>Categoría</strong>
                    <?= h($reporte['categoria']) ?>
                </div>

                <div class="dato">
                    <strong>Ubicación</strong>
                    <?= h($reporte['bloque']) ?> -
                    <?= h($reporte['espacio']) ?>
                </div>

                <div class="dato">
                    <strong>Prioridad</strong>
                    <?= h($reporte['prioridad']) ?>
                </div>
            </div>

            <form action="../../backend/reportes/resolver_tecnico.php" method="POST">

                <input
                    type="hidden"
                    name="id_reporte"
                    value="<?= h($reporte['id_reporte']) ?>"
                >

                <label for="comentario">
                    Comentario de resolución
                </label>

                <textarea
                    name="comentario"
                    id="comentario"
                    required
                    placeholder="Describa el mantenimiento realizado y cómo se solucionó el problema..."
                ></textarea>

                <div class="botones">
                    <button type="submit" class="boton principal">
                        ✅ Resolver reporte
                    </button>

                    <a
                        href="../../frontend/dashboard/detalle_reporte.php?id=<?= h($reporte['id_reporte']) ?>"
                        class="boton secundario"
                    >
                        ↩️ Cancelar
                    </a>
                </div>

            </form>
        </div>

    </div>
</main>

</body>
</html>
