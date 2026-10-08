<?php

session_start();

// ==========================================
// 1. VERIFICAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

// ==========================================
// 2. VERIFICAR QUE SEA ADMINISTRADOR
// ==========================================

if ($_SESSION["id_rol"] != 1) {
    header("Location: ../login.html");
    exit;
}

// ==========================================
// 3. CONECTAR CON LA BASE DE DATOS
// ==========================================

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();


// ==========================================
// 4. CONTAR TODOS LOS REPORTES
// ==========================================

$sql = "
    SELECT COUNT(*) AS total
    FROM reportes
";

$stmt = $db->prepare($sql);
$stmt->execute();

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$total_reportes = $resultado["total"];


// ==========================================
// 5. CONTAR REPORTES PENDIENTES
// ==========================================

$sql = "
    SELECT COUNT(*) AS total
    FROM reportes
    WHERE id_estado = 1
";

$stmt = $db->prepare($sql);
$stmt->execute();

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$pendientes = $resultado["total"];


// ==========================================
// 6. CONTAR REPORTES EN PROCESO
// ==========================================

$sql = "
    SELECT COUNT(*) AS total
    FROM reportes
    WHERE id_estado = 4
";

$stmt = $db->prepare($sql);
$stmt->execute();

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$en_proceso = $resultado["total"];


// ==========================================
// 7. CONTAR REPORTES RESUELTOS
// ==========================================

$sql = "
    SELECT COUNT(*) AS total
    FROM reportes
    WHERE id_estado = 5
";

