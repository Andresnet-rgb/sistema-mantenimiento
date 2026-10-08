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
| 2. VERIFICAR ROL
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
| 5. CONTAR TODOS LOS REPORTES
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT COUNT(*)

    FROM reportes

    WHERE id_usuario = :id_usuario

";


$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_usuario",
    $id_usuario,
    PDO::PARAM_INT
);

$stmt->execute();

$total_reportes = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| 6. CONTAR REPORTES PENDIENTES
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT COUNT(*)

    FROM reportes

    WHERE id_usuario = :id_usuario

    AND id_estado = 1

";


$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_usuario",
    $id_usuario,
    PDO::PARAM_INT
);

$stmt->execute();

$pendientes = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| 7. CONTAR REPORTES RESUELTOS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT COUNT(*)

    FROM reportes

    WHERE id_usuario = :id_usuario

    AND id_estado = 5

";


$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_usuario",
    $id_usuario,
    PDO::PARAM_INT
);

$stmt->execute();

$resueltos = $stmt->fetchColumn();

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
        Dashboard Reportante - Sistema de Mantenimiento
    </title>


    <style>

        /* =========================================================
           REINICIO GENERAL
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


        /* =========================================================
           CUERPO
        ========================================================= */

        body {

            background: #f3f6fa;

            color: #1f2937;

            min-height: 100vh;

        }


        /* =========================================================
           CONTENEDOR PRINCIPAL
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

            left: 0;
            top: 0;

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


        /* =========================================================
           TÍTULO DEL MENÚ
        ========================================================= */

        .menu h2 {

            font-size: 22px;

            text-align: center;

            margin-bottom: 30px;

            font-weight: 600;

        }


        /* =========================================================
           LISTA DEL MENÚ
        ========================================================= */

        .menu ul {

            list-style: none;

        }


        .menu li {

            margin-bottom: 8px;

        }


        /* =========================================================
           ENLACES DEL MENÚ
        ========================================================= */

        .menu a {

            display: block;

            padding: 13px 15px;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 15px;

            transition: background 0.2s;

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

            margin-top: 5px;

        }


        /* =========================================================
           TARJETAS
        ========================================================= */

        .tarjetas {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

        }


        /* =========================================================
           TARJETA
        ========================================================= */

        .tarjeta {

            background: white;

            border-radius: 14px;

            padding: 25px;

            min-height: 190px;

            border: 1px solid #e5e7eb;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.05);

            transition:
                transform 0.2s,
                box-shadow 0.2s;

            border-left:
                5px solid #2563eb;

            display: flex;

            flex-direction: column;

        }


        .tarjeta:hover {

            transform: translateY(-3px);

            box-shadow:
                0 8px 18px rgba(0, 0, 0, 0.08);

        }


        /* =========================================================
           TÍTULO DE TARJETA
        ========================================================= */

        .tarjeta h3 {

            color: #64748b;

            font-size: 15px;

            font-weight: 600;

            margin-bottom: 15px;

        }


        /* =========================================================
           NÚMERO / SÍMBOLO
        ========================================================= */

        .tarjeta p {

            color: #1e293b;

            font-size: 36px;

            font-weight: bold;

            margin-bottom: 18px;

        }


        /* =========================================================
           ENLACES DE LAS TARJETAS
        ========================================================= */

        .tarjeta a {

            margin-top: auto;

            display: inline-block;

            color: #2563eb;

            text-decoration: none;

            font-size: 14px;

            font-weight: bold;

        }


        .tarjeta a:hover {

            text-decoration: underline;

        }


        /* =========================================================
           COLORES DE LAS TARJETAS
        ========================================================= */

        .tarjeta:nth-child(1) {

            border-left-color: #2563eb;

        }


        .tarjeta:nth-child(2) {

            border-left-color: #0ea5e9;

        }


        .tarjeta:nth-child(3) {

            border-left-color: #f59e0b;

        }


        .tarjeta:nth-child(4) {

            border-left-color: #22c55e;

        }


        /* =========================================================
           TARJETA CREAR REPORTE
        ========================================================= */

        .tarjeta:nth-child(1) p {

            color: #2563eb;

        }


        /* =========================================================
           TARJETA PENDIENTES
        ========================================================= */

        .tarjeta:nth-child(3) p {

            color: #f59e0b;

        }


        /* =========================================================
           TARJETA RESUELTOS
        ========================================================= */

        .tarjeta:nth-child(4) p {

            color: #22c55e;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .tarjetas {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 700px) {

            .menu {

                width: 100%;

                height: auto;

                position: relative;

                padding: 15px;

            }


            .menu h2 {

                margin-bottom: 15px;

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

                padding:
                    10px 12px;

            }


            .menu a.cerrar {

                margin-top: 10px;

            }


            .contenedor {

                display: block;

            }


            .contenido {

                margin-left: 0;

                width: 100%;

                padding: 20px;

            }


            .encabezado {

                padding: 20px;

            }


            .encabezado h1 {

                font-size: 24px;

            }


            .tarjetas {

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


            <!-- DASHBOARD -->

            <li>

                <a
                    href="reportante.php"
                    class="activo"
                >

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


        <!-- =====================================================
             ENCABEZADO
        ====================================================== -->

        <div class="encabezado">


            <h1>

                Panel del Reportante

            </h1>


            <p>

                Bienvenido,

                <?php

                echo htmlspecialchars(

                    $_SESSION["nombre"]
                    . " "
                    . $_SESSION["apellido"]

                );

                ?>

            </p>


            <p>

                Rol:

                <?php

                echo htmlspecialchars(

                    $_SESSION["rol"]

                );

                ?>

            </p>


        </div>



        <!-- =====================================================
             TARJETAS
        ====================================================== -->

        <div class="tarjetas">


            <!-- =================================================
                 CREAR REPORTE
            ================================================== -->

            <div class="tarjeta">


                <h3>

                    📝 Crear reporte

                </h3>


                <p>

                    +

                </p>


                <a href="crear_reporte.php">

                    Registrar problema

                </a>


            </div>



            <!-- =================================================
                 MIS REPORTES
            ================================================== -->

            <div class="tarjeta">


                <h3>

                    📋 Mis reportes

                </h3>


                <p>

                    <?php

                    echo $total_reportes;

                    ?>

                </p>


                <a href="mis_reportes.php">

                    Ver reportes

                </a>


            </div>



            <!-- =================================================
                 PENDIENTES
            ================================================== -->

            <div class="tarjeta">


                <h3>

                    ⏳ Pendientes

                </h3>


                <p>

                    <?php

                    echo $pendientes;

                    ?>

                </p>


                <span
                    style="
                        color: #4b5563;
                        font-size: 14px;
                    "
                >

                    Reportes pendientes

                </span>


            </div>



            <!-- =================================================
                 RESUELTOS
            ================================================== -->

            <div class="tarjeta">


                <h3>

                    ✅ Resueltos

                </h3>


                <p>

                    <?php

                    echo $resueltos;

                    ?>

                </p>


                <span
                    style="
                        color: #4b5563;
                        font-size: 14px;
                    "
                >

                    Reportes resueltos

                </span>


            </div>


        </div>


    </main>


</div>


</body>

</html>