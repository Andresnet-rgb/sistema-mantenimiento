<?php

session_start();

// ==========================================
// 1. VERIFICAR SESIÓN
// ==========================================

if (!isset($_SESSION["id_usuario"])) {
    die("Debe iniciar sesión.");
}

// ==========================================
// 2. VERIFICAR QUE SEA ADMINISTRADOR
// ==========================================

if ($_SESSION["id_rol"] != 1) {
    die("Acceso no autorizado.");
}

// ==========================================
// 3. CONEXIÓN A LA BASE DE DATOS
// ==========================================

require_once "../../backend/config/database.php";

$database = new Database();
$db = $database->conectar();

// ==========================================
// 4. OBTENER ID DEL REPORTE
// ==========================================

if (!isset($_GET["id"])) {
    die("No se especificó el reporte.");
}

$id_reporte = intval($_GET["id"]);

// ==========================================
// 5. CONSULTAR INFORMACIÓN DEL REPORTE
// ==========================================

$sql = "SELECT
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
            ) AS reportante

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

        WHERE r.id_reporte = :id_reporte";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":id_reporte" => $id_reporte
]);

$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 6. VERIFICAR QUE EL REPORTE EXISTA
// ==========================================

if (!$reporte) {
    die("El reporte no existe.");
}

// ==========================================
// 7. CONSULTAR TÉCNICOS DISPONIBLES
// ==========================================

$sqlTecnicos = "SELECT
                    id_usuario,
                    nombre,
                    apellido,
                    correo

                FROM usuarios

                WHERE id_rol = 3
                AND estado = 1

                ORDER BY nombre, apellido";

$stmtTecnicos = $db->prepare($sqlTecnicos);

$stmtTecnicos->execute();

