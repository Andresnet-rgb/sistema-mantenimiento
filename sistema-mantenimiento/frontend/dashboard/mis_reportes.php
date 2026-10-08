<?php

session_start();

/*
|--------------------------------------------------------------------------
| MOSTRAR ERRORES DURANTE EL DESARROLLO
|--------------------------------------------------------------------------
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


/*
|--------------------------------------------------------------------------
| 1. VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["id_usuario"])) {

    die("Debe iniciar sesión.");

}


/*
|--------------------------------------------------------------------------
| 2. VERIFICAR QUE SEA REPORTANTE
|--------------------------------------------------------------------------
*/

if ($_SESSION["id_rol"] != 2) {

    die("Acceso no permitido.");

}


/*
|--------------------------------------------------------------------------
| 3. CONECTAR CON LA BASE DE DATOS
|--------------------------------------------------------------------------
*/

require_once "../../backend/config/database.php";

$database = new Database();

$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| 4. CONSULTAR LOS REPORTES DEL USUARIO
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        r.id_reporte,
        r.titulo,
        r.descripcion,
        r.fecha_reporte,

        c.nombre AS categoria,

        CONCAT(
            ub.bloque,
            ' - ',
            ub.espacio
        ) AS ubicacion,

        p.nombre AS prioridad,

        e.nombre AS estado

    FROM reportes r

    INNER JOIN categorias c
        ON r.id_categoria = c.id_categoria

    INNER JOIN ubicaciones ub
        ON r.id_ubicacion = ub.id_ubicacion

    INNER JOIN prioridades p
        ON r.id_prioridad = p.id_prioridad

    INNER JOIN estados e
        ON r.id_estado = e.id_estado

    WHERE r.id_usuario = :id_usuario

    ORDER BY r.fecha_reporte DESC

";


$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_usuario",
    $_SESSION["id_usuario"],
    PDO::PARAM_INT
);

$stmt->execute();

$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| 5. CONSULTAR LAS EVIDENCIAS
|--------------------------------------------------------------------------
*/

$sql_evidencias = "

    SELECT

        id_evidencia,
        id_reporte,
        nombre_archivo,
        ruta_archivo,
        tipo

    FROM evidencias

    WHERE id_reporte = :id_reporte

    ORDER BY fecha_subida DESC

";


