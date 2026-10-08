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
| 4. OBTENER ID DEL USUARIO
|--------------------------------------------------------------------------
*/

$id_usuario = $_SESSION["id_usuario"];


/*
|--------------------------------------------------------------------------
| 5. CONSULTAR HISTORIAL
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        s.id_seguimiento,
        s.id_reporte,
        s.comentario,
        s.fecha,

        e.nombre AS estado,

        r.titulo

    FROM seguimientos s

    INNER JOIN reportes r
        ON s.id_reporte = r.id_reporte

    INNER JOIN estados e
        ON s.id_estado = e.id_estado

    WHERE r.id_usuario = :id_usuario

    ORDER BY s.fecha DESC

";


$stmt = $db->prepare($sql);


$stmt->bindParam(
    ":id_usuario",
    $id_usuario,
    PDO::PARAM_INT
);


$stmt->execute();


$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Historial - Sistema de Mantenimiento
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
           TARJETA DE HISTORIAL
        ========================================================= */

        .tarjeta-historial {

            background: white;

            border-radius: 14px;

            padding: 24px;

            margin-bottom: 18px;

            border: 1px solid #e5e7eb;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

        }


        /* =========================================================
           ENCABEZADO DEL REGISTRO
        ========================================================= */

        .registro-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding-bottom: 16px;

            margin-bottom: 18px;

            border-bottom: 1px solid #e5e7eb;

        }


        .registro-header h2 {

            font-size: 19px;

            color: #111827;

            font-weight: 600;

        }


        .numero-reporte {

            color: #2563eb;

        }


        /* =========================================================
           INFORMACIÓN
        ========================================================= */

        .informacion {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 14px 20px;

        }


        .dato {

            background: #f9fafb;

            border: 1px solid #f0f1f3;

            border-radius: 8px;

            padding: 13px 14px;

            color: #4b5563;

            font-size: 14px;

            line-height: 1.5;

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
           BADGES DE ESTADO
        ========================================================= */

        .badge {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


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
           SIN HISTORIAL
        ========================================================= */

        .sin-historial {

            background: white;

            border-radius: 14px;

            padding: 45px 25px;

            text-align: center;

            border: 1px solid #e5e7eb;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

        }


        .sin-historial h2 {

            color: #374151;

            font-size: 20px;

            margin-bottom: 10px;

        }


        .sin-historial p {

            color: #6b7280;

            font-size: 14px;

            line-height: 1.5;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 800px) {

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


            .tarjeta-historial {

                padding: 20px;

            }


            .registro-header {

                align-items: flex-start;

                flex-direction: column;

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

                <a href="mis_reportes.php">

                    📋 Mis reportes

                </a>

            </li>


            <!-- HISTORIAL -->

            <li>

                <a
                    href="historial.php"
                    class="activo"
                >

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

                📜 Historial de reportes

            </h1>


            <p>

                Historial de los reportes realizados por:

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
             MOSTRAR HISTORIAL
        ====================================================== -->

        <?php if (count($historial) > 0): ?>


            <?php foreach (
                $historial
                as $registro
            ): ?>


                <div class="tarjeta-historial">


                    <!-- =========================================
                         ENCABEZADO DEL REGISTRO
                    ========================================== -->

                    <div class="registro-header">


                        <h2>

                            📋

                            <span class="numero-reporte">

                                #<?php

                                echo $registro["id_reporte"];

                                ?>

                            </span>

                            -

                            <?php

                            echo htmlspecialchars(

                                $registro["titulo"]

                            );

                            ?>

                        </h2>


                    </div>



                    <!-- =========================================
                         INFORMACIÓN DEL SEGUIMIENTO
                    ========================================== -->

                    <div class="informacion">


                        <!-- ESTADO -->

                        <div class="dato">


                            <strong>

                                Estado

                            </strong>


                            <?php

                            $estado =
                                $registro["estado"];

                            $clase_estado =
                                "estado-pendiente";


                            if (
                                $estado ==
                                "En revision"
                            ) {

                                $clase_estado =
                                    "estado-revision";

                            }

                            elseif (
                                $estado ==
                                "Asignado"
                            ) {

                                $clase_estado =
                                    "estado-asignado";

                            }

                            elseif (
                                $estado ==
                                "En proceso"
                            ) {

                                $clase_estado =
                                    "estado-proceso";

                            }

                            elseif (
                                $estado ==
                                "Resuelto"
                            ) {

                                $clase_estado =
                                    "estado-resuelto";

                            }

                            elseif (
                                $estado ==
                                "Cerrado"
                            ) {

                                $clase_estado =
                                    "estado-cerrado";

                            }

                            elseif (
                                $estado ==
                                "Cancelado"
                            ) {

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

                        <div class="dato">


                            <strong>

                                Fecha

                            </strong>


                            <?php

                            echo htmlspecialchars(

                                $registro["fecha"]

                            );

                            ?>


                        </div>



                        <!-- COMENTARIO -->

                        <div class="dato completo">


                            <strong>

                                Comentario / actividad

                            </strong>


                            <?php

                            echo htmlspecialchars(

                                $registro["comentario"]

                            );

                            ?>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- =================================================
                 SIN HISTORIAL
            ================================================== -->

            <div class="sin-historial">


                <h2>

                    📭 No hay historial disponible

                </h2>


                <p>

                    Tus seguimientos aparecerán aquí
                    cuando se registren cambios en tus reportes.

                </p>


            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>