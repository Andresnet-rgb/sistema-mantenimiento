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
| 5. CONTAR REPORTES ASIGNADOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM asignaciones
        WHERE id_tecnico = :id_tecnico
        AND activa = 1";

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_tecnico",
    $id_tecnico,
    PDO::PARAM_INT
);

$stmt->execute();

$total_asignados = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| 6. CONTAR REPORTES EN PROCESO
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM asignaciones a

        INNER JOIN reportes r
            ON a.id_reporte = r.id_reporte

        WHERE a.id_tecnico = :id_tecnico

        AND a.activa = 1

        AND r.id_estado = 4";

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_tecnico",
    $id_tecnico,
    PDO::PARAM_INT
);

$stmt->execute();

$en_proceso = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| 7. CONTAR REPORTES RESUELTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM asignaciones a

        INNER JOIN reportes r
            ON a.id_reporte = r.id_reporte

        WHERE a.id_tecnico = :id_tecnico

        AND r.id_estado = 5";

$stmt = $db->prepare($sql);

$stmt->bindParam(
    ":id_tecnico",
    $id_tecnico,
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

    <title>Dashboard Técnico</title>


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

        }


        /*
        |--------------------------------------------------------------------------
        | CUERPO
        |--------------------------------------------------------------------------
        */

        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f3f6fa;

            color: #1f2937;

            min-height: 100vh;

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

            padding: 28px 18px;

            background:
                linear-gradient(
                    180deg,
                    #243b88 0%,
                    #2563eb 100%
                );

            color: white;

            overflow-y: auto;

        }


        /*
        |--------------------------------------------------------------------------
        | TÍTULO DEL MENÚ
        |--------------------------------------------------------------------------
        */

        .menu h2 {

            font-size: 22px;

            text-align: center;

            margin-bottom: 35px;

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
        | CONTENIDO PRINCIPAL
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

            margin-bottom: 5px;

        }


        .encabezado strong {

            color: #374151;

        }


        /*
        |--------------------------------------------------------------------------
        | TARJETAS DE ESTADÍSTICAS
        |--------------------------------------------------------------------------
        */

        .tarjetas {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 22px;

            margin-bottom: 25px;

        }


        /*
        |--------------------------------------------------------------------------
        | TARJETA
        |--------------------------------------------------------------------------
        */

        .tarjeta {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 25px;

            min-height: 190px;

            box-shadow:
                0 4px 12px
                rgba(15, 23, 42, 0.05);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;

            display: flex;

            flex-direction: column;

        }


        .tarjeta:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(15, 23, 42, 0.08);

        }


        /*
        |--------------------------------------------------------------------------
        | TÍTULO
        |--------------------------------------------------------------------------
        */

        .tarjeta h3 {

            color: #6b7280;

            font-size: 15px;

            font-weight: 600;

            margin-bottom: 15px;

        }


        /*
        |--------------------------------------------------------------------------
        | VALOR
        |--------------------------------------------------------------------------
        */

        .tarjeta .numero {

            font-size: 36px;

            font-weight: 700;

            margin-bottom: 18px;

        }


        /*
        |--------------------------------------------------------------------------
        | DESCRIPCIÓN
        |--------------------------------------------------------------------------
        */

        .tarjeta .descripcion {

            color: #6b7280;

            font-size: 13px;

            line-height: 1.4;

            margin-bottom: 18px;

        }


        /*
        |--------------------------------------------------------------------------
        | BOTÓN
        |--------------------------------------------------------------------------
        */

        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            margin-top: auto;

            padding: 10px 14px;

            border-radius: 8px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            transition:
                background 0.2s ease,
                transform 0.2s ease;

        }


        .btn:hover {

            background: #1d4ed8;

            transform: translateY(-1px);

        }


        /*
        |--------------------------------------------------------------------------
        | COLORES DE LAS TARJETAS
        |--------------------------------------------------------------------------
        */

        .tarjeta-asignados {

            border-top: 4px solid #2563eb;

        }


        .tarjeta-asignados .numero {

            color: #2563eb;

        }


        .tarjeta-proceso {

            border-top: 4px solid #f59e0b;

        }


        .tarjeta-proceso .numero {

            color: #d97706;

        }


        .tarjeta-resueltos {

            border-top: 4px solid #22c55e;

        }


        .tarjeta-resueltos .numero {

            color: #16a34a;

        }


        /*
        |--------------------------------------------------------------------------
        | SECCIÓN INFORMATIVA
        |--------------------------------------------------------------------------
        */

        .informacion {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 25px;

            box-shadow:
                0 4px 12px
                rgba(15, 23, 42, 0.05);

        }


        .informacion h2 {

            font-size: 19px;

            color: #111827;

            margin-bottom: 10px;

        }


        .informacion p {

            color: #6b7280;

            font-size: 14px;

            line-height: 1.6;

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


            .tarjetas {

                grid-template-columns:
                    repeat(2, 1fr);

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


            .tarjetas {

                grid-template-columns: 1fr;

            }


            .tarjeta {

                min-height: 170px;

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
                    href="tecnico.php"
                    class="activo"
                >

                    📊 Dashboard

                </a>

            </li>


            <!-- MIS REPORTES -->

            <li>

                <a href="reportes_asignados.php">

                    📋 Mis reportes

                </a>

            </li>


            <!-- CERRAR SESIÓN -->

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
         CONTENIDO PRINCIPAL
    ========================================================== -->

    <main class="contenido">


        <!-- =====================================================
             ENCABEZADO
        ====================================================== -->

        <div class="encabezado">


            <h1>

                Panel del Técnico

            </h1>


            <p>

                Bienvenido,

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


            <p>

                Rol:

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION["rol"]
                    );

                    ?>

                </strong>

            </p>


        </div>



        <!-- =====================================================
             TARJETAS
        ====================================================== -->

        <div class="tarjetas">


            <!-- =================================================
                 REPORTES ASIGNADOS
            ================================================== -->

            <div class="tarjeta tarjeta-asignados">


                <h3>

                    📋 Reportes asignados

                </h3>


                <div class="numero">

                    <?php

                    echo $total_asignados;

                    ?>

                </div>


                <p class="descripcion">

                    Reportes que actualmente están
                    asignados a ti.

                </p>


                <a
                    href="reportes_asignados.php"
                    class="btn"
                >

                    📋 Ver mis reportes

                </a>


            </div>



            <!-- =================================================
                 EN PROCESO
            ================================================== -->

            <div class="tarjeta tarjeta-proceso">


                <h3>

                    🔧 En proceso

                </h3>


                <div class="numero">

                    <?php

                    echo $en_proceso;

                    ?>

                </div>


                <p class="descripcion">

                    Reportes en los que actualmente
                    estás realizando mantenimiento.

                </p>


                <a
                    href="reportes_asignados.php"
                    class="btn"
                >

                    🔧 Ver reportes

                </a>


            </div>



            <!-- =================================================
                 RESUELTOS
            ================================================== -->

            <div class="tarjeta tarjeta-resueltos">


                <h3>

                    ✅ Resueltos

                </h3>


                <div class="numero">

                    <?php

                    echo $resueltos;

                    ?>

                </div>


                <p class="descripcion">

                    Reportes que ya fueron solucionados
                    por ti.

                </p>


                <a
                    href="reportes_asignados.php"
                    class="btn"
                >

                    ✅ Ver reportes

                </a>


            </div>


        </div>



        <!-- =====================================================
             INFORMACIÓN
        ====================================================== -->

        <div class="informacion">


            <h2>

                🔧 Gestión de mantenimiento

            </h2>


            <p>

                Desde este panel puedes consultar los reportes
                de mantenimiento que te han sido asignados,
                revisar su información, registrar las actividades
                realizadas y marcar como resueltos aquellos
                trabajos que hayan sido completados.

            </p>


        </div>


    </main>


</div>


</body>

</html>