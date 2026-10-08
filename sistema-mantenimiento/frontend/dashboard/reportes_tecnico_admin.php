<?php

session_start();

/*
|--------------------------------------------------------------------------
| VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.html");
    exit;

}


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE SEA ADMINISTRADOR
|--------------------------------------------------------------------------
*/

if ($_SESSION["id_rol"] != 1) {

    echo "Acceso no autorizado.";
    exit;

}


/*
|--------------------------------------------------------------------------
| CONEXIÓN A LA BASE DE DATOS
|--------------------------------------------------------------------------
*/

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| OBTENER ID DEL TÉCNICO
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"])) {

    echo "No se especificó el técnico.";
    exit;

}

$id_tecnico = intval($_GET["id"]);


/*
|--------------------------------------------------------------------------
| OBTENER INFORMACIÓN DEL TÉCNICO
|--------------------------------------------------------------------------
*/

$sql_tecnico = "

    SELECT
        id_usuario,
        nombre,
        apellido,
        correo,
        telefono,
        estado

    FROM usuarios

    WHERE id_usuario = :id_tecnico
    AND id_rol = 3

";

$stmt_tecnico = $db->prepare($sql_tecnico);

$stmt_tecnico->bindParam(
    ":id_tecnico",
    $id_tecnico,
    PDO::PARAM_INT
);

$stmt_tecnico->execute();

$tecnico = $stmt_tecnico->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE EL TÉCNICO EXISTA
|--------------------------------------------------------------------------
*/

if (!$tecnico) {

    echo "El técnico no existe.";
    exit;

}


/*
|--------------------------------------------------------------------------
| OBTENER REPORTES ASIGNADOS
|--------------------------------------------------------------------------
*/

$sql_reportes = "

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

    INNER JOIN usuarios usr
        ON r.id_usuario = usr.id_usuario

    WHERE a.id_tecnico = :id_tecnico

    AND a.activa = 1

    ORDER BY r.fecha_reporte DESC

";

$stmt_reportes = $db->prepare($sql_reportes);

$stmt_reportes->bindParam(
    ":id_tecnico",
    $id_tecnico,
    PDO::PARAM_INT
);

$stmt_reportes->execute();

$reportes = $stmt_reportes->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CONTADORES DE REPORTES POR ESTADO
|--------------------------------------------------------------------------
*/

$sql_estados = "

    SELECT

        e.id_estado,
        e.nombre,
        COUNT(r.id_reporte) AS cantidad

    FROM estados e

    LEFT JOIN reportes r
        ON r.id_estado = e.id_estado

    LEFT JOIN asignaciones a
        ON a.id_reporte = r.id_reporte
        AND a.id_tecnico = :id_tecnico
        AND a.activa = 1

    WHERE a.id_asignacion IS NOT NULL

    GROUP BY
        e.id_estado,
        e.nombre

    ORDER BY
        e.id_estado

";

$stmt_estados = $db->prepare($sql_estados);

$stmt_estados->bindParam(
    ":id_tecnico",
    $id_tecnico,
    PDO::PARAM_INT
);

$stmt_estados->execute();

$estados_tecnico = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| INICIALIZAR CONTADORES
|--------------------------------------------------------------------------
*/

$pendientes = 0;
$en_proceso = 0;
$resueltos = 0;
$cerrados = 0;


/*
|--------------------------------------------------------------------------
| ORGANIZAR LOS CONTADORES
|--------------------------------------------------------------------------
*/

