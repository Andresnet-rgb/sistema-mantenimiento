<?php

session_start();

// ==========================================
// 1. VERIFICAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {
    die("Debe iniciar sesión.");
}

// ==========================================
// 2. VERIFICAR QUE SEA TÉCNICO
// ==========================================

if ($_SESSION["id_rol"] != 3) {
    die("Acceso no permitido.");
}

// ==========================================
// 3. VERIFICAR ID DEL REPORTE
// ==========================================

if (!isset($_GET["id"])) {
    die("No se recibió el número del reporte.");
}

$id_reporte = intval($_GET["id"]);

// ==========================================
// 4. CONECTAR CON LA BASE DE DATOS
// ==========================================

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();

// ==========================================
// 5. OBTENER ID DEL TÉCNICO
// ==========================================

$id_tecnico = $_SESSION["id_usuario"];

// ==========================================
// 6. CONSULTAR EL REPORTE
// ==========================================

$sql = "
    SELECT
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
            usuario.nombre,
            ' ',
            usuario.apellido
        ) AS reportante

    FROM asignaciones a

    INNER JOIN reportes r
        ON a.id_reporte = r.id_reporte

    INNER JOIN categorias c
        ON r.id_categoria = c.id_categoria

    INNER JOIN ubicaciones u
        ON r.id_ubicacion = u.id_ubicacion

    INNER JOIN prioridades p
        ON r.id_prioridad = p.id_prioridad

    INNER JOIN estados e
        ON r.id_estado = e.id_estado

    INNER JOIN usuarios usuario
        ON r.id_usuario = usuario.id_usuario

    WHERE a.id_reporte = :id_reporte
    AND a.id_tecnico = :id_tecnico
    AND a.activa = 1

    LIMIT 1
";

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);

$stmt->bindParam(
    ":id_tecnico",
    $id_tecnico,
    PDO::PARAM_INT
);

$stmt->execute();

$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 7. VERIFICAR SI EXISTE
// ==========================================

if (!$reporte) {
    die("El reporte no existe o no está asignado a este técnico.");
}

// ==========================================
// 8. OBTENER HISTORIAL
// ==========================================

$sqlHistorial = "
    SELECT
        s.comentario,
        s.fecha,

        e.nombre AS estado,

        CONCAT(
            u.nombre,
            ' ',
            u.apellido
        ) AS usuario

    FROM seguimientos s

    INNER JOIN estados e
        ON s.id_estado = e.id_estado

    INNER JOIN usuarios u
        ON s.id_usuario = u.id_usuario

    WHERE s.id_reporte = :id_reporte

    ORDER BY s.fecha DESC
";

$stmtHistorial = $db->prepare($sqlHistorial);

$stmtHistorial->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);

$stmtHistorial->execute();

$seguimientos = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 9. OBTENER EVIDENCIAS
// ==========================================

$sqlEvidencias = "
    SELECT
        id_evidencia,
        nombre_archivo,
        ruta_archivo,
        tipo,
        fecha_subida

    FROM evidencias

    WHERE id_reporte = :id_reporte

    ORDER BY fecha_subida DESC
";

$stmtEvidencias = $db->prepare($sqlEvidencias);

$stmtEvidencias->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);

$stmtEvidencias->execute();

