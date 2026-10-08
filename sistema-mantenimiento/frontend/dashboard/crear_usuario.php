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
| CONECTAR CON LA BASE DE DATOS
|--------------------------------------------------------------------------
*/

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| OBTENER ROLES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id_rol,
        nombre
    FROM roles
    ORDER BY id_rol
";

$stmt = $db->prepare($sql);
$stmt->execute();

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Crear usuario - Sistema de Mantenimiento</title>


    <style>

        /* =========================================================
           CONFIGURACIÓN GENERAL
        ========================================================= */

        * {

            margin: 0;
            padding: 0;
            box-sizing: border-box;

            font-family: Arial, Helvetica, sans-serif;

        }


        body {

            background: #f3f6fa;

            color: #1f2937;

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

            background: linear-gradient(
                180deg,
                #243b88 0%,
                #2563eb 100%
            );

            color: white;

            padding: 25px 18px;

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

            background: rgba(255, 255, 255, 0.15);

        }


        /* OPCIÓN ACTIVA */

        .menu a.activo {

            background: rgba(255, 255, 255, 0.20);

            font-weight: 600;

        }


        /* CERRAR SESIÓN */

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

            padding: 35px;

            background: #f3f6fa;

        }


        /* =========================================================
           ENCABEZADO
        ========================================================= */

        .encabezado {

            margin-bottom: 24px;

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


        /* =========================================================
           TARJETA DEL FORMULARIO
        ========================================================= */

        .tarjeta {

            background: white;

            border-radius: 14px;

            padding: 30px;

            border: 1px solid #e5e7eb;

            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);

            max-width: 1000px;

        }


        /* =========================================================
           ENCABEZADO DEL FORMULARIO
        ========================================================= */

        .titulo-formulario {

            margin-bottom: 25px;

            padding-bottom: 18px;

            border-bottom: 1px solid #e5e7eb;

        }


        .titulo-formulario h2 {

            font-size: 20px;

            color: #1f2937;

            margin-bottom: 5px;

        }


        .titulo-formulario p {

            color: #6b7280;

            font-size: 14px;

        }


        /* =========================================================
           FORMULARIO
        ========================================================= */

        .formulario {

            width: 100%;

        }


        .campos {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;

        }


        .campo {

            display: flex;

            flex-direction: column;

        }


        .campo.completo {

            grid-column: 1 / -1;

        }


        .campo label {

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 600;

            color: #374151;

        }


        .campo input,

        .campo select {

            width: 100%;

            padding: 12px 13px;

            border: 1px solid #d1d5db;

            border-radius: 8px;

            background: white;

            color: #1f2937;

            font-size: 14px;

            outline: none;

            transition: 0.2s;

        }


        .campo input:focus,

        .campo select:focus {

            border-color: #2563eb;

            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);

        }


        .campo input::placeholder {

            color: #9ca3af;

        }


        /* =========================================================
           INFORMACIÓN DEL FORMULARIO
        ========================================================= */

        .ayuda {

            margin-top: 7px;

            color: #6b7280;

            font-size: 12px;

        }


        /* =========================================================
           BOTONES
        ========================================================= */

        .acciones {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 28px;

            padding-top: 22px;

            border-top: 1px solid #e5e7eb;

        }


        .boton {

            display: inline-block;

            padding: 11px 20px;

            border: none;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s;

        }


        /* BOTÓN REGISTRAR */

        .boton-registrar {

            background: #2563eb;

            color: white;

        }


        .boton-registrar:hover {

            background: #1d4ed8;

        }


        /* BOTÓN CANCELAR */

        .boton-cancelar {

            background: #6b7280;

            color: white;

        }


        .boton-cancelar:hover {

            background: #4b5563;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {

            .contenido {

                padding: 25px;

            }

            .campos {

                grid-template-columns: 1fr;

            }

            .campo.completo {

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

                margin-top: 0;

            }


            .contenido {

                margin-left: 0;

                width: 100%;

                padding: 20px;

            }


            .tarjeta {

                padding: 22px;

            }


            .encabezado h1 {

                font-size: 24px;

            }


            .acciones {

                flex-direction: column-reverse;

            }


            .boton {

                width: 100%;

                text-align: center;

            }

        }


        @media (max-width: 480px) {

            .contenido {

                padding: 15px;

            }


            .tarjeta {

                padding: 18px;

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



    <!-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================== -->

    <main class="contenido">


        <!-- ENCABEZADO -->

        <div class="encabezado">

            <h1>

                ➕ Crear usuario

            </h1>


            <p>

                Registre un nuevo usuario en el sistema.

            </p>

        </div>



        <!-- =====================================================
             TARJETA DEL FORMULARIO
        ====================================================== -->

        <div class="tarjeta">


            <div class="titulo-formulario">

                <h2>

                    Información del usuario

                </h2>


                <p>

                    Complete los datos necesarios para registrar
                    una nueva cuenta.

                </p>

            </div>



            <!-- =================================================
                 FORMULARIO
            ================================================== -->

            <form
                class="formulario"
                action="../../backend/usuarios/crear.php"
                method="POST"
            >


                <div class="campos">


                    <!-- NOMBRE -->

                    <div class="campo">

                        <label for="nombre">

                            Nombre

                        </label>


                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            placeholder="Ingrese el nombre"
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
                            placeholder="Ingrese el apellido"
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
                            placeholder="ejemplo@institucion.edu.co"
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
                            placeholder="Ingrese el número de teléfono"
                        >

                    </div>



                    <!-- CONTRASEÑA -->

                    <div class="campo">

                        <label for="password">

                            Contraseña

                        </label>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingrese una contraseña"
                            required
                        >


                        <span class="ayuda">

                            Utilice una contraseña segura para la cuenta.

                        </span>

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

                            <option value="">

                                Seleccione un rol

                            </option>


                            <?php foreach ($roles as $rol): ?>

                                <option
                                    value="<?php echo $rol["id_rol"]; ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $rol["nombre"]
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                </div>



                <!-- =================================================
                     BOTONES
                ================================================== -->

                <div class="acciones">


                    <a
                        href="usuarios.php"
                        class="boton boton-cancelar"
                    >

                        ↩️ Cancelar

                    </a>


                    <button
                        type="submit"
                        class="boton boton-registrar"
                    >

                        💾 Registrar usuario

                    </button>


                </div>


            </form>


        </div>


    </main>


</div>


</body>

</html>