$tecnicos = $stmtTecnicos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Asignar técnico</title>

    <style>

        /* ==========================================
           RESET
           ========================================== */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;

            background: #f3f6fa;

            color: #1f2937;

            min-height: 100vh;
        }

        /* ==========================================
           SIDEBAR
           ========================================== */

        .sidebar {
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

            color: white;

            padding: 25px 18px;

            z-index: 1000;
        }

        .logo {
            text-align: center;

            font-size: 21px;

            font-weight: bold;

            margin-bottom: 35px;
        }

        .menu {
            display: flex;

            flex-direction: column;

            gap: 8px;
        }

        .menu a {
            display: block;

            text-decoration: none;

            color: white;

            padding: 13px 15px;

            border-radius: 9px;

            font-size: 15px;

            transition: 0.2s;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .menu a.activo {
            background: rgba(255, 255, 255, 0.18);

            font-weight: 600;
        }

        .menu a.logout {
            background: #dc2626;

            margin-top: 20px;

            text-align: center;

            font-weight: 600;
        }

        .menu a.logout:hover {
            background: #b91c1c;
        }

        /* ==========================================
           CONTENIDO
           ========================================== */

        .contenido {
            margin-left: 260px;

            width: calc(100% - 260px);

            min-height: 100vh;

            padding: 40px;
        }

        /* ==========================================
           ENCABEZADO
           ========================================== */

        .encabezado {
            margin-bottom: 25px;
        }

        .encabezado h1 {
            font-size: 28px;

            color: #1f2937;

            margin-bottom: 8px;
        }

        .encabezado p {
            color: #6b7280;

            font-size: 15px;
        }

        /* ==========================================
           TARJETAS
           ========================================== */

        .tarjeta {
            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 28px;

            margin-bottom: 20px;

            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.05);
        }

        .tarjeta h2 {
            color: #1f2937;

            font-size: 20px;

            margin-bottom: 20px;
        }

        /* ==========================================
           INFORMACIÓN DEL REPORTE
           ========================================== */

        .datos-reporte {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }

        .dato {
            background: #f8fafc;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

            padding: 15px;
        }

        .dato-completo {
            grid-column: 1 / -1;
        }

        .dato-label {
            display: block;

            color: #6b7280;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 6px;
        }

        .dato-valor {
            color: #1f2937;

            font-size: 15px;

            line-height: 1.5;
        }

        /* ==========================================
           BADGES
           ========================================== */

        .badge {
            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 600;
        }

        .badge-prioridad {
            background: #fef3c7;

            color: #92400e;
        }

        .badge-estado {
            background: #dbeafe;

            color: #1d4ed8;
        }

        /* ==========================================
           FORMULARIO
           ========================================== */

        .formulario {
            max-width: 800px;
        }

        .campo {
            margin-bottom: 22px;
        }

        .campo label {
            display: block;

            color: #374151;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 8px;
        }

        .campo select,
        .campo textarea {
            width: 100%;

            padding: 12px 14px;

            border: 1px solid #d1d5db;

            border-radius: 9px;

            background: white;

            color: #1f2937;

            font-family: inherit;

            font-size: 14px;

            outline: none;

            transition: 0.2s;
        }

        .campo select:focus,
        .campo textarea:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .campo textarea {
            min-height: 110px;

            resize: vertical;
        }

        .ayuda {
            display: block;

            margin-top: 7px;

            color: #6b7280;

            font-size: 13px;
        }

        /* ==========================================
           BOTONES
           ========================================== */

        .acciones {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-top: 10px;

            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;

            border: none;

            border-radius: 9px;

            padding: 12px 20px;

            font-family: inherit;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

            cursor: pointer;

            transition: 0.2s;
        }

        .btn-primary {
            background: #2563eb;

            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #6b7280;

            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* ==========================================
           SIN TÉCNICOS
           ========================================== */

        .sin-tecnicos {
            padding: 20px;

            background: #fef2f2;

            border: 1px solid #fecaca;

            border-radius: 10px;

            color: #991b1b;

            font-size: 14px;
        }

        /* ==========================================
           RESPONSIVE
           ========================================== */

        @media (max-width: 900px) {

            .datos-reporte {
                grid-template-columns: 1fr;
            }

            .dato-completo {
                grid-column: auto;
            }

        }

        @media (max-width: 768px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;

                padding: 20px;
            }

            .logo {
                margin-bottom: 20px;
            }

            .contenido {
                margin-left: 0;

                width: 100%;

                padding: 25px 20px;
            }

            .encabezado h1 {
                font-size: 24px;
            }

            .tarjeta {
                padding: 22px;
            }

            .acciones {
                flex-direction: column;

                align-items: stretch;
            }

            .btn {
                width: 100%;

                text-align: center;
            }

        }

    </style>

</head>

