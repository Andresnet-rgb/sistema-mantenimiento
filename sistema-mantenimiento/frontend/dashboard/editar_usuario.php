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
| VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["id_usuario"])) {
    die("Debe iniciar sesión.");
}


/*
|--------------------------------------------------------------------------
| VERIFICAR ADMINISTRADOR
|--------------------------------------------------------------------------
*/
if ($_SESSION["id_rol"] != 1) {
    die("Acceso no autorizado.");
}


/*
|--------------------------------------------------------------------------
| OBTENER ID DEL USUARIO
|--------------------------------------------------------------------------
*/
$id_usuario = intval($_GET["id"] ?? 0);

if ($id_usuario <= 0) {
    die("Usuario no válido.");
}


/*
|--------------------------------------------------------------------------
| CONECTAR CON LA BASE DE DATOS
|--------------------------------------------------------------------------
*/
require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| OBTENER INFORMACIÓN DEL USUARIO
|--------------------------------------------------------------------------
*/
$sql = "SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            telefono,
            id_rol,
            estado
        FROM usuarios
        WHERE id_usuario = :id_usuario";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":id_usuario" => $id_usuario
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VERIFICAR QUE EL USUARIO EXISTA
|--------------------------------------------------------------------------
*/
if (!$usuario) {
    die("El usuario no existe.");
}


/*
|--------------------------------------------------------------------------
| OBTENER ROLES
|--------------------------------------------------------------------------
*/
$sql = "SELECT
            id_rol,
            nombre
        FROM roles
        ORDER BY id_rol";

