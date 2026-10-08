<?php

session_start();


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
| 2. VERIFICAR QUE SEA TÉCNICO
|--------------------------------------------------------------------------
*/

if ($_SESSION["id_rol"] != 3) {

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
| 4. OBTENER ID DEL TÉCNICO
|--------------------------------------------------------------------------
*/

$id_tecnico = $_SESSION["id_usuario"];


/*
|--------------------------------------------------------------------------
| 5. CONSULTAR REPORTES ASIGNADOS
|--------------------------------------------------------------------------
*/

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

        WHERE a.id_tecnico = :id_tecnico

        AND a.activa = 1

        ORDER BY r.fecha_reporte DESC";


/*
|--------------------------------------------------------------------------
| 6. EJECUTAR CONSULTA
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_tecnico",
    $id_tecnico,
    PDO::PARAM_INT
);

$stmt->execute();

$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mis reportes</title>


    <style>

        /*
        |--------------------------------------------------------------------------
        | ESTILOS GENERALES
        |--------------------------------------------------------------------------
        */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f3f6fa;

            color: #1f2937;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENEDOR
        |--------------------------------------------------------------------------
        */

        .contenedor {

            min-height: 100vh;

        }


        /*
        |--------------------------------------------------------------------------
        | MENÚ LATERAL
        |--------------------------------------------------------------------------
        */

        .menu {

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

            padding: 28px 18px;

            color: white;

            overflow-y: auto;

        }


        /*
        |--------------------------------------------------------------------------
        | TÍTULO
        |--------------------------------------------------------------------------
        */

        .menu h2 {

            font-size: 22px;

            margin-bottom: 35px;

            text-align: center;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | LISTA
        |--------------------------------------------------------------------------
        */

        .menu ul {

            list-style: none;

        }


        .menu li {

            margin-bottom: 8px;

        }


        /*
        |--------------------------------------------------------------------------
        | ENLACES
        |--------------------------------------------------------------------------
        */

        .menu a {

            display: block;

            padding: 13px 16px;

            color: white;

            text-decoration: none;

            border-radius: 9px;

            font-size: 15px;

            transition:
                background 0.2s ease,
                transform 0.2s ease;

        }


        .menu a:hover {

            background:
                rgba(255, 255, 255, 0.14);

            transform: translateX(2px);

        }


        /*
        |--------------------------------------------------------------------------
        | OPCIÓN ACTIVA
        |--------------------------------------------------------------------------
        */

        .menu a.activo {

            background:
                rgba(255, 255, 255, 0.16);

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | CERRAR SESIÓN
        |--------------------------------------------------------------------------
        */

        .menu a.logout {

            margin-top: 25px;

            background: #dc2626;

            font-weight: 600;

            text-align: center;

        }


        .menu a.logout:hover {

            background: #b91c1c;

            transform: none;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENIDO
        |--------------------------------------------------------------------------
        */

        .contenido {

            margin-left: 260px;

            width: calc(100% - 260px);

            min-height: 100vh;

            padding: 40px;

        }


        /*
        |--------------------------------------------------------------------------
        | ENCABEZADO
        |--------------------------------------------------------------------------
        */

        .encabezado {

            margin-bottom: 30px;

        }


        .encabezado h1 {

            font-size: 30px;

            color: #111827;

            margin-bottom: 8px;

        }


        .encabezado p {

            color: #6b7280;

            font-size: 15px;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTADOR DE REPORTES
        |--------------------------------------------------------------------------
        */

        .resumen {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-top: 16px;

            padding: 8px 14px;

            background: #dbeafe;

            color: #1d4ed8;

            border-radius: 999px;

            font-size: 13px;

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENEDOR DE REPORTES
        |--------------------------------------------------------------------------
        */

        .lista-reportes {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(330px, 1fr)
                );

            gap: 22px;

        }


        /*
        |--------------------------------------------------------------------------
        | TARJETA DE REPORTE
        |--------------------------------------------------------------------------
        */

        .tarjeta {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 24px;

            box-shadow:
                0 4px 12px
                rgba(15, 23, 42, 0.05);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;

        }


        .tarjeta:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(15, 23, 42, 0.08);

        }


        /*
        |--------------------------------------------------------------------------
        | CABECERA DE TARJETA
        |--------------------------------------------------------------------------
        */

        .tarjeta-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            margin-bottom: 16px;

        }


        .numero-reporte {

            color: #6b7280;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 5px;

        }


        .tarjeta h2 {

            font-size: 18px;

            line-height: 1.35;

            color: #111827;

        }


        /*
        |--------------------------------------------------------------------------
        | BADGE DE ESTADO
        |--------------------------------------------------------------------------
        */

        .estado {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 999px;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;

        }


        .estado-asignado {

            background: #dbeafe;

            color: #1d4ed8;

        }


        .estado-proceso {

            background: #fef3c7;

            color: #92400e;

        }


        .estado-resuelto {

            background: #dcfce7;

            color: #166534;

        }


        .estado-pendiente {

            background: #f3f4f6;

            color: #374151;

        }


        .estado-cerrado {

            background: #e5e7eb;

            color: #4b5563;

        }


        /*
        |--------------------------------------------------------------------------
        | DESCRIPCIÓN
        |--------------------------------------------------------------------------
        */

        .descripcion {

            color: #4b5563;

            font-size: 14px;

            line-height: 1.55;

            margin-bottom: 20px;

        }


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN
        |--------------------------------------------------------------------------
        */

        .informacion {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 12px;

            padding-top: 18px;

            border-top: 1px solid #e5e7eb;

        }


        .dato {

            font-size: 13px;

        }


        .dato .etiqueta {

            display: block;

            color: #9ca3af;

            margin-bottom: 4px;

            font-size: 12px;

        }


        .dato .valor {

            color: #374151;

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | PRIORIDAD
        |--------------------------------------------------------------------------
        */

        .prioridad {

            display: inline-block;

            padding: 4px 9px;

            border-radius: 6px;

            font-size: 11px;

            font-weight: 700;

        }


        .prioridad-alta,
        .prioridad-critica {

            background: #fee2e2;

            color: #b91c1c;

        }


        .prioridad-media {

            background: #fef3c7;

            color: #92400e;

        }


        .prioridad-baja {

            background: #dcfce7;

            color: #166534;

        }


        /*
        |--------------------------------------------------------------------------
        | BOTÓN VER DETALLE
        |--------------------------------------------------------------------------
        */

        .acciones {

            margin-top: 20px;

            padding-top: 18px;

            border-top: 1px solid #e5e7eb;

        }


        .btn-detalle {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            width: 100%;

            padding: 11px 16px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 600;

            transition:
                background 0.2s ease,
                transform 0.2s ease;

        }


        .btn-detalle:hover {

            background: #1d4ed8;

            transform: translateY(-1px);

        }


        /*
        |--------------------------------------------------------------------------
        | SIN REPORTES
        |--------------------------------------------------------------------------
        */

        .sin-reportes {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 50px 30px;

            text-align: center;

            box-shadow:
                0 4px 12px
                rgba(15, 23, 42, 0.05);

        }


        .sin-reportes .icono {

            font-size: 45px;

            margin-bottom: 15px;

        }


        .sin-reportes h3 {

            font-size: 20px;

            color: #111827;

            margin-bottom: 8px;

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

        @media (max-width: 900px) {

            .menu {

                width: 220px;

            }


            .contenido {

                margin-left: 220px;

                width: calc(100% - 220px);

                padding: 30px 20px;

            }


            .lista-reportes {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 650px) {

            .menu {

                position: relative;

                width: 100%;

                height: auto;

                min-height: auto;

                padding: 20px 15px;

            }


            .menu h2 {

                margin-bottom: 20px;

            }


            .menu ul {

                display: grid;

                grid-template-columns:
                    repeat(2, 1fr);

                gap: 6px;

            }


            .menu li {

                margin-bottom: 0;

            }


            .menu a {

                font-size: 13px;

                padding: 11px 10px;

                text-align: center;

            }


            .menu a.logout {

                margin-top: 0;

            }


            .contenido {

                margin-left: 0;

                width: 100%;

                padding: 25px 15px;

            }


            .encabezado h1 {

                font-size: 25px;

            }


            .tarjeta {

                padding: 20px;

            }


            .tarjeta-header {

                flex-direction: column;

            }


            .informacion {

                grid-template-columns: 1fr;

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


            <li>

                <a href="tecnico.php">

                    📊 Dashboard

                </a>

            </li>


            <li>

                <a
                    href="reportes_asignados.php"
                    class="activo"
                >

                    📋 Mis reportes

                </a>

            </li>


            <li>

                <a
                    href="../../backend/auth/logout.php"
                    class="logout"
                >

                    🚪 Cerrar sesión

                </a>

            </li>


        </ul>

    </aside>



    <!-- =========================================================
         CONTENIDO
    ========================================================== -->

    <main class="contenido">


        <!-- =====================================================
             ENCABEZADO
        ====================================================== -->

        <div class="encabezado">


            <h1>
                📋 Mis reportes
            </h1>


            <p>

                Reportes de mantenimiento asignados a:

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


            <div class="resumen">

                📋

                <?php echo count($reportes); ?>

                reporte<?php echo count($reportes) != 1 ? "s" : ""; ?>

                asignado<?php echo count($reportes) != 1 ? "s" : ""; ?>

            </div>


        </div>



        <!-- =====================================================
             MOSTRAR REPORTES
        ====================================================== -->

        <?php if (count($reportes) == 0): ?>


            <div class="sin-reportes">


                <div class="icono">
                    📋
                </div>


                <h3>
                    No hay reportes asignados
                </h3>


                <p>

                    Actualmente no tienes reportes de
                    mantenimiento asignados.

                </p>


            </div>


        <?php else: ?>


            <div class="lista-reportes">


                <?php foreach ($reportes as $reporte): ?>


                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | CLASE DEL ESTADO
                    |--------------------------------------------------------------------------
                    */

                    $estado = strtolower(
                        trim($reporte["estado"])
                    );


                    $claseEstado = "estado-pendiente";


                    if ($estado == "asignado") {

                        $claseEstado = "estado-asignado";

                    } elseif ($estado == "en proceso") {

                        $claseEstado = "estado-proceso";

                    } elseif ($estado == "resuelto") {

                        $claseEstado = "estado-resuelto";

                    } elseif ($estado == "cerrado") {

                        $claseEstado = "estado-cerrado";

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CLASE DE PRIORIDAD
                    |--------------------------------------------------------------------------
                    */

                    $prioridad = strtolower(
                        trim($reporte["prioridad"])
                    );


                    $clasePrioridad = "prioridad-media";


                    if ($prioridad == "alta") {

                        $clasePrioridad = "prioridad-alta";

                    } elseif ($prioridad == "critica") {

                        $clasePrioridad = "prioridad-critica";

                    } elseif ($prioridad == "baja") {

                        $clasePrioridad = "prioridad-baja";

                    }

                    ?>


                    <!-- =================================================
                         TARJETA DEL REPORTE
                    ================================================== -->

                    <div class="tarjeta">


                        <div class="tarjeta-header">


                            <div>

                                <div class="numero-reporte">

                                    REPORTE
                                    #<?php
                                    echo $reporte["id_reporte"];
                                    ?>

                                </div>


                                <h2>

                                    <?php

                                    echo htmlspecialchars(
                                        $reporte["titulo"]
                                    );

                                    ?>

                                </h2>

                            </div>


                            <span
                                class="estado <?php echo $claseEstado; ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $reporte["estado"]
                                );

                                ?>

                            </span>


                        </div>



                        <!-- DESCRIPCIÓN -->

                        <div class="descripcion">

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $reporte["descripcion"]
                                )
                            );

                            ?>

                        </div>



                        <!-- INFORMACIÓN -->

                        <div class="informacion">


                            <!-- CATEGORÍA -->

                            <div class="dato">

                                <span class="etiqueta">

                                    Categoría

                                </span>

                                <span class="valor">

                                    <?php

                                    echo htmlspecialchars(
                                        $reporte["categoria"]
                                    );

                                    ?>

                                </span>

                            </div>



                            <!-- PRIORIDAD -->

                            <div class="dato">

                                <span class="etiqueta">

                                    Prioridad

                                </span>

                                <span class="valor">

                                    <span
                                        class="prioridad <?php echo $clasePrioridad; ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $reporte["prioridad"]
                                        );

                                        ?>

                                    </span>

                                </span>

                            </div>



                            <!-- UBICACIÓN -->

                            <div class="dato">

                                <span class="etiqueta">

                                    Ubicación

                                </span>

                                <span class="valor">

                                    <?php

                                    echo htmlspecialchars(
                                        $reporte["bloque"]
                                    );

                                    ?>

                                    -

                                    <?php

                                    echo htmlspecialchars(
                                        $reporte["espacio"]
                                    );

                                    ?>

                                </span>

                            </div>



                            <!-- REPORTANTE -->

                            <div class="dato">

                                <span class="etiqueta">

                                    Reportado por

                                </span>

                                <span class="valor">

                                    <?php

                                    echo htmlspecialchars(
                                        $reporte["reportante"]
                                    );

                                    ?>

                                </span>

                            </div>



                            <!-- FECHA -->

                            <div class="dato">

                                <span class="etiqueta">

                                    Fecha del reporte

                                </span>

                                <span class="valor">

                                    <?php

                                    echo htmlspecialchars(
                                        $reporte["fecha_reporte"]
                                    );

                                    ?>

                                </span>

                            </div>


                        </div>



                        <!-- =================================================
                             ACCIÓN
                        ================================================== -->

                        <div class="acciones">


                            <a
                                href="detalle_reporte.php?id=<?php echo $reporte['id_reporte']; ?>"
                                class="btn-detalle"
                            >

                                👁️ Ver detalle del reporte

                            </a>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>