$stmt_evidencias = $db->prepare($sql_evidencias);

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
        Mis reportes - Sistema de Mantenimiento
    </title>


    <style>

        /* =========================================================
           CONFIGURACIÓN GENERAL
        ========================================================= */

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


        /* =========================================================
           CONTENEDOR
        ========================================================= */

        .contenedor {

            min-height: 100vh;

        }


        /* =========================================================
           MENÚ LATERAL
        ========================================================= */

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

            background:
                rgba(255, 255, 255, 0.15);

        }


        /* =========================================================
           OPCIÓN ACTIVA
        ========================================================= */

        .menu a.activo {

            background:
                rgba(255, 255, 255, 0.20);

            font-weight: 600;

        }


        /* =========================================================
           CERRAR SESIÓN
        ========================================================= */

        .menu a.cerrar {

            margin-top: 25px;

            background: #dc2626;

        }


        .menu a.cerrar:hover {

            background: #b91c1c;

        }


        /* =========================================================
           CONTENIDO PRINCIPAL
        ========================================================= */

        .contenido {

            margin-left: 260px;

            width: calc(100% - 260px);

            min-height: 100vh;

            padding: 30px;

            background: #f3f6fa;

        }


        /* =========================================================
           ENCABEZADO
        ========================================================= */

        .encabezado {

            background: white;

            border-radius: 14px;

            padding: 25px 30px;

            margin-bottom: 24px;

            border: 1px solid #e5e7eb;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

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


        .encabezado strong {

            color: #374151;

        }


        /* =========================================================
           TARJETA DEL REPORTE
        ========================================================= */

        .tarjeta {

            background: white;

            border-radius: 14px;

            padding: 25px;

            margin-bottom: 20px;

            border: 1px solid #e5e7eb;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

        }


        /* =========================================================
           ENCABEZADO DE CADA REPORTE
        ========================================================= */

        .reporte-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding-bottom: 16px;

            margin-bottom: 20px;

            border-bottom: 1px solid #e5e7eb;

        }


        .reporte-header h2 {

            color: #111827;

            font-size: 20px;

            font-weight: 600;

        }


        .numero-reporte {

            color: #2563eb;

        }


        /* =========================================================
           INFORMACIÓN DEL REPORTE
        ========================================================= */

        .informacion {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 14px 25px;

        }


        .dato {

            padding: 12px 14px;

            background: #f9fafb;

            border-radius: 8px;

            border: 1px solid #f0f1f3;

            font-size: 14px;

            color: #4b5563;

        }


        .dato.completo {

            grid-column: 1 / -1;

        }


        .dato strong {

            display: block;

            color: #374151;

            font-size: 13px;

            margin-bottom: 5px;

        }


        /* =========================================================
           BADGES
        ========================================================= */

        .badge {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        /* ESTADOS */

        .estado-pendiente {

            background: #fef3c7;

            color: #92400e;

        }


        .estado-revision {

            background: #e0f2fe;

            color: #075985;

        }


        .estado-asignado {

            background: #dbeafe;

            color: #1e40af;

        }


        .estado-proceso {

            background: #ede9fe;

            color: #5b21b6;

        }


        .estado-resuelto {

            background: #dcfce7;

            color: #166534;

        }


        .estado-cerrado {

            background: #e5e7eb;

            color: #374151;

        }


        .estado-cancelado {

            background: #fee2e2;

            color: #991b1b;

        }


        /* =========================================================
           EVIDENCIAS
        ========================================================= */

        .evidencia {

            margin-top: 22px;

            padding: 20px;

            background: #f9fafb;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

        }


        .evidencia-titulo {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 15px;

            color: #374151;

            font-size: 15px;

            font-weight: 600;

        }


        .archivo {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 12px;

            margin-bottom: 10px;

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 8px;

        }


        .archivo:last-child {

            margin-bottom: 0;

        }


        .nombre-archivo {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #4b5563;

            font-size: 14px;

            word-break: break-word;

        }


        .boton-evidencia {

            display: inline-block;

            padding: 9px 14px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 7px;

            font-size: 13px;

            font-weight: 600;

            white-space: nowrap;

            transition: 0.2s;

        }


        .boton-evidencia:hover {

            background: #1d4ed8;

        }


        .sin-evidencia {

            color: #6b7280;

            font-size: 14px;

        }


        /* =========================================================
           SIN REPORTES
        ========================================================= */

        .sin-reportes {

            background: white;

            border-radius: 14px;

            padding: 45px 25px;

            text-align: center;

            border: 1px solid #e5e7eb;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

        }


        .sin-reportes h2 {

            color: #374151;

            font-size: 20px;

            margin-bottom: 10px;

        }


        .sin-reportes p {

            color: #6b7280;

            font-size: 14px;

            margin-bottom: 20px;

        }


        .boton-crear {

            display: inline-block;

            padding: 11px 18px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 600;

            transition: 0.2s;

        }


        .boton-crear:hover {

            background: #1d4ed8;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 850px) {

            .informacion {

                grid-template-columns: 1fr;

            }


            .dato.completo {

                grid-column: auto;

            }

        }


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


            .encabezado {

                padding: 20px;

            }


            .encabezado h1 {

                font-size: 24px;

            }


            .tarjeta {

                padding: 20px;

            }


            .reporte-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .archivo {

                align-items: flex-start;

                flex-direction: column;

            }


            .boton-evidencia {

                width: 100%;

                text-align: center;

            }

        }

    </style>

</head>


<body>