$stmt = $db->prepare($sql);
$stmt->execute();

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$resueltos = $resultado["total"];

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
        Dashboard Administrador - Sistema de Mantenimiento
    </title>


    <style>

        /* ==========================================
           CONFIGURACIÓN GENERAL
        ========================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f3f4f6;
            color: #1f2937;
        }

        .contenedor {
            display: flex;
            min-height: 100vh;
        }


        /* ==========================================
           MENÚ LATERAL
        ========================================== */

        .menu {
            width: 250px;
            min-height: 100vh;

            background: linear-gradient(
                180deg,
                #243b88 0%,
                #2563eb 100%
            );

            color: white;

            padding: 25px 18px;

            flex-shrink: 0;
        }


        /* ==========================================
           TÍTULO DEL MENÚ
        ========================================== */

        .menu h2 {
            text-align: center;

            margin-bottom: 30px;

            padding-bottom: 20px;

            border-bottom: 1px solid rgba(255, 255, 255, 0.20);

            font-size: 22px;
        }


        /* ==========================================
           LISTA DEL MENÚ
        ========================================== */

        .menu ul {
            list-style: none;
        }

        .menu li {
            margin-bottom: 8px;
        }


        /* ==========================================
           ENLACES
        ========================================== */

        .menu a {
            display: block;

            text-decoration: none;

            color: white;

            padding: 13px 15px;

            border-radius: 8px;

            transition: 0.2s;

            font-size: 15px;
        }


        .menu a:hover {
            background: rgba(255, 255, 255, 0.15);
        }


        /* ==========================================
           OPCIÓN ACTIVA
        ========================================== */

        .menu .activo {
            background: rgba(255, 255, 255, 0.20);

            font-weight: bold;
        }


        /* ==========================================
           CERRAR SESIÓN
        ========================================== */

        .menu .cerrar {
            margin-top: 25px;

            background: rgba(220, 38, 38, 0.85);
        }

        .menu .cerrar:hover {
            background: #dc2626;
        }


        /* ==========================================
           CONTENIDO PRINCIPAL
        ========================================== */

        .contenido {
            flex: 1;

            padding: 30px;

            min-width: 0;
        }


        /* ==========================================
           ENCABEZADO
        ========================================== */

        .encabezado {
            margin-bottom: 28px;
        }

        .encabezado h1 {
            font-size: 28px;

            color: #111827;

            margin-bottom: 7px;
        }

        .encabezado p {
            color: #6b7280;

            font-size: 15px;

            margin-bottom: 4px;
        }


        /* ==========================================
           INFORMACIÓN DEL ADMINISTRADOR
        ========================================== */

        .informacion-usuario {
            display: inline-block;

            margin-top: 8px;

            padding: 7px 12px;

            background: #eff6ff;

            color: #2563eb;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 600;
        }


        /* ==========================================
           TARJETAS DE ESTADÍSTICAS
        ========================================== */

        .tarjetas {
            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 20px;
        }


        /* ==========================================
           TARJETA
        ========================================== */

        .tarjeta {
            background: white;

            border-radius: 12px;

            padding: 22px;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.06);

            border-left: 5px solid #2563eb;

            transition: 0.2s;
        }


        .tarjeta:hover {
            transform: translateY(-2px);

            box-shadow:
                0 6px 16px rgba(0, 0, 0, 0.08);
        }


        /* ==========================================
           ENCABEZADO DE LA TARJETA
        ========================================== */

        .tarjeta h3 {
            color: #4b5563;

            font-size: 15px;

            font-weight: 600;

            margin-bottom: 14px;
        }


        /* ==========================================
           NÚMERO
        ========================================== */

        .tarjeta p {
            color: #1f2937;

            font-size: 34px;

            font-weight: bold;
        }


        /* ==========================================
           COLORES DE CADA TARJETA
        ========================================== */

        .tarjeta.total {
            border-left-color: #2563eb;
        }

        .tarjeta.pendientes {
            border-left-color: #f59e0b;
        }

        .tarjeta.proceso {
            border-left-color: #8b5cf6;
        }

        .tarjeta.resueltos {
            border-left-color: #22c55e;
        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 1000px) {

            .tarjetas {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 700px) {

            .contenedor {
                flex-direction: column;
            }


            .menu {
                width: 100%;

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


            .contenido {
                padding: 20px;
            }


            .tarjetas {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 480px) {

            .contenido {
                padding: 15px;
            }


            .encabezado h1 {
                font-size: 24px;
            }


            .tarjeta {
                padding: 20px;
            }

        }

    </style>

</head>


<body>


<div class="contenedor">


    <!-- ==========================================
         MENÚ LATERAL
    ========================================== -->

    <aside class="menu">

        <h2>
            🛠️ Mantenimiento
        </h2>


        <ul>


            <!-- DASHBOARD -->

            <li>

                <a
                    href="administrador.php"
                    class="activo"
                >

                    📊 Dashboard

                </a>

            </li>


            <!-- USUARIOS -->

            <li>

                <a href="usuarios.php">

                    👥 Usuarios

                </a>

            </li>


            <!-- REPORTES -->

            <li>

                <a href="reportes_admin.php">

                    📋 Reportes

                </a>

            </li>


            <!-- TÉCNICOS -->

            <li>

                <a href="tecnicos.php">

                    🔧 Técnicos

                </a>

            </li>


            <!-- ESTADÍSTICAS -->

            <li>

                <a href="estadisticas.php">

                    📈 Estadísticas

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



    <!-- ==========================================
         CONTENIDO PRINCIPAL
    ========================================== -->

    <main class="contenido">


        <!-- ==========================================
             ENCABEZADO
        ========================================== -->

        <div class="encabezado">


            <h1>
                Panel del Administrador
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


            <span class="informacion-usuario">

                👤 Rol:

                <?php

                echo htmlspecialchars(
                    $_SESSION["rol"]
                );

                ?>

            </span>


        </div>



        <!-- ==========================================
             ESTADÍSTICAS
        ========================================== -->

        <div class="tarjetas">


            <!-- TOTAL DE REPORTES -->

            <div class="tarjeta total">

                <h3>
                    📋 Reportes
                </h3>

                <p>

                    <?php

                    echo $total_reportes;

                    ?>

                </p>

            </div>



            <!-- REPORTES PENDIENTES -->

            <div class="tarjeta pendientes">

                <h3>
                    ⏳ Pendientes
                </h3>

                <p>

                    <?php

                    echo $pendientes;

                    ?>

                </p>

            </div>



            <!-- REPORTES EN PROCESO -->

            <div class="tarjeta proceso">

                <h3>
                    🔧 En proceso
                </h3>

                <p>

                    <?php

                    echo $en_proceso;

                    ?>

                </p>

            </div>



            <!-- REPORTES RESUELTOS -->

            <div class="tarjeta resueltos">

                <h3>
                    ✅ Resueltos
                </h3>

                <p>

                    <?php

                    echo $resueltos;

                    ?>

                </p>

            </div>


        </div>


    </main>


</div>


</body>

</html>