<body>

    <!-- ==========================================
         MENÚ LATERAL
         ========================================== -->

    <aside class="sidebar">

        <div class="logo">
            🛠️ Mantenimiento
        </div>

        <nav class="menu">

            <a href="administrador.php">
                📊 Dashboard
            </a>

            <a href="usuarios.php">
                👥 Usuarios
            </a>

            <a href="reportes_admin.php"
               class="activo">
                📋 Reportes
            </a>

            <a href="tecnicos.php">
                🔧 Técnicos
            </a>

            <a href="estadisticas.php">
                📈 Estadísticas
            </a>

            <a href="../../backend/auth/logout.php"
               class="logout">
                🚪 Cerrar sesión
            </a>

        </nav>

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
                Asignar técnico
            </h1>

            <p>
                Selecciona el técnico encargado de atender este reporte.
            </p>

        </div>


        <!-- ==========================================
             INFORMACIÓN DEL REPORTE
             ========================================== -->

        <div class="tarjeta">

            <h2>
                📋 Información del reporte
            </h2>

            <div class="datos-reporte">

                <div class="dato">

                    <span class="dato-label">
                        Número de reporte
                    </span>

                    <span class="dato-valor">
                        #<?php echo htmlspecialchars($reporte["id_reporte"]); ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Título
                    </span>

                    <span class="dato-valor">
                        <?php echo htmlspecialchars($reporte["titulo"]); ?>
                    </span>

                </div>


                <div class="dato dato-completo">

                    <span class="dato-label">
                        Descripción
                    </span>

                    <span class="dato-valor">
                        <?php echo nl2br(htmlspecialchars($reporte["descripcion"])); ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Reportante
                    </span>

                    <span class="dato-valor">
                        <?php echo htmlspecialchars($reporte["reportante"]); ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Categoría
                    </span>

                    <span class="dato-valor">
                        <?php echo htmlspecialchars($reporte["categoria"]); ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Ubicación
                    </span>

                    <span class="dato-valor">
                        <?php
                        echo htmlspecialchars($reporte["bloque"]);
                        ?>
                        -
                        <?php
                        echo htmlspecialchars($reporte["espacio"]);
                        ?>
                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Prioridad
                    </span>

                    <span class="dato-valor">

                        <span class="badge badge-prioridad">
                            <?php
                            echo htmlspecialchars($reporte["prioridad"]);
                            ?>
                        </span>

                    </span>

                </div>


                <div class="dato">

                    <span class="dato-label">
                        Estado actual
                    </span>

                    <span class="dato-valor">

                        <span class="badge badge-estado">
                            <?php
                            echo htmlspecialchars($reporte["estado"]);
                            ?>
                        </span>

                    </span>

                </div>

            </div>

        </div>


        <!-- ==========================================
             FORMULARIO DE ASIGNACIÓN
             ========================================== -->

        <div class="tarjeta formulario">

            <h2>
                🔧 Seleccionar técnico
            </h2>

            <?php if (count($tecnicos) > 0): ?>

                <form
                    action="../../backend/reportes/asignar.php"
                    method="POST"
                >

                    <!-- ID DEL REPORTE -->

                    <input
                        type="hidden"
                        name="id_reporte"
                        value="<?php echo htmlspecialchars($reporte["id_reporte"]); ?>"
                    >


                    <!-- SELECCIONAR TÉCNICO -->

                    <div class="campo">

                        <label for="id_tecnico">
                            Técnico encargado
                        </label>

                        <select
                            name="id_tecnico"
                            id="id_tecnico"
                            required
                        >

                            <option value="">
                                -- Seleccione un técnico --
                            </option>

                            <?php foreach ($tecnicos as $tecnico): ?>

                                <option
                                    value="<?php echo htmlspecialchars($tecnico["id_usuario"]); ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $tecnico["nombre"]
                                        . " "
                                        . $tecnico["apellido"]
                                    );
                                    ?>

                                    -
                                    
                                    <?php
                                    echo htmlspecialchars(
                                        $tecnico["correo"]
                                    );
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <span class="ayuda">
                            Selecciona un técnico activo para asignarle este reporte.
                        </span>

                    </div>


                    <!-- OBSERVACIÓN -->

                    <div class="campo">

                        <label for="observacion">
                            Observación
                        </label>

                        <textarea
                            name="observacion"
                            id="observacion"
                            rows="4"
                            placeholder="Escriba una observación para el técnico..."
                        ></textarea>

                        <span class="ayuda">
                            Puedes agregar instrucciones o información importante
                            para la atención del reporte.
                        </span>

                    </div>


                    <!-- BOTONES -->

                    <div class="acciones">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            🔧 Asignar reporte
                        </button>

                        <a
                            href="reportes_admin.php"
                            class="btn btn-secondary"
                        >
                            ↩ Cancelar
                        </a>

                    </div>

                </form>

            <?php else: ?>

                <div class="sin-tecnicos">

                    ⚠️ No existen técnicos disponibles en este momento.

                </div>

                <div class="acciones">

                    <a
                        href="reportes_admin.php"
                        class="btn btn-secondary"
                    >
                        ↩ Volver a reportes
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </main>

</body>

</html>