<div class="contenedor">


    <!-- =========================================================
         MENÚ LATERAL
    ========================================================== -->

    <aside class="menu">


        <h2>

            🛠️ Mantenimiento

        </h2>


        <ul>


            <!-- DASHBOARD -->

            <li>

                <a href="reportante.php">

                    📊 Dashboard

                </a>

            </li>


            <!-- CREAR REPORTE -->

            <li>

                <a href="crear_reporte.php">

                    📝 Crear reporte

                </a>

            </li>


            <!-- MIS REPORTES -->

            <li>

                <a
                    href="mis_reportes.php"
                    class="activo"
                >

                    📋 Mis reportes

                </a>

            </li>


            <!-- HISTORIAL -->

            <li>

                <a href="historial.php">

                    📜 Historial

                </a>

            </li>


            <!-- CERRAR SESIÓN -->

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



    <!-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================== -->

    <main class="contenido">


        <!-- ENCABEZADO -->

        <div class="encabezado">


            <h1>

                📋 Mis reportes

            </h1>


            <p>

                Reportes registrados por:

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



        <!-- =====================================================
             MOSTRAR REPORTES
        ====================================================== -->

        <?php if (count($reportes) > 0): ?>


            <?php foreach ($reportes as $reporte): ?>


                <div class="tarjeta">


                    <!-- =========================================
                         ENCABEZADO DEL REPORTE
                    ========================================== -->

                    <div class="reporte-header">


                        <h2>

                            📋

                            <span class="numero-reporte">

                                #<?php

                                echo $reporte["id_reporte"];

                                ?>

                            </span>

                            -

                            <?php

                            echo htmlspecialchars(

                                $reporte["titulo"]

                            );

                            ?>

                        </h2>


                    </div>



                    <!-- =========================================
                         INFORMACIÓN
                    ========================================== -->

                    <div class="informacion">


                        <!-- DESCRIPCIÓN -->

                        <div class="dato completo">

                            <strong>

                                Descripción

                            </strong>

                            <?php

                            echo htmlspecialchars(

                                $reporte["descripcion"]

                            );

                            ?>

                        </div>



                        <!-- CATEGORÍA -->

                        <div class="dato">

                            <strong>

                                Categoría

                            </strong>

                            <?php

                            echo htmlspecialchars(

                                $reporte["categoria"]

                            );

                            ?>

                        </div>



                        <!-- UBICACIÓN -->

                        <div class="dato">

                            <strong>

                                Ubicación

                            </strong>

                            <?php

                            echo htmlspecialchars(

                                $reporte["ubicacion"]

                            );

                            ?>

                        </div>



                        <!-- PRIORIDAD -->

                        <div class="dato">

                            <strong>

                                Prioridad

                            </strong>

                            <?php

                            echo htmlspecialchars(

                                $reporte["prioridad"]

                            );

                            ?>

                        </div>



                        <!-- ESTADO -->

                        <div class="dato">

                            <strong>

                                Estado

                            </strong>


                            <?php

                            $estado = $reporte["estado"];

                            $clase_estado = "estado-pendiente";


                            if ($estado == "En revision") {

                                $clase_estado =
                                    "estado-revision";

                            }

                            elseif ($estado == "Asignado") {

                                $clase_estado =
                                    "estado-asignado";

                            }

                            elseif ($estado == "En proceso") {

                                $clase_estado =
                                    "estado-proceso";

                            }

                            elseif ($estado == "Resuelto") {

                                $clase_estado =
                                    "estado-resuelto";

                            }

                            elseif ($estado == "Cerrado") {

                                $clase_estado =
                                    "estado-cerrado";

                            }

                            elseif ($estado == "Cancelado") {

                                $clase_estado =
                                    "estado-cancelado";

                            }

                            ?>


                            <span
                                class="badge <?php echo $clase_estado; ?>"
                            >

                                <?php

                                echo htmlspecialchars(

                                    $estado

                                );

                                ?>

                            </span>


                        </div>



                        <!-- FECHA -->

                        <div class="dato completo">

                            <strong>

                                Fecha del reporte

                            </strong>

                            <?php

                            echo htmlspecialchars(

                                $reporte["fecha_reporte"]

                            );

                            ?>

                        </div>


                    </div>



                    <!-- =========================================
                         EVIDENCIAS
                    ========================================== -->

                    <div class="evidencia">


                        <div class="evidencia-titulo">

                            📎

                            <span>

                                Evidencias adjuntas

                            </span>

                        </div>


                        <?php


                        $stmt_evidencias->execute([

                            ":id_reporte"
                            =>
                            $reporte["id_reporte"]

                        ]);


                        $evidencias =
                            $stmt_evidencias->fetchAll(

                                PDO::FETCH_ASSOC

                            );

                        ?>


                        <?php if (count($evidencias) > 0): ?>


                            <?php foreach (
                                $evidencias
                                as $evidencia
                            ): ?>


                                <div class="archivo">


                                    <div
                                        class="nombre-archivo"
                                    >

                                        📄

                                        <span>

                                            <?php

                                            echo htmlspecialchars(

                                                $evidencia[
                                                    "nombre_archivo"
                                                ]

                                            );

                                            ?>

                                        </span>

                                    </div>


                                    <a
                                        href="../../<?php

                                        echo htmlspecialchars(

                                            $evidencia[
                                                "ruta_archivo"
                                            ]

                                        );

                                        ?>"
                                        target="_blank"
                                        class="boton-evidencia"
                                    >

                                        🔎 Ver archivo

                                    </a>


                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <span class="sin-evidencia">

                                No se adjuntó evidencia.

                            </span>


                        <?php endif; ?>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- =================================================
                 SIN REPORTES
            ================================================== -->

            <div class="sin-reportes">


                <h2>

                    📭 No tienes reportes registrados

                </h2>


                <p>

                    Puedes crear tu primer reporte
                    desde el botón "Crear reporte".

                </p>


                <a
                    href="crear_reporte.php"
                    class="boton-crear"
                >

                    📝 Crear reporte

                </a>


            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>