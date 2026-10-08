<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SESSION["id_rol"] != 1) {
    echo "Acceso no autorizado.";
    exit;
}

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();


// ======================================================
// OBTENER ID DEL REPORTE
// ======================================================

if (!isset($_GET["id"])) {
    echo "No se especificó el reporte.";
    exit;
}

$id_reporte = intval($_GET["id"]);


// ======================================================
// OBTENER INFORMACIÓN DEL REPORTE
// ======================================================

$sql_reporte = "
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
            usr.nombre,
            ' ',
            usr.apellido
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

    INNER JOIN usuarios usr
        ON r.id_usuario = usr.id_usuario

    WHERE r.id_reporte = :id_reporte
";

$stmt_reporte = $db->prepare($sql_reporte);

$stmt_reporte->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);

$stmt_reporte->execute();

$reporte = $stmt_reporte->fetch(PDO::FETCH_ASSOC);


// ======================================================
// VERIFICAR QUE EL REPORTE EXISTA
// ======================================================

if (!$reporte) {
    echo "El reporte no existe.";
    exit;
}


// ======================================================
// OBTENER HISTORIAL
// ======================================================

$sql_historial = "
    SELECT

        s.id_seguimiento,

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

    ORDER BY s.fecha ASC
";

$stmt_historial = $db->prepare($sql_historial);

$stmt_historial->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);

$stmt_historial->execute();

$historial = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>
        Historial del reporte
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css"
    >

    <style>

        .informacion-reporte {

            background-color: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 25px;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.1);

        }


        .dato {

            margin-bottom: 8px;

        }


        .historial {

            position: relative;

            margin-top: 25px;

        }


        .seguimiento {

            background-color: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

            border-left: 5px solid #2563eb;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.1);

        }


        .seguimiento h3 {

            margin-bottom: 10px;

        }


        .seguimiento .fecha {

            color: #6b7280;

            font-size: 14px;

            margin-top: 10px;

        }


        .seguimiento .usuario {

            font-weight: bold;

            margin-top: 8px;

        }


        .boton-volver {

            display: inline-block;

            padding: 10px 15px;

            background-color: #6b7280;

            color: white;

            text-decoration: none;

            border-radius: 5px;

            margin-bottom: 20px;

        }


        .boton-volver:hover {

            background-color: #4b5563;

        }


        .sin-historial {

            background-color: white;

            padding: 25px;

            border-radius: 10px;

            text-align: center;

        }

    </style>

</head>


<body>

<div class="contenedor">


    <!-- ========================================== -->
    <!-- MENÚ LATERAL -->
    <!-- ========================================== -->

    <aside class="menu">

        <h2>
            🛠️ Mantenimiento
        </h2>

        <ul>

            <li>
                <a href="administrador.php">
                    📊 Dashboard
                </a>
            </li>

            <li>
                <a href="usuarios.php">
                    👥 Usuarios
                </a>
            </li>

            <li>
                <a href="reportes_admin.php">
                    📋 Reportes
                </a>
            </li>

            <li>
                <a href="tecnicos.php">
                    🔧 Técnicos
                </a>
            </li>

            <li>
                <a href="estadisticas.php">
                    📊 Estadísticas
                </a>
            </li>

            <li>
                <a href="../../backend/auth/logout.php">
                    🚪 Cerrar sesión
                </a>
            </li>

        </ul>

    </aside>


    <!-- ========================================== -->
    <!-- CONTENIDO -->
    <!-- ========================================== -->

    <main class="contenido">


        <div class="encabezado">

            <h1>
                📜 Historial del reporte
            </h1>

            <p>
                Consulta completa del seguimiento del reporte.
            </p>

        </div>


        <!-- VOLVER -->

        <?php if (isset($_GET["tecnico"])): ?>

    <a
        href="reportes_tecnico_admin.php?id=<?php echo intval($_GET["tecnico"]); ?>"
        class="boton-volver"
    >
        ← Volver a reportes del técnico
    </a>

<?php else: ?>

    <a
        href="tecnicos.php"
        class="boton-volver"
    >
        ← Volver a técnicos
    </a>

<?php endif; ?>


        <!-- ========================================== -->
        <!-- INFORMACIÓN DEL REPORTE -->
        <!-- ========================================== -->

        <div class="informacion-reporte">

            <h2>

                📋 Reporte #

                <?php

                echo $reporte["id_reporte"];

                ?>

                -

                <?php

                echo htmlspecialchars(
                    $reporte["titulo"]
                );

                ?>

            </h2>


            <p class="dato">

                <strong>
                    Descripción:
                </strong>

                <?php

                echo htmlspecialchars(
                    $reporte["descripcion"]
                );

                ?>

            </p>


            <p class="dato">

                <strong>
                    Categoría:
                </strong>

                <?php

                echo htmlspecialchars(
                    $reporte["categoria"]
                );

                ?>

            </p>


            <p class="dato">

                <strong>
                    Ubicación:
                </strong>

                <?php

                echo htmlspecialchars(
                    $reporte["bloque"]
                    . " - "
                    . $reporte["espacio"]
                );

                ?>

            </p>


            <p class="dato">

                <strong>
                    Prioridad:
                </strong>

                <?php

                echo htmlspecialchars(
                    $reporte["prioridad"]
                );

                ?>

            </p>


            <p class="dato">

                <strong>
                    Estado actual:
                </strong>

                <?php

                echo htmlspecialchars(
                    $reporte["estado"]
                );

                ?>

            </p>


            <p class="dato">

                <strong>
                    Reportado por:
                </strong>

                <?php

                echo htmlspecialchars(
                    $reporte["reportante"]
                );

                ?>

            </p>


            <p class="dato">

                <strong>
                    Fecha de reporte:
                </strong>

                <?php

                echo htmlspecialchars(
                    $reporte["fecha_reporte"]
                );

                ?>

            </p>

        </div>


        <!-- ========================================== -->
        <!-- HISTORIAL -->
        <!-- ========================================== -->

        <div class="historial">


            <?php if (count($historial) > 0): ?>


                <?php foreach ($historial as $seguimiento): ?>


                    <div class="seguimiento">


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $seguimiento["estado"]
                            );

                            ?>

                        </h3>


                        <p>

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $seguimiento["comentario"]
                                )
                            );

                            ?>

                        </p>


                        <p class="usuario">

                            👤

                            <?php

                            echo htmlspecialchars(
                                $seguimiento["usuario"]
                            );

                            ?>

                        </p>


                        <p class="fecha">

                            📅

                            <?php

                            echo htmlspecialchars(
                                $seguimiento["fecha"]
                            );

                            ?>

                        </p>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="sin-historial">

                    <h2>
                        📭 Sin historial
                    </h2>

                    <p>
                        Este reporte todavía no tiene registros de seguimiento.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </main>

</div>

</body>

</html>