foreach ($estados_tecnico as $estado) {

    if ($estado["id_estado"] == 1) {

        $pendientes = $estado["cantidad"];

    }

    elseif ($estado["id_estado"] == 4) {

        $en_proceso = $estado["cantidad"];

    }

    elseif ($estado["id_estado"] == 5) {

        $resueltos = $estado["cantidad"];

    }

    elseif ($estado["id_estado"] == 6) {

        $cerrados = $estado["cantidad"];

    }

}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reportes del técnico - Sistema de Mantenimiento</title>


    <style>

        /*
        |--------------------------------------------------------------------------
        | REINICIO GENERAL
        |--------------------------------------------------------------------------
        */

        * {

            margin: 0;
            padding: 0;
            box-sizing: border-box;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        /*
        |--------------------------------------------------------------------------
        | CUERPO
        |--------------------------------------------------------------------------
        */

        body {

            background: #f3f6fa;

            color: #1f2937;

            min-height: 100vh;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENEDOR PRINCIPAL
        |--------------------------------------------------------------------------
        */

        .contenedor {

            min-height: 100vh;

        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .menu {

            width: 260px;

            height: 100vh;

            position: fixed;

            top: 0;
            left: 0;

            background:
                linear-gradient(
                    180deg,
                    #243b88 0%,
                    #2563eb 100%
                );

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

        }


        .menu ul {

            list-style: none;

        }


        .menu li {

            margin-bottom: 8px;

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

        }


        /*
        | Opción activa
        */

        .menu a.activo {

            background: rgba(255, 255, 255, 0.20);

            font-weight: 600;

        }


        /*
        | Botón cerrar sesión
        */

        .menu a.cerrar {

            margin-top: 25px;

            background: #dc2626;

        }


        .menu a.cerrar:hover {

            background: #b91c1c;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENIDO PRINCIPAL
        |--------------------------------------------------------------------------
        */

        .contenido {

            margin-left: 260px;

            width: calc(100% - 260px);

            padding: 30px;

            min-height: 100vh;

        }


        /*
        |--------------------------------------------------------------------------
        | ENCABEZADO
        |--------------------------------------------------------------------------
        */

        .encabezado {

            background: white;

            border-radius: 14px;

            padding: 25px 30px;

            margin-bottom: 24px;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

            border: 1px solid #e5e7eb;

        }


        .encabezado h1 {

            font-size: 30px;

            color: #111827;

            margin-bottom: 8px;

            font-weight: 600;

        }


        .encabezado p {

            color: #6b7280;

            font-size: 15px;

        }


        /*
        |--------------------------------------------------------------------------
        | BOTÓN VOLVER
        |--------------------------------------------------------------------------
        */

        .boton-volver {

            display: inline-block;

            padding: 10px 16px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 24px;

            transition: 0.2s;

        }


        .boton-volver:hover {

            background: #1d4ed8;

        }


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN DEL TÉCNICO
        |--------------------------------------------------------------------------
        */

        .informacion-tecnico {

            background: white;

            padding: 25px;

            border-radius: 14px;

            margin-bottom: 24px;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

            border: 1px solid #e5e7eb;

        }


        .informacion-tecnico h2 {

            color: #111827;

            font-size: 22px;

            margin-bottom: 18px;

        }


        .dato {

            margin-bottom: 10px;

            color: #4b5563;

            font-size: 15px;

        }


        .dato strong {

            color: #1f2937;

        }


        /*
        |--------------------------------------------------------------------------
        | ESTADO DEL TÉCNICO
        |--------------------------------------------------------------------------
        */

        .estado-tecnico {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .estado-activo {

            background: #dcfce7;

            color: #166534;

        }


        .estado-inactivo {

            background: #fee2e2;

            color: #991b1b;

        }


        /*
        |--------------------------------------------------------------------------
        | RESUMEN DE ESTADOS
        |--------------------------------------------------------------------------
        */

        .resumen-estados {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

            margin-top: 22px;

        }


        .tarjeta-estado {

            background: #f9fafb;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

            padding: 18px;

            text-align: center;

            transition: 0.2s;

        }


        .tarjeta-estado:hover {

            transform: translateY(-2px);

            box-shadow:
                0 4px 10px rgba(0, 0, 0, 0.06);

        }


        .tarjeta-estado .icono {

            display: block;

            font-size: 24px;

            margin-bottom: 7px;

        }


        .tarjeta-estado h3 {

            font-size: 14px;

            color: #374151;

            margin-bottom: 8px;

        }


        .tarjeta-estado p {

            font-size: 27px;

            font-weight: 700;

            color: #111827;

        }


        /*
        |--------------------------------------------------------------------------
        | COLORES DE LOS ESTADOS
        |--------------------------------------------------------------------------
        */

        .estado-pendiente {

            border-top: 4px solid #f59e0b;

        }


        .estado-proceso {

            border-top: 4px solid #2563eb;

        }


        .estado-resuelto {

            border-top: 4px solid #10b981;

        }


        .estado-cerrado {

            border-top: 4px solid #6b7280;

        }


        /*
        |--------------------------------------------------------------------------
        | TARJETAS DE REPORTES
        |--------------------------------------------------------------------------
        */

        .tarjeta-reporte {

            background: white;

            padding: 24px;

            border-radius: 14px;

            margin-bottom: 20px;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

            border: 1px solid #e5e7eb;

        }


        .tarjeta-reporte h3 {

            color: #111827;

            font-size: 19px;

            margin-bottom: 18px;

        }


        .tarjeta-reporte .dato {

            margin-bottom: 10px;

            line-height: 1.5;

        }


        /*
        |--------------------------------------------------------------------------
        | BADGE DE ESTADO DEL REPORTE
        |--------------------------------------------------------------------------
        */

        .estado-reporte {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

            background: #eff6ff;

            color: #1d4ed8;

        }


        /*
        |--------------------------------------------------------------------------
        | BOTONES DE ACCIÓN
        |--------------------------------------------------------------------------
        */

        .acciones {

            display: flex;

            flex-wrap: wrap;

            gap: 8px;

            margin-top: 18px;

        }


        .boton-detalle,
        .boton-historial {

            display: inline-block;

            padding: 9px 14px;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s;

        }


        .boton-detalle {

            background: #2563eb;

        }


        .boton-detalle:hover {

            background: #1d4ed8;

        }


        .boton-historial {

            background: #059669;

        }


        .boton-historial:hover {

            background: #047857;

        }


        /*
        |--------------------------------------------------------------------------
        | SIN REPORTES
        |--------------------------------------------------------------------------
        */

        .sin-reportes {

            background: white;

            padding: 40px 25px;

            border-radius: 14px;

            text-align: center;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

            border: 1px solid #e5e7eb;

        }


        .sin-reportes h2 {

            color: #374151;

            margin-bottom: 10px;

            font-size: 20px;

        }


        .sin-reportes p {

            color: #6b7280;

            font-size: 14px;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {

            .resumen-estados {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 700px) {

            .contenedor {

                display: block;

            }


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


            .encabezado {

                padding: 20px;

            }


            .encabezado h1 {

                font-size: 24px;

            }


            .resumen-estados {

                grid-template-columns: 1fr;

            }


            .informacion-tecnico {

                padding: 20px;

            }


            .tarjeta-reporte {

                padding: 20px;

            }

        }


    </style>

</head>


<body>


<div class="contenedor">


    <!-- =====================================================
         MENÚ LATERAL
         ===================================================== -->

    <aside class="menu">

        <h2>🛠️ Mantenimiento</h2>

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

                <a
                    href="tecnicos.php"
                    class="activo"
                >

                    🔧 Técnicos

                </a>

            </li>


            <li>

                <a href="estadisticas.php">

                    📈 Estadísticas

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



    <!-- =====================================================
         CONTENIDO PRINCIPAL
         ===================================================== -->

    <main class="contenido">


        <!-- ENCABEZADO -->

        <div class="encabezado">

            <h1>

                📋 Reportes del técnico

            </h1>

            <p>

                Consulta los reportes asignados al técnico seleccionado.

            </p>

        </div>



        <!-- BOTÓN VOLVER -->

        <a
            href="tecnicos.php"
            class="boton-volver"
        >

            ← Volver a técnicos

        </a>



        <!-- =================================================
             INFORMACIÓN DEL TÉCNICO
             ================================================= -->

        <div class="informacion-tecnico">

            <h2>

                🔧

                <?php

                echo htmlspecialchars(
                    $tecnico["nombre"]
                    . " "
                    . $tecnico["apellido"]
                );

                ?>

            </h2>


            <p class="dato">

                <strong>
                    Correo:
                </strong>

                <?php

                echo htmlspecialchars(
                    $tecnico["correo"]
                );

                ?>

            </p>


            <p class="dato">

                <strong>
                    Teléfono:
                </strong>

                <?php

                echo htmlspecialchars(
                    $tecnico["telefono"] ?? "No registrado"
                );

                ?>

            </p>


            <p class="dato">

                <strong>
                    Estado:
                </strong>

                <?php if ($tecnico["estado"] == 1): ?>

                    <span class="estado-tecnico estado-activo">

                        🟢 Activo

                    </span>

                <?php else: ?>

                    <span class="estado-tecnico estado-inactivo">

                        🔴 Inactivo

                    </span>

                <?php endif; ?>

            </p>


            <p class="dato">

                <strong>
                    Reportes asignados:
                </strong>

                <?php

                echo count($reportes);

                ?>

            </p>



            <!-- =============================================
                 RESUMEN DE ESTADOS
                 ============================================= -->

            <div class="resumen-estados">


                <!-- PENDIENTES -->

                <div class="tarjeta-estado estado-pendiente">

                    <span class="icono">

                        🟡

                    </span>

                    <h3>

                        Pendientes

                    </h3>

                    <p>

                        <?php echo $pendientes; ?>

                    </p>

                </div>



                <!-- EN PROCESO -->

                <div class="tarjeta-estado estado-proceso">

                    <span class="icono">

                        🔵

                    </span>

                    <h3>

                        En proceso

                    </h3>

                    <p>

                        <?php echo $en_proceso; ?>

                    </p>

                </div>



                <!-- RESUELTOS -->

                <div class="tarjeta-estado estado-resuelto">

                    <span class="icono">

                        🟢

                    </span>

                    <h3>

                        Resueltos

                    </h3>

                    <p>

                        <?php echo $resueltos; ?>

                    </p>

                </div>



                <!-- CERRADOS -->

                <div class="tarjeta-estado estado-cerrado">

                    <span class="icono">

                        🔒

                    </span>

                    <h3>

                        Cerrados

                    </h3>

                    <p>

                        <?php echo $cerrados; ?>

                    </p>

                </div>


            </div>

        </div>



        <!-- =================================================
             REPORTES
             ================================================= -->

        <?php if (count($reportes) > 0): ?>


            <?php foreach ($reportes as $reporte): ?>


                <div class="tarjeta-reporte">


                    <h3>

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

                    </h3>


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
                            Estado:
                        </strong>

                        <span class="estado-reporte">

                            <?php

                            echo htmlspecialchars(
                                $reporte["estado"]
                            );

                            ?>

                        </span>

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
                            Fecha:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $reporte["fecha_reporte"]
                        );

                        ?>

                    </p>



                    <!-- =====================================
                         BOTONES
                         ===================================== -->

                    <div class="acciones">


                        <a
                            class="boton-detalle"
                            href="detalle_reporte.php?id=<?php echo $reporte["id_reporte"]; ?>"
                        >

                            🔎 Ver detalle

                        </a>


                        <a
                            class="boton-historial"
                            href="historial_reporte_admin.php?id=<?php echo $reporte["id_reporte"]; ?>&tecnico=<?php echo $id_tecnico; ?>"
                        >

                            📜 Ver historial

                        </a>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- SIN REPORTES -->

            <div class="sin-reportes">

                <h2>

                    📭 No hay reportes asignados

                </h2>

                <p>

                    Este técnico no tiene reportes asignados actualmente.

                </p>

            </div>


        <?php endif; ?>


    </main>

</div>


</body>

</html>