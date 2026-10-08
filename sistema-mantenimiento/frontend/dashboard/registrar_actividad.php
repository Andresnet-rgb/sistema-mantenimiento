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
| 3. VERIFICAR QUE RECIBIMOS EL ID DEL REPORTE
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"])) {

    die("No se recibió el número del reporte.");

}


$id_reporte = intval($_GET["id"]);


/*
|--------------------------------------------------------------------------
| 4. CONECTAR CON LA BASE DE DATOS
|--------------------------------------------------------------------------
*/

require_once "../../backend/config/database.php";

$database = new Database();

$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| 5. VERIFICAR QUE EL REPORTE ESTÉ ASIGNADO AL TÉCNICO
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            r.id_reporte,
            r.titulo,
            r.descripcion,
            e.nombre AS estado

        FROM asignaciones a

        INNER JOIN reportes r
            ON a.id_reporte = r.id_reporte

        INNER JOIN estados e
            ON r.id_estado = e.id_estado

        WHERE a.id_reporte = :id_reporte

        AND a.id_tecnico = :id_tecnico

        AND a.activa = 1

        LIMIT 1";


$stmt = $db->prepare($sql);


$stmt->bindParam(
    ":id_reporte",
    $id_reporte,
    PDO::PARAM_INT
);


$stmt->bindParam(
    ":id_tecnico",
    $_SESSION["id_usuario"],
    PDO::PARAM_INT
);


$stmt->execute();


$reporte = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE EL REPORTE EXISTA Y ESTÉ ASIGNADO
|--------------------------------------------------------------------------
*/

if (!$reporte) {

    die("El reporte no está asignado a este técnico.");

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

    <title>Registrar actividad</title>


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
        | TÍTULO DEL MENÚ
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
        | ELEMENTO ACTIVO
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

            margin-bottom: 25px;

        }


        .encabezado h1 {

            font-size: 30px;

            color: #111827;

            margin-bottom: 8px;

        }


        .encabezado p {

            color: #6b7280;

            font-size: 15px;

            margin-bottom: 6px;

        }


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN DESTACADA DEL REPORTE
        |--------------------------------------------------------------------------
        */

        .informacion-reporte {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 15px;

        }


        .dato-reporte {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 8px;

            padding: 9px 14px;

            font-size: 14px;

            color: #4b5563;

        }


        .dato-reporte strong {

            color: #111827;

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

            padding: 30px;

            max-width: 900px;

            box-shadow:
                0 4px 12px
                rgba(15, 23, 42, 0.05);

        }


        /*
        |--------------------------------------------------------------------------
        | TÍTULO DE LA TARJETA
        |--------------------------------------------------------------------------
        */

        .titulo-tarjeta {

            font-size: 19px;

            font-weight: 700;

            color: #111827;

            margin-bottom: 8px;

        }


        .descripcion-tarjeta {

            color: #6b7280;

            font-size: 14px;

            line-height: 1.5;

            margin-bottom: 25px;

        }


        /*
        |--------------------------------------------------------------------------
        | ETIQUETA
        |--------------------------------------------------------------------------
        */

        .campo label {

            display: block;

            margin-bottom: 9px;

            font-size: 14px;

            font-weight: 600;

            color: #374151;

        }


        /*
        |--------------------------------------------------------------------------
        | TEXTAREA
        |--------------------------------------------------------------------------
        */

        .campo textarea {

            width: 100%;

            min-height: 180px;

            padding: 13px 14px;

            border: 1px solid #d1d5db;

            border-radius: 9px;

            resize: vertical;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            font-size: 14px;

            line-height: 1.5;

            color: #1f2937;

            background: white;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;

        }


        .campo textarea:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.12);

        }


        .campo textarea::placeholder {

            color: #9ca3af;

        }


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN DE AYUDA
        |--------------------------------------------------------------------------
        */

        .ayuda {

            margin-top: 8px;

            color: #6b7280;

            font-size: 13px;

        }


        /*
        |--------------------------------------------------------------------------
        | BOTONES
        |--------------------------------------------------------------------------
        */

        .acciones {

            display: flex;

            gap: 12px;

            margin-top: 25px;

            padding-top: 22px;

            border-top: 1px solid #e5e7eb;

        }


        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 11px 20px;

            border: none;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition:
                background 0.2s ease,
                transform 0.2s ease;

        }


        /*
        |--------------------------------------------------------------------------
        | BOTÓN GUARDAR
        |--------------------------------------------------------------------------
        */

        .btn-guardar {

            background: #2563eb;

            color: white;

        }


        .btn-guardar:hover {

            background: #1d4ed8;

            transform: translateY(-1px);

        }


        /*
        |--------------------------------------------------------------------------
        | BOTÓN VOLVER
        |--------------------------------------------------------------------------
        */

        .btn-volver {

            background: #e5e7eb;

            color: #374151;

        }


        .btn-volver:hover {

            background: #d1d5db;

            transform: translateY(-1px);

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

            font-size: 12px;

            font-weight: 600;

            background: #fef3c7;

            color: #92400e;

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

                grid-template-columns: repeat(2, 1fr);

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


            .acciones {

                flex-direction: column;

            }


            .btn {

                width: 100%;

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

                🔧 Registrar actividad

            </h1>


            <p>

                Registre las actividades realizadas durante el mantenimiento.

            </p>


            <div class="informacion-reporte">


                <div class="dato-reporte">

                    Reporte:

                    <strong>

                        #

                        <?php

                        echo $reporte["id_reporte"];

                        ?>

                        -

                        <?php

                        echo htmlspecialchars(
                            $reporte["titulo"]
                        );

                        ?>

                    </strong>

                </div>


                <div class="dato-reporte">

                    Estado actual:

                    <span class="estado">

                        <?php

                        echo htmlspecialchars(
                            $reporte["estado"]
                        );

                        ?>

                    </span>

                </div>


            </div>


        </div>



        <!-- =====================================================
             FORMULARIO
        ====================================================== -->

        <div class="tarjeta">


            <div class="titulo-tarjeta">

                📝 Actividad realizada

            </div>


            <p class="descripcion-tarjeta">

                Describa de manera clara el trabajo realizado,
                las acciones ejecutadas y cualquier observación
                importante relacionada con el mantenimiento.

            </p>



            <form
                action="../../backend/reportes/registrar_actividad.php"
                method="POST"
            >


                <!-- ID DEL REPORTE -->

                <input
                    type="hidden"
                    name="id_reporte"
                    value="<?php echo $reporte['id_reporte']; ?>"
                >



                <!-- ACTIVIDAD -->

                <div class="campo">


                    <label for="actividad">

                        Actividad realizada

                    </label>


                    <textarea
                        id="actividad"
                        name="actividad"
                        placeholder="Describa el trabajo realizado..."
                        required
                    ></textarea>


                    <div class="ayuda">

                        💡 Sea específico. Por ejemplo:
                        "Se reemplazó el bombillo dañado y se verificó
                        el funcionamiento de la instalación eléctrica."

                    </div>


                </div>



                <!-- BOTONES -->

                <div class="acciones">


                    <button
                        type="submit"
                        class="btn btn-guardar"
                    >

                        💾 Guardar actividad

                    </button>


                    <a
                        href="detalle_reporte.php?id=<?php echo $reporte['id_reporte']; ?>"
                        class="btn btn-volver"
                    >

                        ← Volver al reporte

                    </a>


                </div>


            </form>


        </div>


    </main>


</div>


</body>

</html>