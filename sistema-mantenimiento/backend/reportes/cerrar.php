<?php
session_start();

/* =========================================================
   1. VERIFICAR SESIÓN Y ROL
   ========================================================= */
if (!isset($_SESSION['id_usuario'])) {
    die('Debe iniciar sesión.');
}

if ($_SESSION['id_rol'] != 1) {
    die('Acceso no autorizado.');
}

/* =========================================================
   2. SOLO ACEPTAR POST
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido.');
}

/* =========================================================
   3. CONEXIÓN A LA BASE DE DATOS
   ========================================================= */
require_once '../config/database.php';

$database = new Database();
$db = $database->conectar();

/* =========================================================
   4. OBTENER ID DEL REPORTE
   ========================================================= */
if (!isset($_POST['id_reporte'])) {
    die('No se indicó el reporte.');
}

$id_reporte = intval($_POST['id_reporte']);

/* =========================================================
   5. BUSCAR EL REPORTE
   ========================================================= */
$sql = "
    SELECT
        r.id_reporte,
        r.id_estado,
        e.nombre AS estado
    FROM reportes r
    INNER JOIN estados e
        ON r.id_estado = e.id_estado
    WHERE r.id_reporte = :id_reporte
";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':id_reporte' => $id_reporte
]);

$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporte) {
    die('El reporte no existe.');
}

/* =========================================================
   6. SOLO SE PUEDE CERRAR SI ESTÁ RESUELTO
   ========================================================= */
if ($reporte['estado'] !== 'Resuelto') {
    die(
        'El reporte no puede cerrarse porque su estado actual es: '
        . htmlspecialchars($reporte['estado'])
    );
}

/* =========================================================
   7. PROCESAR CIERRE
   ========================================================= */
try {
    $db->beginTransaction();

    /* Cambiar estado: 5 = Resuelto, 6 = Cerrado */
    $sqlActualizar = "
        UPDATE reportes
        SET id_estado = 6
        WHERE id_reporte = :id_reporte
    ";

    $stmtActualizar = $db->prepare($sqlActualizar);
    $stmtActualizar->execute([
        ':id_reporte' => $id_reporte
    ]);

    /* Registrar cierre en el historial */
    $comentario =
        'El administrador cerró el reporte después de verificar '
        . 'que el mantenimiento fue completado.';

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
            6,
            :comentario
        )
    ";

    $stmtSeguimiento = $db->prepare($sqlSeguimiento);
    $stmtSeguimiento->execute([
        ':id_reporte' => $id_reporte,
        ':id_usuario' => $_SESSION['id_usuario'],
        ':comentario' => $comentario
    ]);

    $db->commit();

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    die(
        'Error al cerrar el reporte: '
        . htmlspecialchars($e->getMessage())
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte cerrado - Sistema de Mantenimiento</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f6fa;
            color: #1f2937;
            min-height: 100vh;
        }

        .contenedor {
            min-height: 100vh;
        }

        /* ================================
           MENÚ LATERAL
           ================================ */
        .menu {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, #243b88 0%, #2563eb 100%);
            color: white;
            padding: 25px 18px;
            overflow-y: auto;
            z-index: 1000;
        }

        .menu h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
            font-weight: 600;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.25);
        }

        .menu ul {
            list-style: none;
        }

        .menu li {
            margin-bottom: 7px;
        }

        .menu a {
            display: block;
            text-decoration: none;
            color: white;
            padding: 13px 15px;
            border-radius: 8px;
            font-size: 15px;
            transition: 0.2s;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(3px);
        }

        .menu a.activo {
            background: rgba(255, 255, 255, 0.20);
            font-weight: 600;
        }

        .menu a.cerrar {
            margin-top: 25px;
            background: #dc2626;
        }

        .menu a.cerrar:hover {
            background: #b91c1c;
        }

        /* ================================
           CONTENIDO
           ================================ */
        .contenido {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ================================
           TARJETA DE CONFIRMACIÓN
           ================================ */
        .tarjeta-confirmacion {
            width: 100%;
            max-width: 700px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 45px 50px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .icono-exito {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            font-size: 36px;
            font-weight: bold;
        }

        .tarjeta-confirmacion h1 {
            color: #111827;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .descripcion {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .reporte {
            padding: 20px;
            margin-bottom: 25px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            color: #166534;
            font-size: 16px;
        }

        .reporte strong {
            font-size: 21px;
        }

        .estado {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 13px;
            border-radius: 20px;
            background: #e5e7eb;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        .mensaje {
            margin-bottom: 25px;
            color: #6b7280;
            font-size: 14px;
        }

        .botones {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .boton {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
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
            color: #374151;
        }

        .boton-secundario:hover {
            background: #d1d5db;
        }

        /* ================================
           RESPONSIVE
           ================================ */
        @media (max-width: 700px) {
            .menu {
                position: relative;
                width: 100%;
                height: auto;
                min-height: auto;
            }

            .menu ul {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }

            .menu li {
                margin-bottom: 0;
            }

            .menu a {
                padding: 10px 12px;
            }

            .menu a.cerrar {
                margin-top: 10px;
            }

            .contenido {
                margin-left: 0;
                width: 100%;
                padding: 20px 15px;
            }

            .tarjeta-confirmacion {
                padding: 35px 22px;
            }

            .tarjeta-confirmacion h1 {
                font-size: 24px;
            }

            .botones {
                flex-direction: column;
            }

            .boton {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="contenedor">

        <!-- MENÚ LATERAL -->
        <aside class="menu">
            <h2>🛠️ Mantenimiento</h2>

            <ul>
                <li>
                    <a href="../../frontend/dashboard/administrador.php">
                        📊 Dashboard
                    </a>
                </li>

                <li>
                    <a href="../../frontend/dashboard/usuarios.php">
                        👥 Usuarios
                    </a>
                </li>

                <li>
                    <a href="../../frontend/dashboard/reportes_admin.php" class="activo">
                        📋 Reportes
                    </a>
                </li>

                <li>
                    <a href="../../frontend/dashboard/tecnicos.php">
                        🔧 Técnicos
                    </a>
                </li>

                <li>
                    <a href="../../frontend/dashboard/estadisticas.php">
                        📈 Estadísticas
                    </a>
                </li>

                <li>
                    <a href="../../backend/auth/logout.php" class="cerrar">
                        🚪 Cerrar sesión
                    </a>
                </li>
            </ul>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="contenido">
            <div class="tarjeta-confirmacion">

                <div class="icono-exito">✓</div>

                <h1>Reporte cerrado correctamente</h1>

                <p class="descripcion">
                    El reporte ha sido cerrado correctamente después de verificar
                    que el mantenimiento fue completado.
                </p>

                <div class="reporte">
                    El reporte
                    <strong>#<?php echo htmlspecialchars($id_reporte); ?></strong>
                    ahora se encuentra en estado:
                    <br>
                    <span class="estado">Cerrado</span>
                </div>

                <p class="mensaje">
                    El cambio de estado también fue registrado en el historial
                    del reporte.
                </p>

                <div class="botones">
                    <a
                        href="../../frontend/dashboard/reportes_admin.php"
                        class="boton boton-principal"
                    >
                        📋 Volver a reportes
                    </a>

                    <a
                        href="../../frontend/dashboard/administrador.php"
                        class="boton boton-secundario"
                    >
                        📊 Ir al dashboard
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
