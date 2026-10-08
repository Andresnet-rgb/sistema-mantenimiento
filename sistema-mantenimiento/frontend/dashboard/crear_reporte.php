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
// 2. VERIFICAR QUE SEA REPORTANTE
// ==========================================

if ($_SESSION["id_rol"] != 2) {
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
// 4. CONSULTAR CATEGORÍAS
// ==========================================

$sql = "
    SELECT *
    FROM categorias
    WHERE estado = 1
    ORDER BY nombre
";

$stmt = $db->prepare($sql);
$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 5. CONSULTAR UBICACIONES
// ==========================================

$sql = "
    SELECT *
    FROM ubicaciones
    WHERE estado = 1
    ORDER BY bloque, espacio
";

$stmt = $db->prepare($sql);
$stmt->execute();

$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 6. CONSULTAR PRIORIDADES
// ==========================================

$sql = "
    SELECT *
    FROM prioridades
    ORDER BY id_prioridad
";

$stmt = $db->prepare($sql);
$stmt->execute();

$prioridades = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Crear reporte - Sistema de Mantenimiento
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

        .menu h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
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
            transition: 0.2s;
            font-size: 15px;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .menu .activo {
            background: rgba(255, 255, 255, 0.20);
            font-weight: bold;
        }

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
           TARJETA DEL FORMULARIO
        ========================================== */

        .tarjeta-formulario {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 850px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
        }

        .titulo-formulario {
            margin-bottom: 25px;
        }

        .titulo-formulario h2 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .titulo-formulario p {
            font-size: 14px;
            color: #6b7280;
        }


        /* ==========================================
           FORMULARIO
        ========================================== */

        .formulario {
            width: 100%;
        }

        .campo {
            margin-bottom: 20px;
        }

        .campo label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
        }

        .campo input[type="text"],
        .campo textarea,
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

        .campo input[type="text"]:focus,
        .campo textarea:focus,
        .campo select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .campo textarea {
            min-height: 130px;
            resize: vertical;
        }


        /* ==========================================
           CAMPOS EN DOS COLUMNAS
        ========================================== */

        .fila {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }


        /* ==========================================
           EVIDENCIA
        ========================================== */

        .campo-evidencia {
            margin-top: 5px;
            margin-bottom: 25px;
            padding: 18px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        .campo-evidencia label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
        }

        .campo-evidencia input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 3px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .ayuda-evidencia {
            font-size: 13px;
            color: #6b7280;
            margin-top: 9px;
        }


        /* ==========================================
           INFORMACIÓN DEL USUARIO
        ========================================== */

        .usuario-info {
            display: inline-block;
            margin-top: 4px;
            padding: 7px 12px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }


        /* ==========================================
           BOTONES
        ========================================== */

        .acciones {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 5px;
        }

        .boton {
            display: inline-block;
            padding: 11px 20px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }

        .boton:hover {
            background: #1d4ed8;
        }

        .boton-secundario {
            display: inline-block;
            padding: 11px 20px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            color: #4b5563;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }

        .boton-secundario:hover {
            background: #f9fafb;
        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 900px) {

            .menu {
                width: 210px;
            }

            .contenido {
                padding: 20px;
            }

            .tarjeta-formulario {
                max-width: 100%;
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
                padding: 15px;
            }

            .tarjeta-formulario {
                padding: 20px;
            }

            .fila {
                grid-template-columns: 1fr;
                gap: 0;
            }

        }


        @media (max-width: 480px) {

            .encabezado h1 {
                font-size: 24px;
            }

            .acciones {
                flex-direction: column;
                align-items: stretch;
            }

            .boton,
            .boton-secundario {
                text-align: center;
                width: 100%;
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

        <h2>🛠️ Mantenimiento</h2>

        <ul>

            <li>
                <a href="reportante.php">
                    📊 Dashboard
                </a>
            </li>

            <li>
                <a href="crear_reporte.php" class="activo">
                    📝 Crear reporte
                </a>
            </li>

            <li>
                <a href="mis_reportes.php">
                    📋 Mis reportes
                </a>
            </li>

            <li>
                <a href="historial.php">
                    📜 Historial
                </a>
            </li>

            <li>
                <a href="../../backend/auth/logout.php" class="cerrar">
                    🚪 Cerrar sesión
                </a>
            </li>

        </ul>

    </aside>


    <!-- ==========================================
         CONTENIDO PRINCIPAL
    ========================================== -->

    <main class="contenido">


        <!-- ENCABEZADO -->

        <div class="encabezado">

            <h1>
                Crear reporte de mantenimiento
            </h1>

            <p>
                Registre un problema de mantenimiento para que pueda ser atendido.
            </p>

            <span class="usuario-info">

                👤 Usuario:

                <?php

                echo htmlspecialchars(
                    $_SESSION["nombre"]
                    . " "
                    . $_SESSION["apellido"]
                );

                ?>

            </span>

        </div>


        <!-- ==========================================
             FORMULARIO
        ========================================== -->

        <div class="tarjeta-formulario">


            <div class="titulo-formulario">

                <h2>
                    📝 Información del reporte
                </h2>

                <p>
                    Complete los siguientes campos para registrar el problema.
                </p>

            </div>


            <form
                action="../../backend/reportes/crear.php"
                method="POST"
                enctype="multipart/form-data"
                class="formulario"
            >


                <!-- TÍTULO -->

                <div class="campo">

                    <label for="titulo">
                        Título del reporte
                    </label>

                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        placeholder="Ejemplo: Ventilador dañado"
                        required
                    >

                </div>


                <!-- DESCRIPCIÓN -->

                <div class="campo">

                    <label for="descripcion">
                        Descripción del problema
                    </label>

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        placeholder="Describa detalladamente el problema encontrado..."
                        required
                    ></textarea>

                </div>


                <!-- CATEGORÍA Y UBICACIÓN -->

                <div class="fila">


                    <div class="campo">

                        <label for="id_categoria">
                            Categoría
                        </label>

                        <select
                            id="id_categoria"
                            name="id_categoria"
                            required
                        >

                            <option value="">
                                Seleccione una categoría
                            </option>

                            <?php foreach ($categorias as $categoria): ?>

                                <option
                                    value="<?php echo $categoria["id_categoria"]; ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $categoria["nombre"]
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="campo">

                        <label for="id_ubicacion">
                            Ubicación
                        </label>

                        <select
                            id="id_ubicacion"
                            name="id_ubicacion"
                            required
                        >

                            <option value="">
                                Seleccione una ubicación
                            </option>

                            <?php foreach ($ubicaciones as $ubicacion): ?>

                                <option
                                    value="<?php echo $ubicacion["id_ubicacion"]; ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $ubicacion["bloque"]
                                        . " - "
                                        . $ubicacion["espacio"]
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <!-- PRIORIDAD -->

                <div class="campo">

                    <label for="id_prioridad">
                        Prioridad
                    </label>

                    <select
                        id="id_prioridad"
                        name="id_prioridad"
                        required
                    >

                        <option value="">
                            Seleccione una prioridad
                        </option>

                        <?php foreach ($prioridades as $prioridad): ?>

                            <option
                                value="<?php echo $prioridad["id_prioridad"]; ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $prioridad["nombre"]
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- EVIDENCIA -->

                <div class="campo-evidencia">

                    <label for="evidencia">
                        📎 Evidencia
                    </label>

                    <input
                        type="file"
                        id="evidencia"
                        name="evidencia"
                        accept="image/*,.pdf"
                    >

                    <p class="ayuda-evidencia">

                        Puede adjuntar una imagen o un archivo PDF
                        como evidencia del problema.

                    </p>

                </div>


                <!-- BOTONES -->

                <div class="acciones">

                    <button
                        type="submit"
                        class="boton"
                    >
                        📋 Registrar reporte
                    </button>

                    <a
                        href="reportante.php"
                        class="boton-secundario"
                    >
                        Cancelar
                    </a>

                </div>


            </form>

        </div>


    </main>

</div>

</body>

</html>