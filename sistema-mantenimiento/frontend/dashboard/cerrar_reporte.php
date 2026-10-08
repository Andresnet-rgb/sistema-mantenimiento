<?php

session_start();

// ==========================================
// MOSTRAR ERRORES MIENTRAS DESARROLLAMOS
// ==========================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==========================================
// 1. VERIFICAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {
    die("Debe iniciar sesión.");
}

// ==========================================
// 2. VERIFICAR QUE SEA ADMINISTRADOR
// ==========================================

if ($_SESSION["id_rol"] != 1) {
    die("Acceso no autorizado.");
}

// ==========================================
// 3. CONEXIÓN A LA BASE DE DATOS
// ==========================================

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();

// ==========================================
// 4. OBTENER ID DEL REPORTE
// ==========================================

if (!isset($_GET["id"])) {
    die("No se indicó el reporte.");
}

$id_reporte = intval($_GET["id"]);

// ==========================================
// 5. BUSCAR INFORMACIÓN DEL REPORTE
// ==========================================

$sql = "SELECT
            r.id_reporte,
            r.titulo,
            r.descripcion,
            r.fecha_reporte,

            c.nombre AS categoria,

            u.bloque,
            u.espacio,

            p.nombre AS prioridad,

            e.nombre AS estado,

            CONCAT(
                us.nombre,
                ' ',
                us.apellido
            ) AS reportante

        FROM reportes r

        INNER JOIN categorias c
            ON r.id_categoria = c.id_categoria

        INNER JOIN ubicaciones u
            ON r.id_ubicacion = u.id_ubicacion

        INNER JOIN prioridades p
            ON r.id_prioridad = p.id_prioridad

        INNER JOIN estados e
            ON r.id_estado = e.id_estado

        INNER JOIN usuarios us
            ON r.id_usuario = us.id_usuario

        WHERE r.id_reporte = :id_reporte";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":id_reporte" => $id_reporte
]);

$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 6. VERIFICAR SI EXISTE EL REPORTE
// ==========================================

if (!$reporte) {
    die("El reporte no existe.");
}

// ==========================================
// 7. SOLO SE PUEDE CERRAR UN REPORTE RESUELTO
// ==========================================

if ($reporte["estado"] != "Resuelto") {
    die("Este reporte todavía no está en estado Resuelto.");
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Cerrar reporte</title>

    <style>

        /* ==========================================
           RESET
           ========================================== */

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

        /* ==========================================
           SIDEBAR
           ========================================== */

        .sidebar {
            position: fixed;

            top: 0;
            left: 0;

            width: 260px;
            height: 100vh;

            background: linear-gradient(
                180deg,
                #243b88 0%,
                #2563eb 100%
            );

            color: white;

            padding: 25px 18px;

            z-index: 1000;
        }

        .logo {
            text-align: center;

            font-size: 21px;

            font-weight: bold;

            margin-bottom: 35px;
        }

        .menu {
            display: flex;

            flex-direction: column;

            gap: 8px;
        }

        .menu a {
            display: block;

            text-decoration: none;

            color: white;

            padding: 13px 15px;

            border-radius: 9px;

            font-size: 15px;

            transition: 0.2s;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .menu a.activo {
            background: rgba(255, 255, 255, 0.18);

            font-weight: 600;
        }

        .menu a.logout {
            background: #dc2626;

            margin-top: 20px;

            text-align: center;

            font-weight: 600;
        }

        .menu a.logout:hover {
            background: #b91c1c;
        }

        /* ==========================================
           CONTENIDO
           ========================================== */

        .contenido {
            margin-left: 260px;

            width: calc(100% - 260px);

            min-height: 100vh;

            padding: 40px;
        }

        /* ==========================================
           ENCABEZADO
           ========================================== */

        .encabezado {
            margin-bottom: 25px;
        }

        .encabezado h1 {
            font-size: 28px;

            color: #1f2937;

            margin-bottom: 8px;
        }

        .encabezado p {
            color: #6b7280;

            font-size: 15px;
        }

        /* ==========================================
           TARJETA PRINCIPAL
           ========================================== */

        .tarjeta {
            max-width: 950px;

            margin: 0 auto;

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 30px;

            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.05);
        }

        .tarjeta h2 {
            color: #1f2937;

            font-size: 21px;

            margin-bottom: 25px;
        }

        /* ==========================================
           ALERTA DE CONFIRMACIÓN
           ========================================== */

        .alerta {
            display: flex;

            align-items: flex-start;

            gap: 14px;

            background: #fff7ed;

            border: 1px solid #fed7aa;

            border-radius: 10px;

            padding: 16px;

            margin-bottom: 25px;

            color: #9a3412;
        }

        .alerta-icono {
            font-size: 22px;

            line-height: 1;
        }

        .alerta-contenido strong {
            display: block;

            margin-bottom: 5px;

            font-size: 15px;
        }

        .alerta-contenido span {
            font-size: 14px;

            line-height: 1.5;
        }

        /* ==========================================
           DATOS DEL REPORTE
           ========================================== */

        .datos-reporte {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 30px;
        }

        .dato {
            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

            padding: 15px;
        }

        .dato-completo {
            grid-column: 1 / -1;
        }

        .dato-label {
            display: block;

            color: #6b7280;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 6px;
        }

        .dato-valor {
            color: #1f2937;

            font-size: 15px;

            line-height: 1.5;
        }

        /* ==========================================
           BADGES
           ========================================== */

        .badge {
            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 600;
        }

        .badge-prioridad {
            background: #fef3c7;

            color: #92400e;
        }

        .badge-resuelto {
            background: #dcfce7;

            color: #166534;
        }

        /* ==========================================
           CONFIRMACIÓN FINAL
           ========================================== */

        .confirmacion {
            border-top: 1px solid #e5e7eb;

            padding-top: 25px;

            text-align: center;
        }

        .confirmacion-titulo {
            color: #374151;

            font-size: 16px;

            font-weight: 600;

            margin-bottom: 8px;
        }

        .confirmacion-texto {
            color: #6b7280;

            font-size: 14px;

            margin-bottom: 20px;
        }

        /* ==========================================
           BOTONES
           ========================================== */

        .acciones {
            display: flex;

            justify-content: center;

            align-items: center;

            gap: 10px;

            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;

            border: none;

            border-radius: 9px;

            padding: 12px 20px;

            font-family: inherit;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

            cursor: pointer;

            transition: 0.2s;
        }

        .btn-cerrar {
            background: #dc2626;

            color: white;
        }

        .btn-cerrar:hover {
            background: #b91c1c;
        }

        .btn-cancelar {
            background: #6b7280;

            color: white;
        }

        .btn-cancelar:hover {
            background: #4b5563;
        }

        /* ==========================================
           RESPONSIVE
           ========================================== */

        @media (max-width: 900px) {

            .datos-reporte {
                grid-template-columns: 1fr;
            }

            .dato-completo {
                grid-column: auto;
            }

        }

        @media (max-width: 768px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;

                padding: 20px;
            }

            .logo {
                margin-bottom: 20px;
            }

            .contenido {
                margin-left: 0;

                width: 100%;

                padding: 25px 20px;
            }

            .encabezado h1 {
                font-size: 24px;
            }

            .tarjeta {
                padding: 22px;
            }

            .acciones {
                flex-direction: column;

                align-items: stretch;
            }

            .btn {
                width: 100%;

                text-align: center;
            }

        }

    </style>

