<?php

session_start();

/* =========================================================
   VERIFICAR SESIÓN
   ========================================================= */

if (!isset($_SESSION["id_usuario"])) {
    die("Debe iniciar sesión.");
}


/* =========================================================
   VERIFICAR ADMINISTRADOR
   ========================================================= */

if ($_SESSION["id_rol"] != 1) {
    die("Acceso no autorizado.");
}


/* =========================================================
   CONEXIÓN A LA BASE DE DATOS
   ========================================================= */

require_once "../../backend/config/database.php";

$database = new Database();

$db = $database->conectar();


/* =========================================================
   OBTENER FILTROS
   ========================================================= */

$estado = $_GET["estado"] ?? "";
$prioridad = $_GET["prioridad"] ?? "";
$categoria = $_GET["categoria"] ?? "";
$ubicacion = $_GET["ubicacion"] ?? "";
$tecnico = $_GET["tecnico"] ?? "";
$fecha_desde = $_GET["fecha_desde"] ?? "";
$fecha_hasta = $_GET["fecha_hasta"] ?? "";


/* =========================================================
   OBTENER TÉCNICOS
   ========================================================= */

$sqlTecnicos = "

    SELECT
        id_usuario,
        nombre,
        apellido

    FROM usuarios

    WHERE id_rol = 3

    ORDER BY
        nombre ASC,
        apellido ASC

";

$stmtTecnicos = $db->prepare($sqlTecnicos);

$stmtTecnicos->execute();

$tecnicos = $stmtTecnicos->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   CONSTRUIR FILTROS
   ========================================================= */

$where = [];

$parametros = [];


/* ESTADO */

if ($estado !== "") {

    $where[] =
        "r.id_estado = :estado";

    $parametros[":estado"] =
        $estado;
}


/* PRIORIDAD */

if ($prioridad !== "") {

    $where[] =
        "r.id_prioridad = :prioridad";

    $parametros[":prioridad"] =
        $prioridad;
}


/* CATEGORÍA */

if ($categoria !== "") {

    $where[] =
        "r.id_categoria = :categoria";

    $parametros[":categoria"] =
        $categoria;
}


/* UBICACIÓN */

if ($ubicacion !== "") {

    $where[] =
        "r.id_ubicacion = :ubicacion";

    $parametros[":ubicacion"] =
        $ubicacion;
}


/* TÉCNICO */

if ($tecnico !== "") {

    $where[] =
        "a.id_tecnico = :tecnico";

    $parametros[":tecnico"] =
        $tecnico;
}


/* FECHA DESDE */

if ($fecha_desde !== "") {

    $where[] =
        "DATE(r.fecha_reporte) >= :fecha_desde";

    $parametros[":fecha_desde"] =
        $fecha_desde;
}


/* FECHA HASTA */

if ($fecha_hasta !== "") {

    $where[] =
        "DATE(r.fecha_reporte) <= :fecha_hasta";

    $parametros[":fecha_hasta"] =
        $fecha_hasta;
}


/* =========================================================
   CREAR WHERE
   ========================================================= */

$condicionWhere = "";

if (count($where) > 0) {

    $condicionWhere =
        " WHERE " .
        implode(" AND ", $where);
}


/* =========================================================
   CONSULTAR REPORTES
   ========================================================= */

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
            usr.nombre,
            ' ',
            usr.apellido
        ) AS reportante,

        CONCAT(
            tec.nombre,
            ' ',
            tec.apellido
        ) AS tecnico

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

    LEFT JOIN asignaciones a
        ON r.id_reporte = a.id_reporte
        AND a.activa = 1

    LEFT JOIN usuarios tec
        ON a.id_tecnico = tec.id_usuario

    $condicionWhere

    ORDER BY
        r.fecha_reporte DESC

";


$stmt = $db->prepare($sql);

$stmt->execute($parametros);

$reportes =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   CONSULTAR EVIDENCIAS
   ========================================================= */

$sqlEvidencias = "

    SELECT

        id_evidencia,
        nombre_archivo,
        ruta_archivo,
        tipo,
        fecha_subida

    FROM evidencias

    WHERE id_reporte = :id_reporte

    ORDER BY
        fecha_subida DESC

";

