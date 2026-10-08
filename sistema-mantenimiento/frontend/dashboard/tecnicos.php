<?php

/* =========================================================
   INICIAR SESIÓN
   ========================================================= */

session_start();


/* =========================================================
   VERIFICAR SESIÓN
   ========================================================= */

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.html");

    exit;
}


/* =========================================================
   VERIFICAR QUE SEA ADMINISTRADOR
   ========================================================= */

if ($_SESSION["id_rol"] != 1) {

    header("Location: ../login.html");

    exit;
}


/* =========================================================
   CONEXIÓN A LA BASE DE DATOS
   ========================================================= */

require_once "../../backend/config/database.php";

$database = new Database();

$db = $database->conectar();


/* =========================================================
   OBTENER TÉCNICOS
   ========================================================= */

$sql = "

    SELECT

        u.id_usuario,
        u.nombre,
        u.apellido,
        u.correo,
        u.telefono,
        u.estado,

        COUNT(a.id_asignacion) AS reportes_asignados

    FROM usuarios u

    LEFT JOIN asignaciones a

        ON u.id_usuario = a.id_tecnico

        AND a.activa = 1

    WHERE u.id_rol = 3

    GROUP BY

        u.id_usuario,
        u.nombre,
        u.apellido,
        u.correo,
        u.telefono,
        u.estado

    ORDER BY

        u.nombre ASC,
        u.apellido ASC

";


$stmt = $db->prepare($sql);

$stmt->execute();


$tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Técnicos - Sistema de Mantenimiento
    </title>


    <style>

        /* =====================================================
           CONFIGURACIÓN GENERAL
           ===================================================== */

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
        }


        /* =====================================================
           CONTENEDOR
           ===================================================== */

        .contenedor {

            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
           ===================================================== */

        .menu {

            width: 260px;

            height: 100vh;

            min-height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    #243b88 0%,
                    #2563eb 100%
                );

            color: white;

            padding: 25px 18px;

            position: fixed;

            left: 0;

            top: 0;

            z-index: 1000;
        }


        /* =====================================================
           TÍTULO SIDEBAR
           ===================================================== */

        .menu h2 {

            text-align: center;

            font-size: 22px;

            margin-bottom: 28px;

            padding-bottom: 20px;

            border-bottom:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.25
                );
        }


        /* =====================================================
           LISTA SIDEBAR
           ===================================================== */

        .menu ul {

            list-style: none;
        }


        .menu li {

            margin-bottom: 7px;
        }


        /* =====================================================
           ENLACES SIDEBAR
           ===================================================== */

        .menu a {

            display: block;

            padding: 13px 15px;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 15px;

            transition: 0.2s;
        }


        /* HOVER */

        .menu a:hover {

            background-color:
                rgba(
                    255,
                    255,
                    255,
                    0.15
                );

            transform:
                translateX(3px);
        }


        /* =====================================================
           OPCIÓN ACTIVA
           ===================================================== */

        .menu a.activo {

            background-color:
                rgba(
                    255,
                    255,
                    255,
                    0.20
                );

            font-weight: 600;
        }


        /* =====================================================
           CERRAR SESIÓN
           ===================================================== */

        .menu .cerrar-sesion {

            margin-top: 32px;
        }


        .menu .cerrar-sesion a {

            background-color: #dc2626;

            color: white;
        }


        .menu .cerrar-sesion a:hover {

            background-color: #b91c1c;

            transform:
                translateX(3px);
        }


        /* =====================================================
           CONTENIDO PRINCIPAL
           ===================================================== */

        .contenido {

            margin-left: 260px;

            width:
                calc(100% - 260px);

            min-height: 100vh;

            padding: 35px;
        }


        /* =====================================================
           ENCABEZADO
           ===================================================== */

        .encabezado {

            margin-bottom: 24px;
        }


        .encabezado h1 {

            font-size: 30px;

            color: #1f2937;

            margin-bottom: 8px;
        }


        .encabezado p {

            color: #64748b;

            font-size: 14px;
        }


        /* =====================================================
           TARJETA
           ===================================================== */

        .tarjeta {

            background: white;

            border-radius: 14px;

            padding: 25px;

            box-shadow:
                0 4px 15px
                rgba(
                    0,
                    0,
                    0,
                    0.06
                );

            border:
                1px solid #e5e7eb;
        }


        /* =====================================================
           RESUMEN
           ===================================================== */

        .resumen {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            margin-bottom: 20px;

            gap: 15px;
        }


        .resumen h2 {

            font-size: 21px;

            color: #1f2937;
        }


        .cantidad {

            background: #eff6ff;

            color: #2563eb;

            padding: 8px 14px;

            border-radius: 20px;

            font-size: 14px;

            font-weight: 600;

            white-space: nowrap;
        }


        /* =====================================================
           TABLA
           ===================================================== */

        .tabla-contenedor {

            width: 100%;

            overflow-x: auto;
        }


        .tabla {

            width: 100%;

            border-collapse: collapse;

            min-width: 800px;
        }


        /* ENCABEZADO TABLA */

        .tabla thead {

            background: #f1f3f5;
        }


        .tabla thead th {

            color: #374151;

            font-weight: 600;

            border-bottom:
                1px solid #e5e7eb;
        }


        .tabla th {

            padding: 14px 12px;

            text-align: left;

            font-size: 14px;
        }


        .tabla td {

            padding: 14px 12px;

            border-bottom:
                1px solid #e5e7eb;

            font-size: 14px;

            vertical-align: middle;
        }


        /* HOVER FILAS */

        .tabla tbody tr:hover {

            background: #f9fafb;
        }


        /* =====================================================
           NOMBRE TÉCNICO
           ===================================================== */

        .nombre-tecnico {

            font-weight: 600;

            color: #111827;
        }


        /* =====================================================
           CORREO
           ===================================================== */

        .correo {

            color: #4b5563;
        }


        /* =====================================================
           TELÉFONO
           ===================================================== */

        .telefono {

            color: #4b5563;
        }


        /* =====================================================
           ESTADOS
           ===================================================== */

        .estado {

            display: inline-block;

            padding: 6px 11px;

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


        /* =====================================================
           REPORTES ASIGNADOS
           ===================================================== */

        .contador-reportes {

            display: inline-block;

            min-width: 30px;

            padding: 6px 10px;

            text-align: center;

            background: #eff6ff;

            color: #2563eb;

            border-radius: 20px;

            font-weight: 600;

            font-size: 13px;
        }


        /* =====================================================
           BOTÓN VER REPORTES
           ===================================================== */

        .boton-reportes {

            display: inline-block;

            padding: 8px 13px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 7px;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s;
        }


        .boton-reportes:hover {

            background: #1d4ed8;

            transform:
                translateY(-1px);
        }


        /* =====================================================
           SIN TÉCNICOS
           ===================================================== */

        .sin-tecnicos {

            text-align: center;

            padding: 40px 20px;

            color: #64748b;

            font-size: 14px;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1000px) {

            .menu {

                width: 240px;
            }


            .contenido {

                margin-left: 240px;

                width:
                    calc(100% - 240px);

                padding: 25px;
            }

        }


        @media (max-width: 700px) {

            .contenedor {

                min-height: auto;
            }


            .menu {

                position: relative;

                width: 100%;

                height: auto;

                min-height: auto;

                padding: 20px 18px;
            }


            .contenido {

                margin-left: 0;

                width: 100%;

                padding: 20px;
            }


            .menu ul {

                display: grid;

                grid-template-columns:
                    1fr 1fr;

                gap: 5px;
            }


            .menu li {

                margin-bottom: 0;
            }


            .menu .cerrar-sesion {

                margin-top: 5px;
            }


            .resumen {

                flex-direction: column;

                align-items:
                    flex-start;
            }


            .tarjeta {

                padding: 18px;
            }

        }


    </style>

</head>


<body>


<div class="contenedor">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="menu">


        <h2>
            🛠️ Mantenimiento
        </h2>


        <ul>


            <!-- DASHBOARD -->

            <li>

                <a href="administrador.php">

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


            <!-- TÉCNICOS ACTIVO -->

            <li>

                <a
                    href="tecnicos.php"
                    class="activo"
                >

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

            <li class="cerrar-sesion">

                <a
                    href="../../backend/auth/logout.php"
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


        <!-- =================================================
             ENCABEZADO
             ================================================= -->

        <div class="encabezado">


            <h1>

                Gestión de técnicos

            </h1>


            <p>

                Consulta los técnicos registrados
                y los reportes que tienen asignados.

            </p>


        </div>


        <!-- =================================================
             TARJETA
             ================================================= -->

        <div class="tarjeta">


            <!-- RESUMEN -->

            <div class="resumen">


                <h2>

                    Lista de técnicos

                </h2>


                <span class="cantidad">

                    <?php
                    echo count($tecnicos);
                    ?>

                    técnicos

                </span>


            </div>


            <!-- =================================================
                 TABLA
                 ================================================= -->

            <div class="tabla-contenedor">


                <?php if (
                    count($tecnicos) > 0
                ): ?>


                    <table class="tabla">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Técnico
                                </th>

                                <th>
                                    Correo
                                </th>

                                <th>
                                    Teléfono
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Reportes asignados
                                </th>

                                <th>
                                    Acción
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $tecnicos
                                as $tecnico
                            ): ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $tecnico[
                                                "id_usuario"
                                            ]
                                        );

                                        ?>

                                    </td>


                                    <!-- TÉCNICO -->

                                    <td>

                                        <span
                                            class="nombre-tecnico"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $tecnico[
                                                    "nombre"
                                                ]
                                                . " "
                                                .
                                                $tecnico[
                                                    "apellido"
                                                ]
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- CORREO -->

                                    <td>

                                        <span
                                            class="correo"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $tecnico[
                                                    "correo"
                                                ]
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- TELÉFONO -->

                                    <td>

                                        <span
                                            class="telefono"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $tecnico[
                                                    "telefono"
                                                ]
                                                ??
                                                "No registrado"
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- ESTADO -->

                                    <td>


                                        <?php if (
                                            $tecnico[
                                                "estado"
                                            ] == 1
                                        ): ?>


                                            <span
                                                class="
                                                    estado
                                                    estado-activo
                                                "
                                            >

                                                Activo

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="
                                                    estado
                                                    estado-inactivo
                                                "
                                            >

                                                Inactivo

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- REPORTES -->

                                    <td>

                                        <span
                                            class="
                                                contador-reportes
                                            "
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $tecnico[
                                                    "reportes_asignados"
                                                ]
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACCIÓN -->

                                    <td>

                                        <a
                                            href="
                                                reportes_tecnico_admin.php?id=<?php
                                                echo
                                                    $tecnico[
                                                        "id_usuario"
                                                    ];
                                                ?>"
                                            class="
                                                boton-reportes
                                            "
                                        >

                                            Ver reportes

                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                <?php else: ?>


                    <div class="sin-tecnicos">

                        <p>

                            No hay técnicos registrados
                            en el sistema.

                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>