$evidencias = $stmtEvidencias->fetchAll(PDO::FETCH_ASSOC);

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
        Detalle del reporte
    </title>

    <style>

        /* ==========================================
           CONFIGURACIÓN GENERAL
           ========================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        body {
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

            background:
                linear-gradient(
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

            padding: 13px 15px;

            border-radius: 9px;

            color: white;

            text-decoration: none;

            font-size: 15px;

            transition: 0.2s;
        }

        .menu a:hover {
            background:
                rgba(255, 255, 255, 0.15);
        }

        .menu a.activo {
            background:
                rgba(255, 255, 255, 0.18);

            font-weight: 600;
        }

        .menu a.logout {
            margin-top: 20px;

            background: #dc2626;

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

            padding: 35px;
        }

        /* ==========================================
           ENCABEZADO
           ========================================== */

        .encabezado {
            margin-bottom: 25px;
        }

        .encabezado h1 {
            color: #111827;

            font-size: 28px;

            margin-bottom: 8px;
        }

        .encabezado p {
            color: #6b7280;

            font-size: 15px;
        }

        /* ==========================================
           TARJETAS
           ========================================== */

        .tarjeta {
            background: white;

            border-radius: 14px;

            padding: 28px;

            border: 1px solid #e5e7eb;

            box-shadow:
                0 5px 18px
                rgba(0, 0, 0, 0.05);

            margin-bottom: 20px;
        }

        .tarjeta h2 {
            color: #1f2937;

            font-size: 20px;

            margin-bottom: 20px;
        }

        /* ==========================================
           DATOS DEL REPORTE
           ========================================== */

        .datos-reporte {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 16px;
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

        .badge-pendiente {
            background: #f3f4f6;

            color: #4b5563;
        }

        .badge-asignado {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .badge-proceso {
            background: #fef3c7;

            color: #92400e;
        }

        .badge-resuelto {
            background: #dcfce7;

            color: #166534;
        }

        .badge-cerrado {
            background: #e5e7eb;

            color: #374151;
        }

        /* ==========================================
           EVIDENCIAS
           ========================================== */

        .evidencias {
            margin-top: 25px;

            padding-top: 25px;

            border-top: 1px solid #e5e7eb;
        }

        .evidencias h3 {
            font-size: 17px;

            color: #1f2937;

            margin-bottom: 15px;
        }

        .evidencia-item {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 15px;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

            margin-bottom: 10px;
        }

        .evidencia-info {
            min-width: 0;
        }

        .evidencia-nombre {
            color: #1f2937;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 5px;

            word-break: break-word;
        }

        .evidencia-meta {
            color: #6b7280;

            font-size: 12px;

            line-height: 1.5;
        }

        .boton-evidencia {
            display: inline-block;

            flex-shrink: 0;

            padding: 9px 14px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s;
        }

        .boton-evidencia:hover {
            background: #1d4ed8;
        }

        .sin-evidencia {
            padding: 15px;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

            color: #6b7280;

            font-size: 14px;
        }

        /* ==========================================
           ACCIONES
           ========================================== */

        .acciones {
            display: flex;

            gap: 10px;

            flex-wrap: wrap;

            margin-top: 25px;

            padding-top: 25px;

            border-top: 1px solid #e5e7eb;
        }

        .boton {
            display: inline-block;

            padding: 11px 18px;

            border: none;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s;
        }

        .boton-azul {
            background: #2563eb;

            color: white;
        }

        .boton-azul:hover {
            background: #1d4ed8;
        }

        .boton-verde {
            background: #16a34a;

            color: white;
        }

        .boton-verde:hover {
            background: #15803d;
        }

        .boton-gris {
            background: #6b7280;

            color: white;
        }

        .boton-gris:hover {
            background: #4b5563;
        }

        .boton-rojo {
            background: #dc2626;

            color: white;
        }

        .boton-rojo:hover {
            background: #b91c1c;
        }

        .mensaje-resuelto {
            display: inline-block;

            padding: 11px 16px;

            background: #dcfce7;

            color: #166534;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 600;
        }

        /* ==========================================
           HISTORIAL
           ========================================== */

        .historial-item {
            position: relative;

            padding: 0 0 20px 22px;

            margin-bottom: 20px;

            border-left: 3px solid #2563eb;
        }

        .historial-item:last-child {
            margin-bottom: 0;
        }

        .historial-estado {
            color: #1f2937;

            font-size: 15px;

            font-weight: 700;

            margin-bottom: 7px;
        }

        .historial-comentario {
            color: #374151;

            font-size: 14px;

            line-height: 1.5;

            margin-bottom: 8px;
        }

        .historial-meta {
            color: #6b7280;

            font-size: 12px;
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

            .evidencia-item {
                flex-direction: column;

                align-items: flex-start;
            }

            .boton-evidencia {
                width: 100%;

                text-align: center;
            }

            .acciones {
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

    <!-- ==========================================
         SIDEBAR
         ========================================== -->

    <aside class="sidebar">

        <div class="logo">
            🛠️ Mantenimiento
        </div>

        <nav class="menu">

            <a href="tecnico.php">
                📊 Dashboard
            </a>

            <a
                href="reportes_asignados.php"
                class="activo"
            >
                📋 Mis reportes
            </a>

            <a
                href="../../backend/auth/logout.php"
                class="logout"
            >
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
                📋 Detalle del reporte #
                <?php echo htmlspecialchars($reporte["id_reporte"]); ?>
            </h1>

            <p>
                Técnico encargado:
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"]
                        . " "
                        . $_SESSION["apellido"]
                    );
                    ?>
                </strong>
            </p>

        </div>


        <!-- ==========================================
             INFORMACIÓN DEL REPORTE
             ========================================== -->

        <div class="tarjeta">

            <h2>
                Información del reporte
            </h2>

            <div class="datos-reporte">

                <!-- TÍTULO -->

                <div class="dato dato-completo">

                    <span class="dato-label">
                        Título
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars(
                            $reporte["titulo"]
                        );
                        ?>
                    </span>

                </div>


                <!-- DESCRIPCIÓN -->

                <div class="dato dato-completo">

                    <span class="dato-label">
                        Descripción
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $reporte["descripcion"]
                            )
                        );
                        ?>
                    </span>

                </div>


                <!-- CATEGORÍA -->

                <div class="dato">

                    <span class="dato-label">
                        Categoría
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars(
                            $reporte["categoria"]
                        );
                        ?>
                    </span>

                </div>


                <!-- UBICACIÓN -->

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


                <!-- PRIORIDAD -->

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


                <!-- ESTADO -->

                <div class="dato">

                    <span class="dato-label">
                        Estado
                    </span>

                    <span class="dato-valor">

                        <?php

                        $claseEstado = "badge-pendiente";

                        switch ($reporte["estado"]) {

                            case "Asignado":
                                $claseEstado = "badge-asignado";
                                break;

                            case "En proceso":
                                $claseEstado = "badge-proceso";
                                break;

                            case "Resuelto":
                                $claseEstado = "badge-resuelto";
                                break;

                            case "Cerrado":
                                $claseEstado = "badge-cerrado";
                                break;
                        }

                        ?>

                        <span
                            class="badge <?php echo $claseEstado; ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $reporte["estado"]
                            );
                            ?>

                        </span>

                    </span>

                </div>


                <!-- REPORTANTE -->

                <div class="dato">

                    <span class="dato-label">
                        Reportado por
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars(
                            $reporte["reportante"]
                        );
                        ?>
                    </span>

                </div>


                <!-- FECHA -->

                <div class="dato">

                    <span class="dato-label">
                        Fecha del reporte
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars(
                            $reporte["fecha_reporte"]
                        );
                        ?>
                    </span>

                </div>

            </div>


            <!-- ==========================================
                 EVIDENCIAS
                 ========================================== -->

            <div class="evidencias">

                <h3>
                    📎 Evidencias del reporte
                </h3>


                <?php if (count($evidencias) > 0): ?>

                    <?php foreach ($evidencias as $evidencia): ?>

                        <div class="evidencia-item">

                            <div class="evidencia-info">

                                <div class="evidencia-nombre">

                                    📄
                                    <?php
                                    echo htmlspecialchars(
                                        $evidencia["nombre_archivo"]
                                    );
                                    ?>

                                </div>

                                <div class="evidencia-meta">

                                    Tipo:
                                    <?php
                                    echo htmlspecialchars(
                                        $evidencia["tipo"]
                                    );
                                    ?>

                                    <br>

                                    Fecha:
                                    <?php
                                    echo htmlspecialchars(
                                        $evidencia["fecha_subida"]
                                    );
                                    ?>

                                </div>

                            </div>


                            <a
                                href="../../<?php
                                    echo htmlspecialchars(
                                        $evidencia["ruta_archivo"]
                                    );
                                ?>"
                                target="_blank"
                                class="boton-evidencia"
                            >
                                🔎 Ver evidencia
                            </a>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="sin-evidencia">

                        No hay evidencias adjuntas a este reporte.

                    </div>

                <?php endif; ?>

            </div>


            <!-- ==========================================
                 ACCIONES SEGÚN EL ESTADO
                 ========================================== -->

            <div class="acciones">

                <?php if ($reporte["estado"] == "Asignado"): ?>

                    <!-- REPORTE ASIGNADO -->

                    <a
                        href="actualizar_estado.php?id=<?php
                            echo $reporte["id_reporte"];
                        ?>&estado=4"
                        class="boton boton-azul"
                    >
                        🔧 Iniciar mantenimiento
                    </a>


                <?php elseif ($reporte["estado"] == "En proceso"): ?>

                    <!-- REPORTE EN PROCESO -->

                    <a
                        href="registrar_actividad.php?id=<?php
                            echo $reporte["id_reporte"];
                        ?>"
                        class="boton boton-azul"
                    >
                        📝 Registrar actividad
                    </a>

                    <a
                        href="resolver_reporte.php?id=<?php
                            echo $reporte["id_reporte"];
                        ?>"
                        class="boton boton-verde"
                    >
                        ✅ Marcar como resuelto
                    </a>


                <?php elseif ($reporte["estado"] == "Resuelto"): ?>

                    <!-- REPORTE RESUELTO -->

                    <span class="mensaje-resuelto">
                        ✅ Este reporte ya fue resuelto.
                    </span>


                <?php elseif ($reporte["estado"] == "Cerrado"): ?>

                    <!-- REPORTE CERRADO -->

                    <span class="mensaje-resuelto">
                        🔒 Este reporte está cerrado.
                    </span>

                <?php endif; ?>


                <a
                    href="reportes_asignados.php"
                    class="boton boton-gris"
                >
                    ← Volver a mis reportes
                </a>

            </div>

        </div>


        <!-- ==========================================
             HISTORIAL
             ========================================== -->

        <div class="tarjeta">

            <h2>
                📜 Historial del reporte
            </h2>


            <?php if (count($seguimientos) == 0): ?>

                <div class="sin-evidencia">

                    No hay actividades registradas.

                </div>


            <?php else: ?>

                <?php foreach ($seguimientos as $seguimiento): ?>

                    <div class="historial-item">

                        <div class="historial-estado">

                            <?php
                            echo htmlspecialchars(
                                $seguimiento["estado"]
                            );
                            ?>

                        </div>


                        <div class="historial-comentario">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $seguimiento["comentario"]
                                )
                            );
                            ?>

                        </div>


                        <div class="historial-meta">

                            👤
                            <?php
                            echo htmlspecialchars(
                                $seguimiento["usuario"]
                            );
                            ?>

                            &nbsp; | &nbsp;

                            📅
                            <?php
                            echo htmlspecialchars(
                                $seguimiento["fecha"]
                            );
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </main>

</body>

</html>