$stmtEvidencias =
    $db->prepare($sqlEvidencias);

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
        Reportes - Administrador
    </title>


    <style>

        /* =====================================================
           CONFIGURACIÓN GENERAL
           ===================================================== */

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

            background-color: #f3f6fa;

            color: #1f2937;
        }


        /* =====================================================
           CONTENEDOR PRINCIPAL
           ===================================================== */

        .contenedor {

            display: flex;

            min-height: 100vh;
        }


        /* =====================================================
           MENÚ LATERAL
           ===================================================== */

        .menu {

            width: 260px;

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

            bottom: 0;

            z-index: 10;
        }


        /* TÍTULO DEL MENÚ */

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


        /* LISTA */

        .menu ul {

            list-style: none;
        }


        /* ELEMENTOS */

        .menu li {

            margin-bottom: 7px;
        }


        /* ENLACES */

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

            padding: 35px;
        }


        /* =====================================================
           ENCABEZADO
           ===================================================== */

        .encabezado {

            background-color: white;

            border-radius: 14px;

            padding: 30px;

            margin-bottom: 24px;

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
           TARJETA PRINCIPAL
           ===================================================== */

        .tarjeta {

            background-color: white;

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

            margin-bottom: 24px;
        }


        /* =====================================================
           CABECERA DE TARJETA
           ===================================================== */

        .cabecera-tarjeta {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 20px;
        }


        .cabecera-tarjeta h2 {

            font-size: 21px;

            color: #1f2937;

            margin-bottom: 6px;
        }


        .cabecera-tarjeta p {

            color: #64748b;

            font-size: 14px;
        }


        /* =====================================================
           RESUMEN
           ===================================================== */

        .resumen {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 18px;

            color: #64748b;

            font-size: 14px;
        }


        .resumen strong {

            color: #1f2937;
        }


        /* =====================================================
           FILTROS
           ===================================================== */

        .filtros {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;
        }


        .campo-filtro {

            display: flex;

            flex-direction: column;
        }


        .campo-filtro label {

            color: #374151;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 7px;
        }


        .campo-filtro select,
        .campo-filtro input {

            width: 100%;

            padding: 10px 12px;

            border:
                1px solid #d1d5db;

            border-radius: 7px;

            background-color: white;

            color: #475569;

            font-size: 13px;

            outline: none;

            transition: 0.2s;
        }


        .campo-filtro select:focus,
        .campo-filtro input:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(
                    37,
                    99,
                    235,
                    0.10
                );
        }


        /* =====================================================
           BOTONES DE FILTRO
           ===================================================== */

        .acciones-filtros {

            grid-column: 1 / -1;

            display: flex;

            gap: 10px;

            margin-top: 2px;
        }


        .boton-aplicar {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 17px;

            background-color: #2563eb;

            color: white;

            border: none;

            border-radius: 7px;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s;
        }


        .boton-aplicar:hover {

            background-color: #1d4ed8;

            transform:
                translateY(-1px);
        }


        .boton-limpiar {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 17px;

            background-color: #f1f5f9;

            color: #475569;

            border:
                1px solid #cbd5e1;

            border-radius: 7px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            transition: 0.2s;
        }


        .boton-limpiar:hover {

            background-color: #e2e8f0;
        }


        /* =====================================================
           CABECERA DE REPORTES
           ===================================================== */

        .cabecera-reportes {

            margin-top: 30px;

            margin-bottom: 20px;
        }


        .cabecera-reportes h2 {

            font-size: 21px;

            color: #1f2937;

            margin-bottom: 6px;
        }


        .cabecera-reportes p {

            color: #64748b;

            font-size: 14px;
        }


        /* =====================================================
           TARJETA DE CADA REPORTE
           ===================================================== */

        .reporte {

            background-color: white;

            border-radius: 10px;

            padding: 22px;

            margin-bottom: 18px;

            border:
                1px solid #e2e8f0;

            box-shadow:
                0 2px 8px
                rgba(
                    0,
                    0,
                    0,
                    0.04
                );
        }


        /* =====================================================
           CABECERA DEL REPORTE
           ===================================================== */

        .cabecera-reporte {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 16px;
        }


        .cabecera-reporte h3 {

            font-size: 19px;

            color: #1f2937;
        }


        .id-reporte {

            color: #2563eb;
        }


        .fecha {

            color: #64748b;

            font-size: 13px;

            white-space: nowrap;
        }


        /* =====================================================
           DESCRIPCIÓN
           ===================================================== */

        .descripcion {

            color: #475569;

            font-size: 14px;

            line-height: 1.6;

            padding: 13px;

            background-color: #f8fafc;

            border:
                1px solid #e5e7eb;

            border-radius: 7px;

            margin-bottom: 18px;
        }


        /* =====================================================
           INFORMACIÓN DEL REPORTE
           ===================================================== */

        .informacion {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

            margin-bottom: 18px;
        }


        .dato {

            padding: 12px;

            border:
                1px solid #e5e7eb;

            border-radius: 7px;

            background-color: #ffffff;
        }


        .dato-label {

            display: block;

            color: #64748b;

            font-size: 12px;

            font-weight: 600;

            margin-bottom: 5px;
        }


        .dato-valor {

            color: #1f2937;

            font-size: 13px;

            font-weight: 600;
        }


        /* =====================================================
           BADGES
           ===================================================== */

        .badge {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;
        }


        /* ESTADOS */

        .estado-pendiente {

            background-color: #fef3c7;

            color: #92400e;
        }


        .estado-revision {

            background-color: #e0f2fe;

            color: #075985;
        }


        .estado-asignado {

            background-color: #ede9fe;

            color: #5b21b6;
        }


        .estado-proceso {

            background-color: #dbeafe;

            color: #1d4ed8;
        }


        .estado-resuelto {

            background-color: #dcfce7;

            color: #166534;
        }


        .estado-cerrado {

            background-color: #e2e8f0;

            color: #475569;
        }


        .estado-cancelado {

            background-color: #fee2e2;

            color: #991b1b;
        }


        /* PRIORIDADES */

        .prioridad-baja {

            background-color: #dcfce7;

            color: #166534;
        }


        .prioridad-media {

            background-color: #fef3c7;

            color: #92400e;
        }


        .prioridad-alta {

            background-color: #ffedd5;

            color: #9a3412;
        }


        .prioridad-critica {

            background-color: #fee2e2;

            color: #991b1b;
        }


        /* =====================================================
           EVIDENCIAS
           ===================================================== */

        .evidencias {

            border-top:
                1px solid #e5e7eb;

            padding-top: 17px;

            margin-bottom: 17px;
        }


        .evidencias h4 {

            font-size: 15px;

            color: #1f2937;

            margin-bottom: 10px;
        }


        .evidencia {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 15px;

            padding: 10px 12px;

            background-color: #f8fafc;

            border:
                1px solid #e5e7eb;

            border-radius: 7px;

            margin-bottom: 7px;
        }


        .nombre-evidencia {

            color: #475569;

            font-size: 13px;

            word-break: break-word;
        }


        .boton-evidencia {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 8px 13px;

            background-color: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 6px;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;
        }


        .boton-evidencia:hover {

            background-color: #1d4ed8;
        }


        .sin-evidencia {

            color: #94a3b8;

            font-size: 13px;

            font-style: italic;
        }


        /* =====================================================
           ACCIONES DEL REPORTE
           ===================================================== */

        .acciones-reporte {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 10px;

            border-top:
                1px solid #e5e7eb;

            padding-top: 16px;
        }


        .boton-accion {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 10px 15px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s;
        }


        .boton-asignar {

            background-color: #2563eb;

            color: white;
        }


        .boton-asignar:hover {

            background-color: #1d4ed8;

            transform:
                translateY(-1px);
        }


        .boton-cerrar {

            background-color: #16a34a;

            color: white;
        }


        .boton-cerrar:hover {

            background-color: #15803d;

            transform:
                translateY(-1px);
        }


        .tecnico-asignado {

            padding: 10px 14px;

            background-color: #eff6ff;

            color: #1d4ed8;

            border-radius: 7px;

            font-size: 13px;

            font-weight: 600;
        }


        .reporte-cerrado {

            padding: 10px 14px;

            background-color: #f0fdf4;

            color: #166534;

            border-radius: 7px;

            font-size: 13px;

            font-weight: 600;
        }


        /* =====================================================
           SIN REPORTES
           ===================================================== */

        .sin-reportes {

            text-align: center;

            padding: 45px 20px;

            color: #64748b;
        }


        .sin-reportes h3 {

            color: #334155;

            margin-bottom: 8px;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1100px) {

            .menu {

                width: 230px;
            }


            .contenido {

                margin-left: 230px;

                width:
                    calc(100% - 230px);

                padding: 25px;
            }


            .filtros {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .informacion {

                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 800px) {

            .cabecera-tarjeta {

                align-items:
                    flex-start;

                flex-direction: column;
            }


            .cabecera-reporte {

                align-items:
                    flex-start;

                flex-direction: column;
            }
        }


        @media (max-width: 700px) {

            .contenedor {

                display: block;
            }


            .menu {

                position: relative;

                width: 100%;

                min-height: auto;
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


            .encabezado {

                padding: 22px;
            }


            .encabezado h1 {

                font-size: 25px;
            }


            .tarjeta {

                padding: 18px;
            }


            .filtros {

                grid-template-columns: 1fr;
            }


            .acciones-filtros {

                grid-column: auto;

                flex-direction: column;
            }


            .boton-aplicar,
            .boton-limpiar {

                width: 100%;
            }


            .informacion {

                grid-template-columns: 1fr;
            }


            .evidencia {

                align-items:
                    flex-start;

                flex-direction: column;
            }


            .boton-evidencia {

                width: 100%;
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


            <!-- REPORTES ACTIVO -->

            <li>

                <a
                    href="reportes_admin.php"
                    class="activo"
                >

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

                📋 Gestión de reportes

            </h1>


            <p>

                Consulte y administre los reportes
                de mantenimiento registrados
                en el sistema.

            </p>

        </div>


        <!-- =================================================
             TARJETA DE FILTROS
             ================================================= -->

        <div class="tarjeta">


            <div class="cabecera-tarjeta">

                <div>

                    <h2>

                        🔎 Filtrar reportes

                    </h2>


                    <p>

                        Seleccione uno o varios criterios
                        para encontrar los reportes
                        que necesita.

                    </p>

                </div>

            </div>


            <form method="GET">


                <div class="filtros">


                    <!-- ESTADO -->

                    <div class="campo-filtro">

                        <label for="estado">

                            Estado

                        </label>


                        <select
                            name="estado"
                            id="estado"
                        >

                            <option value="">

                                Todos los estados

                            </option>


                            <option
                                value="1"
                                <?php
                                echo
                                    $estado == "1"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Pendiente

                            </option>


                            <option
                                value="2"
                                <?php
                                echo
                                    $estado == "2"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                En revision

                            </option>


                            <option
                                value="3"
                                <?php
                                echo
                                    $estado == "3"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Asignado

                            </option>


                            <option
                                value="4"
                                <?php
                                echo
                                    $estado == "4"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                En proceso

                            </option>


                            <option
                                value="5"
                                <?php
                                echo
                                    $estado == "5"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Resuelto

                            </option>


                            <option
                                value="6"
                                <?php
                                echo
                                    $estado == "6"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Cerrado

                            </option>


                            <option
                                value="7"
                                <?php
                                echo
                                    $estado == "7"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Cancelado

                            </option>

                        </select>

                    </div>


                    <!-- PRIORIDAD -->

                    <div class="campo-filtro">

                        <label for="prioridad">

                            Prioridad

                        </label>


                        <select
                            name="prioridad"
                            id="prioridad"
                        >

                            <option value="">

                                Todas las prioridades

                            </option>


                            <option
                                value="1"
                                <?php
                                echo
                                    $prioridad == "1"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Baja

                            </option>


                            <option
                                value="2"
                                <?php
                                echo
                                    $prioridad == "2"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Media

                            </option>


                            <option
                                value="3"
                                <?php
                                echo
                                    $prioridad == "3"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Alta

                            </option>


                            <option
                                value="4"
                                <?php
                                echo
                                    $prioridad == "4"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Critica

                            </option>

                        </select>

                    </div>


                    <!-- CATEGORÍA -->

                    <div class="campo-filtro">

                        <label for="categoria">

                            Categoría

                        </label>


                        <select
                            name="categoria"
                            id="categoria"
                        >

                            <option value="">

                                Todas las categorías

                            </option>


                            <option
                                value="1"
                                <?php
                                echo
                                    $categoria == "1"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Electrico

                            </option>


                            <option
                                value="2"
                                <?php
                                echo
                                    $categoria == "2"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Plomeria

                            </option>


                            <option
                                value="3"
                                <?php
                                echo
                                    $categoria == "3"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Infraestructura

                            </option>


                            <option
                                value="4"
                                <?php
                                echo
                                    $categoria == "4"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Mobiliario

                            </option>


                            <option
                                value="5"
                                <?php
                                echo
                                    $categoria == "5"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Tecnologia

                            </option>


                            <option
                                value="6"
                                <?php
                                echo
                                    $categoria == "6"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Aseo

                            </option>

                        </select>

                    </div>


                    <!-- UBICACIÓN -->

                    <div class="campo-filtro">

                        <label for="ubicacion">

                            Ubicación

                        </label>


                        <select
                            name="ubicacion"
                            id="ubicacion"
                        >

                            <option value="">

                                Todas las ubicaciones

                            </option>


                            <option
                                value="1"
                                <?php
                                echo
                                    $ubicacion == "1"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Bloque A - Aula 101

                            </option>


                            <option
                                value="2"
                                <?php
                                echo
                                    $ubicacion == "2"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Bloque A - Aula 102

                            </option>


                            <option
                                value="3"
                                <?php
                                echo
                                    $ubicacion == "3"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Bloque A - Aula 203

                            </option>


                            <option
                                value="4"
                                <?php
                                echo
                                    $ubicacion == "4"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Bloque B - Sala de informática

                            </option>


                            <option
                                value="5"
                                <?php
                                echo
                                    $ubicacion == "5"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Bloque B - Baños segundo piso

                            </option>


                            <option
                                value="6"
                                <?php
                                echo
                                    $ubicacion == "6"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Bloque C - Biblioteca

                            </option>

                        </select>

                    </div>


                    <!-- TÉCNICO -->

                    <div class="campo-filtro">

                        <label for="tecnico">

                            Técnico

                        </label>


                        <select
                            name="tecnico"
                            id="tecnico"
                        >

                            <option value="">

                                Todos los técnicos

                            </option>


                            <?php foreach (
                                $tecnicos
                                as $tec
                            ): ?>

                                <option
                                    value="<?php
                                    echo
                                        $tec[
                                            "id_usuario"
                                        ];
                                    ?>"
                                    <?php
                                    echo
                                        $tecnico ==
                                        $tec[
                                            "id_usuario"
                                        ]
                                        ? "selected"
                                        : "";
                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $tec["nombre"]
                                        . " "
                                        . $tec["apellido"]
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- FECHA DESDE -->

                    <div class="campo-filtro">

                        <label for="fecha_desde">

                            Fecha desde

                        </label>


                        <input
                            type="date"
                            name="fecha_desde"
                            id="fecha_desde"
                            value="<?php
                            echo htmlspecialchars(
                                $fecha_desde
                            );
                            ?>"
                        >

                    </div>


                    <!-- FECHA HASTA -->

                    <div class="campo-filtro">

                        <label for="fecha_hasta">

                            Fecha hasta

                        </label>


                        <input
                            type="date"
                            name="fecha_hasta"
                            id="fecha_hasta"
                            value="<?php
                            echo htmlspecialchars(
                                $fecha_hasta
                            );
                            ?>"
                        >

                    </div>


                    <!-- BOTONES -->

                    <div class="acciones-filtros">


                        <button
                            type="submit"
                            class="boton-aplicar"
                        >

                            🔎 Aplicar filtros

                        </button>


                        <a
                            href="reportes_admin.php"
                            class="boton-limpiar"
                        >

                            ↻ Limpiar filtros

                        </a>


                    </div>


                </div>


            </form>


        </div>


        <!-- =================================================
             TARJETA PRINCIPAL DE REPORTES
             ================================================= -->

        <div class="tarjeta">


            <div class="cabecera-tarjeta">

                <div>

                    <h2>

                        Reportes registrados

                    </h2>


                    <p>

                        Administración y seguimiento
                        de los reportes de mantenimiento.

                    </p>

                </div>

            </div>


            <!-- RESUMEN -->

            <div class="resumen">

                📋

                <span>

                    Total de reportes:

                    <strong>

                        <?php
                        echo count($reportes);
                        ?>

                    </strong>

                </span>

            </div>


            <!-- =================================================
                 LISTADO DE REPORTES
                 ================================================= -->

            <?php if (
                count($reportes) > 0
            ): ?>


                <?php foreach (
                    $reportes
                    as $reporte
                ): ?>


                    <div class="reporte">


                        <!-- CABECERA -->

                        <div class="cabecera-reporte">


                            <h3>

                                <span class="id-reporte">

                                    #

                                    <?php
                                    echo
                                        $reporte[
                                            "id_reporte"
                                        ];
                                    ?>

                                </span>


                                -

                                <?php
                                echo htmlspecialchars(
                                    $reporte[
                                        "titulo"
                                    ]
                                );
                                ?>

                            </h3>


                            <div class="fecha">

                                📅

                                <?php
                                echo date(
                                    "d/m/Y H:i",
                                    strtotime(
                                        $reporte[
                                            "fecha_reporte"
                                        ]
                                    )
                                );
                                ?>

                            </div>


                        </div>


                        <!-- DESCRIPCIÓN -->

                        <div class="descripcion">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $reporte[
                                        "descripcion"
                                    ]
                                )
                            );
                            ?>

                        </div>


                        <!-- INFORMACIÓN -->

                        <div class="informacion">


                            <!-- REPORTANTE -->

                            <div class="dato">

                                <span
                                    class="dato-label"
                                >

                                    👤 Reportante

                                </span>


                                <span
                                    class="dato-valor"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $reporte[
                                            "reportante"
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>


                            <!-- CATEGORÍA -->

                            <div class="dato">

                                <span
                                    class="dato-label"
                                >

                                    🗂️ Categoría

                                </span>


                                <span
                                    class="dato-valor"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $reporte[
                                            "categoria"
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>


                            <!-- UBICACIÓN -->

                            <div class="dato">

                                <span
                                    class="dato-label"
                                >

                                    📍 Ubicación

                                </span>


                                <span
                                    class="dato-valor"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $reporte[
                                            "bloque"
                                        ]
                                        . " - "
                                        . $reporte[
                                            "espacio"
                                        ]
                                    );
                                    ?>

                                </span>

                            </div>


                            <!-- PRIORIDAD -->

                            <div class="dato">

                                <span
                                    class="dato-label"
                                >

                                    ⚠️ Prioridad

                                </span>


                                <span
                                    class="dato-valor"
                                >


                                    <?php

                                    $clasePrioridad = "";

                                    switch (
                                        strtolower(
                                            $reporte[
                                                "prioridad"
                                            ]
                                        )
                                    ) {

                                        case "baja":

                                            $clasePrioridad =
                                                "prioridad-baja";

                                            break;


                                        case "media":

                                            $clasePrioridad =
                                                "prioridad-media";

                                            break;


                                        case "alta":

                                            $clasePrioridad =
                                                "prioridad-alta";

                                            break;


                                        case "critica":
                                        case "crítica":

                                            $clasePrioridad =
                                                "prioridad-critica";

                                            break;

                                    }

                                    ?>


                                    <span
                                        class="badge
                                        <?php
                                        echo
                                            $clasePrioridad;
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $reporte[
                                                "prioridad"
                                            ]
                                        );
                                        ?>

                                    </span>


                                </span>

                            </div>


                            <!-- ESTADO -->

                            <div class="dato">

                                <span
                                    class="dato-label"
                                >

                                    📌 Estado

                                </span>


                                <span
                                    class="dato-valor"
                                >


                                    <?php

                                    $claseEstado = "";

                                    switch (
                                        strtolower(
                                            $reporte[
                                                "estado"
                                            ]
                                        )
                                    ) {

                                        case "pendiente":

                                            $claseEstado =
                                                "estado-pendiente";

                                            break;


                                        case "en revision":
                                        case "en revisión":

                                            $claseEstado =
                                                "estado-revision";

                                            break;


                                        case "asignado":

                                            $claseEstado =
                                                "estado-asignado";

                                            break;


                                        case "en proceso":

                                            $claseEstado =
                                                "estado-proceso";

                                            break;


                                        case "resuelto":

                                            $claseEstado =
                                                "estado-resuelto";

                                            break;


                                        case "cerrado":

                                            $claseEstado =
                                                "estado-cerrado";

                                            break;


                                        case "cancelado":

                                            $claseEstado =
                                                "estado-cancelado";

                                            break;

                                    }

                                    ?>


                                    <span
                                        class="badge
                                        <?php
                                        echo
                                            $claseEstado;
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $reporte[
                                                "estado"
                                            ]
                                        );
                                        ?>

                                    </span>


                                </span>

                            </div>


                            <!-- TÉCNICO -->

                            <div class="dato">

                                <span
                                    class="dato-label"
                                >

                                    🔧 Técnico

                                </span>


                                <span
                                    class="dato-valor"
                                >

                                    <?php if (
                                        !empty(
                                            $reporte[
                                                "tecnico"
                                            ]
                                        )
                                    ): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $reporte[
                                                "tecnico"
                                            ]
                                        );
                                        ?>

                                    <?php else: ?>

                                        Sin asignar

                                    <?php endif; ?>

                                </span>

                            </div>


                        </div>


                        <!-- =================================================
                             EVIDENCIAS
                             ================================================= -->

                        <div class="evidencias">


                            <h4>

                                📎 Evidencias

                            </h4>


                            <?php

                            $stmtEvidencias->execute([
                                ":id_reporte" =>
                                    $reporte[
                                        "id_reporte"
                                    ]
                            ]);


                            $evidencias =
                                $stmtEvidencias->fetchAll(
                                    PDO::FETCH_ASSOC
                                );

                            ?>


                            <?php if (
                                count(
                                    $evidencias
                                ) > 0
                            ): ?>


                                <?php foreach (
                                    $evidencias
                                    as $evidencia
                                ): ?>


                                    <div class="evidencia">


                                        <span
                                            class="
                                                nombre-evidencia
                                            "
                                        >

                                            📄

                                            <?php
                                            echo htmlspecialchars(
                                                $evidencia[
                                                    "nombre_archivo"
                                                ]
                                            );
                                            ?>

                                        </span>


                                        <a
                                            href="../../<?php
                                            echo htmlspecialchars(
                                                $evidencia[
                                                    "ruta_archivo"
                                                ]
                                            );
                                            ?>"
                                            target="_blank"
                                            class="
                                                boton-evidencia
                                            "
                                        >

                                            🔎 Ver evidencia

                                        </a>


                                    </div>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <p class="sin-evidencia">

                                    Este reporte no tiene
                                    evidencias adjuntas.

                                </p>


                            <?php endif; ?>


                        </div>


                        <!-- =================================================
                             ACCIONES
                             ================================================= -->

                        <div class="acciones-reporte">


                            <?php if (
                                empty(
                                    $reporte[
                                        "tecnico"
                                    ]
                                )
                            ): ?>


                                <a
                                    href="asignar_reporte.php?id=<?php
                                    echo
                                        $reporte[
                                            "id_reporte"
                                        ];
                                    ?>"
                                    class="
                                        boton-accion
                                        boton-asignar
                                    "
                                >

                                    🔧 Asignar técnico

                                </a>


                            <?php else: ?>


                                <div
                                    class="
                                        tecnico-asignado
                                    "
                                >

                                    ✓ Técnico asignado:

                                    <?php
                                    echo htmlspecialchars(
                                        $reporte[
                                            "tecnico"
                                        ]
                                    );
                                    ?>

                                </div>


                            <?php endif; ?>


                            <?php if (
                                $reporte[
                                    "estado"
                                ] == "Resuelto"
                            ): ?>


                                <a
                                    href="cerrar_reporte.php?id=<?php
                                    echo
                                        $reporte[
                                            "id_reporte"
                                        ];
                                    ?>"
                                    class="
                                        boton-accion
                                        boton-cerrar
                                    "
                                >

                                    🔒 Cerrar reporte

                                </a>


                            <?php elseif (
                                $reporte[
                                    "estado"
                                ] == "Cerrado"
                            ): ?>


                                <div
                                    class="
                                        reporte-cerrado
                                    "
                                >

                                    ✅ Reporte cerrado

                                </div>


                            <?php endif; ?>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="sin-reportes">

                    <h3>

                        📭 No hay reportes registrados

                    </h3>


                    <p>

                        Actualmente no existen reportes
                        que coincidan con los filtros
                        seleccionados.

                    </p>

                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>