$stmt = $db->prepare($sql);
$stmt->execute();

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar usuario</title>

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
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f6fa;
            color: #1f2937;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTENEDOR PRINCIPAL
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

            background: linear-gradient(
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
        | LISTA DEL MENÚ
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
        | ENLACES DEL MENÚ
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
            background: rgba(255, 255, 255, 0.14);

            transform: translateX(2px);
        }


        /*
        |--------------------------------------------------------------------------
        | ELEMENTO ACTIVO
        |--------------------------------------------------------------------------
        */

        .menu a.activo {
            background: rgba(255, 255, 255, 0.16);

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
        }


        /*
        |--------------------------------------------------------------------------
        | TARJETA DEL FORMULARIO
        |--------------------------------------------------------------------------
        */

        .tarjeta {
            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 30px;

            max-width: 900px;

            box-shadow:
                0 4px 12px rgba(15, 23, 42, 0.05);
        }


        /*
        |--------------------------------------------------------------------------
        | TÍTULO DE SECCIÓN
        |--------------------------------------------------------------------------
        */

        .titulo-seccion {
            font-size: 18px;

            font-weight: 700;

            color: #111827;

            margin-bottom: 25px;

            padding-bottom: 15px;

            border-bottom: 1px solid #e5e7eb;
        }


        /*
        |--------------------------------------------------------------------------
        | GRID DEL FORMULARIO
        |--------------------------------------------------------------------------
        */

        .formulario-grid {
            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 22px;
        }


        /*
        |--------------------------------------------------------------------------
        | CAMPO
        |--------------------------------------------------------------------------
        */

        .campo {
            display: flex;

            flex-direction: column;
        }


        .campo.completo {
            grid-column: 1 / -1;
        }


        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS
        |--------------------------------------------------------------------------
        */

        .campo label {
            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 600;

            color: #374151;
        }


        /*
        |--------------------------------------------------------------------------
        | INPUTS Y SELECTS
        |--------------------------------------------------------------------------
        */

        .campo input,
        .campo select {
            width: 100%;

            padding: 12px 14px;

            border: 1px solid #d1d5db;

            border-radius: 8px;

            background: white;

            color: #1f2937;

            font-size: 14px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .campo input:focus,
        .campo select:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.12);
        }


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN DEL USUARIO
        |--------------------------------------------------------------------------
        */

        .informacion-usuario {
            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 25px;

            padding: 14px 16px;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 10px;
        }


        .avatar {
            width: 42px;

            height: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #dbeafe;

            color: #2563eb;

            font-size: 19px;

            font-weight: bold;
        }


        .informacion-usuario strong {
            display: block;

            color: #111827;

            font-size: 15px;

            margin-bottom: 3px;
        }


        .informacion-usuario span {
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

            margin-top: 30px;

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
        | BOTÓN CANCELAR
        |--------------------------------------------------------------------------
        */

        .btn-cancelar {
            background: #e5e7eb;

            color: #374151;
        }


        .btn-cancelar:hover {
            background: #d1d5db;

            transform: translateY(-1px);
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

            .formulario-grid {
                grid-template-columns: 1fr;
            }

            .campo.completo {
                grid-column: auto;
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

        <h2>🛠️ Mantenimiento</h2>

        <ul>

            <li>
                <a href="administrador.php">
                    📊 Dashboard
                </a>
            </li>


            <li>
                <a href="usuarios.php" class="activo">
                    👥 Usuarios
                </a>
            </li>


            <li>
                <a href="reportes_admin.php">
                    📋 Reportes
                </a>
            </li>


            <li>
                <a href="tecnicos.php">
                    🔧 Técnicos
                </a>
            </li>


            <li>
                <a href="estadisticas.php">
                    📈 Estadísticas
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
         CONTENIDO PRINCIPAL
    ========================================================== -->

    <main class="contenido">


        <!-- ENCABEZADO -->

        <div class="encabezado">

            <h1>
                ✏️ Editar usuario
            </h1>

            <p>
                Modifique la información y permisos del usuario seleccionado.
            </p>

        </div>



        <!-- =====================================================
             INFORMACIÓN DEL USUARIO
        ====================================================== -->

        <div class="tarjeta">


            <div class="informacion-usuario">

                <div class="avatar">
                    👤
                </div>

                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $usuario["nombre"] . " " . $usuario["apellido"]
                        );
                        ?>
                    </strong>

                    <span>
                        ID de usuario:
                        <?php echo $usuario["id_usuario"]; ?>
                    </span>

                </div>

            </div>



            <div class="titulo-seccion">
                Información del usuario
            </div>



            <!-- =================================================
                 FORMULARIO
            ================================================== -->

            <form
                action="../../backend/usuarios/editar.php"
                method="POST"
            >


                <!-- ID OCULTO -->

                <input
                    type="hidden"
                    name="id_usuario"
                    value="<?php echo $usuario["id_usuario"]; ?>"
                >



                <div class="formulario-grid">


                    <!-- NOMBRE -->

                    <div class="campo">

                        <label for="nombre">
                            Nombre
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="<?php echo htmlspecialchars($usuario["nombre"]); ?>"
                            required
                        >

                    </div>



                    <!-- APELLIDO -->

                    <div class="campo">

                        <label for="apellido">
                            Apellido
                        </label>

                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            value="<?php echo htmlspecialchars($usuario["apellido"]); ?>"
                            required
                        >

                    </div>



                    <!-- CORREO -->

                    <div class="campo">

                        <label for="correo">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            value="<?php echo htmlspecialchars($usuario["correo"]); ?>"
                            required
                        >

                    </div>



                    <!-- TELÉFONO -->

                    <div class="campo">

                        <label for="telefono">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            id="telefono"
                            name="telefono"
                            value="<?php echo htmlspecialchars($usuario["telefono"] ?? ""); ?>"
                        >

                    </div>



                    <!-- ROL -->

                    <div class="campo">

                        <label for="id_rol">
                            Rol
                        </label>

                        <select
                            id="id_rol"
                            name="id_rol"
                            required
                        >

                            <?php foreach ($roles as $rol): ?>

                                <option
                                    value="<?php echo $rol["id_rol"]; ?>"

                                    <?php
                                    if ($usuario["id_rol"] == $rol["id_rol"]) {
                                        echo "selected";
                                    }
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars($rol["nombre"]);
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- ESTADO -->

                    <div class="campo">

                        <label for="estado">
                            Estado
                        </label>

                        <select
                            id="estado"
                            name="estado"
                            required
                        >

                            <option
                                value="1"

                                <?php
                                if ($usuario["estado"] == 1) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Activo
                            </option>


                            <option
                                value="0"

                                <?php
                                if ($usuario["estado"] == 0) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Inactivo
                            </option>

                        </select>

                    </div>


                </div>



                <!-- =================================================
                     BOTONES
                ================================================== -->

                <div class="acciones">

                    <button
                        type="submit"
                        class="btn btn-guardar"
                    >
                        💾 Guardar cambios
                    </button>


                    <a
                        href="usuarios.php"
                        class="btn btn-cancelar"
                    >
                        ↩️ Cancelar
                    </a>

                </div>


            </form>

        </div>


    </main>

</div>

</body>

</html>