</head>

<body>

    <!-- ==========================================
         MENÚ LATERAL
         ========================================== -->

    <aside class="sidebar">

        <div class="logo">
            🛠️ Mantenimiento
        </div>

        <nav class="menu">

            <a href="administrador.php">
                📊 Dashboard
            </a>

            <a href="usuarios.php">
                👥 Usuarios
            </a>

            <a href="reportes_admin.php"
               class="activo">
                📋 Reportes
            </a>

            <a href="tecnicos.php">
                🔧 Técnicos
            </a>

            <a href="estadisticas.php">
                📈 Estadísticas
            </a>

            <a href="../../backend/auth/logout.php"
               class="logout">
                🚪 Cerrar sesión
            </a>

        </nav>

    </aside>


    <!-- ==========================================
         CONTENIDO PRINCIPAL
         ========================================== -->

    <main class="contenido">

        <!-- ==========================================
             ENCABEZADO
             ========================================== -->

        <div class="encabezado">

            <h1>
                🔒 Cerrar reporte
            </h1>

            <p>
                Confirma que el mantenimiento del reporte fue completado.
            </p>

        </div>


        <!-- ==========================================
             TARJETA DEL REPORTE
             ========================================== -->

        <div class="tarjeta">

            <div class="alerta">

                <div class="alerta-icono">
                    ⚠️
                </div>

                <div class="alerta-contenido">

                    <strong>
                        Confirmación de cierre
                    </strong>

                    <span>
                        Al cerrar este reporte, su estado cambiará de
                        <strong>Resuelto</strong> a
                        <strong>Cerrado</strong>.
                    </span>

                </div>

            </div>


            <h2>
                📋 Información del reporte
                #<?php echo htmlspecialchars($reporte["id_reporte"]); ?>
            </h2>


            <!-- ==========================================
                 DATOS DEL REPORTE
                 ========================================== -->

            <div class="datos-reporte">

                <div class="dato">

                    <span class="dato-label">
                        Título
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars($reporte["titulo"]);
                        ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Reportante
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars($reporte["reportante"]);
                        ?>
                    </span>

                </div>


                <div class="dato dato-completo">

                    <span class="dato-label">
                        Descripción
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo nl2br(
                            htmlspecialchars($reporte["descripcion"])
                        );
                        ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Categoría
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars($reporte["categoria"]);
                        ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Ubicación
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars(
                            $reporte["bloque"]
                            . " - "
                            . $reporte["espacio"]
                        );
                        ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Prioridad
                    </span>

                    <span class="dato-valor">

                        <span class="badge badge-prioridad">
                            <?php
                            echo htmlspecialchars(
                                $reporte["prioridad"]
                            );
                            ?>
                        </span>

                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Estado actual
                    </span>

                    <span class="dato-valor">

                        <span class="badge badge-resuelto">
                            ✓
                            <?php
                            echo htmlspecialchars(
                                $reporte["estado"]
                            );
                            ?>
                        </span>

                    </span>

                </div>

            </div>


            <!-- ==========================================
                 CONFIRMACIÓN FINAL
                 ========================================== -->

            <div class="confirmacion">

                <div class="confirmacion-titulo">
                    ¿Deseas cerrar este reporte?
                </div>

                <div class="confirmacion-texto">
                    Esta acción registrará el cierre en el historial
                    del reporte.
                </div>


                <!-- ==========================================
                     FORMULARIO DE CIERRE
                     ========================================== -->

                <form
                    action="../../backend/reportes/cerrar.php"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="id_reporte"
                        value="<?php echo htmlspecialchars(
                            $reporte["id_reporte"]
                        ); ?>"
                    >

                    <div class="acciones">

                        <button
                            type="submit"
                            class="btn btn-cerrar"
                        >
                            🔒 Confirmar cierre del reporte
                        </button>

                        <a
                            href="reportes_admin.php"
                            class="btn btn-cancelar"
                        >
                            ↩ Cancelar
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </main>

</body>

</html>