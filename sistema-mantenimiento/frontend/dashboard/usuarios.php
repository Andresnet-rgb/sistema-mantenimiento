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
// 2. VERIFICAR ADMINISTRADOR
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
// 4. OBTENER USUARIOS
// ==========================================

$sql = "
    SELECT
        u.id_usuario,
        u.nombre,
        u.apellido,
        u.correo,
        u.telefono,
        u.estado,
        u.fecha_registro,
        r.nombre AS rol
    FROM usuarios u
    INNER JOIN roles r
        ON u.id_rol = r.id_rol
    ORDER BY u.id_usuario ASC
";

$stmt = $db->prepare($sql);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Usuarios - Administrador
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


        /* ==========================================
           CONTENEDOR PRINCIPAL
        ========================================== */

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

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.20);

            font-size: 22px;
        }


        /* ==========================================
           LISTA
        ========================================== */

        .menu ul {
            list-style: none;
        }

        .menu li {
            margin-bottom: 8px;
        }


        /* ==========================================
           ENLACES DEL MENÚ
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
            margin-bottom: 25px;
        }

        .encabezado h1 {
            font-size: 28px;

            color: #111827;

            margin-bottom: 7px;
        }

        .encabezado p {
            color: #6b7280;

            font-size: 15px;
        }


        /* ==========================================
           TARJETA PRINCIPAL
        ========================================== */

        .tarjeta {
            background: white;

            border-radius: 12px;

            padding: 25px;

            box-shadow:
                0 4px 12px
                rgba(0, 0, 0, 0.06);

            border:
                1px solid
                #f1f5f9;
        }


        /* ==========================================
           CABECERA DE LA TARJETA
        ========================================== */

        .cabecera-tarjeta {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 18px;
        }

        .cabecera-tarjeta h2 {
            font-size: 20px;

            color: #1f2937;

            margin-bottom: 5px;
        }

        .cabecera-tarjeta p {
            color: #6b7280;

            font-size: 14px;
        }


        /* ==========================================
           BOTÓN NUEVO USUARIO
        ========================================== */

        .boton-nuevo {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding: 11px 17px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 600;

            white-space: nowrap;

            transition: 0.2s;
        }

        .boton-nuevo:hover {
            background: #1d4ed8;

            transform: translateY(-1px);
        }


        /* ==========================================
           RESUMEN
        ========================================== */

        .resumen {
            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 18px;

            color: #6b7280;

            font-size: 14px;
        }

        .resumen strong {
            color: #1f2937;
        }


        /* ==========================================
           CONTENEDOR DE TABLA
        ========================================== */

        .tabla-contenedor {
            width: 100%;

            overflow-x: auto;

            border:
                1px solid
                #e2e8f0;

            border-radius: 8px;
        }


        /* ==========================================
           TABLA
        ========================================== */

        table {
            width: 100%;

            border-collapse: collapse;

            table-layout: auto;
        }


        /* ==========================================
           ENCABEZADO DE TABLA
        ========================================== */

        thead {
            background: #f1f3f5;

            color: #374151;
        }

        th {
            padding: 12px 9px;

            font-size: 12px;

            font-weight: 600;

            text-align: left;

            white-space: nowrap;

            border-bottom:
                1px solid
                #e2e8f0;
        }


        /* ==========================================
           CELDAS
        ========================================== */

        td {
            padding: 12px 9px;

            border-bottom:
                1px solid
                #e5e7eb;

            font-size: 13px;

            color: #475569;

            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }


        /* ==========================================
           ID
        ========================================== */

        .id-usuario {
            color: #64748b;

            font-weight: 600;

            white-space: nowrap;
        }


        /* ==========================================
           USUARIO
        ========================================== */

        .usuario {
            display: flex;

            align-items: center;

            gap: 8px;

            min-width: 145px;
        }

        .avatar {
            width: 34px;

            height: 34px;

            border-radius: 50%;

            background: #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            font-size: 16px;
        }

        .nombre {
            font-weight: 600;

            color: #1f2937;

            line-height: 1.2;
        }


        /* ==========================================
           CORREO
        ========================================== */

        .correo {
            color: #64748b;

            font-size: 12px;

            white-space: nowrap;
        }


        /* ==========================================
           TELÉFONO
        ========================================== */

        .telefono {
            font-size: 12px;

            white-space: nowrap;
        }


        /* ==========================================
           ROL
        ========================================== */

        .badge-rol {
            display: inline-block;

            padding: 5px 9px;

            background: #f1f5f9;

            color: #334155;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;
        }


        /* ==========================================
           ESTADO
        ========================================== */

        .badge-estado {
            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;
        }

        .activo-estado {
            background: #dcfce7;

            color: #166534;
        }

        .inactivo {
            background: #fee2e2;

            color: #991b1b;
        }


        /* ==========================================
           FECHA
        ========================================== */

        .fecha {
            color: #64748b;

            font-size: 11px;

            white-space: nowrap;
        }


        /* ==========================================
           COLUMNA ACCIÓN
        ========================================== */

        .columna-accion {
            text-align: center;

            white-space: nowrap;

            width: 80px;
        }


        /* ==========================================
           BOTÓN EDITAR
        ========================================== */

        .boton-editar {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 4px;

            padding: 7px 10px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 7px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;

            transition: 0.2s;
        }

        .boton-editar:hover {
            background: #1d4ed8;

            transform: translateY(-1px);
        }


        /* ==========================================
           SIN USUARIOS
        ========================================== */

        .sin-usuarios {
            text-align: center;

            padding: 45px 20px;

            color: #64748b;
        }

        .sin-usuarios h3 {
            color: #334155;

            margin-bottom: 8px;
        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 1100px) {

            .menu {
                width: 230px;
            }

            .contenido {
                padding: 25px;
            }

        }


        @media (max-width: 800px) {

            .cabecera-tarjeta {
                align-items: flex-start;

                flex-direction: column;
            }

            .boton-nuevo {
                width: 100%;

                text-align: center;
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

            .contenido {
                padding: 20px;
            }

            .menu ul {
                display: grid;

                grid-template-columns: 1fr 1fr;

                gap: 5px;
            }

            .tarjeta {
                padding: 18px;
            }

        }


        @media (max-width: 480px) {

            .contenido {
                padding: 15px;
            }

            .encabezado h1 {
                font-size: 24px;
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

                <a href="administrador.php">

                    📊 Dashboard

                </a>

            </li>


            <!-- USUARIOS -->

            <li>

                <a
                    href="usuarios.php"
                    class="activo"
                >

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
                👥 Gestión de usuarios
            </h1>

            <p>
                Consulte y administre los usuarios registrados
                en el sistema.
            </p>

        </div>



        <!-- ==========================================
             TARJETA PRINCIPAL
        ========================================== -->

        <div class="tarjeta">


            <!-- CABECERA -->

            <div class="cabecera-tarjeta">

                <div>

                    <h2>
                        Usuarios registrados
                    </h2>

                    <p>
                        Administración de las cuentas del sistema.
                    </p>

                </div>


                <a
                    href="crear_usuario.php"
                    class="boton-nuevo"
                >

                    ➕ Nuevo usuario

                </a>

            </div>



            <!-- RESUMEN -->

            <div class="resumen">

                👥

                <span>

                    Total de usuarios:

                    <strong>

                        <?php

                        echo count($usuarios);

                        ?>

                    </strong>

                </span>

            </div>



            <!-- ==========================================
                 TABLA
            ========================================== -->

            <?php if (count($usuarios) > 0): ?>


                <div class="tabla-contenedor">

                    <table>


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Usuario
                                </th>

                                <th>
                                    Correo
                                </th>

                                <th>
                                    Teléfono
                                </th>

                                <th>
                                    Rol
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Fecha
                                </th>

                                <th class="columna-accion">
                                    Acción
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($usuarios as $usuario): ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <span class="id-usuario">

                                            #

                                            <?php

                                            echo $usuario["id_usuario"];

                                            ?>

                                        </span>

                                    </td>



                                    <!-- USUARIO -->

                                    <td>

                                        <div class="usuario">

                                            <div class="avatar">

                                                👤

                                            </div>

                                            <span class="nombre">

                                                <?php

                                                echo htmlspecialchars(
                                                    $usuario["nombre"]
                                                    . " "
                                                    . $usuario["apellido"]
                                                );

                                                ?>

                                            </span>

                                        </div>

                                    </td>



                                    <!-- CORREO -->

                                    <td>

                                        <span class="correo">

                                            <?php

                                            echo htmlspecialchars(
                                                $usuario["correo"]
                                            );

                                            ?>

                                        </span>

                                    </td>



                                    <!-- TELÉFONO -->

                                    <td>

                                        <span class="telefono">

                                            <?php

                                            if (
                                                !empty(
                                                    $usuario["telefono"]
                                                )
                                            ) {

                                                echo htmlspecialchars(
                                                    $usuario["telefono"]
                                                );

                                            } else {

                                                echo "No registrado";

                                            }

                                            ?>

                                        </span>

                                    </td>



                                    <!-- ROL -->

                                    <td>

                                        <span class="badge-rol">

                                            <?php

                                            echo htmlspecialchars(
                                                $usuario["rol"]
                                            );

                                            ?>

                                        </span>

                                    </td>



                                    <!-- ESTADO -->

                                    <td>

                                        <?php

                                        if (
                                            $usuario["estado"] == 1
                                        ):

                                        ?>

                                            <span
                                                class="
                                                    badge-estado
                                                    activo-estado
                                                "
                                            >

                                                🟢 Activo

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="
                                                    badge-estado
                                                    inactivo
                                                "
                                            >

                                                🔴 Inactivo

                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- FECHA -->

                                    <td>

                                        <span class="fecha">

                                            <?php

                                            echo htmlspecialchars(
                                                $usuario["fecha_registro"]
                                            );

                                            ?>

                                        </span>

                                    </td>



                                    <!-- ACCIÓN -->

                                    <td class="columna-accion">

                                        <a
                                            href="editar_usuario.php?id=<?php echo $usuario["id_usuario"]; ?>"
                                            class="boton-editar"
                                        >

                                            ✏️ Editar

                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>

                </div>


            <?php else: ?>


                <div class="sin-usuarios">

                    <h3>
                        👥 No hay usuarios registrados
                    </h3>

                    <p>
                        Actualmente no existen usuarios
                        para mostrar.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>